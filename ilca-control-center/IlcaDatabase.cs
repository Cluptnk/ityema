using MySqlConnector;

namespace Itema.Ilca.ControlCenter;

internal sealed class IlcaDatabase
{
    private readonly string _connectionString;

    public IlcaDatabase(string connectionString)
    {
        if (string.IsNullOrWhiteSpace(connectionString))
        {
            throw new InvalidOperationException("Geliştirme veritabanı bağlantı bilgisi girilmemiş.");
        }

        var builder = new MySqlConnectionStringBuilder(connectionString);
        if (!string.Equals(builder.Database, "ilmsdb_dev", StringComparison.OrdinalIgnoreCase))
        {
            throw new InvalidOperationException("Güvenlik nedeniyle yalnız ilmsdb_dev veritabanına yazılabilir.");
        }

        _connectionString = builder.ConnectionString;
    }

    public async Task<IReadOnlyList<LoomSettings>> LoadLoomsAsync(CancellationToken cancellationToken)
    {
        const string sql = """
            SELECT telaio.IdTelaio, telaio.NomeTelaio, telaio.IpTelaio, telaio.PortaTelaio, COALESCE(telaio.protocollo, 9) AS protocollo
            FROM telaio
            INNER JOIN modello_telaio ON telaio.Modello = modello_telaio.Modello
            WHERE telaio.EnableTelaio = 1
            ORDER BY telaio.IdTelaio;
            """;
        var result = new List<LoomSettings>();
        await using var connection = new MySqlConnection(_connectionString);
        await connection.OpenAsync(cancellationToken);
        await using var command = new MySqlCommand(sql, connection);
        await using var reader = await command.ExecuteReaderAsync(cancellationToken);
        while (await reader.ReadAsync(cancellationToken))
        {
            result.Add(new LoomSettings
            {
                LoomId = reader.GetInt32("IdTelaio"),
                Name = reader.IsDBNull(reader.GetOrdinal("NomeTelaio")) ? string.Empty : reader.GetString("NomeTelaio"),
                Host = reader.GetString("IpTelaio"),
                Port = reader.GetInt32("PortaTelaio"),
                Protocol = reader.GetInt32("protocollo"),
                EventCount = 20
            });
        }

        return result;
    }

    public async Task SaveAsync(LoomReading reading, IReadOnlyList<LoomEvent> events, CancellationToken cancellationToken)
    {
        await using var connection = new MySqlConnection(_connectionString);
        await connection.OpenAsync(cancellationToken);
        await using var transaction = await connection.BeginTransactionAsync(cancellationToken);
        try
        {
            foreach (var loomEvent in events)
            {
                await InsertEventAsync(connection, transaction, loomEvent, cancellationToken);
            }

            var currentEvent = events.FirstOrDefault();
            await UpsertRealtimeAsync(connection, transaction, reading, currentEvent, cancellationToken);
            await using var online = new MySqlCommand("UPDATE telaio SET Online = 1 WHERE IdTelaio = @loomId", connection, transaction);
            online.Parameters.AddWithValue("@loomId", reading.LoomId);
            await online.ExecuteNonQueryAsync(cancellationToken);
            await transaction.CommitAsync(cancellationToken);
        }
        catch
        {
            await transaction.RollbackAsync(cancellationToken);
            throw;
        }
    }

    public async Task MarkOfflineAsync(int loomId, CancellationToken cancellationToken)
    {
        if (loomId <= 0)
        {
            return;
        }

        await using var connection = new MySqlConnection(_connectionString);
        await connection.OpenAsync(cancellationToken);
        await using var command = new MySqlCommand("UPDATE telaio SET Online = 0 WHERE IdTelaio = @loomId", connection);
        command.Parameters.AddWithValue("@loomId", loomId);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    /// <summary>Üç vardiya ve 00:00'dan başlayan günlük özetleri hazırlar.</summary>
    public async Task RefreshShiftSummariesAsync(CancellationToken cancellationToken)
    {
        var now = DateTime.Now;
        var today = DateTime.Today;
        var yesterday = today.AddDays(-1);
        var tomorrow = today.AddDays(1);
        var windows = new List<ShiftWindow>
        {
            new("day", today, today, now < tomorrow ? now : tomorrow),
            new("s3", yesterday, yesterday.AddHours(23), today.AddHours(7))
        };
        if (now >= today.AddHours(7)) windows.Add(new ShiftWindow("s1", today, today.AddHours(7), now < today.AddHours(15) ? now : today.AddHours(15)));
        if (now >= today.AddHours(15)) windows.Add(new ShiftWindow("s2", today, today.AddHours(15), now < today.AddHours(23) ? now : today.AddHours(23)));
        if (now >= today.AddHours(23)) windows.Add(new ShiftWindow("s3", today, today.AddHours(23), now));

        var dataStart = windows.Min(window => window.Start);
        var dataEnd = windows.Max(window => window.End);
        await using var connection = new MySqlConnection(_connectionString);
        await connection.OpenAsync(cancellationToken);
        await EnsureShiftSummaryTablesAsync(connection, cancellationToken);

        const string eventsSql = """
            SELECT IdTelaio, Timestamp, StatoProduzione, IdEvento
            FROM evento
            WHERE Timestamp >= @start AND Timestamp < @end
            UNION ALL
            SELECT previous_event.IdTelaio, previous_event.Timestamp, previous_event.StatoProduzione, previous_event.IdEvento
            FROM evento AS previous_event
            INNER JOIN (
                SELECT IdTelaio, MAX(Timestamp) AS LastTimestamp
                FROM evento
                WHERE Timestamp < @start
                GROUP BY IdTelaio
            ) AS previous_state
              ON previous_state.IdTelaio = previous_event.IdTelaio
             AND previous_state.LastTimestamp = previous_event.Timestamp
            ORDER BY IdTelaio, Timestamp, IdEvento;
            """;
        var events = new List<(int LoomId, DateTime Timestamp, int Status)>();
        await using (var command = new MySqlCommand(eventsSql, connection))
        {
            command.Parameters.AddWithValue("@start", dataStart);
            command.Parameters.AddWithValue("@end", dataEnd);
            await using var reader = await command.ExecuteReaderAsync(cancellationToken);
            while (await reader.ReadAsync(cancellationToken)) events.Add((reader.GetInt32("IdTelaio"), reader.GetDateTime("Timestamp"), reader.GetInt32("StatoProduzione")));
        }

        await using var transaction = await connection.BeginTransactionAsync(cancellationToken);
        try
        {
            foreach (var window in windows)
            {
                var summaries = new Dictionary<int, StopSummaryRow>();
                var causes = new Dictionary<(int LoomId, int StatusCode), StopCauseSummaryRow>();
                var active = new Dictionary<int, (DateTime StartedAt, bool StartedInWindow, int StatusCode)>();
                void AddStop(int loomId, int statusCode, bool startedInWindow, DateTime startedAt, DateTime endedAt)
                {
                    if (loomId == 100) return; // Permanently excluded from calculations.
                    var seconds = Math.Max(0L, (long)Math.Floor((endedAt - startedAt).TotalSeconds));
                    var count = startedInWindow ? 1 : 0;
                    if (!summaries.TryGetValue(loomId, out var summary)) summary = new StopSummaryRow(loomId, 0, 0);
                    summaries[loomId] = summary with { Count = summary.Count + count, Seconds = summary.Seconds + seconds };
                    var key = (loomId, statusCode);
                    if (!causes.TryGetValue(key, out var cause)) cause = new StopCauseSummaryRow(loomId, statusCode, 0, 0);
                    causes[key] = cause with { Count = cause.Count + count, Seconds = cause.Seconds + seconds };
                }
                foreach (var item in events)
                {
                    var code = item.Status % 256;
                    var isStop = code is >= 1 and <= 20;
                    if (item.Timestamp >= window.End) continue;
                    if (isStop && !active.ContainsKey(item.LoomId))
                    {
                        active[item.LoomId] = (item.Timestamp < window.Start ? window.Start : item.Timestamp, item.Timestamp >= window.Start, code);
                    }
                    else if (!isStop && code == 0 && active.Remove(item.LoomId, out var stop))
                    {
                        AddStop(item.LoomId, stop.StatusCode, stop.StartedInWindow, stop.StartedAt, item.Timestamp);
                    }
                }
                foreach (var (loomId, stop) in active) AddStop(loomId, stop.StatusCode, stop.StartedInWindow, stop.StartedAt, window.End);
                await DeleteShiftWindowAsync(connection, transaction, window, cancellationToken);
                foreach (var summary in summaries.Values) await InsertShiftSummaryAsync(connection, transaction, window, summary, cancellationToken);
                foreach (var cause in causes.Values) await InsertShiftCauseSummaryAsync(connection, transaction, window, cause, cancellationToken);
            }
            await transaction.CommitAsync(cancellationToken);
        }
        catch
        {
            await transaction.RollbackAsync(cancellationToken);
            throw;
        }
    }

    private static async Task EnsureShiftSummaryTablesAsync(MySqlConnection connection, CancellationToken cancellationToken)
    {
        const string sql = """
            CREATE TABLE IF NOT EXISTS loom_stop_window_summary (
              SummaryDate DATE NOT NULL, WindowCode VARCHAR(8) NOT NULL, LoomId INT NOT NULL,
              StopCount INT UNSIGNED NOT NULL DEFAULT 0, StopSeconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
              UpdatedAt DATETIME NOT NULL, PRIMARY KEY (SummaryDate, WindowCode, LoomId),
              KEY ix_stop_window_lookup (WindowCode, SummaryDate, LoomId)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            CREATE TABLE IF NOT EXISTS loom_stop_window_cause_summary (
              SummaryDate DATE NOT NULL, WindowCode VARCHAR(8) NOT NULL, LoomId INT NOT NULL,
              StatusCode TINYINT UNSIGNED NOT NULL, StopCount INT UNSIGNED NOT NULL DEFAULT 0,
              StopSeconds BIGINT UNSIGNED NOT NULL DEFAULT 0, UpdatedAt DATETIME NOT NULL,
              PRIMARY KEY (SummaryDate, WindowCode, LoomId, StatusCode),
              KEY ix_stop_window_cause_lookup (WindowCode, SummaryDate, LoomId, StatusCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            """;
        await using var command = new MySqlCommand(sql, connection);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task DeleteShiftWindowAsync(MySqlConnection connection, MySqlTransaction transaction, ShiftWindow window, CancellationToken cancellationToken)
    {
        await using var command = new MySqlCommand("DELETE FROM loom_stop_window_summary WHERE SummaryDate=@date AND WindowCode=@code; DELETE FROM loom_stop_window_cause_summary WHERE SummaryDate=@date AND WindowCode=@code;", connection, transaction);
        command.Parameters.AddWithValue("@date", window.SummaryDate.Date);
        command.Parameters.AddWithValue("@code", window.Code);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task InsertShiftSummaryAsync(MySqlConnection connection, MySqlTransaction transaction, ShiftWindow window, StopSummaryRow summary, CancellationToken cancellationToken)
    {
        await using var command = new MySqlCommand("INSERT INTO loom_stop_window_summary (SummaryDate,WindowCode,LoomId,StopCount,StopSeconds,UpdatedAt) VALUES (@date,@code,@loomId,@count,@seconds,NOW())", connection, transaction);
        command.Parameters.AddWithValue("@date", window.SummaryDate.Date); command.Parameters.AddWithValue("@code", window.Code); command.Parameters.AddWithValue("@loomId", summary.LoomId); command.Parameters.AddWithValue("@count", summary.Count); command.Parameters.AddWithValue("@seconds", summary.Seconds);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task InsertShiftCauseSummaryAsync(MySqlConnection connection, MySqlTransaction transaction, ShiftWindow window, StopCauseSummaryRow summary, CancellationToken cancellationToken)
    {
        await using var command = new MySqlCommand("INSERT INTO loom_stop_window_cause_summary (SummaryDate,WindowCode,LoomId,StatusCode,StopCount,StopSeconds,UpdatedAt) VALUES (@date,@code,@loomId,@status,@count,@seconds,NOW())", connection, transaction);
        command.Parameters.AddWithValue("@date", window.SummaryDate.Date); command.Parameters.AddWithValue("@code", window.Code); command.Parameters.AddWithValue("@loomId", summary.LoomId); command.Parameters.AddWithValue("@status", summary.StatusCode); command.Parameters.AddWithValue("@count", summary.Count); command.Parameters.AddWithValue("@seconds", summary.Seconds);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    /// <summary>
    /// Kartlar ve raporlar için günün duruş özetini üretir. Bu işlem web
    /// isteklerinde yapılmaz; toplayıcı tarafından sınırlı aralıklarla çağrılır.
    /// Bir duruş, 1-20 arasındaki ilk durum koduyla başlar ve sonraki 0
    /// (çalışıyor) kaydıyla biter. Aradaki ek olaylar aynı fiziksel duruştur.
    /// </summary>
    public async Task RefreshTodayStopSummariesAsync(CancellationToken cancellationToken)
    {
        await using var connection = new MySqlConnection(_connectionString);
        await connection.OpenAsync(cancellationToken);
        await EnsureStopSummaryTablesAsync(connection, cancellationToken);

        // Geçici doğrulama penceresi: yalnızca 07:00-15:00 arasındaki
        // fiziksel duruşlar hesaplanır. Kontrol tamamlandığında bu iki saat
        // değeri tekrar 00:00-24:00 yapılacaktır.
        const string eventsSql = """
            SELECT IdTelaio, Timestamp, StatoProduzione, IdEvento
            FROM evento
            WHERE Timestamp >= DATE_ADD(CURDATE(), INTERVAL 7 HOUR)
              AND Timestamp < DATE_ADD(CURDATE(), INTERVAL 15 HOUR)
            UNION ALL
            SELECT previous_event.IdTelaio, previous_event.Timestamp, previous_event.StatoProduzione, previous_event.IdEvento
            FROM evento AS previous_event
            INNER JOIN (
                SELECT IdTelaio, MAX(Timestamp) AS LastTimestamp
                FROM evento
                WHERE Timestamp < DATE_ADD(CURDATE(), INTERVAL 7 HOUR)
                GROUP BY IdTelaio
            ) AS last_before_day
              ON last_before_day.IdTelaio = previous_event.IdTelaio
             AND last_before_day.LastTimestamp = previous_event.Timestamp
            ORDER BY IdTelaio, Timestamp, IdEvento;
            """;

        var events = new List<(int LoomId, DateTime Timestamp, int Status)>();
        await using (var command = new MySqlCommand(eventsSql, connection))
        await using (var reader = await command.ExecuteReaderAsync(cancellationToken))
        {
            while (await reader.ReadAsync(cancellationToken))
            {
                events.Add((reader.GetInt32("IdTelaio"), reader.GetDateTime("Timestamp"), reader.GetInt32("StatoProduzione")));
            }
        }

        var windowStart = DateTime.Today.AddHours(7);
        var windowEnd = DateTime.Today.AddHours(15);
        var now = DateTime.Now < windowEnd ? DateTime.Now : windowEnd;
        var activeStops = new Dictionary<int, (DateTime StartedAt, bool StartedInWindow, int StatusCode)>();
        var summaries = new Dictionary<int, StopSummaryRow>();
        var causeSummaries = new Dictionary<(int LoomId, int StatusCode), StopCauseSummaryRow>();

        void AddStop(int loomId, int statusCode, bool startedInWindow, DateTime startedAt, DateTime endedAt)
        {
            if (loomId == 100) return; // Permanently excluded from calculations.
            var seconds = Math.Max(0L, (long)Math.Floor((endedAt - startedAt).TotalSeconds));
            var count = startedInWindow ? 1 : 0;
            if (!summaries.TryGetValue(loomId, out var summary)) summary = new StopSummaryRow(loomId, 0, 0);
            summaries[loomId] = summary with { Count = summary.Count + count, Seconds = summary.Seconds + seconds };

            var key = (loomId, statusCode);
            if (!causeSummaries.TryGetValue(key, out var causeSummary)) causeSummary = new StopCauseSummaryRow(loomId, statusCode, 0, 0);
            causeSummaries[key] = causeSummary with { Count = causeSummary.Count + count, Seconds = causeSummary.Seconds + seconds };
        }

        foreach (var item in events)
        {
            var statusCode = item.Status % 256;
            var isStop = statusCode is >= 1 and <= 20;
            if (isStop && !activeStops.ContainsKey(item.LoomId))
            {
                activeStops[item.LoomId] = (item.Timestamp < windowStart ? windowStart : item.Timestamp, item.Timestamp >= windowStart && item.Timestamp < windowEnd, statusCode);
            }
            else if (!isStop && statusCode == 0 && activeStops.Remove(item.LoomId, out var stop))
            {
                AddStop(item.LoomId, stop.StatusCode, stop.StartedInWindow, stop.StartedAt, item.Timestamp);
            }
        }

        foreach (var (loomId, stop) in activeStops)
        {
            AddStop(loomId, stop.StatusCode, stop.StartedInWindow, stop.StartedAt, now);
        }

        await using var transaction = await connection.BeginTransactionAsync(cancellationToken);
        try
        {
            await DeleteTodayStopSummariesAsync(connection, transaction, cancellationToken);
            foreach (var summary in summaries.Values)
            {
                await InsertStopSummaryAsync(connection, transaction, summary, cancellationToken);
            }
            foreach (var summary in causeSummaries.Values)
            {
                await InsertStopCauseSummaryAsync(connection, transaction, summary, cancellationToken);
            }
            await transaction.CommitAsync(cancellationToken);
        }
        catch
        {
            await transaction.RollbackAsync(cancellationToken);
            throw;
        }
    }

    private static async Task EnsureStopSummaryTablesAsync(MySqlConnection connection, CancellationToken cancellationToken)
    {
        const string sql = """
            CREATE TABLE IF NOT EXISTS loom_stop_summary (
              SummaryDate DATE NOT NULL,
              LoomId INT NOT NULL,
              StopCount INT UNSIGNED NOT NULL DEFAULT 0,
              StopSeconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
              UpdatedAt DATETIME NOT NULL,
              PRIMARY KEY (SummaryDate, LoomId),
              KEY ix_loom_stop_summary_loom_day (LoomId, SummaryDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            CREATE TABLE IF NOT EXISTS loom_stop_cause_summary (
              SummaryDate DATE NOT NULL,
              LoomId INT NOT NULL,
              StatusCode TINYINT UNSIGNED NOT NULL,
              StopCount INT UNSIGNED NOT NULL DEFAULT 0,
              StopSeconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
              UpdatedAt DATETIME NOT NULL,
              PRIMARY KEY (SummaryDate, LoomId, StatusCode),
              KEY ix_loom_stop_cause_summary_day_loom (SummaryDate, LoomId),
              KEY ix_loom_stop_cause_summary_code_day (StatusCode, SummaryDate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            """;
        await using var command = new MySqlCommand(sql, connection);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task DeleteTodayStopSummariesAsync(MySqlConnection connection, MySqlTransaction transaction, CancellationToken cancellationToken)
    {
        await using var command = new MySqlCommand("DELETE FROM loom_stop_summary WHERE SummaryDate = CURDATE(); DELETE FROM loom_stop_cause_summary WHERE SummaryDate = CURDATE();", connection, transaction);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task InsertStopSummaryAsync(MySqlConnection connection, MySqlTransaction transaction, StopSummaryRow summary, CancellationToken cancellationToken)
    {
        await using var command = new MySqlCommand("INSERT INTO loom_stop_summary (SummaryDate, LoomId, StopCount, StopSeconds, UpdatedAt) VALUES (CURDATE(), @loomId, @count, @seconds, NOW())", connection, transaction);
        command.Parameters.AddWithValue("@loomId", summary.LoomId);
        command.Parameters.AddWithValue("@count", summary.Count);
        command.Parameters.AddWithValue("@seconds", summary.Seconds);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task InsertStopCauseSummaryAsync(MySqlConnection connection, MySqlTransaction transaction, StopCauseSummaryRow summary, CancellationToken cancellationToken)
    {
        await using var command = new MySqlCommand("INSERT INTO loom_stop_cause_summary (SummaryDate, LoomId, StatusCode, StopCount, StopSeconds, UpdatedAt) VALUES (CURDATE(), @loomId, @statusCode, @count, @seconds, NOW())", connection, transaction);
        command.Parameters.AddWithValue("@loomId", summary.LoomId);
        command.Parameters.AddWithValue("@statusCode", summary.StatusCode);
        command.Parameters.AddWithValue("@count", summary.Count);
        command.Parameters.AddWithValue("@seconds", summary.Seconds);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task InsertEventAsync(MySqlConnection connection, MySqlTransaction transaction, LoomEvent value, CancellationToken cancellationToken)
    {
        const string sql = """
            INSERT INTO evento
              (IdEvento, Timestamp, StatoProduzione, IdTelaio, Turno, Far, Armatura1, Armatura2, Disegno, LottoOrditoA, LottoOrditoB, LottoTrama, Dichiarazione, LunghezzaDaInizio, NumeroPezza, RFID, TempoEvento, Battute, Articolo, IdTrigger, NumeroSerie)
            VALUES
              (@eventNumber, @timestamp, @status, @loomId, @shift, @far, @dobby1, @dobby2, @pattern, @warpLotA, @warpLotB, @weftLot, @declaration, @length, @piece, @rfid, @seconds, @picks, @article, 1, @serial)
            ON DUPLICATE KEY UPDATE
              LunghezzaDaInizio=IF(VALUES(TempoEvento)>=TempoEvento,VALUES(LunghezzaDaInizio),LunghezzaDaInizio),
              Battute=IF(VALUES(TempoEvento)>=TempoEvento,VALUES(Battute),Battute),
              TempoEvento=GREATEST(TempoEvento,VALUES(TempoEvento));
            """;
        await using var command = new MySqlCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("@eventNumber", value.EventNumber);
        command.Parameters.AddWithValue("@timestamp", value.Timestamp);
        command.Parameters.AddWithValue("@status", value.ProductionStatus);
        command.Parameters.AddWithValue("@loomId", value.LoomId);
        command.Parameters.AddWithValue("@shift", value.Shift);
        command.Parameters.AddWithValue("@far", value.Far);
        command.Parameters.AddWithValue("@dobby1", value.Dobby1);
        command.Parameters.AddWithValue("@dobby2", value.Dobby2);
        command.Parameters.AddWithValue("@pattern", value.Pattern);
        command.Parameters.AddWithValue("@warpLotA", value.WarpLotA);
        command.Parameters.AddWithValue("@warpLotB", value.WarpLotB);
        command.Parameters.AddWithValue("@weftLot", value.WeftLot);
        command.Parameters.AddWithValue("@declaration", value.Declaration);
        command.Parameters.AddWithValue("@length", value.LengthFromStart);
        command.Parameters.AddWithValue("@piece", value.PieceNumber);
        command.Parameters.AddWithValue("@rfid", value.Rfid);
        command.Parameters.AddWithValue("@seconds", value.EventSeconds);
        command.Parameters.AddWithValue("@picks", value.Picks);
        command.Parameters.AddWithValue("@article", value.Article);
        command.Parameters.AddWithValue("@serial", value.LoomSerial);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }

    private static async Task UpsertRealtimeAsync(MySqlConnection connection, MySqlTransaction transaction, LoomReading r, LoomEvent? e, CancellationToken cancellationToken)
    {
        const string sql = """
            INSERT INTO dato_rt
              (IdTelaio, Timestamp, Modello, SisMisura, Velocita, Resa, DensitaTrama, NumeroSubbi, TensioneOrdito1, TensioneOrdito2, TensioneOrdito3, TensioneOrdito4, TipoProduzione, ProduzioneEffettuata, ProduzioneDesiderata, TempoDaInizio, TempoMancante, DefinizioneTempoMancanteSubbio, TempoMancanteSubbio1, TempoMancanteSubbio2, TempoMancanteSubbio3, TempoMancanteSubbio4, Warnings, TransponderRFID, StatoProduzione, Consumo, Turno, Far, Articolo, Armatura1, Armatura2, Disegno, LottoOrditoA, LottoOrditoB, LottoTrama, Dichiarazione, NumeroPezza, PezzeProgrammate, PezzeProdotte, MetriTotaliSubbio1, MetriTotaliSubbio2, MetriTotaliSubbio3, MetriTotaliSubbio4, MetriFattiSubbio1, MetriFattiSubbio2, MetriFattiSubbio3, MetriFattiSubbio4)
            VALUES
              (@loomId, @timestamp, @model, @measurementSystem, @speed, @efficiency, @density, @beamCount, @tension1, @tension2, @tension3, @tension4, @productionType, @productionDone, @productionTarget, @elapsed, @remaining, @remainingDefinition, @remaining1, @remaining2, @remaining3, @remaining4, @warnings, @rfid, @status, @consumption, @shift, @far, @article, @dobby1, @dobby2, @pattern, @warpLotA, @warpLotB, @weftLot, @declaration, @piece, @plannedPieces, @producedPieces, @total1, @total2, @total3, @total4, @made1, @made2, @made3, @made4)
            ON DUPLICATE KEY UPDATE
              Timestamp=VALUES(Timestamp), Modello=VALUES(Modello), SisMisura=VALUES(SisMisura), Velocita=VALUES(Velocita), Resa=VALUES(Resa), DensitaTrama=VALUES(DensitaTrama), NumeroSubbi=VALUES(NumeroSubbi), TensioneOrdito1=VALUES(TensioneOrdito1), TensioneOrdito2=VALUES(TensioneOrdito2), TensioneOrdito3=VALUES(TensioneOrdito3), TensioneOrdito4=VALUES(TensioneOrdito4), TipoProduzione=VALUES(TipoProduzione), ProduzioneEffettuata=VALUES(ProduzioneEffettuata), ProduzioneDesiderata=VALUES(ProduzioneDesiderata), TempoDaInizio=VALUES(TempoDaInizio), TempoMancante=VALUES(TempoMancante), DefinizioneTempoMancanteSubbio=VALUES(DefinizioneTempoMancanteSubbio), TempoMancanteSubbio1=VALUES(TempoMancanteSubbio1), TempoMancanteSubbio2=VALUES(TempoMancanteSubbio2), TempoMancanteSubbio3=VALUES(TempoMancanteSubbio3), TempoMancanteSubbio4=VALUES(TempoMancanteSubbio4), Warnings=VALUES(Warnings), TransponderRFID=VALUES(TransponderRFID), StatoProduzione=VALUES(StatoProduzione), Consumo=VALUES(Consumo), Turno=VALUES(Turno), Far=VALUES(Far), Articolo=VALUES(Articolo), Armatura1=VALUES(Armatura1), Armatura2=VALUES(Armatura2), Disegno=VALUES(Disegno), LottoOrditoA=VALUES(LottoOrditoA), LottoOrditoB=VALUES(LottoOrditoB), LottoTrama=VALUES(LottoTrama), Dichiarazione=VALUES(Dichiarazione), NumeroPezza=VALUES(NumeroPezza), PezzeProgrammate=VALUES(PezzeProgrammate), PezzeProdotte=VALUES(PezzeProdotte), MetriTotaliSubbio1=VALUES(MetriTotaliSubbio1), MetriTotaliSubbio2=VALUES(MetriTotaliSubbio2), MetriTotaliSubbio3=VALUES(MetriTotaliSubbio3), MetriTotaliSubbio4=VALUES(MetriTotaliSubbio4), MetriFattiSubbio1=VALUES(MetriFattiSubbio1), MetriFattiSubbio2=VALUES(MetriFattiSubbio2), MetriFattiSubbio3=VALUES(MetriFattiSubbio3), MetriFattiSubbio4=VALUES(MetriFattiSubbio4);
            """;
        await using var command = new MySqlCommand(sql, connection, transaction);
        var values = new Dictionary<string, object?>
        {
            ["@loomId"] = r.LoomId, ["@timestamp"] = r.Timestamp, ["@model"] = r.Model, ["@measurementSystem"] = r.MeasurementSystem, ["@speed"] = r.Speed, ["@efficiency"] = r.Efficiency, ["@density"] = r.WeftDensity, ["@beamCount"] = r.BeamCount,
            ["@tension1"] = r.WarpTension1, ["@tension2"] = r.WarpTension2, ["@tension3"] = r.WarpTension3, ["@tension4"] = r.WarpTension4, ["@productionType"] = r.ProductionType, ["@productionDone"] = r.ProductionDone, ["@productionTarget"] = r.ProductionTarget,
            ["@elapsed"] = r.ElapsedSeconds, ["@remaining"] = r.RemainingSeconds, ["@remainingDefinition"] = r.RemainingDefinition, ["@remaining1"] = r.RemainingBeam1, ["@remaining2"] = r.RemainingBeam2, ["@remaining3"] = r.RemainingBeam3, ["@remaining4"] = r.RemainingBeam4,
            ["@warnings"] = r.Warnings, ["@rfid"] = r.Rfid, ["@status"] = e?.ProductionStatus ?? 295, ["@consumption"] = r.AirConsumption, ["@shift"] = e?.Shift ?? string.Empty, ["@far"] = e?.Far ?? 0, ["@article"] = e?.Article ?? string.Empty, ["@dobby1"] = e?.Dobby1 ?? string.Empty, ["@dobby2"] = e?.Dobby2 ?? string.Empty,
            ["@pattern"] = e?.Pattern ?? string.Empty, ["@warpLotA"] = e?.WarpLotA ?? string.Empty, ["@warpLotB"] = e?.WarpLotB ?? string.Empty, ["@weftLot"] = e?.WeftLot ?? string.Empty, ["@declaration"] = e?.Declaration ?? 0, ["@piece"] = e?.PieceNumber ?? 0,
            ["@plannedPieces"] = r.PlannedPieces, ["@producedPieces"] = r.ProducedPieces, ["@total1"] = r.TotalMetersBeam1, ["@total2"] = r.TotalMetersBeam2, ["@total3"] = r.TotalMetersBeam3, ["@total4"] = r.TotalMetersBeam4, ["@made1"] = r.MadeMetersBeam1, ["@made2"] = r.MadeMetersBeam2, ["@made3"] = r.MadeMetersBeam3, ["@made4"] = r.MadeMetersBeam4
        };
        foreach (var (name, value) in values) command.Parameters.AddWithValue(name, value ?? DBNull.Value);
        await command.ExecuteNonQueryAsync(cancellationToken);
    }
}

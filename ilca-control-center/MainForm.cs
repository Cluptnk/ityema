using System.ComponentModel;
using System.Text.Json;

namespace Itema.Ilca.ControlCenter;

internal sealed class MainForm : Form
{
    private readonly AppSettings _settings;
    private readonly DataGridView _loomGrid;
    private readonly TextBox _log;
    private readonly NumericUpDown _pollInterval;
    private readonly TextBox _developmentConnectionString;
    private readonly CheckBox _developmentWriteEnabled;
    private readonly Button _oneCycleButton;
    private readonly Button _continuousButton;
    private readonly System.Windows.Forms.Timer _timer;
    private readonly CancellationTokenSource _shutdown = new();
    private bool _pollInProgress;
    private DateTime _nextStopSummaryRefresh = DateTime.MinValue;

    public MainForm()
    {
        _settings = SettingsStore.Load();
        _timer = new System.Windows.Forms.Timer();
        Text = "ITEMA ILCA Kontrol Merkezi — 2026.10.22 / 3";
        StartPosition = FormStartPosition.CenterScreen;
        MinimumSize = new Size(900, 650);
        Size = new Size(1100, 760);

        var safeMode = new Label
        {
            Dock = DockStyle.Top,
            Height = 42,
            Text = "Otomatik tezgâh komutu gönderilmez. Ayar ve dosya işlemleri yalnız Manuel işlemler ekranından, sizin gönderiminizle yapılır.",
            TextAlign = ContentAlignment.MiddleCenter,
            BackColor = Color.FromArgb(255, 243, 205),
            ForeColor = Color.FromArgb(102, 77, 3),
            Font = new Font(Font, FontStyle.Bold)
        };

        _loomGrid = new DataGridView
        {
            Dock = DockStyle.Fill,
            AutoGenerateColumns = false,
            AllowUserToAddRows = false,
            DataSource = _settings.Looms,
            AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill
        };
        _loomGrid.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = nameof(LoomSettings.Name), HeaderText = "Tezgâh adı" });
        _loomGrid.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = nameof(LoomSettings.LoomId), HeaderText = "ID", FillWeight = 35 });
        _loomGrid.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = nameof(LoomSettings.Host), HeaderText = "IP / Ana bilgisayar" });
        _loomGrid.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = nameof(LoomSettings.Port), HeaderText = "TCP portu", FillWeight = 50 });
        _loomGrid.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = nameof(LoomSettings.EventCount), HeaderText = "Olay sayısı", FillWeight = 50 });
        _loomGrid.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = nameof(LoomSettings.Protocol), HeaderText = "Protokol", FillWeight = 45 });

        _loomGrid.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = nameof(LoomSettings.ClockStatus), HeaderText = "Tezgâh saati", ReadOnly = true, FillWeight = 130 });

        _pollInterval = new NumericUpDown { Minimum = 1, Maximum = 3600, Value = Math.Clamp(_settings.PollIntervalSeconds, 1, 3600), Width = 70 };
        _oneCycleButton = new Button { Text = "Bir kez oku", AutoSize = true };
        _continuousButton = new Button { Text = "Sürekli başlat", AutoSize = true };
        var addButton = new Button { Text = "Tezgâh ekle", AutoSize = true };
        var removeButton = new Button { Text = "Seçileni sil", AutoSize = true };
        var saveButton = new Button { Text = "Ayarları kaydet", AutoSize = true };
        var loadDatabaseLoomsButton = new Button { Text = "Tezgâhları dev. DB'den al", AutoSize = true };

        addButton.Click += (_, _) => _settings.Looms.Add(new LoomSettings { Name = "Yeni tezgâh", Port = 12888, EventCount = 20 });
        removeButton.Click += (_, _) => RemoveSelectedLoom();
        saveButton.Click += (_, _) => SaveSettings();
        loadDatabaseLoomsButton.Click += async (_, _) => await LoadLoomsFromDevelopmentDatabaseAsync();
        _oneCycleButton.Click += async (_, _) => await PollAllAsync();
        _continuousButton.Click += (_, _) => ToggleContinuousPolling();

        var manualButton = new Button { Text = "Manuel işlemler…", AutoSize = true };
        manualButton.Click += (_, _) =>
        {
            if (_pollInProgress) { MessageBox.Show(this, "Bir okuma turu sürüyor. Tur bitince tekrar açın.", "Veri okunuyor"); return; }
            var resume = _timer.Enabled;
            _timer.Stop();
            try { _loomGrid.EndEdit(); using var dialog = new ManualOperationsForm(_settings.Looms, AppendLog); dialog.ShowDialog(this); }
            finally { if (resume) _timer.Start(); }
        };

        var commandBar = new FlowLayoutPanel { Dock = DockStyle.Top, Padding = new Padding(10), AutoSize = true, AutoSizeMode = AutoSizeMode.GrowAndShrink };
        commandBar.Controls.AddRange([addButton, removeButton, saveButton, loadDatabaseLoomsButton, new Label { Text = "  Tekrar (sn):", AutoSize = true, TextAlign = ContentAlignment.MiddleCenter, Padding = new Padding(8, 6, 0, 0) }, _pollInterval, _oneCycleButton, _continuousButton, manualButton]);

        var configuredConnection = _settings.DevelopmentConnectionString;
        if (string.IsNullOrWhiteSpace(configuredConnection))
        {
            DevelopmentConnection.TryLoad(out configuredConnection, out _);
        }
        _developmentConnectionString = new TextBox { Dock = DockStyle.Fill, Text = configuredConnection, UseSystemPasswordChar = true };
        _developmentWriteEnabled = new CheckBox { Text = "Geliştirme veritabanına yaz", Checked = _settings.DevelopmentDatabaseWriteEnabled, AutoSize = true };
        var reloadDevelopmentConfigButton = new Button { Text = "Dev. ayarını siteden al", AutoSize = true };
        reloadDevelopmentConfigButton.Click += (_, _) => LoadDevelopmentConnectionFromSite();
        var databasePanel = new TableLayoutPanel { Dock = DockStyle.Top, AutoSize = true, AutoSizeMode = AutoSizeMode.GrowAndShrink, Padding = new Padding(10, 5, 10, 5), ColumnCount = 2, RowCount = 2 };
        databasePanel.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        databasePanel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));
        databasePanel.Controls.Add(new Label { Text = "Dev. bağlantısı:", AutoSize = true, Padding = new Padding(0, 5, 8, 0) }, 0, 0);
        databasePanel.Controls.Add(_developmentConnectionString, 1, 0);
        var databaseOptions = new FlowLayoutPanel { Dock = DockStyle.Fill, AutoSize = true };
        databaseOptions.Controls.AddRange([_developmentWriteEnabled, reloadDevelopmentConfigButton]);
        databasePanel.Controls.Add(databaseOptions, 1, 1);

        _log = new TextBox { Dock = DockStyle.Fill, Multiline = true, ReadOnly = true, ScrollBars = ScrollBars.Vertical, Font = new Font(FontFamily.GenericMonospace, 9f), BackColor = Color.White };
        var logGroup = new GroupBox { Text = "Çalışma günlüğü", Dock = DockStyle.Bottom, Height = 230, Padding = new Padding(8) };
        logGroup.Controls.Add(_log);

        Controls.Add(_loomGrid);
        Controls.Add(logGroup);
        Controls.Add(databasePanel);
        Controls.Add(commandBar);
        Controls.Add(safeMode);

        _timer.Tick += async (_, _) => await PollAllAsync();
        FormClosing += (_, _) => _shutdown.Cancel();
        AppendLog("Hazır. İlk test için tek bir tezgâh ekleyip ‘Bir kez oku’ düğmesine basın.");
    }

    private async Task PollAllAsync()
    {
        if (_pollInProgress)
        {
            return;
        }

        _loomGrid.EndEdit();
        if (_settings.Looms.Count == 0)
        {
            AppendLog("Önce en az bir tezgâh ekleyin.");
            return;
        }

        _pollInProgress = true;
        _oneCycleButton.Enabled = false;
        try
        {
            var outputDirectory = Path.Combine(AppContext.BaseDirectory, "data");
            var results = await Task.WhenAll(_settings.Looms.Select(loom => LoomPoller.PollAsync(loom, outputDirectory, _settings.DiagnosticCaptureEnabled, _shutdown.Token)));
            IlcaDatabase? database = _developmentWriteEnabled.Checked ? new IlcaDatabase(_developmentConnectionString.Text) : null;
            foreach (var result in results)
            {
                var loom = _settings.Looms.FirstOrDefault(item => item.LoomId == result.LoomId);
                if (loom is not null)
                {
                    loom.ClockStatus = result.Succeeded && result.Reading is not null && result.Events?.Count > 0
                        ? LoomClock.Describe(result.Events[0].Timestamp, result.Events[0].EventSeconds, result.Reading.Timestamp)
                        : "Veri alınamadı";
                }
                if (_settings.DiagnosticCaptureEnabled)
                {
                    Directory.CreateDirectory(outputDirectory);
                    var record = JsonSerializer.Serialize(result);
                    await File.AppendAllTextAsync(Path.Combine(outputDirectory, "polls.jsonl"), record + Environment.NewLine, _shutdown.Token);
                }
                if (result.Succeeded && database is not null && result.Reading is not null && result.Events is not null)
                {
                    try
                    {
                        await database.SaveAsync(result.Reading, result.Events, _shutdown.Token);
                        AppendLog($"{result.LoomName}: OK — {result.ResponseBytes} bayt, {result.Events.Count} olay. ilmsdb_dev güncellendi.");
                    }
                    catch (Exception exception)
                    {
                        AppendLog($"{result.LoomName}: veri alındı fakat ilmsdb_dev yazımı başarısız — {exception.Message}");
                    }
                }
                else
                {
                    if (!result.Succeeded && result.ConnectionFailed && database is not null)
                    {
                        try
                        {
                            await database.MarkOfflineAsync(result.LoomId, _shutdown.Token);
                            AppendLog($"{result.LoomName}: ÇEVRİMDIŞI olarak işaretlendi.");
                        }
                        catch (Exception exception)
                        {
                            AppendLog($"{result.LoomName}: çevrimdışı durumu veritabanına yazılamadı — {exception.Message}");
                        }
                    }
                    AppendLog(result.Succeeded
                        ? $"{result.LoomName}: OK — {result.ResponseBytes} bayt, {result.Events?.Count ?? 0} olay alındı."
                        : $"{result.LoomName}: HATA — {result.Error}");
                }
            }

            _settings.Looms.ResetBindings();

            if (database is not null && DateTime.Now >= _nextStopSummaryRefresh)
            {
                try
                {
                    // Summary reconstruction has CPU work between database awaits.
                    // Keep it off the WinForms message loop so the window stays responsive.
                    await Task.Run(() => database.RefreshShiftSummariesAsync(_shutdown.Token), _shutdown.Token);
                    _nextStopSummaryRefresh = DateTime.Now.AddMinutes(3);
                    AppendLog("Vardiya ve 24 saat duruş özetleri güncellendi. Kartlar, Tezgâh Veri Takibi ve raporlar hızlı özet verisini kullanacak.");
                }
                catch (Exception exception)
                {
                    _nextStopSummaryRefresh = DateTime.Now.AddMinutes(1);
                    AppendLog($"Duruş özeti güncellenemedi — {exception.Message}");
                }
            }
        }
        catch (OperationCanceledException)
        {
            AppendLog("Toplayıcı durduruldu.");
        }
        catch (Exception exception)
        {
            AppendLog($"Okuma turu tamamlanamadı — {exception.Message}");
        }
        finally
        {
            _pollInProgress = false;
            _oneCycleButton.Enabled = true;
        }
    }

    private void ToggleContinuousPolling()
    {
        if (_timer.Enabled)
        {
            _timer.Stop();
            _continuousButton.Text = "Sürekli başlat";
            AppendLog("Sürekli okuma durduruldu.");
            return;
        }

        SaveSettings();
        _timer.Interval = (int)_pollInterval.Value * 1000;
        _timer.Start();
        _continuousButton.Text = "Sürekli durdur";
        AppendLog($"Sürekli okuma {_pollInterval.Value} saniyede bir çalışacak.");
        _ = PollAllAsync();
    }

    private void RemoveSelectedLoom()
    {
        if (_loomGrid.CurrentRow?.DataBoundItem is LoomSettings loom)
        {
            _settings.Looms.Remove(loom);
        }
    }

    private void SaveSettings()
    {
        _loomGrid.EndEdit();
        _settings.PollIntervalSeconds = (int)_pollInterval.Value;
        _settings.DevelopmentDatabaseWriteEnabled = _developmentWriteEnabled.Checked;
        _settings.DevelopmentConnectionString = _developmentConnectionString.Text.Trim();
        SettingsStore.Save(_settings);
        AppendLog("Ayarlar kaydedildi.");
    }

    private async Task LoadLoomsFromDevelopmentDatabaseAsync()
    {
        try
        {
            var database = new IlcaDatabase(_developmentConnectionString.Text);
            var looms = await database.LoadLoomsAsync(_shutdown.Token);
            _settings.Looms.Clear();
            foreach (var loom in looms)
            {
                _settings.Looms.Add(loom);
            }

            SaveSettings();
            AppendLog($"{looms.Count} tezgâh ilmsdb_dev veritabanından alındı.");
        }
        catch (Exception exception)
        {
            AppendLog($"Tezgâh listesi alınamadı — {exception.Message}");
        }
    }

    private void LoadDevelopmentConnectionFromSite()
    {
        if (DevelopmentConnection.TryLoad(out var connectionString, out var error))
        {
            _developmentConnectionString.Text = connectionString;
            AppendLog("Geliştirme site ayarından ilmsdb_dev bağlantısı alındı.");
            return;
        }

        AppendLog($"Geliştirme site ayarı alınamadı — {error}");
    }

    private void AppendLog(string message)
    {
        // This window stays open continuously; bound the native text buffer.
        if (_log.TextLength > 200_000)
        {
            var cut = _log.Text.IndexOf('\n', _log.TextLength - 100_000);
            if (cut >= 0)
            {
                _log.Select(0, cut + 1);
                _log.SelectedText = string.Empty;
            }
        }
        _log.AppendText($"{DateTime.Now:HH:mm:ss}  {message}{Environment.NewLine}");
    }
}

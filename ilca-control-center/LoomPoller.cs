using System.Net.Sockets;
using System.Security.Cryptography;

namespace Itema.Ilca.ControlCenter;

internal static class LoomPoller
{
    public static async Task<PollenResult> PollAsync(LoomSettings loom, string outputDirectory, bool diagnosticCaptureEnabled, CancellationToken cancellationToken)
    {
        var started = DateTimeOffset.UtcNow;
        try
        {
            Validate(loom);
            using var client = new TcpClient();
            using var connectionTimeout = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
            connectionTimeout.CancelAfter(TimeSpan.FromSeconds(5));
            await client.ConnectAsync(loom.Host, loom.Port, connectionTimeout.Token);

            await using var stream = client.GetStream();
            var request = LoomProtocol.BuildMonitoringRequest(loom.EventCount);
            await stream.WriteAsync(request, cancellationToken);
            var response = await ReadResponseAsync(stream, cancellationToken);

            string? capturePath = null;
            if (diagnosticCaptureEnabled)
            {
                var captureDirectory = Path.Combine(outputDirectory, "captures");
                Directory.CreateDirectory(captureDirectory);
                var filename = $"{DateTimeOffset.UtcNow:yyyyMMddTHHmmssfffZ}_{SafeFileName(loom.Name)}.bin";
                capturePath = Path.Combine(captureDirectory, filename);
                await File.WriteAllBytesAsync(capturePath, response, cancellationToken);
            }

            var decoded = LoomProtocol.Decode(response, loom);
            return PollenResult.Ok(loom, started, response, capturePath, decoded.Reading, decoded.Events);
        }
        catch (OperationCanceledException) when (cancellationToken.IsCancellationRequested)
        {
            return PollenResult.Fail(loom, started, "İşlem durduruldu.", false);
        }
        catch (OperationCanceledException)
        {
            return PollenResult.Fail(loom, started, $"{loom.Host}:{loom.Port} için 5 saniye içinde bağlantı kurulamadı. IP adresini ve TCP portunu kontrol edin.", true);
        }
        catch (SocketException exception)
        {
            return PollenResult.Fail(loom, started, $"{loom.Host}:{loom.Port} bağlantı hatası — {exception.Message}", true);
        }
        catch (IOException exception)
        {
            return PollenResult.Fail(loom, started, $"{loom.Host}:{loom.Port} iletişimi kesildi — {exception.Message}", true);
        }
        catch (Exception exception)
        {
            return PollenResult.Fail(loom, started, exception.Message, false);
        }
    }

    private static async Task<byte[]> ReadResponseAsync(NetworkStream stream, CancellationToken cancellationToken)
    {
        const int responseLimit = 65536;
        var buffer = new byte[4096];
        using var result = new MemoryStream();

        using (var initialTimeout = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken))
        {
            initialTimeout.CancelAfter(TimeSpan.FromSeconds(5));
            var initialRead = await stream.ReadAsync(buffer, initialTimeout.Token);
            if (initialRead == 0)
            {
                throw new IOException("Tezgâh yanıt göndermeden bağlantıyı kapattı.");
            }

            await result.WriteAsync(buffer.AsMemory(0, initialRead), cancellationToken);
        }

        while (result.Length < responseLimit)
        {
            using var idleTimeout = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
            idleTimeout.CancelAfter(TimeSpan.FromMilliseconds(750));
            try
            {
                var bytesRead = await stream.ReadAsync(buffer, idleTimeout.Token);
                if (bytesRead == 0)
                {
                    break;
                }

                var remaining = responseLimit - (int)result.Length;
                await result.WriteAsync(buffer.AsMemory(0, Math.Min(bytesRead, remaining)), cancellationToken);
                if (bytesRead > remaining)
                {
                    break;
                }
            }
            catch (OperationCanceledException) when (!cancellationToken.IsCancellationRequested)
            {
                break;
            }
        }

        if (result.Length >= responseLimit)
        {
            throw new InvalidDataException("Yanıt güvenli üst sınır olan 64 KB değerine ulaştı.");
        }

        return result.ToArray();
    }

    private static void Validate(LoomSettings loom)
    {
        if (string.IsNullOrWhiteSpace(loom.Name) || string.IsNullOrWhiteSpace(loom.Host))
        {
            throw new InvalidOperationException("Tezgâh adı ve IP/ana bilgisayar adresi zorunludur.");
        }

        if (loom.Port is < 1 or > 65535 || loom.EventCount is < 5 or > 20)
        {
            throw new InvalidOperationException("Port 1-65535, olay sayısı 5-20 aralığında olmalıdır.");
        }
    }

    private static string SafeFileName(string value) => string.Concat(value.Select(character => Path.GetInvalidFileNameChars().Contains(character) ? '_' : character));
}

internal sealed record PollenResult(int LoomId, string LoomName, DateTimeOffset StartedAtUtc, DateTimeOffset CompletedAtUtc, bool Succeeded, int? ResponseBytes, string? Sha256, string? CapturePath, LoomReading? Reading, IReadOnlyList<LoomEvent>? Events, string? Error, bool ConnectionFailed)
{
    public static PollenResult Ok(LoomSettings loom, DateTimeOffset started, byte[] response, string? capturePath, LoomReading reading, IReadOnlyList<LoomEvent> events) =>
        new(loom.LoomId, loom.Name, started, DateTimeOffset.UtcNow, true, response.Length, Convert.ToHexString(SHA256.HashData(response)), capturePath, reading, events, null, false);

    public static PollenResult Fail(LoomSettings loom, DateTimeOffset started, string error, bool connectionFailed) =>
        new(loom.LoomId, loom.Name, started, DateTimeOffset.UtcNow, false, null, null, null, null, null, error, connectionFailed);
}

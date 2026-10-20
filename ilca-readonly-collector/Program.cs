using System.Net.Sockets;
using System.Security.Cryptography;
using System.Text.Json;

var settingsPath = GetOption(args, "--config") ?? "appsettings.json";
var singleRun = args.Contains("--once", StringComparer.OrdinalIgnoreCase);

if (!File.Exists(settingsPath))
{
    Console.Error.WriteLine($"Configuration file was not found: {Path.GetFullPath(settingsPath)}");
    return 2;
}

var serializerOptions = new JsonSerializerOptions
{
    PropertyNameCaseInsensitive = true,
    WriteIndented = false
};

var settings = JsonSerializer.Deserialize<CollectorSettings>(await File.ReadAllTextAsync(settingsPath), serializerOptions)
    ?? throw new InvalidOperationException("Configuration is empty or invalid JSON.");
settings.Validate();

if (settings.Looms.Count == 0)
{
    Console.Error.WriteLine("No looms are configured. Add explicit IP addresses to appsettings.json before running.");
    return 3;
}

var outputDirectory = Path.GetFullPath(settings.OutputDirectory);
Directory.CreateDirectory(outputDirectory);
Directory.CreateDirectory(Path.Combine(outputDirectory, "captures"));

Console.WriteLine("ILCA read-only collector started. It only sends monitoring request 151/2 and never writes to MySQL or sends loom commands.");
Console.WriteLine($"Configured looms: {string.Join(", ", settings.Looms.Select(loom => $"{loom.Name} ({loom.Host}:{loom.Port})"))}");

using var shutdown = new CancellationTokenSource();
Console.CancelKeyPress += (_, eventArgs) =>
{
    eventArgs.Cancel = true;
    shutdown.Cancel();
};

do
{
    var pollStarted = DateTimeOffset.UtcNow;
    var results = await Task.WhenAll(settings.Looms.Select(loom => PollAsync(loom, settings, outputDirectory, shutdown.Token)));

    foreach (var result in results)
    {
        await AppendResultAsync(outputDirectory, result, serializerOptions, shutdown.Token);
        Console.WriteLine(result.ToConsoleLine());
    }

    if (singleRun || shutdown.IsCancellationRequested)
    {
        break;
    }

    var delay = TimeSpan.FromSeconds(settings.PollIntervalSeconds) - (DateTimeOffset.UtcNow - pollStarted);
    if (delay > TimeSpan.Zero)
    {
        await Task.Delay(delay, shutdown.Token);
    }
}
while (!shutdown.IsCancellationRequested);

return 0;

static async Task<PollResult> PollAsync(LoomSettings loom, CollectorSettings settings, string outputDirectory, CancellationToken cancellationToken)
{
    var started = DateTimeOffset.UtcNow;
    try
    {
        using var client = new TcpClient();
        using var connectTimeout = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
        connectTimeout.CancelAfter(settings.ConnectionTimeoutMilliseconds);
        await client.ConnectAsync(loom.Host, loom.Port, connectTimeout.Token);

        await using var stream = client.GetStream();
        var request = BuildMonitoringRequest(loom.EventCount, settings.ConnectionTimeoutMilliseconds);
        await stream.WriteAsync(request, cancellationToken);

        var response = await ReadResponseAsync(stream, settings, cancellationToken);
        var timestamp = DateTimeOffset.UtcNow;
        var fileStem = $"{timestamp:yyyyMMddTHHmmssfffZ}_{SafeFileName(loom.Name)}";
        var capturePath = Path.Combine(outputDirectory, "captures", $"{fileStem}.bin");
        await File.WriteAllBytesAsync(capturePath, response, cancellationToken);

        return PollResult.Succeeded(loom, started, timestamp, response, Path.GetRelativePath(outputDirectory, capturePath));
    }
    catch (OperationCanceledException) when (cancellationToken.IsCancellationRequested)
    {
        return PollResult.Failed(loom, started, "Cancelled");
    }
    catch (Exception exception)
    {
        return PollResult.Failed(loom, started, exception.Message);
    }
}

static async Task<byte[]> ReadResponseAsync(NetworkStream stream, CollectorSettings settings, CancellationToken cancellationToken)
{
    using var capture = new MemoryStream();
    var buffer = new byte[4096];

    using (var initialTimeout = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken))
    {
        initialTimeout.CancelAfter(settings.ConnectionTimeoutMilliseconds);
        var initialRead = await stream.ReadAsync(buffer, initialTimeout.Token);
        if (initialRead == 0)
        {
            throw new IOException("The loom closed the connection without a response.");
        }

        await capture.WriteAsync(buffer.AsMemory(0, initialRead), cancellationToken);
    }

    while (capture.Length < settings.ResponseLimitBytes)
    {
        using var idleTimeout = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
        idleTimeout.CancelAfter(settings.IdleReadMilliseconds);
        try
        {
            var bytesRead = await stream.ReadAsync(buffer, idleTimeout.Token);
            if (bytesRead == 0)
            {
                break;
            }

            var remaining = settings.ResponseLimitBytes - (int)capture.Length;
            await capture.WriteAsync(buffer.AsMemory(0, Math.Min(bytesRead, remaining)), cancellationToken);
            if (bytesRead > remaining)
            {
                break;
            }
        }
        catch (OperationCanceledException) when (!cancellationToken.IsCancellationRequested)
        {
            break; // No more bytes arrived during the bounded idle period.
        }
    }

    if (capture.Length >= settings.ResponseLimitBytes)
    {
        throw new InvalidDataException($"Response reached the configured safety limit of {settings.ResponseLimitBytes} bytes.");
    }

    return capture.ToArray();
}

static byte[] BuildMonitoringRequest(int eventCount, int timeoutMilliseconds)
{
    var request = new byte[13];
    request[0] = byte.MaxValue;
    BitConverter.GetBytes(timeoutMilliseconds).CopyTo(request, 1);
    request[5] = 151;
    request[6] = 3;
    request[10] = 2;
    BitConverter.GetBytes(Math.Clamp(eventCount, 5, 20)).AsSpan(0, 2).CopyTo(request.AsSpan(11, 2));
    return request;
}

static async Task AppendResultAsync(string outputDirectory, PollResult result, JsonSerializerOptions serializerOptions, CancellationToken cancellationToken)
{
    var logPath = Path.Combine(outputDirectory, "polls.jsonl");
    var json = JsonSerializer.Serialize(result, serializerOptions);
    await File.AppendAllTextAsync(logPath, json + Environment.NewLine, cancellationToken);
}

static string? GetOption(string[] values, string name)
{
    var index = Array.FindIndex(values, value => string.Equals(value, name, StringComparison.OrdinalIgnoreCase));
    return index >= 0 && index + 1 < values.Length ? values[index + 1] : null;
}

static string SafeFileName(string value) => string.Concat(value.Select(character => Path.GetInvalidFileNameChars().Contains(character) ? '_' : character));

internal sealed class CollectorSettings
{
    public int PollIntervalSeconds { get; init; } = 10;
    public int ConnectionTimeoutMilliseconds { get; init; } = 5000;
    public int IdleReadMilliseconds { get; init; } = 750;
    public int ResponseLimitBytes { get; init; } = 65536;
    public string OutputDirectory { get; init; } = "./data";
    public List<LoomSettings> Looms { get; init; } = [];

    public void Validate()
    {
        if (PollIntervalSeconds is < 1 or > 3600 || ConnectionTimeoutMilliseconds is < 100 or > 60000 || IdleReadMilliseconds is < 10 or > 10000 || ResponseLimitBytes is < 1024 or > 1048576)
        {
            throw new InvalidOperationException("One or more collector limits are outside the safe range.");
        }

        foreach (var loom in Looms)
        {
            loom.Validate();
        }
    }
}

internal sealed class LoomSettings
{
    public string Name { get; init; } = string.Empty;
    public string Host { get; init; } = string.Empty;
    public int Port { get; init; } = 12888;
    public int EventCount { get; init; } = 20;

    public void Validate()
    {
        if (string.IsNullOrWhiteSpace(Name) || string.IsNullOrWhiteSpace(Host) || Port is < 1 or > 65535 || EventCount is < 5 or > 20)
        {
            throw new InvalidOperationException("Each loom needs a name, host, TCP port, and an event count from 5 through 20.");
        }
    }
}

internal sealed record PollResult(string Loom, string Host, int Port, DateTimeOffset StartedAtUtc, DateTimeOffset CompletedAtUtc, bool Success, int? ResponseBytes, string? ResponseSha256, string? CaptureFile, string? Error)
{
    public static PollResult Succeeded(LoomSettings loom, DateTimeOffset started, DateTimeOffset completed, byte[] response, string captureFile) =>
        new(loom.Name, loom.Host, loom.Port, started, completed, true, response.Length, Convert.ToHexString(SHA256.HashData(response)), captureFile, null);

    public static PollResult Failed(LoomSettings loom, DateTimeOffset started, string error) =>
        new(loom.Name, loom.Host, loom.Port, started, DateTimeOffset.UtcNow, false, null, null, null, error);

    public string ToConsoleLine() => Success
        ? $"{Loom}: OK, {ResponseBytes} bytes, SHA-256 {ResponseSha256}, capture {CaptureFile}"
        : $"{Loom}: FAILED - {Error}";
}

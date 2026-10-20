using System.ComponentModel;
using System.Text.Json;

namespace Itema.Ilca.ControlCenter;

internal sealed class AppSettings
{
    public int PollIntervalSeconds { get; set; } = 10;
    public bool DevelopmentDatabaseWriteEnabled { get; set; }
    // Ham paket ve JSON günlükleri yalnızca kısa süreli hata ayıklamada açılır.
    // Normal üretimde disk büyümesini önlemek için kapalıdır.
    public bool DiagnosticCaptureEnabled { get; set; }
    public string DevelopmentConnectionString { get; set; } = string.Empty;
    public BindingList<LoomSettings> Looms { get; set; } = [];
}

internal sealed class LoomSettings
{
    public int LoomId { get; set; }
    public string Name { get; set; } = string.Empty;
    public string Host { get; set; } = string.Empty;
    public int Port { get; set; } = 12888;
    public int EventCount { get; set; } = 20;
    public int Protocol { get; set; } = 9;
}

internal static class SettingsStore
{
    private static readonly JsonSerializerOptions SerializerOptions = new() { WriteIndented = true };

    public static string SettingsPath => Path.Combine(AppContext.BaseDirectory, "ilca-control-center.json");

    public static AppSettings Load()
    {
        if (!File.Exists(SettingsPath))
        {
            return new AppSettings();
        }

        return JsonSerializer.Deserialize<AppSettings>(File.ReadAllText(SettingsPath), SerializerOptions) ?? new AppSettings();
    }

    public static void Save(AppSettings settings) => File.WriteAllText(SettingsPath, JsonSerializer.Serialize(settings, SerializerOptions));
}

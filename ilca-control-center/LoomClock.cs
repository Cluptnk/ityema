namespace Itema.Ilca.ControlCenter;

// Monitoring packets contain the current event's start and elapsed duration.
// Their sum estimates the loom clock at receipt; it must not rewrite history.
internal static class LoomClock
{
    public static string Describe(DateTime eventStart, int seconds, DateTime receivedAt)
    {
        if (seconds < 0) return "Olay süresi hatalı";
        DateTime deviceNow;
        try { deviceNow = eventStart.AddSeconds(seconds); }
        catch (ArgumentOutOfRangeException) { return "Tezgâh tarihi hatalı"; }
        var difference = deviceNow - receivedAt;
        if (Math.Abs(difference.TotalSeconds) <= 60) return "Saat uyumlu";
        if (Math.Abs(difference.TotalHours) >= 24) return $"Tarih hatalı: {deviceNow:dd.MM.yyyy HH:mm}";
        var minutes = (int)Math.Round(Math.Abs(difference.TotalMinutes));
        return $"Yaklaşık {minutes} dk {(difference.TotalSeconds > 0 ? "ileri" : "geri")}";
    }
}

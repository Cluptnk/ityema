namespace Itema.Ilca.ControlCenter;

internal sealed record StopSummaryRow(int LoomId, int Count, long Seconds);

internal sealed record StopCauseSummaryRow(int LoomId, int StatusCode, int Count, long Seconds);

internal sealed record ShiftWindow(string Code, DateTime SummaryDate, DateTime Start, DateTime End);

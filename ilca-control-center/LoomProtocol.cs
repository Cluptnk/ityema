using System.Buffers.Binary;
using System.Text;

namespace Itema.Ilca.ControlCenter;

internal sealed record LoomReading(
    int LoomSerial,
    int LoomId,
    DateTime Timestamp,
    int Model,
    int MeasurementSystem,
    int Speed,
    int Efficiency,
    int WeftDensity,
    int BeamCount,
    int WarpTension1,
    int WarpTension2,
    int WarpTension3,
    int WarpTension4,
    int ProductionType,
    int ProductionDone,
    int ProductionTarget,
    int ElapsedSeconds,
    int RemainingSeconds,
    int RemainingDefinition,
    int RemainingBeam1,
    int RemainingBeam2,
    int RemainingBeam3,
    int RemainingBeam4,
    int AirConsumption,
    int Warnings,
    string Rfid,
    int CommunicationError,
    int PlannedPieces,
    int ProducedPieces,
    int TotalMetersBeam1,
    int TotalMetersBeam2,
    int TotalMetersBeam3,
    int TotalMetersBeam4,
    int MadeMetersBeam1,
    int MadeMetersBeam2,
    int MadeMetersBeam3,
    int MadeMetersBeam4);

internal sealed record LoomEvent(
    int EventNumber,
    DateTime Timestamp,
    int ProductionStatus,
    int LoomId,
    int LoomSerial,
    string Shift,
    int Far,
    string Dobby1,
    string Dobby2,
    string Pattern,
    string WarpLotA,
    string WarpLotB,
    string WeftLot,
    int Declaration,
    int LengthFromStart,
    int PieceNumber,
    string Rfid,
    int EventSeconds,
    int Picks,
    string Article);

internal static class LoomProtocol
{
    private const int HeaderOffset = 10;
    private const int EventLength = 188;

    public static byte[] BuildMonitoringRequest(int requestedEvents)
    {
        var request = new byte[13];
        request[0] = byte.MaxValue;
        BinaryPrimitives.WriteInt32LittleEndian(request.AsSpan(1, 4), 5000);
        request[5] = 151;
        request[6] = 3;
        request[10] = 2;
        BinaryPrimitives.WriteUInt16LittleEndian(request.AsSpan(11, 2), (ushort)Math.Clamp(requestedEvents, 5, 20));
        return request;
    }

    public static (LoomReading Reading, IReadOnlyList<LoomEvent> Events) Decode(byte[] packet, LoomSettings loom)
    {
        var rtLength = loom.Protocol == 11 ? 121 : 73;
        Require(packet, HeaderOffset + rtLength + 1);
        var o = HeaderOffset;
        var serial = ReadU24(packet, o + 1);
        var loomId = ReadU16(packet, o + 4);
        var measurementSystem = packet[o + 6] == 0 ? 0 : 1;
        var model = DecodeModel(packet[o + 7]);
        var reading = new LoomReading(
            serial, loomId, DateTime.Now, model, measurementSystem,
            ReadU16(packet, o + 8), ReadU16(packet, o + 10), ReadU16(packet, o + 12), packet[o + 14],
            ReadU16(packet, o + 15), ReadU16(packet, o + 17), ReadU16(packet, o + 19), ReadU16(packet, o + 21), packet[o + 23],
            ReadU24(packet, o + 24), ReadU24(packet, o + 27), loom.Protocol == 11 ? ReadU24(packet, o + 30) : ReadU16(packet, o + 30),
            loom.Protocol == 11 ? ReadU24(packet, o + 33) : ReadU32(packet, o + 32), packet[o + 36],
            ReadU24(packet, o + 37), ReadU24(packet, o + 40), ReadU24(packet, o + 43), ReadU24(packet, o + 46),
            ReadU16(packet, o + 49), ReadU32(packet, o + 51), ReadText(packet, o + 55, 16), packet[o + 73],
            loom.Protocol == 11 ? ReadU16(packet, o + 74) : 0, loom.Protocol == 11 ? ReadU16(packet, o + 76) : 0,
            loom.Protocol == 11 ? ReadU24(packet, o + 78) : 0, loom.Protocol == 11 ? ReadU24(packet, o + 81) : 0,
            loom.Protocol == 11 ? ReadU24(packet, o + 84) : 0, loom.Protocol == 11 ? ReadU24(packet, o + 87) : 0,
            loom.Protocol == 11 ? ReadU24(packet, o + 90) : 0, loom.Protocol == 11 ? ReadU24(packet, o + 93) : 0,
            loom.Protocol == 11 ? ReadU24(packet, o + 96) : 0, loom.Protocol == 11 ? ReadU24(packet, o + 99) : 0);

        if (reading.ProductionType is < 0 or > 6 || reading.RemainingDefinition is < 0 or > 1 || reading.Warnings is < 0 or > 31)
        {
            throw new InvalidDataException("Tezgâhtan gelen canlı veri doğrulanamadı.");
        }

        var eventCount = ReadU16(packet, o + 71);
        var requiredLength = 1 + HeaderOffset + rtLength + eventCount * EventLength;
        if (packet.Length < requiredLength)
        {
            throw new InvalidDataException($"Eksik tezgâh paketi: {packet.Length} bayt geldi, en az {requiredLength} bayt gerekli.");
        }

        var events = new List<LoomEvent>(eventCount);
        for (var eventIndex = 1; eventIndex <= eventCount; eventIndex++)
        {
            events.Add(DecodeEvent(packet, HeaderOffset + rtLength + (eventIndex - 1) * EventLength, reading, measurementSystem));
        }

        return (reading, events);
    }

    private static LoomEvent DecodeEvent(byte[] packet, int o, LoomReading reading, int measurementSystem)
    {
        var timestamp = DecodeBcdTimestamp(packet, o + 5);
        var rawLength = ReadU32(packet, o + 167);
        var length = measurementSystem == 0 ? (int)Math.Round(rawLength * 25.4d) : rawLength * 10;
        return new LoomEvent(
            ReadU32(packet, o + 1), timestamp, ReadU24(packet, o + 12), reading.LoomId, reading.LoomSerial,
            ((char)packet[o + 11]).ToString(), ReadU16(packet, o + 15),
            ReadText(packet, o + 45, 20), ReadText(packet, o + 65, 20), ReadText(packet, o + 85, 20),
            ReadText(packet, o + 105, 20), ReadText(packet, o + 125, 20), ReadText(packet, o + 145, 20),
            ReadU16(packet, o + 165), length, ReadU16(packet, o + 171), ReadText(packet, o + 173, 16),
            BinaryPrimitives.ReadInt32LittleEndian(packet.AsSpan(o + 17, 4)), ReadU32(packet, o + 21), ReadText(packet, o + 25, 20));
    }

    private static int DecodeModel(int value) => value switch { 0 => 0, 1 => 1, 2 => 2, _ => value };
    private static int ReadU16(byte[] source, int offset) => BinaryPrimitives.ReadUInt16LittleEndian(source.AsSpan(offset, 2));
    private static int ReadU24(byte[] source, int offset) => source[offset] | (source[offset + 1] << 8) | (source[offset + 2] << 16);
    private static int ReadU32(byte[] source, int offset) => BinaryPrimitives.ReadInt32LittleEndian(source.AsSpan(offset, 4));
    private static string ReadText(byte[] source, int offset, int length) => Encoding.ASCII.GetString(source, offset, length).Trim(' ', '\0');
    private static void Require(byte[] source, int length) { if (source.Length < length) throw new InvalidDataException("Tezgâh paketi beklenenden kısa."); }
    private static int Bcd(byte value) => (value >> 4) * 10 + (value & 15);
    private static DateTime DecodeBcdTimestamp(byte[] source, int offset)
    {
        try { return new DateTime(2000 + Bcd(source[offset + 5]), Bcd(source[offset + 4]), Bcd(source[offset + 3]), Bcd(source[offset + 2]), Bcd(source[offset + 1]), Bcd(source[offset])); }
        catch (ArgumentOutOfRangeException) { return DateTime.Now; }
    }
}

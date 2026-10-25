using Itema.Ilca.ControlCenter;
void Check(bool ok, string message) { if (!ok) throw new Exception(message); }
var receipt = new DateTime(2026,10,22,20,34,34);
Check(LoomClock.Describe(new DateTime(2026,10,22,20,21,47),1714,receipt).Contains("16 dk ileri"),"Captured loom 14 must expose its actual clock discrepancy.");
Check(LoomClock.Describe(new DateTime(2023,1,7,21,21,18),43,receipt).StartsWith("Tarih hatalı"),"Old loom date cannot be treated as offline or zero production.");
Check(LoomClock.Describe(receipt.AddSeconds(-60),60,receipt)=="Saat uyumlu","Elapsed event must be anchored to receipt.");
Check(LoomClock.Describe(receipt,-1,receipt)=="Olay süresi hatalı","Invalid counter must not be trusted.");
Console.WriteLine("Clock capture regression checks passed.");

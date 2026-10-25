using Itema.Ilca.ControlCenter;
using System.Buffers.Binary;
void Check(bool ok,string why){if(!ok)throw new Exception(why);}
void Reject(Action action,string why){try{action();}catch(ArgumentException){return;}throw new Exception(why);}
var r=new ManualRequest(ManualOperation.Clock,14,"Fixture","not-a-live-host.invalid",12888,new DateTime(2026,10,22,20,45,12),"test.bin",[1,2,3],new DateTime(2026,10,22,20,45,12),123,true,true);
var clock=ManualLoomProtocol.Build(r);
Check(Convert.ToHexString(clock)=="FF00000000C8080000001012452022100426","Clock frame must match legacy BCD day/month/year layout.");
foreach(var op in Enum.GetValues<ManualOperation>()) {
    var packet=ManualLoomProtocol.Build(r with{Operation=op});
    var bytes=BinaryPrimitives.ReadInt32LittleEndian(packet.AsSpan(packet[0]==250?10:6,4));
    Check(packet.Length==bytes+(packet[0]==250?14:10),$"Truncated frame: {op}");
}
Check(ManualLoomProtocol.Build(r with{Operation=ManualOperation.Directory}).Length==14,"Directory frame contains only its 14-byte header.");
var upload=ManualLoomProtocol.Build(r with{Operation=ManualOperation.ArticleUpload});
Check(upload[9]==32&&BinaryPrimitives.ReadUInt32LittleEndian(upload.AsSpan(14,4))==123&&upload[^1]==3,"Article code and file content must not overwrite filename or header.");
Check(ManualLoomProtocol.Build(r with{Operation=ManualOperation.LockRun})[11]==3&&ManualLoomProtocol.Build(r with{Operation=ManualOperation.UnlockRun})[11]==0,"Lock/unlock cannot be reversed.");
Reject(()=>ManualLoomProtocol.Build(r with{Operation=ManualOperation.FileUpload,RemoteName="../secret"}),"Remote traversal must be rejected.");
Reject(()=>ManualLoomProtocol.Build(r with{Operation=ManualOperation.Dobby1Upload,AirLoom=null}),"Upload must require an explicit loom type for the stop guard.");
Reject(()=>ManualLoomProtocol.Build(r with{Operation=ManualOperation.FileUpload,FileBytes=[]}),"Empty upload cannot be sent.");
Reject(()=>ManualLoomProtocol.Build(r with{LoomId=0}),"Missing target cannot become all looms.");
var stopped=new byte[381];stopped[5]=18;stopped[10]=3;
Check(ManualLoomProtocol.IsStopped(stopped,false),"Full stop fixture rejected.");stopped[380]=16;Check(!ManualLoomProtocol.IsStopped(stopped,false),"Running loom cannot pass upload guard.");
try{ManualLoomProtocol.IsStopped(stopped[..380],false);throw new Exception("Truncated FIB must fail closed.");}catch(IOException){}
try{ManualLoomProtocol.CheckResponse(ManualOperation.Clock,clock);throw new Exception("Echoed request cannot be an ACK.");}catch(IOException){}
var file=new byte[114];file[111]=1;file[112]=2;file[113]=3;Check(ManualLoomProtocol.FileContent(ManualOperation.Dobby1Download,file).SequenceEqual(new byte[]{1,2,3}),"Download metadata must not be saved as content.");
var directory=new byte[11+11+System.Text.Encoding.Unicode.GetByteCount("test.bin,")];System.Text.Encoding.Unicode.GetBytes("test.bin,").CopyTo(directory,22);Check(ManualLoomProtocol.DirectoryText(directory)=="test.bin","Directory metadata cannot corrupt the filename.");
Console.WriteLine("Offline protocol checks passed. No socket or loom connection was created.");

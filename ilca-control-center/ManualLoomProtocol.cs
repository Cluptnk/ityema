using System.Buffers.Binary;
using System.Text;

namespace Itema.Ilca.ControlCenter;

internal enum ManualOperation { Clock, Directory, FileDownload, FileUpload, Dobby1Download, Dobby2Download, Dobby1Upload, Dobby2Upload, ColorDownload, ColorUpload, ArticleDownload, ArticleUpload, LockRun, UnlockRun, Stop, StopStatus }
internal sealed record ManualRequest(ManualOperation Operation, int LoomId, string LoomName, string Host, int Port, DateTime Clock, string RemoteName, byte[] FileBytes, DateTime FileModified, uint ArticleCode, bool Overwrite, bool? AirLoom);
internal static class ManualLoomProtocol
{
    public const int MaximumFileBytes = 8 * 1024 * 1024;
    public static bool Upload(ManualOperation op) => op is ManualOperation.FileUpload or ManualOperation.Dobby1Upload or ManualOperation.Dobby2Upload or ManualOperation.ColorUpload or ManualOperation.ArticleUpload;
    public static bool Download(ManualOperation op) => op is ManualOperation.FileDownload or ManualOperation.Dobby1Download or ManualOperation.Dobby2Download or ManualOperation.ColorDownload or ManualOperation.ArticleDownload;
    public static string Title(ManualOperation op) => op switch {
        ManualOperation.Clock => "Tarih ve saat eşitle", ManualOperation.Directory => "Tezgâh dosyalarını listele",
        ManualOperation.FileDownload => "Tezgâhtan dosya al", ManualOperation.FileUpload => "Tezgâha dosya gönder",
        ManualOperation.Dobby1Download => "Çalışan armür 1 dosyasını al", ManualOperation.Dobby2Download => "Çalışan armür 2 dosyasını al",
        ManualOperation.Dobby1Upload => "Armür 1 programını uygula", ManualOperation.Dobby2Upload => "Armür 2 programını uygula",
        ManualOperation.ColorDownload => "Çalışan atkı renk programını al", ManualOperation.ColorUpload => "Atkı renk programını uygula",
        ManualOperation.ArticleDownload => "Çalışan artikel / parametre dosyasını al", ManualOperation.ArticleUpload => "Artikel / parametre dosyasını uygula",
        ManualOperation.LockRun => "Çalıştırma düğmesini kilitle", ManualOperation.UnlockRun => "Çalıştırma düğmesini serbest bırak",
        ManualOperation.Stop => "Tezgâha duruş isteği gönder", ManualOperation.StopStatus => "Tam duruş durumunu sorgula", _ => throw new ArgumentOutOfRangeException(nameof(op)) };
    public static byte[] Build(ManualRequest r)
    {
        if (r.LoomId <= 0 || string.IsNullOrWhiteSpace(r.Host) || r.Port is < 1 or > 65535) throw new ArgumentException("Önce geçerli bir tezgâh seçin.");
        if (r.FileBytes.Length > MaximumFileBytes) throw new ArgumentException("Dosya en fazla 8 MB olabilir.");
        if (Upload(r.Operation) && r.FileBytes.Length == 0) throw new ArgumentException("Gönderilecek dosya boş olamaz.");
        if ((Upload(r.Operation) || r.Operation == ManualOperation.StopStatus) && r.AirLoom is null) throw new ArgumentException("Duruş kontrolü için tezgâh tipini seçin.");
        if (r.Operation == ManualOperation.Clock) {
            if (r.Clock.Year is < 2000 or > 2099) throw new ArgumentException("Yıl 2000–2099 arasında olmalıdır.");
            var p=Control(200,8);p[10]=16; p[11]=Bcd(r.Clock.Second);p[12]=Bcd(r.Clock.Minute);p[13]=Bcd(r.Clock.Hour);p[14]=Bcd(r.Clock.Day);p[15]=Bcd(r.Clock.Month);p[16]=Bcd((int)r.Clock.DayOfWeek);p[17]=Bcd(r.Clock.Year-2000);return p;
        }
        if (r.Operation is ManualOperation.LockRun or ManualOperation.UnlockRun) {var p=Control(66,2);p[10]=0;p[11]=(byte)(r.Operation==ManualOperation.LockRun?3:0);return p;}
        if (r.Operation==ManualOperation.Stop) {var p=Control(66,1);p[10]=4;return p;}
        if (r.Operation==ManualOperation.StopStatus) return Control(18,1);
        if (r.Operation==ManualOperation.FileDownload) {
            var name=Name(r.RemoteName); var p=Transfer(71,name.Length);name.CopyTo(p,14);return p;
        }
        if (r.Operation==ManualOperation.ArticleDownload) {var p=Transfer(33,4);U32(p,14,r.ArticleCode);return p;}
        if (!Upload(r.Operation)) return Transfer(r.Operation switch {ManualOperation.Directory=>80,ManualOperation.Dobby1Download=>41,ManualOperation.Dobby2Download=>43,ManualOperation.ColorDownload=>51,_=>throw new ArgumentException("İşlem tanınmıyor.")},0);
        var filename=Name(r.RemoteName); var header=r.Operation==ManualOperation.FileUpload?122:r.Operation==ManualOperation.ArticleUpload?118:114;
        var command=r.Operation switch {ManualOperation.FileUpload=>70,ManualOperation.Dobby1Upload=>40,ManualOperation.Dobby2Upload=>42,ManualOperation.ColorUpload=>50,ManualOperation.ArticleUpload=>32,_=>throw new ArgumentException("İşlem tanınmıyor.")};
        var packet=Transfer(command,header-14+r.FileBytes.Length);
        var nameOffset=14;
        if (r.Operation==ManualOperation.FileUpload) {packet[14]=(byte)(r.Overwrite?1:0);packet[15]=(byte)r.FileModified.Day;packet[16]=(byte)r.FileModified.Month;BinaryPrimitives.WriteUInt16LittleEndian(packet.AsSpan(17,2),(ushort)r.FileModified.Year);packet[19]=(byte)r.FileModified.Hour;packet[20]=(byte)r.FileModified.Minute;packet[21]=(byte)r.FileModified.Second;nameOffset=22;}
        else if(r.Operation==ManualOperation.ArticleUpload) {U32(packet,14,r.ArticleCode);nameOffset=18;}
        filename.CopyTo(packet,nameOffset);r.FileBytes.CopyTo(packet,header);return packet;
    }
    private static byte[] Name(string name) {if(string.IsNullOrWhiteSpace(name)||name.IndexOfAny(['\0','/','\\',':','*','?','"','<','>','|'])>=0||name is "." or "..")throw new ArgumentException("Tezgâh dosya adı yalnız dosya adı olmalıdır; klasör yolu yazmayın.");var bytes=Encoding.Unicode.GetBytes(name);if(bytes.Length>98)throw new ArgumentException("Tezgâh dosya adı en fazla 49 UTF-16 karakter olabilir.");return bytes;}
    private static byte Bcd(int v)=>(byte)((v/10<<4)|(v%10));
    private static void U32(byte[] p,int o,uint v)=>BinaryPrimitives.WriteUInt32LittleEndian(p.AsSpan(o,4),v);
    private static byte[] Control(byte command,int bytes) {var p=new byte[10+bytes];p[0]=255;p[5]=command;U32(p,6,(uint)bytes);return p;}
    private static byte[] Transfer(int command,int bytes) {var p=new byte[14+bytes];p[0]=250;p[9]=(byte)command;U32(p,10,(uint)bytes);return p;}
    public static void CheckResponse(ManualOperation operation,byte[] response)
    {
        if(response.Length<11)throw new IOException("Eksik tezgâh yanıtı; başarı doğrulanamadı.");
        if(operation==ManualOperation.Clock) {if(response.Length!=12||response[5]!=200||response[10]!=17||response[11]!=0)throw new IOException("Saat güncellemesi tezgâh tarafından onaylanmadı.");return;}
        if(operation is ManualOperation.LockRun or ManualOperation.UnlockRun) {if(response[5]!=66||response[10]!=1)throw new IOException("Çalıştırma düğmesi işlemi onaylanmadı.");return;}
        if(operation==ManualOperation.Stop) {if(response.Length<12||response[5]!=66||response[10]!=5)throw new IOException("Duruş isteği onaylanmadı.");return;}
        if(operation==ManualOperation.StopStatus) {if(response[5]!=18||response[10]!=3)throw new IOException("Duruş durumu okunamadı.");return;}
        if(response[10]!=0)throw new IOException($"Tezgâh işlemi reddetti. Sonuç kodu: {response[10]}.");
    }
    public static bool IsStopped(byte[] response,bool airLoom) {CheckResponse(ManualOperation.StopStatus,response);int offset=airLoom?295:380;if(response.Length<=offset)throw new IOException("Duruş bilgisi eksik; dosya gönderilmedi.");return (response[offset]&16)==0;}
    public static byte[] FileContent(ManualOperation operation,byte[] response) {CheckResponse(operation,response);int start=operation==ManualOperation.FileDownload?118:111;if(response.Length<start)throw new IOException("Dosya yanıtı eksik.");return response[start..];}
    public static string DirectoryText(byte[] response)
    {
        CheckResponse(ManualOperation.Directory,response);
        var payload=response.AsSpan(11);if(payload.Length==0)return "Tezgâh boş dosya listesi döndürdü.";
        // Legacy records have an 11-byte prefix and UTF-16LE names separated
        // at the trailing zero byte + comma boundary. Do not decode metadata as text.
        var names=new List<string>();int start=0;
        for(int i=0;i+1<payload.Length;i++)if(payload[i]==0&&payload[i+1]==44) {
            AddDirectoryName(payload,start+11,i,names);start=i+2;
        }
        if(start+11<payload.Length)AddDirectoryName(payload,start+11,payload.Length,names);
        if(names.Count==0)throw new IOException("Dosya listesi yanıtı alındı fakat kayıt biçimi çözümlenemedi. Liste boş kabul edilmedi.");
        return string.Join(Environment.NewLine,names.Distinct());
    }
    private static void AddDirectoryName(ReadOnlySpan<byte> payload,int start,int end,List<string> names)
    {
        if(end<=start||end-start>100)return;
        var bytes=new byte[(end-start+1)/2*2];payload[start..end].CopyTo(bytes);
        var name=Encoding.Unicode.GetString(bytes).TrimEnd('\0');
        if(name.Length>0&&!name.Any(char.IsControl))names.Add(name);
    }
}

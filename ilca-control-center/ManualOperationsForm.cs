using System.Security.Cryptography;

namespace Itema.Ilca.ControlCenter;

internal sealed class ManualOperationsForm : Form
{
    private sealed record Target(int Id,string Name,string Host,int Port) {public override string ToString()=>$"{Id} — {Name} ({Host}:{Port})";}
    private sealed record Choice(ManualOperation Operation) {public override string ToString()=>ManualLoomProtocol.Title(Operation);}
    private readonly ComboBox _target=new(){DropDownStyle=ComboBoxStyle.DropDownList,Dock=DockStyle.Fill};
    private readonly ComboBox _operation=new(){DropDownStyle=ComboBoxStyle.DropDownList,Dock=DockStyle.Fill};
    private readonly ComboBox _type=new(){DropDownStyle=ComboBoxStyle.DropDownList,Dock=DockStyle.Fill};
    private readonly TextBox _remoteName=new(){Dock=DockStyle.Fill,MaxLength=49};
    private readonly TextBox _localFile=new(){Dock=DockStyle.Fill,ReadOnly=true};
    private readonly NumericUpDown _article=new(){Maximum=uint.MaxValue,Dock=DockStyle.Fill};
    private readonly DateTimePicker _clock=new(){Format=DateTimePickerFormat.Custom,CustomFormat="dd.MM.yyyy HH:mm:ss",ShowUpDown=true,Dock=DockStyle.Fill};
    private readonly CheckBox _overwrite=new(){Text="Aynı adlı tezgâh dosyası varsa üzerine yaz",AutoSize=true};
    private readonly TextBox _result=new(){Multiline=true,ReadOnly=true,ScrollBars=ScrollBars.Vertical,Dock=DockStyle.Fill};
    private readonly Button _prepare=new(){Text="Önizle (göndermez)",AutoSize=true};
    private readonly Button _send=new(){Text="Seçili tezgâha gönder…",AutoSize=true,Enabled=false};
    private readonly Button _close=new(){Text="Kapat",AutoSize=true};
    private readonly TableLayoutPanel _fields=new(){Dock=DockStyle.Top,AutoSize=true,ColumnCount=2,Padding=new Padding(12)};
    private readonly Label _hint=new(){AutoSize=true,MaximumSize=new Size(760,0)};
    private ManualRequest? _prepared;
    private CancellationTokenSource? _sending;
    private readonly Action<string> _log;

    public ManualOperationsForm(IEnumerable<LoomSettings> looms,Action<string> log)
    {
        _log=log;Text="Manuel tezgâh işlemleri — Otomatik gönderim yok";Size=new Size(920,760);MinimumSize=new Size(760,640);StartPosition=FormStartPosition.CenterParent;
        _fields.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute,165));_fields.ColumnStyles.Add(new ColumnStyle(SizeType.Percent,100));
        foreach(var loom in looms)_target.Items.Add(new Target(loom.LoomId,loom.Name,loom.Host,loom.Port));
        foreach(var op in Enum.GetValues<ManualOperation>())_operation.Items.Add(new Choice(op));
        _operation.SelectedIndex=0;_target.SelectedIndex=-1;_type.Items.AddRange(["Havalı tezgâh (air-jet)","Rapier / kancalı tezgâh"]);_type.SelectedIndex=-1;
        Row("Hedef tezgâh",_target);Row("Yapılacak işlem",_operation);Row("Tezgâh tipi",_type);
        var timePanel=new TableLayoutPanel{AutoSize=true,Dock=DockStyle.Fill,ColumnCount=2};timePanel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent,100));timePanel.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        var now=new Button{Text="Bilgisayar saatini al",AutoSize=true};now.Click+=(_,_)=>_clock.Value=DateTime.Now;timePanel.Controls.Add(_clock,0,0);timePanel.Controls.Add(now,1,0);Row("Gönderilecek tarih/saat",timePanel);
        Row("Tezgâhtaki dosya adı",_remoteName);Row("Artikel kodu",_article);
        var files=new TableLayoutPanel{AutoSize=true,Dock=DockStyle.Fill,ColumnCount=2};files.ColumnStyles.Add(new ColumnStyle(SizeType.Percent,100));files.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        var browse=new Button{Text="Yerel dosya seç…",AutoSize=true};browse.Click+=(_,_)=>{using var picker=new OpenFileDialog{CheckFileExists=true,Multiselect=false,Title="Tezgâha göndermek için yerel dosya seçin"};if(picker.ShowDialog(this)==DialogResult.OK){_localFile.Text=picker.FileName;_remoteName.Text=Path.GetFileName(picker.FileName);}};
        files.Controls.Add(_localFile,0,0);files.Controls.Add(browse,1,0);Row("Gönderilecek yerel dosya",files);Row("Dosya seçeneği",_overwrite);Row("İşlem açıklaması",_hint);
        var notice=new Label{Text="Yalnız siz Gönder düğmesine basıp hedefi onaylarsanız işlem yapılır. Pencereyi açmak ve önizlemek tezgâha istek göndermez.",Dock=DockStyle.Top,AutoSize=true,Padding=new Padding(12),MaximumSize=new Size(880,0)};
        var buttons=new FlowLayoutPanel{Dock=DockStyle.Bottom,AutoSize=true,Padding=new Padding(12)};buttons.Controls.AddRange([_prepare,_send,_close]);
        Controls.Add(_result);Controls.Add(_fields);Controls.Add(notice);Controls.Add(buttons);
        _target.SelectedIndexChanged+=(_,_)=>InvalidatePreparation();_type.SelectedIndexChanged+=(_,_)=>InvalidatePreparation();
        _operation.SelectedIndexChanged+=(_,_)=>{UpdateHints();InvalidatePreparation();};
        _remoteName.TextChanged+=(_,_)=>InvalidatePreparation();_localFile.TextChanged+=(_,_)=>InvalidatePreparation();_article.ValueChanged+=(_,_)=>InvalidatePreparation();_clock.ValueChanged+=(_,_)=>InvalidatePreparation();_overwrite.CheckedChanged+=(_,_)=>InvalidatePreparation();
        _prepare.Click+=(_,_)=>Prepare();_send.Click+=async(_,_)=>await SendFromUserClickAsync();
        _close.Click+=(_,_)=>{if(_sending is not null)_sending.Cancel();else Close();};
        FormClosing+=(_,e)=>{if(_sending is not null)e.Cancel=true;};UpdateHints();InvalidatePreparation();
    }
    private void Row(string label,Control control) {int row=_fields.RowCount++;_fields.RowStyles.Add(new RowStyle(SizeType.AutoSize));_fields.Controls.Add(new Label{Text=label,AutoSize=true,Padding=new Padding(0,6,8,6)},0,row);control.Margin=new Padding(3,4,3,4);_fields.Controls.Add(control,1,row);}
    private ManualOperation Operation=>((Choice)_operation.SelectedItem!).Operation;
    private void InvalidatePreparation() {_prepared=null;_send.Enabled=false;_result.Text="Seçimi hazırlayıp önizleyin. Henüz tezgâha bir işlem gönderilmedi.";}
    private void UpdateHints()
    {
        var op=Operation;bool upload=ManualLoomProtocol.Upload(op);
        _remoteName.Enabled=upload||op==ManualOperation.FileDownload;_article.Enabled=op is ManualOperation.ArticleDownload or ManualOperation.ArticleUpload;
        _localFile.Parent!.Enabled=upload;_overwrite.Enabled=op==ManualOperation.FileUpload;_clock.Parent!.Enabled=op==ManualOperation.Clock;_type.Enabled=upload||op==ManualOperation.StopStatus;
        _hint.Text=op switch {
            ManualOperation.Clock=>"Seçilen tarih ve saat gönderilir. Saat değişikliği geçmiş kayıtları düzeltmez; mevcut olay zamanlarını etkileyebilir.",
            ManualOperation.UnlockRun=>"Yalnız çalıştırma düğmesinin kilidini kaldırır. Tezgâhı kendiliğinden başlatmaz.",
            ManualOperation.Stop=>"Üretimi etkileyen bir duruş isteğidir. Otomatik tekrar yapılmaz.",
            _ when upload=>"Dosya göndermeden önce tam duruş bilgisi sorgulanır. Tezgâh çalışıyorsa veya duruş doğrulanamazsa dosya gönderilmez. Otomatik durdurma veya kilit açma yapılmaz.",
            _ when ManualLoomProtocol.Download(op)=>"Dosyanın bilgisayara kaydedileceği yeri gönderimden önce siz seçeceksiniz.",
            _=>"Bu istek yalnız Gönder düğmesi ve hedef onayından sonra yapılır."};
    }
    private void Prepare()
    {
        try {
            if(_target.SelectedItem is not Target target)throw new ArgumentException("Hedef tezgâhı açıkça seçin.");
            byte[] bytes=[];DateTime modified=DateTime.Now;
            if(ManualLoomProtocol.Upload(Operation)) {var info=new FileInfo(_localFile.Text);if(!info.Exists||info.Length<=0||info.Length>ManualLoomProtocol.MaximumFileBytes)throw new ArgumentException("1 bayt–8 MB arasında mevcut bir yerel dosya seçin.");using var stream=new FileStream(info.FullName,FileMode.Open,FileAccess.Read,FileShare.Read);if(stream.Length>ManualLoomProtocol.MaximumFileBytes)throw new ArgumentException("Dosya boyutu değişti; yeniden seçin.");bytes=new byte[checked((int)stream.Length)];stream.ReadExactly(bytes);modified=info.LastWriteTime;}
            _prepared=new ManualRequest(Operation,target.Id,target.Name,target.Host,target.Port,_clock.Value,_remoteName.Text,bytes,modified,(uint)_article.Value,_overwrite.Checked,_type.SelectedIndex<0?null:_type.SelectedIndex==0);
            var packet=ManualLoomProtocol.Build(_prepared);
            _result.Text=$"HAZIRLIK — TEZGÂHA GÖNDERİLMEDİ\r\nHedef: {target}\r\nİşlem: {ManualLoomProtocol.Title(Operation)}\r\nPaket: {packet.Length:N0} bayt\r\n"+(_prepared.Operation==ManualOperation.Clock?$"Tarih/saat: {_prepared.Clock:dd.MM.yyyy HH:mm:ss}\r\n":"")+(bytes.Length>0?$"Dosya: {_prepared.RemoteName}\r\nBoyut: {bytes.Length:N0} bayt\r\nSHA256: {Convert.ToHexString(SHA256.HashData(bytes))}\r\n":"")+"\r\n"+_hint.Text;
            _send.Enabled=true;
        }catch(Exception e){_prepared=null;_send.Enabled=false;_result.Text="Hazırlanamadı: "+e.Message;}
    }
    private async Task SendFromUserClickAsync()
    {
        var request=_prepared;if(request is null||_sending is not null)return;
        string? destination=null;
        if(ManualLoomProtocol.Download(request.Operation)) {using var picker=new SaveFileDialog{Title="Alınacak dosyayı kaydet",FileName=request.Operation==ManualOperation.FileDownload?request.RemoteName:$"tezgah-{request.LoomId}-{DateTime.Now:yyyyMMdd-HHmmss}.bin",OverwritePrompt=true};if(picker.ShowDialog(this)!=DialogResult.OK)return;destination=picker.FileName;}
        string details=$"Tezgâh: {request.LoomId} — {request.LoomName}\r\nAdres: {request.Host}:{request.Port}\r\nİşlem: {ManualLoomProtocol.Title(request.Operation)}\r\n";
        if(request.Operation==ManualOperation.Clock)details+=$"Tarih/saat: {request.Clock:dd.MM.yyyy HH:mm:ss}\r\n";
        if(request.FileBytes.Length>0)details+=$"Dosya: {request.RemoteName} ({request.FileBytes.Length:N0} bayt)\r\nSHA256: {Convert.ToHexString(SHA256.HashData(request.FileBytes))}\r\n";
        if(MessageBox.Show(this,details+"\r\nBu işlemi bu tezgâha şimdi göndermek istiyor musunuz?","Manuel gönderim onayı",MessageBoxButtons.YesNo,MessageBoxIcon.Question,MessageBoxDefaultButton.Button2)!=DialogResult.Yes)return;
        _sending=new CancellationTokenSource();_fields.Enabled=false;_prepare.Enabled=false;_send.Enabled=false;_close.Text="Beklemeyi iptal et";
        _result.Text="İstek işleniyor… Yanıt gelmezse otomatik tekrar yapılmaz.";
        try {
            var response=await ManualLoomClient.ExecuteAsync(request,_sending.Token);
            if(destination is not null) {var content=ManualLoomProtocol.FileContent(request.Operation,response);var temp=destination+"."+Guid.NewGuid().ToString("N")+".partial";try{await File.WriteAllBytesAsync(temp,content);File.Move(temp,destination,true);}finally{if(File.Exists(temp))File.Delete(temp);}_result.Text=$"Tezgâh yanıtı alındı. Dosya kaydedildi: {destination}\r\n{content.Length:N0} bayt";}
            else if(request.Operation==ManualOperation.Directory)_result.Text="Tezgâh dosya listesi:\r\n"+ManualLoomProtocol.DirectoryText(response);
            else if(request.Operation==ManualOperation.StopStatus)_result.Text=ManualLoomProtocol.IsStopped(response,request.AirLoom!.Value)?"Tam duruş bilgisi doğrulandı.":"Tezgâh tam duruşta değil.";
            else if(request.Operation==ManualOperation.Stop)_result.Text=response[11]==1?"Duruş yanıtı: tam duruş bildirildi.":"Duruş isteği kabul edildi; tam duruş henüz doğrulanmadı.";
            else if(request.Operation==ManualOperation.Clock)_result.Text="Tezgâh saat güncelleme isteğini onayladı. Sonraki veri okumalarındaki saat farkını ayrıca kontrol edin.";
            else _result.Text="Tezgâh işlemi onayladı. Üretim durumu ayrıca kontrol edilmelidir.";
            _log($"MANUEL: Tezgâh {request.LoomId} / {ManualLoomProtocol.Title(request.Operation)} — {_result.Text.Split('\r','\n')[0]}");
        }catch(Exception e){_result.Text="İşlem tamamlanamadı: "+e.Message+"\r\nGönderim başladıysa sonuç belirsiz olabilir. Otomatik tekrar yapılmadı; tezgâhı kontrol etmeden yeniden göndermeyin.";_log($"MANUEL: Tezgâh {request.LoomId} — sonuç doğrulanamadı: {e.Message}");}
        finally{_sending.Dispose();_sending=null;_prepared=null;_fields.Enabled=true;_prepare.Enabled=true;_send.Enabled=false;_close.Text="Kapat";}
    }
}

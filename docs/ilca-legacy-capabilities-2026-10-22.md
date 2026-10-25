# Eski ILCA yetenek incelemesi — 22 Ekim 2026

Kaynak klasör: C:\Users\clipt\OneDrive\Desktop\Itema\ILCA

APP2.exe SHA256: 6D19C4198A6375B3F0443DA478E044691FD0E9C566493D5A8F8E80E0D26E061B

İnceleme yöntemi: APP2.exe, ILSpy 11.1 ile çalıştırılmadan C# olarak çözümlendi. Çözüm çıktısı kullanıcının TEMP klasöründeki itema-ilca-source-audit-20261022 dizinindedir. Kaynak klasörde değişiklik, programı başlatma, veritabanı yazımı veya tezgâha ağ isteği yapılmadı. Kodun varlığı doğrulandı; her tezgâh/modelin komutu kabul ettiği canlı olarak denenmedi.

## Tarih ve saat ayarı VAR

APP2/clockmanager.cs içinde createClockUpdatetPacket, Updateclock ve decodeClockUpdateResponse metotları vardır. createClockUpdatetPacket bilgisayarın DateTime.Now değerinden saniye, dakika, saat, gün, ay, haftanın günü ve iki basamaklı yılı BCD biçiminde kodlar. 18 baytlık istek TCP ile tezgâh IP/portuna gönderilir. Komut ailesi 200, alt işlem 16; beklenen yanıt 12 bayt, aile 200, alt işlem 17, sonuç 0. Eski uygulamanın kendi başarı ölçütü budur; yeni izleme olayıyla ayrıca kontrol edilmeden cihaz saati doğrulanmış sayılamaz.

Form1.SetLoomListClockRequired etkin ve ClockSyncStatus=1 olan tezgâhları kuyruğa alır. Kuyruğa alındı=2, işlemde=3, başarılı=4, başarısız=10; beş denemelik akış bulunur. tcpLoom.CheckThreadClockUpdate normal izleme bağlantısından önce bu kuyruğu işler; başka transfer görevi varsa saat işlemi sonraya kalabilir. APP2.exe.config içindeki clockUpdateRate=5, istek kuyruğunu kontrol etme aralığıdır; her beş saniyede tüm saatleri kendiliğinden değiştirme anlamına gelmez.

Form1.button6_Click tek IP'ye saat isteği gönderen test yoludur. Tasarımda Clock düğmesi Visible=false olduğu için normal pencerede görünmeyebilir. Daha eski bir SetLoomTime parametresi kullanan tek iş parçacıklı yol da vardır; aktif UpdateClockWorker_DoWork yolu PerformClockUpdateMultiThread çağırır. Bu yüzden yalnız eski SetLoomTime alanına bakarak etkin senkronizasyon akışı çıkarılmamalıdır.

## Diğer yetenekler

| İşlem | Kod kanıtı | Açıklama |
|---|---|---|
| Anlık ve olay verilerini okuma | tcpLoom.createRequestPacket, DecodeRTData9/11, decodeEventData | Hız, randıman, yoğunluk, gerginlik, üretim ve duruş bilgileri |
| Tezgâhtaki dosyaları listeleme | getDirList, createRequestPacketdir | Dosya listesini alır; görev kodu 80 |
| Genel dosya gönderme/alma | uploadGenFile, downGenFile | Bilgisayar ile tezgâh arasında dosya aktarımı |
| Armür/desen 1 ve 2 okuma | DownDobby | Çalışan birinci/ikinci dobby verisini alır |
| Armür/desen uygulama | executeDobby, createRequestPacketUploadDobby | Dosyayı tezgâha gönderip çalıştırma akışına sokar |
| Atkı renk/desen bilgisi | downloadWeftPatterInExecution, executeCOP | Çalışan renk notası verisini alma ve COP uygulama |
| Artikel/tezgâh parametre dosyaları | downCUrrArt, uploadArt | Artikel koduyla çalışan parametreleri alma/gönderme |
| Çalıştırma düğmesini kilitleme/açma | commandControlRunButton | Düğmeyi izinli/engelli yapar; doğrudan motoru başlatma kanıtı değildir |
| Tezgâhı durdurma ve durum kontrolü | commandLoomStop, checkLoomStop | Duruş isteği ve tam duruşun kontrolü |

Aktif artikel/desen gönderme yollarının bazılarında önce çalıştırma düğmesi engelleniyor, duruş isteniyor ve duruş kontrol ediliyor; dosya aktarıldıktan sonra düğme tekrar serbest bırakılıyor. Bu işlemler üretimi etkileyebilir. İnceleme sırasında hiçbiri uygulanmadı.

Hız, atkı yoğunluğu ve çözgü gerginliklerinin anlık okunması kesin olarak var. Bunları tek tek değiştiren bağımsız bir ayar komutu bu uygulamada saptanmadı. Parametre/ﬁle aktarımı olması, her sayısal ayarın dosya biçimi bilinmeden güvenle değiştirilebileceği anlamına gelmez.

İşler çoğunlukla veritabanındaki task kuyruğu üzerinden tcpLoom.DoLoomWork tarafından yürütülür. Saat için ayrı ClockSyncStatus kuyruğu vardır. Bunlar bizim yeni salt izleme Kontrol Merkezi'ne otomatik olarak aktarılmış özellikler değildir.

## Klasördeki diğer dosyalar

MySql.Data ve MySqlBackup veritabanı/backup bağımlılıkları; Newtonsoft.Json serileştirme bağımlılığıdır. xnodusnet sınıfları OxySec.XNodus/XLight lisans/donanım anahtarı bileşenlerini gösterir; xnodus ve xnodus32 yerel eşleridir. CONFIG3333 ve CONFIG444 içeriği 2023 tarihli SQL veri dökümleridir; çalıştırılmadı. Bağlantı sırları bu rapora alınmadı.

## Sonuç

Bu eski ILCA sürümünde bilgisayardan tarih/saat eşitleme desteği vardır. Önceki açıklamadaki saat değiştirememe sınırı yeni Kontrol Merkezi'ne aittir. Eski protokol bilgisi kullanılarak yeni uygulamaya yalnız saat senkronizasyonu eklenebilir; bu incelemede eklenmedi ve cihazlarda denenmedi. Saatin ileri/geri alınmasının mevcut olay aralıklarına etkisi ayrıca doğrulanmalıdır; geçmiş yanlış tarihli kayıtlar otomatik düzelmez.

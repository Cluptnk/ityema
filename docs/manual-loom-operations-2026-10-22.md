# Manuel tezgâh işlemleri — sürüm 2026.10.22 / 3

Kullanıcının talebi: Eski ILCA'daki yetenekler yeni programa eklensin; işlemleri kullanıcı kendi başlatsın; geliştirici canlı tezgâha komut göndermesin veya deneme yapmasın.

## Eklenen işlemler

- Tarih/saat ayarlama: açık seçilen tarih/saat; bilgisayar saatini elle alma.
- Tezgâh dosyalarını listeleme, genel dosya gönderme ve alma.
- Çalışan armür 1 ve armür 2 dosyalarını alma; armür 1/2 uygulama.
- Çalışan atkı renk programını alma ve uygulama.
- Artikel koduyla çalışan artikel/parametre dosyasını alma ve uygulama.
- Çalıştırma düğmesini kilitleme ve serbest bırakma.
- Duruş isteği ve tezgâh türüne göre tam duruş durumu sorgulama.

Bunlar bağımsız manuel işlemlerdir. RPM/gerginlik için doğrulanmamış tekil ayar komutu veya doğrudan motor başlatma eklenmedi. Bunların yerine eski uygulamada doğrulanan parametre dosyası aktarımı var.

## Kullanım

1. Ana ekranda Manuel işlemler… düğmesini açın. Okuma turu sürüyorsa bitince açılır; diyalog açıkken otomatik okuma zamanlayıcısı duraklar.
2. Tek bir hedef tezgâh ve işlem seçin. Hedef kendiliğinden seçilmez.
3. İşleme göre saat, yerel dosya, uzak dosya adı, artikel kodu ve tezgâh tipi girin.
4. Önizle yalnız yerel doğrulama yapar. Ağa bağlanmaz. Dosya için boyut ve SHA256 gösterilir.
5. Siz Gönder düğmesine basarsanız hedef ve işlem tekrar onaylanır. Onayın varsayılanı Hayır'dır.
6. Sonuç belirsizse program otomatik tekrar göndermez. Sonuçları tezgâh üzerinden ayrıca kontrol edin.

Pencere açılması, alanların değişmesi, program başlangıcı, zamanlayıcı ve veritabanı task kuyruğu manuel komut göndermez. Üretimde ManualLoomClient.ExecuteAsync çağrısının tek yeri onaylanan kullanıcı Gönder düğmesi işleyicisidir. Ayar değişirse eski hazırlanmış işlem geçersiz olur.

Dosya uygulamadan önce tam duruş sorgulanır; tezgâh çalışıyorsa veya durum okunamazsa dosya gönderilmez. Program buna karşılık otomatik durdurma/kilitleme/kilit açma yapmaz. Bunlar ayrı kullanıcı seçimleridir. Dosya alma hedefi gönderim öncesi seçilir; eksik yanıt dosya diye kaydedilmez.

## Doğrulama sınırı

Derleme ve ağ bağlantısı kurmayan çevrimdışı testler: eski protokolle BCD saat paketi, tüm işlem çerçeve uzunlukları, artikel kodu/dosya yerleşimi, kilitleme yönü, dosya yolu doğrulaması, açık hedef seçimi, duruş türü gereksinimi, eksik duruş yanıtının reddi, ACK doğrulaması, dosya içerik ayrımı ve eski dosya listesi biçimi kontrol edildi. Canlı tezgâha yeni manuel komut gönderilmedi; fiziksel işlev denemesi yapılmadı. Kullanıcı kendi programından deneyecek.

100 PiCANOL, masaüstünün türetilmiş duruş hesaplarında da dışlanır; ham izleme verilerinin alınması ayrı işlemdir.


## Sunucu kurulumu

22 Ekim yaklaşık 21:01'de C:\Users\Administrator\Desktop\publish\Ilca.ControlCenter.exe sürüm 2026.10.22 / 3 ile değiştirildi; SHA256 manifest ile doğrulandı. Yedek: C:\ILCA\Backups\control-center-manual-20261022-205835. Eski ve yeni EXE ile manifest saklandı.

Uzak Masaüstünde yeni sürüm başlığı ve Manuel işlemler ekranı görüldü. Hedef boş, Gönder pasif ve Henüz tezgâha bir işlem gönderilmedi açıklaması doğrulandı. Hiçbir hedef seçilmedi, önizleme veya Gönder düğmesine basılmadı. Pencere kapatıldı; normal sürekli veri okuma 5 saniye aralıkla yeniden açıldı. 21:02 güncel yazım günlükleri ve Sürekli durdur düğmesi görüldü. Tanı kaydı kapalı, geliştirme veritabanı yazımı açık bırakıldı.

Ekran kanıtı: [Gönderim yapılmadan manuel ekran](manual-operations-2026-10-22.png).

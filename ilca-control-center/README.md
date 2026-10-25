# ITEMA ILCA Kontrol Merkezi

Sürüm 2026.10.22 / 3. Otomatik döngü yalnız izleme isteği gönderir. Manuel işlemler yalnız kullanıcının açık Gönder tıklaması ve hedef onayıyla komut gönderebilir. Kullanıcı etkinleştirirse sadece ilmsdb_dev veritabanına olay/canlı veri ve özet yazar.

Olay sayaçları aynı olay tekrar alındığında güncellenir. Eski yanıt daha güncel sayaçları düşürmez. Özet yenileme arayüz dışında çalışır; ağ yanıtında toplam zaman aşımı ve arayüz günlüğünde boyut sınırı vardır.

Tezgâh saati sütunu, o anda devam eden olayın başlangıcı + süresini sunucunun okuma saatiyle karşılaştırır. Bu bir tanı bilgisidir: cihazın saatini veya tarihsel kayıtları değiştirmez. Yanlış tarihli olaylar doğru günlük/vardiyalı rapora kendiliğinden dönüşmez.

`dotnet publish` yalnız yerel çıktı üretir. Önceki otomatik, tarih ekli ayrı EXE kopyalama kaldırıldı. Kurulumda çalışan pencere düzenli kapatılmalı, sunucudaki asıl Ilca.ControlCenter.exe yedeklenmeli, yerel publish çıktısıyla değiştirilmeli ve SHA256 doğrulanmalıdır. Sonra asıl EXE açılıp başlıktaki sürüm ve Sürekli durdur düğmesiyle aktif toplama doğrulanmalıdır. Ayar dosyası korunmalıdır.

Ham paket/JSON tanısı normalde kapalıdır. Uzun süre açık bırakılmamalıdır.


## Manuel işlemler

Saat eşitleme, dosya listesi, genel dosya gönderme/alma, armür 1/2 alma ve uygulama, atkı renk programı alma ve uygulama, artikel/parametre dosyası alma ve uygulama, çalıştırma düğmesini kilitleme/serbest bırakma, duruş isteği ve tam duruş sorgusu eklendi. Doğrudan motor başlatma komutu yoktur.

Pencere açılması ve önizleme ağ bağlantısı açmaz. Hedef başlangıçta seçili değildir. Her ayar değişikliği hazırlığı iptal eder; yeniden önizlemek gerekir. Son onayda tezgâh kimliği, IP/port, işlem, seçilen saat veya dosyanın SHA256 değeri gösterilir. Onayın varsayılanı Hayır'dır. Başlangıçta, zamanlayıcıda veya task kuyruğundan komut gönderimi yoktur. Otomatik tekrar veya toplu hedef desteği eklenmedi.

Dosya gönderimi öncesinde kullanıcı tarafından belirtilen tezgâh tipiyle tam duruş sorgulanır. Duruş doğrulanamazsa dosya gönderilmez. Çalıştırma düğmesi otomatik kilitlenmez/açılmaz ve tezgâh otomatik durdurulmaz. Bu işlemler ayrı manuel seçimlerdir. Saat seçicisindeki açık tarih/saat gönderilir; Bilgisayar saatini al düğmesiyle elle yenilenebilir. Bir yanıtta başarı kodu alınması tüm fiziksel sonucun ayrıca doğrulandığı anlamına gelmez.

Dosyalar en fazla 8 MiB; uzak dosya adları en fazla 49 UTF-16 karakter ve yalnız dosya adıdır. Alınan dosya önce tam yanıt olarak bellekte doğrulanır, ardından seçilen yerel hedefe geçici dosyadan taşınır. Ağ yanıtı başlık ve boyutla sınırlandırılır; TCP parçalanması ReadExactly ile ele alınır. Bağlantıda 5, her alışverişte toplam 30 saniye zaman aşımı vardır. Belirsiz sonuç otomatik yinelenmez.

Programın canlı tezgâha manuel komutları geliştirici tarafından denenmedi. Paket ve hata kontrolleri ağ bağlantısı oluşturmayan çevrimdışı testlerle incelendi. Kullanıcı donanım üzerindeki denemeleri kendisi yapacaktır.

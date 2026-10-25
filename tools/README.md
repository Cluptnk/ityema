# Rapor düzeltmelerini dağıtma

Güncel kapsam ve kanıtlar: [Rapor denetimi](../docs/report-audit-2026-10-22.md).

`deploy-report-fixes.ps1` değişen site-dashboard dosyalarını sunucu ile SHA256 karşılaştırır. Varsayılan yalnız listeleme; `-Apply` özgün dosyaları yedekler, bağımlılık sırasıyla atomik değiştirir ve hash doğrular. Hata olursa o dağıtımın değişikliklerini geri alır. Veritabanına yazmaz. 100 PiCANOL kalıcı hesap dışıdır.

```powershell
./tools/deploy-report-fixes.ps1
./tools/deploy-report-fixes.ps1 -Apply
```

Ortak rapor motoru geliştirme bağlantısında ana tamamlanmış olay kaynağını salt okunur kullanır. Eksik değerleri sıfır saymaz. Hesaplanabilen olayları kayıtlı alt kapsam olarak gösterir; tam dönem doğrulamasını ayrı tutar. Güncel kapsam ve sınırlar denetim belgesinin son bölümündedir. Tarihsel onarım aşağıdaki ayrı araçtır; rapor düzeltmelerinin çalışması için uygulanması gerekmez. Bu oturumda `--apply` çalıştırılmadı.

# Üretim performansı onarımı

Kontrol Merkezi, devam eden olayları `INSERT IGNORE` ile ilk haliyle bırakıyordu.
Önce güncellenmiş Kontrol Merkezi'ni kurun. Yeni sürüm, aynı olayın süre, atkı ve
metraj sayaçlarını günceller; daha eski bir yanıt güncel sayaçları geri düşürmez.

`repair-performance-events.php` geçmişte eksik kalmış sayaçları eski sistemin
`ilmsdb.evento` kayıtlarıyla karşılaştırır. Eski sistemin verisi yalnızca okunur.
Yalnız olay kimliği, zaman, tezgâh, seri ve durum kodu eşleşen kayıtlar düzeltilir.
Kaynakta bulunmayan olaylar bu araçla kurtarılamaz.

Sunucudaki PHP CLI ve PDO MySQL uzantısıyla önce deneme taraması yapın:

```powershell
php tools/repair-performance-events.php --config=C:/Apache24/htdocs-dev/connectionDB.php --from=2026-10-22 --to=2026-10-22
```

Tarih aralığı iki tarihi de kapsar. Bu komut veriyi değiştirmez. Aynı komuta
`--apply` eklenirse yalnız `ilmsdb_dev` değiştirilir. Değişecek satırların tamamı
önce `ilmsdb_dev.performance_event_backup_*` tablosuna kaydedilir. Satır yedeği
ve güncelleme aynı transaction içinde yapılır; hata olursa geri alınır. Araç
oluşturulan yedek tablosunun adını bildirir. Bağlantı bilgilerini çıktıya yazmaz.

Web güncellemesinde `performance_data.php`, `performance_calculation.php`,
`js/performance.js` ve `uretim_performansi.php` birlikte kopyalanmalıdır. Rapor artık duruşları da aynı
olaylardan hesaplar; eski duruş özetlerini bu ekran için yeniden üretmek gerekmez.
Dönem sınırındaki üretim ve atkı, olayın süre oranına göre tahmin edilir.
İlk bilinen sayacın öncesine ait üretim ve henüz kaydedilmeyen süre eklenmez.
Parça değişmeden sayaç düşerse belirsiz olayın kalan sayaç değeri üretim sayılmaz.
Diğer hesaplanabilen olayların kayıtlı metrajı görünür; eksik kapsam ayrıca
belirtilir ve tam dönem üretimi olarak sunulmaz.

Doğrulama:

```powershell
php tests/performance_calculation_test.php
dotnet build ilca-control-center/Ilca.ControlCenter.csproj
```

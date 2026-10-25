# Rapor denetimi — 22 Ekim 2026

Raporlar sayfasındaki on raporun hesaplama ve veri sunma yolları incelendi. Web düzeltmeleri sunucuya aktarıldı. Geçmiş olay kayıtları değiştirilmedi.

| Rapor | Düzeltme |
|---|---|
| Üretim performansı | Parça değişimindeki sayaç sıfırlaması; süre ağırlıklı dönem hesabı; atkı/RPM/randıman; aynı duruşun devamını tekrar saymama |
| Duruş analizi | Ortak olay kaynağı, vardiya sınırları, kapsam ve neden toplamları; eksik filoda kesin sıralama ve dışa aktarma engeli |
| Yönetim analizi | Aynı dönem ve kaynak; güncel canlı veri ile tarihsel verinin ayrılması |
| Metraj analizi | Günlük bellek sınırı, ham değerlerden toplam, doğrulanamayan sayaçlar için bilinmeyen değer |
| Saatlik üretim | Dönemlere süre oranlı dağıtım, gelecek saatleri sıfır göstermeme, ham değerlerden toplam |
| Bakım takibi | Olaylardan neden hesabı, eksik veri uyarısı, kayıt anındaki önceliğin açık gösterimi |
| Verim kaybı | Yalnız doğrulanmış süre/metrajdan tahmin; eksik kayıtta tahmin üretmeme |
| Desen değişimi | Sıralı gözlenen desen geçişleri; ilk gözlem zamanını kesin değişim zamanı saymama |
| Kapasite planlama | Tamamlanmış ve doğrulanmış günler; eksik geçmişten kesin bitiş tarihi üretmeme; günlük veri parçaları |
| Veri güvenliği | Başarı kaydı, gerçek dosya varlığı/boyutu ve güncellik kontrolü; doğrulanmamış zamanlama/geri yükleme iddiası kaldırıldı |

## Kalıcı kapsam kuralı

100 numaralı PiCANOL hiçbir rapor hesabına dahil edilmez. Ortak veri sorgusunda, hesaplama katmanında, seçim listelerinde, yönetim canlı verisinde, bakım iş toplamlarında ve pano/anlık veri sorgularında dışlandı. Elle tek tezgâh seçimi reddedilir; dahil listesine 100 yazılması bu kuralı aşmaz. Tezgâh veya geçmiş kayıtları silinmedi.

## Veri kaynağı ve sınırlar

Geliştirme toplayıcısının ilk aldığı olay sayaçlarını güncellemeden bırakması saptandı. Raporlar ilmsdb_dev bağlantısında ana toplayıcının ilmsdb.evento kayıtlarını salt okunur kullanır. Devam eden olaylar henüz bulunmayabilir. Aynı parçada açıklanamayan sayaç düşüşleri veya dönem boşlukları varsa metraj/randıman kesin sayı olarak gösterilmez. Duruş süreleri kayıtlı alt kapsamdır. Dönem sınırını aşan olayların metrajı süre oranıyla paylaştırılan tahmindir. Operatör ataması bulunmayan veriden çalışan performansı çıkarılamaz.

PHP 5.6 saat dilimi verisinin Türkiye için eski olması nedeniyle raporlar sabit UTC+3 kullanır; 1 Kasım için MySQL ile zaman eşleşmesi doğrulandı.

## Doğrulama

- Gerçek veritabanında salt okunur test: 22 Ekim 07–15 tezgâh 14 üretimi 67,22 m = (501,04 − 454,21) + 20,39. Eski MAX−MIN hesabı 501,04 m veriyordu.
- Aynı örnekte 6 duruş, 836 saniye; 300,1 RPM ve %97,10 randıman.
- 21 Ekim tezgâh 14 günlük üretimi performans, metraj ve saatlik raporlarda 175,30 m ile eşleşti.
- Son kalıcı dışlama testinde filo 50 tezgâh; 100 açıkça dahil istendiğinde veri kümesi boş. Sentetik hesaplama girdisiyle de 100 dışlanıyor.
- Sayaç düzeltmesi bulunan tezgâh 6 için metraj bilinmeyen kalıyor.
- PHP 5.6 sözdizimi, JavaScript sözdizimi, hesaplama/veri kalitesi/yedek doğrulama testleri ve arayüzün eski yanıt/hata/boş değer testleri geçti.
- Dokuz veri raporu/SSR gövdesi gerçek veritabanında SELECT-only adaptörle çalıştırıldı. 31 günlük kapasite testi günlük sınır taşımasıyla yaklaşık 29,5 saniye ve 17,75 MiB bellekle geçti.
- Sunucudaki gerçek son veritabanı ve site yedekleri mevcut; kayıtlı boyutlarla eşleşiyor (1.119.759.948 ve 11.639.964 bayt). Geri yükleme denenmedi.
- Önceki canlı tarayıcı kontrollerinde rapor ekranları ve 67,22 m örneği görüldü. Son oturumda tarayıcı oturumu kapanmıştı; son değişikliklerin görsel kontrolü giriş ekranı nedeniyle tamamlanamadı.

## Dağıtım ve geri dönüş

Son dağıtım: C:\ILCA\Backups\report-fix-20261022-202231. Son 12 değişen dosyanın SHA256 eşleşmesi doğrulandı. Önceki dağıtımların yedekleri de aynı kökte tutuldu; ilk kapsamlı dağıtım report-fix-20261022-183327. Her dağıtımda original, updated ve manifest.json vardır. Veritabanına onarım yazımı yapılmadı; Apache yeniden başlatılmadı.

Kontrol Merkezi için sayaç güncelleme, UI iş parçacığı yükü ve ağ yanıt zaman aşımı düzeltmeleri kaynakta hazır ve derlendi. Çalışan sunucu uygulamasına kurulması/yeniden başlatılması ve kilitlenmenin canlı doğrulaması tamamlanmış değildir.


## 20:28 sonrası canlı inceleme

Chrome’da kullanıcının açık üretim raporu incelendi: devam eden 15–23 vardiyasında toplamlar ve birçok satır bilinmeyen görünüyordu. Önceki “tamamlandı” beyanı raporların kullanılabilirliği açısından erken verilmişti. Sarı uzun açıklama kaldırıldı; dinamik raporlarda kısa kapsam satırı ve kapalı Veri ayrıntıları, sunucu taraflı sayfalarda da kapalı ayrıntılar kullanılıyor. Değişiklik 20261022-202817 yedeğiyle dağıtıldı; Chrome’da arka planın saydam ve ayrıntıların kapalı olduğu görüldü. Veri eksiklikleri giderilmiş sayılmadı.

20:26 civarında tezgâh 14 için ana olay kaynağının son tamamlanmış olayı 179976, geliştirme kaynağının devam eden olayı 179977 idi. Devam eden olayın başlangıç zamanı/süresi ile sunucu saati arasında ayrıca araştırılması gereken uyumsuzluk görüldü. Tahminle birleştirme yapılmadı. Canlı dato_rt kayıtları iki kaynakta da güncelleniyordu; bu, masaüstü arayüzünün yanıt verdiğinin kanıtı değildir.

Kontrol Merkezi yeniden derlendi. Sunucudaki C:\Users\Administrator\Desktop\publish\Ilca.ControlCenter.exe SHA256 değeri yeni derlemeyle aynı çıktı. Ancak çalışan sürecin yolu/başlama zamanı CIM üzerinden sorgulanamadı (erişim engellendi). Çalışan uygulamanın yeniden başlatıldığı veya sorunun canlı giderildiği doğrulanmadı; sunucu masaüstü erişimi gerekiyor.


## Ham paket ile kök neden doğrulaması

22 Ekim yaklaşık 20:34 sunucu okumasında geçici tek döngü ham paket kaydı alındı; tanı ayarı ardından kapatıldı. Paketler doğrudan tezgâhın gönderdiği olay tarihlerini doğruladı. 44: 09.09.2026; 201: 07.01.2023; 202: 20.01.2024; 203: 21.10.2026. 14 numaranın devam eden olayı 20:21:47 ve 1714 saniye; alım 20:34:34. Olay saati + süre sunucudan yaklaşık 946 saniye ileri. Başka tezgâhlarda da farklı sapmalar var; tek bir ortak saat kaydırması uygulanamaz.

Bu bulgu nedeniyle 67,22 m kanıtı, kayıtlardaki cihaz zamanına göre sayacın/parça değişiminin aritmetik kontrolüdür; gerçek sunucu saatine göre vardiya aidiyetini tek başına doğrulamaz. Geçmiş kayıtlara mevcut saat sapmasını körlemesine uygulamak doğru değildir. Ham geçmiş kayıtlar değiştirilmedi. Cihaz saatlerinin düzeltilmesi veya alım zamanı ile kanıtlı normalizasyon henüz yapılmadığı için tüm raporlar doğrulanmış kabul edilemez.

Kontrol Merkezi dağıtımındaki proje hedefinin tarih ekli ayrı EXE kopyaladığı saptandı. Bu hedef kaldırıldı. Sürüm 2026.10.22 / 2 başlığa eklendi; saat tanı sütunu ve kırpılan bağlantı seçeneklerinin otomatik yerleşimi eklendi. Gerçek paket kaynaklı saat sapması için regresyon testleri geçti. Özgün ve yeni EXE control-center-20261022-2040 klasöründe yedeklendi.


## Son durum — 20:42

Asıl sunucu EXE'si kapalı pencereyle değiştirildi ve SHA256 eşleşmesi doğrulandı. Uzak Masaüstü ekranında 2026.10.22 / 2 başlığı, aktif Sürekli durdur düğmesi, güncel veritabanı yazım günlükleri ve saat farkı sütunu görüldü. Normal ayarlar: 5 saniyede bir toplama, veritabanı yazımı açık, ham tanı kaydı kapalı. Uzun süreli kilitlenmeme henüz kanıtlanmış değildir.

Kullanıcı cihaz tarih/saatinin tezgâh panellerinden ayarlandığını ve kendisinin daha sonra düzelteceğini belirtti. Bu nedenle cihaz saatleri veya geçmiş kayıtlar yazılımla değiştirilmedi. Günlük/vardiyalı raporların tümü doğrulanmış değildir; mevcut fiziksel saat sorunları ve tarihsel sayaç belirsizlikleri devam ediyor. Yeni doğru saatli kayıtlar geçmiş yanlış zamanların otomatik onarımı anlamına gelmez.


## 21:12 sonrası kullanılabilir rapor düzeltmesi

Önceki tamamlık kapısı, bir tezgâhın eksikliğiyle tüm fabrikanın üretim/RPM/randımanını boşaltıyordu. Bu davranış kaldırıldı. Tam dönem doğrulaması (complete) ayrı tutulur; hesaplanabilen kayıtlı metraj, atkı, hız, kayıtlı sürede randıman ve duruşlar gösterilir. Sayaç düzeltmesi veya sıra boşluğu olan olayların belirsiz üretimi eklenmez; aynı tezgâhın diğer hesaplanabilir olayları korunur. Kaydı olmayan tezgâh sıfır sayılmaz. Kapsam sayıları ve cihaz saatine göre dönem ibaresi ekranın görünür kısmındadır. Uzun teknik ayrıntılar kapalı bölümde kalır.

Günlük ve saatlik raporlar aynı olayların hesaplanabilir parçalarını toplar; tüm filoda saatlik ham toplamın günlük ham toplama eşitliği gerçek veritabanında aynı veri kesitiyle test edildi. 21 Ekim kontrol anında performans, metraj ve saatlik rapor 12.497,02 m verdi. Saatleri yanlış tezgâhlar eski tarihli olay üretmeye devam ettiği için sonradan aynı tarihli kaydın değişmesi mümkündür; bu sayı gerçek sunucu vardiyası doğrulaması değildir. Gerçek saat düzeltmesi yapılmadan kayıtlar tam fabrika gerçek üretimi diye sunulamaz.

Verim kaybı artık hesabı yapılabilen tezgâh grubunun kayıtlı dokuma hızından tahmin üretir ve grubun büyüklüğünü belirtir. Kapasite, seçilen tamamlanmış günlerin tümünde tam ve tutarlı kaydı bulunan ortak tezgâh grubunu kullanır. Varsayılan 7 günlük kontrolde kapsam 13 tezgâh, günlük 2.942,14 m, 10.000 m için 4 gün; bunun tüm fabrika kapasitesi olmadığı açıklanır. Eksik günler ortalamada sıfıra dönüştürülmez.

Kayıtlı duruşların Excel/PDF dışa aktarımı kullanılabilir; çıktıda kayıt kapsamı ve veri ayrıntıları korunur. Eksik filo üzerinden kesin kötü tezgâh sıralaması yapılmaz. 100 PiCANOL kapsam dışı kalır.

Chrome'da on rapor canlı oturumla kontrol edildi: üretim performansı, duruş, yönetim, metraj, saatlik üretim, bakım, verim kaybı, desen değişimi, kapasite, veri güvenliği. Dinamik raporlar sayısal değer üretti; verim kaybı, desen ve kapasite KPI'ları görüldü. Gerçek yedek dosyaları doğrulanmış olarak göründü. Konsol hatası görülmedi. Kapalı gün/tek tezgâh regresyonu, eksik bir tezgâhın diğerini gizlememesi ve tam kapsam iddiası üretmemesi, gelecek dönem/boş veri ve PiCANOL dışlama testleri geçti. PHP 5.6 ve JavaScript sözdizimleri doğrulandı.

Dağıtım yedekleri: report-fix-20261022-211253 (22 dosya), report-fix-20261022-211641 (9 dosya). SHA256 eşleşmeleri doğrulandı. Veritabanı tarihi/sayacı değiştirilmedi; tezgâha ayar komutu gönderilmedi.

### Monthly report performance, 22 October 2026 21:32
User required preserving calculation accuracy over speed. Only two performance changes deployed: raw events ordered by Timestamp,IdEvento using the timestamp index (per-loom grouping retains identical internal order); stop report reuses the already calculated full-fleet measurement when selected machine arrays are strictly identical. No formulas, clock offsets, counters or database records changed.
Read-only consistent-snapshot comparison of installed baseline versus candidate for 2026-09-23 through 2026-10-22: strict equality of every daily measurement array and summary; 30 days, 374311.51 recorded metres, 54975 stops, 8702163 stop seconds. No database writes or loom commands. Quality and performance calculation tests passed under PHP 5.6.
Endpoint benchmark: baseline 30.354 seconds, final candidate 27.986 seconds, both 141 queries; separate live snapshots, so this modest single-run timing improvement is not a guaranteed speedup. Remaining monthly processing still takes about 28 seconds; no claim of full performance resolution. Two files installed with verified SHA256 hashes; backup: \\192.168.2.5\c\ILCA\Backups\report-fix-20261022-213217. Snapshot equivalence establishes unchanged calculations, not historical device-clock correctness.

### Read-only monthly optimization trial, 22 October 2026
User asked for a measured trial after explicitly prioritizing unchanged calculations. Candidate remains LOCAL ONLY; no deployment performed in this trial.
Baseline: currently installed engine (backup report-fix-20261022-213217). Candidate changes only auditDataset: batches independent LIMIT 2 predecessor lookups using UNION ALL; each inner lookup selects only IdTelaio,IdEvento,Timestamp covered by the existing machine index; joins back on the full primary key to fetch at most two payloads per loom. The original IdEvento DESC,Timestamp DESC selection is retained, including incorrect device clocks. Raw range query, time boundaries, daily carry, all calculation functions and accumulator are unchanged. No index/schema/database changes or hardware commands.
Profiling baseline monthly stop endpoint (23 Sep through 22 Oct): 141 queries,25.565s,158776 raw rows; predecessor lookups alone 50 queries/11.023s. Batching full payload lookups alone:92 queries,27.821s; no demonstrated gain. Final covering-key candidate:92 queries,18.099s,predecessors0.373s,peak18.25MiB. Separate requests contain moving live records; these timings alone are not an equivalence proof.
Strict same-read-only-transaction comparison with a frozen clock, 30 daily chunks: full raw datasets, derived boundary fields, complete measurement arrays and summaries identical. Baseline30.847s,candidate20.587s (~33.3% reduction); 374382.47 recorded metres,54997 stops,8703595 stop seconds in that snapshot. All calculation functions and performance_calculation.php additionally checked unchanged against installed sources. Three-day night-shift comparison also strictly identical (2.004s vs1.228s). Tests use PHP5.6 under128MiB; measurements include client-to-database network costs and do not guarantee identical browser/server timing.
Quality and calculation tests passed; live read-only integration passed including loom14=67.22m, hourly conservation, missing/uncertain scope, timezone and permanent PiCANOL100 exclusion. No commands sent; no database writes.

### Deployment of verified monthly query optimization, 22 October 2026 21:48
User explicitly requested applying the tested candidate to server. Deployed only report_engine.php with original saved at \\192.168.2.5\c\ILCA\Backups\report-fix-20261022-214810. SHA256 verified by deployment script. No report formula, database schema/data, device settings or loom commands changed. The 30-day equality and timing evidence above refers to the exact deployed source.
Post-deployment Chrome check: Duruş Analizi completed for 2026-09-23 through 2026-10-22, full fleet, day window: 55014 recorded stops, 2418h24m03s, 10 causes; export controls enabled and no browser warning/error logs. Daily report also rendered. Live counters differ from the earlier test snapshot as collection continues. No trustworthy browser elapsed timing was captured; do not represent the earlier CLI timing as a measured live-page duration.

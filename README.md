# ITEMA / ILCA

Dokuma tezgâhlarının izlenmesi, üretim raporları ve Türkçe yönetim arayüzü için geliştirme kaynakları.

## Bileşenler

- `ilca-control-center`: .NET 8 Windows Forms kontrol ve izleme uygulaması.
- `ilca-readonly-collector`: Yalnızca izleme istekleri gönderen .NET 8 veri toplayıcı.
- `site-dashboard`: PHP raporlama, üretim ve bakım ekranları.
- `site-modern`: Web arayüzü stilleri ve modernleştirme dosyaları.
- `site-turkish`: Türkçe arayüz uyarlamaları.
- `site-improvements`: Durum ekranı ve gezinme iyileştirmeleri.
- `setup-ilca-development.ps1`: Mevcut Apache/MySQL kurulumundan ayrı bir geliştirme ortamı hazırlama betiği.

## Geliştirme

Windows uygulaması için Windows ve .NET 8 SDK gerekir:

```powershell
dotnet build ilca-control-center/Ilca.ControlCenter.csproj
dotnet build ilca-readonly-collector/Ilca.ReadOnlyCollector.csproj
```

Web klasörleri mevcut ILCA PHP uygulamasına yönelik eklemelerdir; bağımsız, eksiksiz bir web sunucusu kurulumu içermez. Veritabanı bağlantısı ve gerekli temel uygulama bağımlılıkları yerel ortamda ayrıca yapılandırılmalıdır.

Veri toplayıcının kullanımı için kendi klasöründeki README dosyasına bakın. Geliştirme ortamı hazırlama betiği veritabanı oluşturur ve Apache yapılandırmasını değiştirir; çalıştırmadan önce içeriğini ve hedef ortamı inceleyin. Windows uygulamasını derlemek veya yayımlamak çalışan sunucu uygulamasını otomatik olarak değiştirmez.

## Raporlar ve manuel işlemler

Raporların ortak hesap motoru, kayıtlı üretim ile eksik dönem kapsamını ayrı gösterir. 100 numaralı PiCANOL tüm hesapların dışındadır. Aylık sorgu iyileştirmesi, aynı veri kesitindeki ham olaylar ve hesap sonuçları karşılaştırılarak doğrulanmıştır. Ayrıntılar ve ölçümler [rapor denetiminde](docs/report-audit-2026-10-22.md), dağıtım araçları [araç belgelerinde](tools/README.md) bulunur.

Kontrol Merkezi'nin saat ayarı ve diğer manuel işlemleri kullanıcı tarafından hazırlanıp onaylanır; otomatik gönderilmez. Kapsam ve sınırlar [manuel işlem belgesinde](docs/manual-loom-operations-2026-10-22.md) açıklanır.

Çevrimdışı kontroller (tezgâhlara bağlanmaz):

```powershell
php tests/performance_calculation_test.php
php tests/report_quality_test.php
php tests/backup_validation_test.php
node tests/performance_ui_test.cjs
dotnet run --project tests/clock-check/ClockCheck.csproj
dotnet run --project tests/manual-operations/ManualOperations.csproj
```

Yedekler, veritabanı dökümleri, yerel bağlantı bilgileri, çalışma verileri ve derleme çıktıları bu depoya dahil edilmez.

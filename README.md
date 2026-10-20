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

Veri toplayıcının kullanımı için kendi klasöründeki README dosyasına bakın. Geliştirme ortamı hazırlama betiği veritabanı oluşturur ve Apache yapılandırmasını değiştirir; çalıştırmadan önce içeriğini ve hedef ortamı inceleyin. Windows uygulamasının publish hedefinde ortama özel bir dağıtım yolu bulunur.

Yedekler, veritabanı dökümleri, yerel bağlantı bilgileri, çalışma verileri ve derleme çıktıları bu depoya dahil edilmez.

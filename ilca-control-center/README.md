# ITEMA ILCA Kontrol Merkezi

Bu, eski `APP2.exe` yerine hazırlanan yeni Windows uygulamasının güvenli ilk sürümüdür. Çift tıklanabilir arayüzle tezgâh IP ve TCP portu tanımlanır; uygulama yalnızca eski yazılımdaki izleme paketini gönderir ve gelen veriyi yapılandırılmış veritabanına aktarır.

## Güvenlik sınırı

- MySQL'e yazmaz.
- `task` kuyruğunu okumaz veya değiştirmez.
- Dosya yüklemez/indirmez.
- Tezgâhı başlatmaz veya durdurmaz.

Ham paket ve JSON günlükleri varsayılan olarak kapalıdır. Yalnızca kısa süreli hata ayıklama için `DiagnosticCaptureEnabled` etkinleştirilirse `data/captures` ve `polls.jsonl` yazılır.

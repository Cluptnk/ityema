Güncel sunucu dağıtımı ve kapsam: [22 Ekim rapor denetimi](report-audit-2026-10-22.md). Aşağıdaki metin ilk doğrulama anının kaydıdır.

# Üretim uzunluğu doğrulaması — 22 Ekim 2026

İnceleme salt okunur veritabanı işlemleriyle yapıldı. Sunucudaki uygulama,
rapor dosyaları ve kayıtlar değiştirilmedi.

## Doğrulanan örnek

Tezgâh: **14**, dönem: **22 Ekim 2026, 07:00–15:00**.

Mevcut ekranın `MAX(LunghezzaDaInizio)-MIN(LunghezzaDaInizio)` formülü,
`ilmsdb_dev.evento` üzerinde **501,04 metre** veriyor.

Eski toplayıcının tamamlanmış `ilmsdb.evento` kayıtları:

| Kayıt | Zaman | Süre (sn) | Parça | Kayıt sonundaki sayaç (m) |
| --- | --- | ---: | ---: | ---: |
| 179935 | 06:31:31 | 1709 | 211 | 454,21 |
| 179954 | 13:23:05 | 23 | 211 | 501,04 |
| 179955 | 13:23:29 | 108 | 212 | 0,00 |
| 179961 | 14:30:36 | 1764 | 212 | 20,39 |

İlk kayıt 07:00'da, son kayıt 15:00'da bitiyor. Vardiya başlangıç
sayacı 454,21 m. Parça 211'deki vardiya üretimi 501,04 − 454,21 = 46,83 m.
Parça 212'de 20,39 m üretilmiş. Toplam **67,22 m**.

Mevcut formül **433,82 m fazla** hesaplıyor; parçanın eski üretimini de
seçili vardiyaya katıyor. Hazırlanan yeni hesap, aynı kaynak kayıtlarında
bağımsız parça hesabıyla eşleşerek **67,22 m** döndürdü.

## Toplamın sınırları

Bu doğrulama fiziksel kumaş ölçümü değil, tamamlanmış kayıtlı sayaçların
uzlaştırılmasıdır. Diğer tezgâhların tümü için doğruluk sonucu çıkarılamaz.
Örneğin 6 numaralı tezgâhta parça 153 değişmeden sayaç 399,53 m'den
302,23 m'ye düşüyor (184000 → 184001). Bu düşüşün nedenini kayıtlar tek
başına açıklamıyor; 302,23 m yeni üretim olarak eklenemez.

Bu nedenle tüm tesis için kesin bir düzeltilmiş toplam verilmedi. Hazırlanan
hesap bu tür sayaç düzeltmelerini belirsiz olarak işaretler ve arayüzde
metrajı `Doğrulanamadı` gösterir. Sunucudaki mevcut rapor henüz güncellenmedi.

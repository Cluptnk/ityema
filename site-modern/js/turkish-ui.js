/* Geliştirme sitesi için ortak sabit metin çevirileri.
 * Veritabanından gelen ifadeler head.php ile seçilen "tr" dil paketinden gelir.
 */
(function () {
    'use strict';

    var words = Object.freeze({
        'Room': 'Oda',
        'Rooms': 'Odalar',
        'Current Data': 'Anlık Veriler',
        'RT Data Table': 'Anlık Veri Tablosu',
        'Statistics': 'İstatistikler',
        'Cloth analysis': 'Kumaş Analizi',
        'Machines access': 'Tezgâh Erişimi',
        'Pattern Management': 'Desen Yönetimi',
        'Settings': 'Ayarlar',
        'Login': 'Giriş',
        'Logout': 'Çıkış',
        'Loading...': 'Yükleniyor...',
        'Loading data...': 'Veriler yükleniyor...',
        'Please wait...': 'Lütfen bekleyin...',
        'Please Sign In': 'Lütfen giriş yapın',
        'Password': 'Şifre',
        'Send': 'Giriş yap',
        'Save': 'Kaydet',
        'Cancel': 'İptal',
        'Close': 'Kapat',
        'Delete': 'Sil',
        'Edit': 'Düzenle',
        'Add': 'Ekle',
        'Update': 'Güncelle',
        'Search': 'Ara',
        'Filter': 'Filtrele',
        'Reset': 'Sıfırla',
        'Confirm': 'Onayla',
        'Yes': 'Evet',
        'No': 'Hayır',
        'Warning': 'Uyarı',
        'Error': 'Hata',
        'Success': 'Başarılı',
        'Date': 'Tarih',
        'Time': 'Saat',
        'Shift': 'Vardiya',
        'Loom': 'Tezgâh',
        'Looms': 'Tezgâhlar',
        'Speed': 'Hız',
        'Efficiency': 'Verim',
        'Production': 'Üretim',
        'Production state': 'Üretim durumu',
        'Status': 'Durum',
        'Event': 'Olay',
        'Events': 'Olaylar',
        'Alarm': 'Alarm',
        'Alarms': 'Alarmlar',
        'Report': 'Rapor',
        'Reports': 'Raporlar',
        'Download': 'İndir',
        'Upload': 'Yükle',
        'Print': 'Yazdır',
        'Export': 'Dışa aktar',
        'Previous': 'Önceki',
        'Next': 'Sonraki',
        'First': 'İlk',
        'Last': 'Son',
        'Total': 'Toplam',
        'All': 'Tümü',
        'None': 'Yok',
        'Select': 'Seç',
        'Select all': 'Tümünü seç',
        'Clear': 'Temizle',
        'Online': 'Bağlı',
        'Offline': 'Bağlantısız',
        'Connected': 'Bağlı',
        'Disconnected': 'Bağlantı kesildi'
    });

    function translate(value) {
        var leading = value.match(/^\s*/)[0];
        var trailing = value.match(/\s*$/)[0];
        var core = value.slice(leading.length, value.length - trailing.length);
        return Object.prototype.hasOwnProperty.call(words, core) ? leading + words[core] + trailing : value;
    }

    function translateNode(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            var translated = translate(node.nodeValue);
            if (translated !== node.nodeValue) node.nodeValue = translated;
            return;
        }
        if (node.nodeType !== Node.ELEMENT_NODE || node.tagName === 'SCRIPT' || node.tagName === 'STYLE') return;
        ['placeholder', 'title', 'aria-label', 'value'].forEach(function (attribute) {
            if (!node.hasAttribute(attribute)) return;
            var original = node.getAttribute(attribute);
            var translated = translate(original);
            if (translated !== original) node.setAttribute(attribute, translated);
        });
        Array.prototype.forEach.call(node.childNodes, translateNode);
    }

    function translatePage() { translateNode(document.body); }
    document.addEventListener('DOMContentLoaded', function () {
        translatePage();
        new MutationObserver(function (changes) {
            changes.forEach(function (change) {
                Array.prototype.forEach.call(change.addedNodes, translateNode);
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
}());

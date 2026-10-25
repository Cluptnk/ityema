<!DOCTYPE html>
<html>
<?php include('head.php'); require_once __DIR__.'/report_engine.php'; ?>
<?php
$looms = $db->exec("SELECT IdTelaio, NomeTelaio FROM telaio WHERE EnableTelaio = 1 AND IdTelaio<>100 ORDER BY IdTelaio");
$today = date('Y-m-d'); if ((int)date('G')<7) $today=date('Y-m-d',strtotime('-1 day'));
?>
<body class="management-report-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>
        <div id="page-wrapper" class="management-report-wrapper">
            <main class="management-report-shell">
                <header class="management-report-heading">
                    <div><span>ÜRETİM YÖNETİMİ</span><h1>Yönetim Analizi</h1><p>Vardiya, duruş, tezgâh, desen ve erişim durumunu tek ekranda inceleyin.</p></div>
                </header>
                <section class="management-report-controls" aria-label="Yönetim analizi filtreleri">
                    <label>Tarih<input id="management-date" type="date" value="<?php echo $today; ?>"></label>
                    <label>Duruş dönemi<select id="management-window"><option value="all">Tüm vardiyalar (07:00–ertesi gün 07:00)</option><option value="s1">07:00–15:00</option><option value="s2">15:00–23:00</option><option value="s3">23:00–07:00</option><option value="day">24 saat</option></select></label>
                    <label>Rapor tezgâhı<select id="management-loom"><option value="0">Tüm tezgâhlar</option><?php foreach ($looms as $loom) { ?><option value="<?php echo (int) $loom['IdTelaio']; ?>"><?php echo (int) $loom['IdTelaio'] . ' ' . htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></label>
                    <label class="management-exclude">Rapor dışı tezgâhlar<input id="management-exclude" type="search" placeholder="Örn. 25, 26, 6" autocomplete="off"><small>Yazılan tezgâhlar tüm hesaplamalardan çıkarılır.</small></label>
                    <button id="management-run" type="button"><i class="fa fa-refresh"></i> Analizi getir</button>
                </section>
                <section class="management-kpis" aria-live="polite">
                    <article><span>Kayıtlı duruş başlangıcı</span><strong id="management-stop-count">—</strong></article>
                    <article><span>Kayıtlı duruş süresi</span><strong id="management-stop-duration">—</strong></article>
                    <article><span>Bağlantı/verisi güncel olmayan</span><strong id="management-offline-count">—</strong></article>
                    <article><span>Hedef tanımlı tezgâh</span><strong id="management-goal-count">—</strong></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Vardiya karşılaştırması</h2><small>Seçilen tarihteki vardiya duruş özeti</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Vardiya</th><th>Duruş</th><th>Süre</th><th>Etkilenen tezgâh</th></tr></thead><tbody id="management-shifts"></tbody></table></div></article>
                    <article class="management-panel"><header><h2>Kayıtlı duruşlar</h2><small>Eksik veri olan dönemlerde tezgâhlar sıralanmaz.</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Duruş</th><th>Süre</th></tr></thead><tbody id="management-problems"></tbody></table></div></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Duruş Pareto analizi</h2><small>Seçili dönemin kayda geçen duruş nedenleri</small></header><div id="management-pareto" class="management-pareto"></div></article>
                    <article class="management-panel"><header><h2>Ana duruş grupları</h2><small>Koni, çözgü, atkı, manuel ve diğer duruşlar</small></header><div id="management-categories" class="management-categories"></div></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Tezgâh geçmişi</h2><small id="management-history-title">Grafik için üstten bir tezgâh seçin.</small></header><div id="management-history" class="management-history"></div></article>
                    <article class="management-panel"><header><h2>Desen bazlı anlık görünüm</h2><small>Tarih filtresinden bağımsız; yalnız son 60 saniyede güncellenen kayıtlar</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Desen</th><th>Tezgâh</th><th>Ort. hız</th><th>Ort. randıman</th></tr></thead><tbody id="management-patterns"></tbody></table></div></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Anlık bağlantı ve veri güncelliği</h2><small>Tarih filtresinden bağımsız; bağlantı hatası ve eski veri ayrı belirtilir</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Son veri</th><th>Durum</th></tr></thead><tbody id="management-offline"></tbody></table></div></article>
                    <article class="management-panel"><header><h2>Anlık üretim hedefi</h2><small>Tarih filtresinden bağımsız; yalnız güncel makine sayaçlarının karşılaştırması</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Gerçekleşen</th><th>Hedef</th><th>Oran</th></tr></thead><tbody id="management-goals"></tbody></table></div></article>
                </section>
                <p class="management-note"><i class="fa fa-info-circle"></i> Anlık tabloların kontrol zamanı: <span id="management-live-time">—</span>. Operatör bazlı rapor için sistemde operatör giriş/çıkış veya tezgâh atama kaydı bulunması gerekir. Bu ekran mevcut makine ve vardiya verilerini kullanır.</p>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/management-reports.css?v=20260923-1">
    <link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1">
    <script src="js/report-quality.js?v=20261022-6"></script><script src="js/management-reports.js?v=20261022-6"></script>
    <script src="js/report-table-sort.js?v=20260923-2"></script>
</body>
</html>

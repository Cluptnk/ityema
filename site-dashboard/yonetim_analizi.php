<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<?php
$looms = $db->exec("SELECT IdTelaio, NomeTelaio FROM telaio WHERE EnableTelaio = 1 ORDER BY IdTelaio");
$today = date('Y-m-d');
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
                    <label>Duruş dönemi<select id="management-window"><option value="all">Tüm vardiyalar</option><option value="s1">07:00–15:00</option><option value="s2">15:00–23:00</option><option value="s3">23:00–07:00</option><option value="day">24 saat</option></select></label>
                    <label>Rapor tezgâhı<select id="management-loom"><option value="0">Tüm tezgâhlar</option><?php foreach ($looms as $loom) { ?><option value="<?php echo (int) $loom['IdTelaio']; ?>"><?php echo (int) $loom['IdTelaio'] . ' ' . htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></label>
                    <label class="management-exclude">Rapor dışı tezgâhlar<input id="management-exclude" type="search" placeholder="Örn. 25, 26, 6" autocomplete="off"><small>Yazılan tezgâhlar tüm hesaplamalardan çıkarılır.</small></label>
                    <button id="management-run" type="button"><i class="fa fa-refresh"></i> Analizi getir</button>
                </section>
                <section class="management-kpis" aria-live="polite">
                    <article><span>Toplam duruş</span><strong id="management-stop-count">—</strong></article>
                    <article><span>Toplam duruş süresi</span><strong id="management-stop-duration">—</strong></article>
                    <article><span>Çevrimdışı tezgâh</span><strong id="management-offline-count">—</strong></article>
                    <article><span>Hedef tanımlı tezgâh</span><strong id="management-goal-count">—</strong></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Vardiya karşılaştırması</h2><small>Seçilen tarihteki vardiya duruş özeti</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Vardiya</th><th>Duruş</th><th>Süre</th><th>Etkilenen tezgâh</th></tr></thead><tbody id="management-shifts"></tbody></table></div></article>
                    <article class="management-panel"><header><h2>En problemli tezgâhlar</h2><small>En uzun duruş süresine göre tüm tezgâhlar</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Duruş</th><th>Süre</th></tr></thead><tbody id="management-problems"></tbody></table></div></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Duruş Pareto analizi</h2><small>En fazla kayıp oluşturan nedenler</small></header><div id="management-pareto" class="management-pareto"></div></article>
                    <article class="management-panel"><header><h2>Ana duruş grupları</h2><small>Koni, çözgü, atkı, manuel ve diğer duruşlar</small></header><div id="management-categories" class="management-categories"></div></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Tezgâh geçmişi</h2><small id="management-history-title">Grafik için üstten bir tezgâh seçin.</small></header><div id="management-history" class="management-history"></div></article>
                    <article class="management-panel"><header><h2>Desen bazlı anlık görünüm</h2><small>Canlı verideki hız ve randıman ortalaması</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Desen</th><th>Tezgâh</th><th>Ort. hız</th><th>Ort. randıman</th></tr></thead><tbody id="management-patterns"></tbody></table></div></article>
                </section>
                <section class="management-grid management-grid-two">
                    <article class="management-panel"><header><h2>Anlık çevrimdışılık</h2><small>Son verisi 60 saniyeyi geçen veya erişilemeyen tezgâhlar</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Son veri</th><th>Durum</th></tr></thead><tbody id="management-offline"></tbody></table></div></article>
                    <article class="management-panel"><header><h2>Anlık üretim hedefi</h2><small>Makineden gelen hedef ve gerçekleşen üretim karşılaştırması</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Gerçekleşen</th><th>Hedef</th><th>Oran</th></tr></thead><tbody id="management-goals"></tbody></table></div></article>
                </section>
                <p class="management-note"><i class="fa fa-info-circle"></i> Operatör bazlı rapor için sistemde operatör giriş/çıkış veya tezgâh atama kaydı bulunması gerekir. Bu ekran mevcut makine ve vardiya verilerini kullanır.</p>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/management-reports.css?v=20260923-1">
    <link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1">
    <script src="js/management-reports.js?v=20260923-2"></script>
    <script src="js/report-table-sort.js?v=20260923-2"></script>
</body>
</html>

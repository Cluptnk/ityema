<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<?php
$looms = $db->exec("SELECT IdTelaio, NomeTelaio FROM telaio WHERE EnableTelaio = 1 ORDER BY IdTelaio");
$today = date('Y-m-d');
?>
<body class="meterage-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>
        <div id="page-wrapper" class="meterage-wrapper">
            <main class="meterage-shell">
                <header class="meterage-heading"><span>ÜRETİM RAPORLARI</span><h1>Metraj Analizi</h1><p>Seçilen tarih aralığında gün gün dokunan toplam metreyi inceleyin.</p></header>
                <section class="meterage-controls" aria-label="Metraj raporu filtreleri">
                    <label>Başlangıç<input id="meterage-from" type="date" value="<?php echo $today; ?>"></label>
                    <label>Bitiş<input id="meterage-to" type="date" value="<?php echo $today; ?>"></label>
                    <label>Rapor tezgâhı<select id="meterage-loom"><option value="0">Tüm tezgâhlar</option><?php foreach ($looms as $loom) { ?><option value="<?php echo (int) $loom['IdTelaio']; ?>"><?php echo (int) $loom['IdTelaio'] . ' ' . htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></label>
                    <label class="meterage-exclude">Rapor dışı tezgâhlar<input id="meterage-exclude" type="search" placeholder="Örn. 25, 26, 6" autocomplete="off"><small>Yazılan tezgâhlar metraj hesabına dahil edilmez.</small></label>
                    <button id="meterage-run" type="button"><i class="fa fa-bar-chart"></i> Metrajı getir</button>
                </section>
                <section class="meterage-kpis" aria-live="polite">
                    <article><span>Toplam metraj</span><strong id="meterage-total">—</strong></article>
                    <article><span>Günlük ortalama</span><strong id="meterage-average">—</strong></article>
                    <article><span>Üretim görülen tezgâh</span><strong id="meterage-looms">—</strong></article>
                    <article><span>Rapor günü</span><strong id="meterage-days">—</strong></article>
                </section>
                <section class="meterage-panel">
                    <header><h2>Günlük metraj</h2><small id="meterage-period">Seçilen tarih aralığı</small></header>
                    <div class="meterage-table-scroll"><table data-report-table><thead><tr><th>Tarih</th><th>Dokunan metraj</th><th>Üretim görülen tezgâh</th></tr></thead><tbody id="meterage-daily"></tbody></table></div>
                </section>
                <section class="meterage-panel meterage-chart-panel">
                    <header><h2>Günlük metraj grafiği</h2><small>Her çubuk bir günün toplam dokuma metresini gösterir.</small></header>
                    <div id="meterage-chart" class="meterage-chart"></div>
                </section>
                <p class="meterage-note"><i class="fa fa-info-circle"></i> Metraj, makinenin <em>LunghezzaDaInizio</em> üretim sayacındaki günlük farktan metreye çevrilerek hesaplanır.</p>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/meterage-report.css?v=20260923-1">
    <link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1">
    <script src="js/meterage-report.js?v=20260923-1"></script>
    <script src="js/report-table-sort.js?v=20260923-2"></script>
</body>
</html>

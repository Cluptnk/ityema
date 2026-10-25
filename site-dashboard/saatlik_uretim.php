<!DOCTYPE html>
<html>
<?php include('head.php'); require_once __DIR__.'/report_engine.php'; ?>
<?php
$looms = $db->exec("SELECT IdTelaio, NomeTelaio FROM telaio WHERE EnableTelaio = 1 AND IdTelaio<>100 ORDER BY IdTelaio");
$today = date('Y-m-d');
?>
<body class="hourly-production-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>
        <div id="page-wrapper" class="hourly-production-wrapper">
            <main class="hourly-production-shell">
                <header class="hourly-production-heading"><span>ÜRETİM RAPORLARI</span><h1>Saatlik Üretim</h1><p>Seçtiğiniz günün üretimini saat saat inceleyin; yoğun ve düşük üretim saatlerini kolayca görün.</p></header>
                <section class="hourly-production-controls" aria-label="Saatlik üretim raporu filtreleri">
                    <label>Tarih<input id="hourly-production-date" type="date" value="<?php echo $today; ?>"></label>
                    <label>Rapor tezgâhı<select id="hourly-production-loom"><option value="0">Tüm tezgâhlar</option><?php foreach ($looms as $loom) { ?><option value="<?php echo (int) $loom['IdTelaio']; ?>"><?php echo (int) $loom['IdTelaio'] . ' ' . htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></label>
                    <label class="hourly-production-exclude">Rapor dışı tezgâhlar<input id="hourly-production-exclude" type="search" placeholder="Örn. 25, 26, 6" autocomplete="off"><small>Yazılan tezgâhlar üretim hesabına dahil edilmez.</small></label>
                    <button id="hourly-production-run" type="button"><i class="fa fa-bar-chart"></i> Raporu getir</button>
                </section>
                <section class="hourly-production-kpis" aria-live="polite">
                    <article><span>Kayıtlı metraj</span><strong id="hourly-production-total">—</strong></article>
                    <article><span>En yüksek saat</span><strong id="hourly-production-peak">—</strong></article>
                    <article><span>Saatlik ortalama</span><strong id="hourly-production-average">—</strong></article>
                    <article><span>Üretim görülen tezgâh</span><strong id="hourly-production-looms">—</strong></article>
                </section>
                <section class="hourly-production-panel">
                    <header><h2>Saatlik metraj</h2><small id="hourly-production-period">Seçilen gün</small></header>
                    <div class="hourly-production-table-scroll"><table data-report-table><thead><tr><th>Saat aralığı</th><th>Dokunan metraj</th><th>Üretim görülen tezgâh</th></tr></thead><tbody id="hourly-production-rows"></tbody></table></div>
                </section>
                <section class="hourly-production-panel hourly-production-chart-panel">
                    <header><h2>Saatlik üretim grafiği</h2><small>Her çubuk, ilgili saat içinde dokunan toplam metreyi gösterir.</small></header>
                    <div id="hourly-production-chart" class="hourly-production-chart"></div>
                </section>
                <p class="hourly-production-note"><i class="fa fa-info-circle"></i> Kayıtlı olay metrajları saatlere süre oranıyla dağıtılır. Saatlerin ham toplamı günlük kayıtlı metrajla aynıdır; boş kayıtlar sıfır üretim sayılmaz.</p>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/hourly-production-report.css?v=20260923-1">
    <link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1">
    <script src="js/report-quality.js?v=20261022-6"></script><script src="js/hourly-production-report.js?v=20261022-6"></script>
    <script src="js/report-table-sort.js?v=20260923-2"></script>
</body>
</html>

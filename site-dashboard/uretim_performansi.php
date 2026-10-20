<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<?php
$today = date('Y-m-d');
$hour = (int) date('G');
$defaultWindow = $hour >= 7 && $hour < 15 ? 's1' : ($hour >= 15 && $hour < 23 ? 's2' : 's3');
?>
<body class="reports-page performance-page">
<div id="wrapper">
    <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation"><?php include('header.php'); ?><?php include('sidebar.php'); ?></nav>
    <div id="page-wrapper" class="reports-wrapper"><main class="reports-shell">
        <header class="reports-heading"><div><span>ÜRETİM RAPORLARI</span><h1>Üretim Performansı</h1></div></header>
        <section class="report-controls performance-controls">
            <label>Tarih<input id="performance-date" type="date" value="<?php echo $today; ?>"></label>
            <label>Vardiya / dönem<select id="performance-window"><option value="s1" <?php echo $defaultWindow === 's1' ? 'selected' : ''; ?>>07:00–15:00</option><option value="s2" <?php echo $defaultWindow === 's2' ? 'selected' : ''; ?>>15:00–23:00</option><option value="s3" <?php echo $defaultWindow === 's3' ? 'selected' : ''; ?>>23:00–07:00</option><option value="day">24 saat (00:00–24:00)</option></select></label>
            <label class="report-loom-search">Tezgâh ara<input id="performance-looms" type="search" placeholder="Örn. 13, 14, 15" autocomplete="off"><small>Boş bırakılırsa tüm tezgâhlar gelir.</small></label>
            <button id="performance-run" type="button"><i class="fa fa-line-chart"></i> Performansı getir</button>
        </section>
        <section class="report-summary performance-summary" aria-live="polite">
            <article><span>Üretim uzunluğu</span><strong id="performance-length">—</strong></article>
            <article><span>Ortalama hız</span><strong id="performance-speed">—</strong></article>
            <article><span>Randıman</span><strong id="performance-efficiency">—</strong></article>
            <article><span>Duruş süresi</span><strong id="performance-stop-duration">—</strong></article>
        </section>
        <section class="report-table-panel performance-table"><header><h2>Tezgâh bazında performans <small id="performance-period"></small></h2></header><div class="report-table-scroll"><table data-report-table><thead><tr><th>ID</th><th>Tezgâh</th><th>Üretim</th><th>Ortalama hız</th><th>Randıman</th><th>Duruş</th><th>Duruş süresi</th><th>Atkı</th></tr></thead><tbody id="performance-results"></tbody></table></div></section>
        <p class="performance-note"><i class="fa fa-info-circle"></i> Hız ve randıman, seçilen dönem içindeki olay kayıtlarından hesaplanır; duruş adedi ve süresi vardiya özetinden alınır.</p>
    </main></div>
</div>
<?php include('footer.php'); ?>
<link rel="stylesheet" href="css/reports.css?v=20260922-5"><link rel="stylesheet" href="css/performance.css?v=20260922-1"><link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1"><script src="js/performance.js?v=20260922-1"></script><script src="js/report-table-sort.js?v=20260923-2"></script>
</body></html>

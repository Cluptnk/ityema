<!DOCTYPE html>
<html>
<?php include('head.php'); require_once __DIR__.'/report_engine.php'; ?>
<?php
$looms = $db->exec("SELECT IdTelaio, NomeTelaio FROM telaio WHERE EnableTelaio = 1 AND IdTelaio<>100 ORDER BY IdTelaio");
$today = date('Y-m-d'); if ((int)date('G')<7) $today=date('Y-m-d',strtotime('-1 day'));
if (empty($_SESSION['maintenanceCsrf'])) $_SESSION['maintenanceCsrf'] = function_exists('random_bytes') ? bin2hex(random_bytes(24)) : sha1(uniqid('', true));
?>
<body class="maintenance-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>
        <div id="page-wrapper" class="maintenance-wrapper">
            <main class="maintenance-shell">
                <header class="maintenance-heading"><span>ÜRETİM VE BAKIM</span><h1>Bakım Takibi</h1><p>Tekrarlayan duruşları önceliğe göre takip edin; işlemi atayın ve çözüldüğünde kayıt altına alın.</p></header>
                <section class="maintenance-controls" aria-label="Bakım takip filtreleri">
                    <label>Tarih<input id="maintenance-date" type="date" value="<?php echo $today; ?>"></label>
                    <label>Vardiya / dönem<select id="maintenance-window"><option value="all">Tüm vardiyalar (07:00–ertesi gün 07:00)</option><option value="s1">07:00–15:00 vardiyası</option><option value="s2">15:00–23:00 vardiyası</option><option value="s3">23:00–07:00 vardiyası</option><option value="day">24 saat (00:00–24:00)</option></select></label>
                    <label>Tezgâh<select id="maintenance-loom"><option value="0">Tüm tezgâhlar</option><?php foreach ($looms as $loom) { ?><option value="<?php echo (int) $loom['IdTelaio']; ?>"><?php echo (int) $loom['IdTelaio'] . ' ' . htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></label>
                    <button id="maintenance-run" type="button"><i class="fa fa-refresh"></i> Listeyi yenile</button>
                </section>
                <section class="maintenance-kpis" aria-live="polite">
                    <article class="maintenance-kpi-critical"><span>Kritik öncelikli kayıt</span><strong id="maintenance-critical">—</strong><small>Uzun veya sık tekrarlayan duruş</small></article>
                    <article class="maintenance-kpi-high"><span>Yüksek öncelikli kayıt</span><strong id="maintenance-high">—</strong><small>İzlenmesi gereken duruş</small></article>
                    <article><span>Açık işlem</span><strong id="maintenance-open">—</strong><small>Henüz işleme alınmamış</small></article>
                    <article><span>İşlemde</span><strong id="maintenance-progress">—</strong><small>Bakım ekibinde</small></article>
                </section>
                <section class="maintenance-panel maintenance-guide"><i class="fa fa-info-circle"></i><div><b>Öncelik kuralı:</b> 30 dk ve üzeri veya 10+ tekrar <em>kritik</em>; 10 dk ve üzeri veya 4+ tekrar <em>yüksek</em> kabul edilir. Diğer kayıtlar normal önceliktedir.</div></section>
                <section class="maintenance-panel">
                    <header><h2>Bakım adayları</h2><small>Her satır, seçilen dönem içindeki bir tezgâh ve duruş nedeninin toplamıdır.</small></header>
                    <div class="maintenance-table-scroll"><table data-report-table><thead><tr><th>Öncelik</th><th>Tezgâh</th><th>Duruş nedeni</th><th>Adet</th><th>Toplam süre</th><th>İşlem durumu</th><th>Not</th><th>İşlem</th></tr></thead><tbody id="maintenance-candidates"></tbody></table></div>
                </section>
                <section class="maintenance-panel">
                    <header><h2>Açık bakım işlemleri</h2><small>Seçilen tarihten bağımsız olarak henüz çözülmemiş kayıtlar burada kalır.</small></header>
                    <div class="maintenance-table-scroll"><table data-report-table><thead><tr><th>Öncelik</th><th>Tezgâh</th><th>Duruş nedeni</th><th>Oluşturma</th><th>Sorumlu</th><th>Not</th><th>Durum</th><th>İşlem</th></tr></thead><tbody id="maintenance-open-items"></tbody></table></div>
                </section>
                <p id="maintenance-message" class="maintenance-message" aria-live="polite"></p>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <script>window.maintenanceCsrf=<?php echo json_encode($_SESSION['maintenanceCsrf']); ?>;</script>
    <link rel="stylesheet" href="css/maintenance.css?v=20260923-1">
    <link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1">
    <script src="js/report-quality.js?v=20261022-6"></script><script src="js/maintenance.js?v=20261022-6"></script>
    <script src="js/report-table-sort.js?v=20260923-2"></script>
</body>
</html>

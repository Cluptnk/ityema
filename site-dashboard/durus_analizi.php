<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<?php
$looms = $db->exec("SELECT IdTelaio, NomeTelaio FROM telaio WHERE EnableTelaio = 1 ORDER BY IdTelaio");
$today = date('Y-m-d');
?>
<body class="reports-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>
        <div id="page-wrapper" class="reports-wrapper">
            <main class="reports-shell">
                <header class="reports-heading">
                    <div>
                        <span>ÜRETİM RAPORLARI</span>
                        <h1>Duruş Analizi</h1>
                    </div>
                    <div class="report-export-actions report-export-top" aria-label="Rapor dışa aktarma">
                        <button id="report-excel" type="button" disabled><i class="fa fa-file-excel-o"></i> Excel</button>
                        <button id="report-pdf" type="button" disabled><i class="fa fa-file-pdf-o"></i> PDF</button>
                    </div>
                </header>
                <section class="report-controls" aria-label="Rapor filtreleri">
                    <label>Başlangıç<input id="report-from" type="date" value="<?php echo $today; ?>"></label>
                    <label>Bitiş<input id="report-to" type="date" value="<?php echo $today; ?>"></label>
                    <label>Vardiya / dönem
                        <select id="report-window">
                            <option value="s1">07:00–15:00 vardiyası</option>
                            <option value="s2">15:00–23:00 vardiyası</option>
                            <option value="s3">23:00–07:00 vardiyası</option>
                            <option value="day" selected>24 saat (00:00–24:00)</option>
                        </select>
                    </label>
                    <label class="report-loom-select">Tezgâh
                        <select id="report-loom">
                            <option value="0">Tüm tezgâhlar</option>
                            <?php foreach ($looms as $loom) { ?>
                                <option value="<?php echo (int) $loom['IdTelaio']; ?>"><?php echo (int) $loom['IdTelaio'] . ' ' . htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <button id="report-run" type="button"><i class="fa fa-bar-chart"></i> Raporu getir</button>
                    <label class="report-loom-search">Tezgâh ara
                        <span class="report-loom-search-field">
                            <input id="report-loom-search" type="search" placeholder="Örn. 13, 14, 15" autocomplete="off">
                            <button id="report-loom-mode" type="button" aria-pressed="true" title="Yazılan tezgâhlar rapordan çıkarılır"><i class="fa fa-minus-circle"></i><span>Hariç tut</span></button>
                        </span>
                        <small id="report-loom-filter-help">Yazılan ID'leri virgülle ayırın; bu tezgâhlar rapordan çıkarılır.</small>
                    </label>
                </section>
                <section class="report-summary" aria-live="polite">
                    <article><span>Toplam duruş kaydı</span><strong id="report-total-count">—</strong></article>
                    <article><span>Toplam duruş süresi</span><strong id="report-total-duration">—</strong></article>
                    <article><span>Farklı duruş nedeni</span><strong id="report-cause-count">—</strong></article>
                </section>
                <section class="report-content">
                    <article class="report-chart-panel">
                        <h2>Duruş süresi dağılımı</h2>
                        <div class="report-chart-layout">
                            <div id="report-pie" class="report-pie"><span>Veri<br>bekleniyor</span></div>
                            <ul id="report-legend" class="report-legend"></ul>
                        </div>
                    </article>
                    <article class="report-table-panel">
                        <h2>Nedene göre özet <small>Satıra tıklayarak filtreleyin; tekrar tıklayarak kaldırın.</small></h2>
                        <div class="report-table-scroll">
                            <table data-report-table><thead><tr><th>Duruş nedeni</th><th>Adet</th><th>Toplam süre</th></tr></thead><tbody id="report-causes"></tbody></table>
                        </div>
                    </article>
                </section>
                <section class="report-table-panel report-detail-panel">
                    <h2>Tezgâh ve neden detayı</h2>
                    <div class="report-table-scroll">
                        <table data-report-table><thead><tr><th>Tezgâh</th><th>Duruş nedeni</th><th>Adet</th><th>Toplam süre</th></tr></thead><tbody id="report-details"></tbody></table>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/reports.css?v=20260922-6">
    <link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1">
    <script src="js/reports.js?v=20260922-10"></script>
    <script src="js/report-table-sort.js?v=20260923-2"></script>
</body>
</html>

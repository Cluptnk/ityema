<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<body class="backup-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>
        <div id="page-wrapper" class="backup-wrapper">
            <main class="backup-shell">
                <header class="backup-heading"><span>SİSTEM GÜVENLİĞİ</span><h1>Veri Güvenliği</h1><p>Son yedek kaydı, dosyaların varlığı, boyutu ve güncelliği birlikte kontrol edilir.</p></header>
                <section id="backup-status" class="backup-status" aria-live="polite"><i class="fa fa-spinner fa-spin"></i> Yedekleme durumu okunuyor…</section>
                <section class="backup-info-grid">
                    <article><i class="fa fa-database"></i><h2>MySQL tam yedeği</h2><p>Tüm tablolar; duruş kayıtları, rapor özetleri ve bakım işlemleri dahil tek dosyada yedeklenir.</p></article>
                    <article><i class="fa fa-code"></i><h2>Site yedeği</h2><p>Geliştirme sitesinin tamamı ayrı bir ZIP dosyası olarak yedeklenir.</p></article>
                    <article><i class="fa fa-calendar"></i><h2>Otomatik çalışma</h2><p>Planlanan saat 02:15, saklama süresi 30 gündür. Bu ekran zamanlanmış görevin etkin olduğunu veya yedeğin geri yüklenebildiğini doğrulamaz.</p></article>
                </section>
                <section class="backup-panel"><h2>Yedek konumu</h2><code>C:\ILCA\Backups</code><p>Geri dönüş gerektiğinde bu klasördeki aynı tarihli <b>database</b> ve <b>site</b> dosyaları birlikte kullanılır. Bu kontrol dosya varlığını ve boyutunu doğrular; başarılı geri yükleme testi yerine geçmez.</p></section>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/backup.css?v=20260923-1">
    <script src="js/backup-status.js?v=20260923-1"></script>
</body>
</html>

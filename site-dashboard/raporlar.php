<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<body class="reports-hub-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>
        <div id="page-wrapper" class="reports-hub-wrapper">
            <main class="reports-hub-shell">
                <header class="reports-hub-heading">
                    <span>ÜRETİM RAPORLARI</span>
                    <h1>Raporlar</h1>
                    <p>İhtiyacınız olan raporu seçerek ayrıntılı analiz ekranına geçin.</p>
                </header>
                <section class="report-card-grid" aria-label="Rapor listesi">
                    <a class="report-launch-card" href="durus_analizi.php">
                        <span class="report-launch-icon"><i class="fa fa-pie-chart"></i></span>
                        <span class="report-launch-content">
                            <strong>Duruş Analizi</strong>
                            <small>Tezgâh, duruş nedeni, adet ve süre ayrıntıları</small>
                        </span>
                        <span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span>
                    </a>
                    <a class="report-launch-card" href="uretim_performansi.php">
                        <span class="report-launch-icon"><i class="fa fa-line-chart"></i></span>
                        <span class="report-launch-content">
                            <strong>Üretim Performansı</strong>
                            <small>Vardiya üretimi, ortalama hız, randıman ve duruş özeti</small>
                        </span>
                        <span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span>
                    </a>
                    <a class="report-launch-card" href="yonetim_analizi.php">
                        <span class="report-launch-icon"><i class="fa fa-dashboard"></i></span>
                        <span class="report-launch-content">
                            <strong>Yönetim Analizi</strong>
                            <small>Vardiya, Pareto, problemli tezgâh, desen, hedef ve çevrimdışılık özeti</small>
                        </span>
                        <span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span>
                    </a>
                    <a class="report-launch-card" href="metraj_analizi.php">
                        <span class="report-launch-icon"><i class="fa fa-area-chart"></i></span>
                        <span class="report-launch-content">
                            <strong>Metraj Analizi</strong>
                            <small>Tarih aralığına göre gün gün dokunan toplam metraj ve grafik</small>
                        </span>
                        <span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span>
                    </a>
                    <a class="report-launch-card" href="saatlik_uretim.php">
                        <span class="report-launch-icon"><i class="fa fa-clock-o"></i></span>
                        <span class="report-launch-content">
                            <strong>Saatlik Üretim</strong>
                            <small>Seçilen gün için saat saat dokunan metraj, en verimli saat ve üretim grafiği</small>
                        </span>
                        <span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span>
                    </a>
                    <a class="report-launch-card" href="bakim_takibi.php">
                        <span class="report-launch-icon"><i class="fa fa-wrench"></i></span>
                        <span class="report-launch-content">
                            <strong>Bakım Takibi</strong>
                            <small>Tekrarlayan duruşları önceliklendirin, işlemi takip edin ve çözüme alın</small>
                        </span>
                        <span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span>
                    </a>
                    <a class="report-launch-card" href="verim_kaybi.php"><span class="report-launch-icon"><i class="fa fa-bolt"></i></span><span class="report-launch-content"><strong>Verim Kaybı</strong><small>Duruşlardan kaynaklanan kayıp çalışma zamanını inceleyin</small></span><span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span></a>
                    <a class="report-launch-card" href="desen_degisim_analizi.php"><span class="report-launch-icon"><i class="fa fa-exchange"></i></span><span class="report-launch-content"><strong>Desen Değişim Analizi</strong><small>Tezgâhlarda görülen desen değişimlerini takip edin</small></span><span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span></a>
                    <a class="report-launch-card" href="kapasite_planlama.php"><span class="report-launch-icon"><i class="fa fa-calendar-check-o"></i></span><span class="report-launch-content"><strong>Kapasite Planlama</strong><small>Sipariş metrajı için günlük kapasite, gerekli gün ve tahmini bitiş tarihi</small></span><span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span></a>
                    <a class="report-launch-card" href="veri_guvenligi.php">
                        <span class="report-launch-icon"><i class="fa fa-shield"></i></span>
                        <span class="report-launch-content">
                            <strong>Veri Güvenliği</strong>
                            <small>Günlük veritabanı ve site yedeğinin son durumunu takip edin</small>
                        </span>
                        <span class="report-launch-arrow"><i class="fa fa-arrow-right"></i></span>
                    </a>
                </section>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/reports-hub.css?v=20260922-2">
</body>
</html>

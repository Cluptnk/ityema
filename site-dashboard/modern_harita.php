<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<?php
// Only views that have been assigned to the signed-in user are shown here.
$viewRows = $db->exec(
    "SELECT DISTINCT vista.Nome AS nome, vista.Immagine AS immagine, vista.IdVista AS idvista
     FROM vista
     INNER JOIN utenti_in_vista ON vista.IdVista = utenti_in_vista.IdVista
     WHERE utenti_in_vista.IdUtente = ?
     ORDER BY vista.Nome",
    array(1 => $_SESSION['login_username'])
);
$requestedView = filter_input(INPUT_GET, 'idVista', FILTER_VALIDATE_INT);
$selectedView = $requestedView !== false && $requestedView !== null
    ? $requestedView
    : (isset($_SESSION['savedVista']) ? (int) $_SESSION['savedVista'] : -1);
$viewAllowed = false;
foreach ($viewRows as $viewRow) {
    if ((int) $viewRow['idvista'] === $selectedView) {
        $viewAllowed = true;
        break;
    }
}
if (!$viewAllowed && count($viewRows) > 0) {
    $selectedView = (int) $viewRows[0]['idvista'];
}
?>
<body class="modern-map-page">
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php'); ?>
            <?php include('sidebar.php'); ?>
        </nav>

        <div id="page-wrapper" class="modern-map-wrapper">
            <main class="modern-map-shell" aria-live="polite">
                <section class="modern-map-toolbar">
                    <div class="modern-map-filterbar" aria-label="Tezgâh durum filtresi">
                        <button class="modern-filter is-active" type="button" data-filter="all">Tümü</button>
                        <button class="modern-filter" type="button" data-filter="running"><i class="fa fa-play"></i> Çalışıyor</button>
                        <button class="modern-filter" type="button" data-filter="manual"><i class="fa fa-hand-paper-o"></i> Manuel</button>
                        <button class="modern-filter" type="button" data-filter="cone"><i class="fa fa-flag"></i> Koni</button>
                        <button class="modern-filter" type="button" data-filter="weft"><i class="fa fa-bars"></i> Atkı</button>
                        <button class="modern-filter" type="button" data-filter="warp"><i class="fa fa-columns"></i> Çözgü</button>
                        <button class="modern-filter" type="button" data-filter="offline"><i class="fa fa-power-off"></i> Çevrimdışı</button>
                    </div>
                    <div class="modern-map-actions">
                        <label class="sr-only" for="modern-view-select">Görünüm seç</label>
                        <select id="modern-view-select" class="modern-view-select">
                            <?php foreach ($viewRows as $viewRow) { ?>
                                <option value="<?php echo (int) $viewRow['idvista']; ?>"
                                    data-image="<?php echo htmlspecialchars($viewRow['immagine'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo (int) $viewRow['idvista'] === $selectedView ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($viewRow['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <label class="sr-only" for="modern-shift-select">Vardiya seç</label>
                        <select id="modern-shift-select" class="modern-view-select modern-shift-select" title="Kartlardaki duruş özetini seçin">
                            <option value="auto">Aktif vardiya</option>
                            <option value="s1">07:00–15:00</option>
                            <option value="s2">15:00–23:00</option>
                            <option value="s3">23:00–07:00</option>
                            <option value="day">24 saat (00:00–24:00)</option>
                        </select>
                        <button id="modern-fullscreen" class="modern-icon-button" type="button" title="Haritayı tam ekranda aç">
                            <i class="fa fa-expand" aria-hidden="true"></i><span>Tam ekran</span>
                        </button>
                        <button id="modern-map-reset" class="modern-icon-button" type="button" title="Haritayı başlangıç konumuna döndür">
                            <i class="fa fa-home" aria-hidden="true"></i><span>Haritayı ortala</span>
                        </button>
                    </div>
                </section>

                <section id="modern-map-viewport" class="modern-map-viewport" aria-label="Tezgâh yerleşim haritası">
                    <div id="modern-map-canvas" class="modern-map-canvas">
                        <div id="modern-map-empty" class="modern-map-empty">Canlı tezgâh verisi yükleniyor…</div>
                    </div>
                </section>
                <p class="modern-map-help"><i class="fa fa-info-circle"></i> Masaüstünde haritayı fareyle sürükleyin; tekerlek ile yakınlaştırın. Telefonda kartlar okunabilir liste olarak gösterilir.</p>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <link rel="stylesheet" href="css/modern-map.css?v=20260922-17">
    <script>
        window.MODERN_MAP_INITIAL_VIEW = <?php echo (int) $selectedView; ?>;
    </script>
    <script src="js/modern-map.js?v=20260922-22"></script>
</body>
</html>

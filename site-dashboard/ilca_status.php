<!DOCTYPE html>
<html>
<?php include('head.php'); ?>
<?php
function loom_status_label($loom) {
    $age = isset($loom['SecondsSinceUpdate']) ? (int) $loom['SecondsSinceUpdate'] : null;
    return (int) $loom['Online'] === 1 && $age !== null && $age <= 60 ? array('Canlı', 'status-live') : array('Çevrimdışı', 'status-offline');
}
function loom_age_text($seconds) {
    if ($seconds === null) return 'Veri yok';
    $seconds = max(0, (int) $seconds);
    if ($seconds < 60) return $seconds . ' sn önce';
    if ($seconds < 3600) return floor($seconds / 60) . ' dk önce';
    return floor($seconds / 3600) . ' sa önce';
}
function loom_duration_text($seconds) {
    $seconds = max(0, (int) $seconds); $minutes = floor($seconds / 60); $rest = $seconds % 60;
    return $minutes > 0 ? $minutes . ' dk' . ($rest > 0 ? ' ' . $rest . ' sn' : '') : $rest . ' sn';
}
function loom_weft_density($value) {
    if ($value === null || $value === '') return '—';
    $density = ((int) $value) & 32767;
    if ($density < 7000) return (string) ($density / 10);
    if ($density < 17000) return (string) (($density - 7000) / 100);
    return (string) (($density - 10000) / 10);
}
function loom_map_state($loom) {
    if ((int) $loom['Online'] !== 1) return array('Çevrimdışı', 'offline');
    $code = ((int) $loom['ProductionStatus']) % 256;
    if ($code === 0) return array('Çalışıyor', 'running');
    if ($code === 1 || $code === 15 || $code === 16) return array('Manuel duruş', 'manual');
    if ($code === 17 || $code === 20) return array('Koni değişimi', 'cone');
    if ($code === 18) return array('Atkı uyarısı', 'weft');
    if ($code === 19) return array('Çözgü uyarısı', 'warp');
    return array('Duruş', 'stopped');
}

// Bu sayfa kartlarla aynı vardiya özetini doğrudan bağlar. JSONmsg ve ham
// evento çağrısı yapmadığı için tezgâh listesi harita ekranını yavaşlatmaz.
$now = new DateTime(); $today = new DateTime($now->format('Y-m-d') . ' 00:00:00'); $hour = (int) $now->format('G');
if ($hour >= 7 && $hour < 15) { $windowCode = 's1'; $windowLabel = '07:00–15:00 vardiyası'; }
elseif ($hour >= 15 && $hour < 23) { $windowCode = 's2'; $windowLabel = '15:00–23:00 vardiyası'; }
else { $windowCode = 's3'; $windowLabel = '23:00–07:00 vardiyası'; if ($hour < 23) $today->modify('-1 day'); }
$looms = $db->exec(
    "SELECT t.IdTelaio, t.NomeTelaio, t.Online, r.Timestamp AS LastUpdate,
            r.Velocita, r.Resa, r.DensitaTrama, r.Disegno, r.StatoProduzione AS ProductionStatus,
            CASE WHEN r.Timestamp IS NULL THEN NULL ELSE TIMESTAMPDIFF(SECOND, r.Timestamp, NOW()) END AS SecondsSinceUpdate,
            COALESCE(s.StopCount, 0) AS StopCount, COALESCE(s.StopSeconds, 0) AS StopSeconds
     FROM telaio AS t
     LEFT JOIN dato_rt AS r ON r.IdTelaio = t.IdTelaio
     LEFT JOIN loom_stop_window_summary AS s ON s.LoomId = t.IdTelaio AND s.SummaryDate = ? AND s.WindowCode = ?
     WHERE t.EnableTelaio = 1 AND t.IdTelaio<>100 ORDER BY t.IdTelaio",
    array(1 => $today->format('Y-m-d'), 2 => $windowCode)
);
$live = 0; $offline = 0;
foreach ($looms as $loom) {
    $age = isset($loom['SecondsSinceUpdate']) ? (int) $loom['SecondsSinceUpdate'] : null;
    if ((int) $loom['Online'] === 1 && $age !== null && $age <= 60) $live++; else $offline++;
}
?>
<body class="ilca-status-page"><div id="wrapper">
    <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation"><?php include('header.php'); ?><?php include('sidebar.php'); ?></nav>
    <div id="page-wrapper" class="ilca-status-wrapper"><main class="container-fluid">
        <header class="ilca-status-heading"><div><span>CANLI ÜRETİM</span><h1><i class="fa fa-heartbeat"></i> Tezgâh Veri Takibi</h1></div><small><i class="fa fa-refresh"></i> 30 saniyede bir yenilenir</small></header>
        <section class="row ilca-summary" aria-label="Bağlantı özeti">
            <div class="col-md-4 col-sm-6"><article class="ilca-metric-card metric-total"><span>Toplam tezgâh</span><strong><?php echo count($looms); ?></strong></article></div>
            <div class="col-md-4 col-sm-6"><article class="ilca-metric-card metric-live"><span>Canlı</span><strong><?php echo $live; ?></strong></article></div>
            <div class="col-md-4 col-sm-6"><article class="ilca-metric-card metric-offline"><span>Çevrimdışı / veri yok</span><strong><?php echo $offline; ?></strong></article></div>
        </section>
        <section class="ilca-table-panel"><header><h2><i class="fa fa-list"></i> Tezgâh durumu ve canlı bilgiler</h2><small><?php echo htmlspecialchars($windowLabel, ENT_QUOTES, 'UTF-8'); ?> duruş özeti</small></header><div class="table-responsive">
            <table id="ilca-status-table" class="table table-hover"><thead><tr><th>ID</th><th>Tezgâh</th><th>Bağlantı</th><th>Harita durumu</th><th>Son veri</th><th>Son güncelleme</th><th>T. Hızı</th><th>Randıman</th><th>Atkı Sıklığı</th><th>Desen</th><th>Vardiya duruş</th><th>Toplam süre</th></tr></thead><tbody>
            <?php foreach ($looms as $loom) { list($connectionLabel, $connectionClass) = loom_status_label($loom); list($mapLabel, $mapClass) = loom_map_state($loom); $efficiency = $loom['Resa'] === null ? '—' : number_format(((float) $loom['Resa']) / 100, 2, '.', '') . ' %'; ?>
                <tr data-loom-id="<?php echo (int) $loom['IdTelaio']; ?>"><td><?php echo (int) $loom['IdTelaio']; ?></td><td class="ilca-loom-name"><?php echo htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="status-badge <?php echo $connectionClass; ?>"><?php echo $connectionLabel; ?></span></td><td><span class="map-status-badge map-status-<?php echo $mapClass; ?>"><?php echo $mapLabel; ?></span></td>
                    <td><?php echo loom_age_text($loom['SecondsSinceUpdate']); ?></td><td><?php echo $loom['LastUpdate'] ? htmlspecialchars($loom['LastUpdate'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                    <td><?php echo $loom['Velocita'] === null ? '—' : htmlspecialchars($loom['Velocita'], ENT_QUOTES, 'UTF-8') . ' RPM'; ?></td><td><?php echo $efficiency; ?></td>
                    <td><?php echo loom_weft_density($loom['DensitaTrama']); ?><?php echo $loom['DensitaTrama'] === null ? '' : ' a./cm'; ?></td><td><?php echo $loom['Disegno'] === null || $loom['Disegno'] === '' ? '—' : htmlspecialchars($loom['Disegno'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int) $loom['StopCount']; ?> kez</td><td><?php echo loom_duration_text($loom['StopSeconds']); ?></td></tr>
            <?php } ?>
            </tbody></table>
        </div></section>
    </main></div>
</div><?php include('footer.php'); ?><link rel="stylesheet" href="css/ilca-status.css?v=20260922-4"><script src="js/ilca-status.js?v=20260922-4"></script><script>window.setTimeout(function () { window.location.reload(); }, 30000);</script></body>
</html>

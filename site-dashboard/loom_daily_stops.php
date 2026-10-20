<?php
// Harita kartları için hızlı vardiya/24 saat özeti. Ham evento taraması yoktur.
if (session_status() === PHP_SESSION_NONE) { session_name('ITEMA'); session_start(); }
if (!isset($_SESSION['name'])) {
    http_response_code(401); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Oturum gerekli.')); exit;
}
ob_start(); include('head.php'); ob_end_clean();

$requested = filter_input(INPUT_GET, 'window');
$requested = in_array($requested, array('auto', 's1', 's2', 's3', 'day'), true) ? $requested : 'auto';
$now = new DateTime();
$today = new DateTime($now->format('Y-m-d') . ' 00:00:00');
$hour = (int) $now->format('G');
if ($requested === 'auto') {
    if ($hour >= 7 && $hour < 15) $requested = 's1';
    elseif ($hour >= 15 && $hour < 23) $requested = 's2';
    elseif ($hour >= 23) $requested = 's3';
    else $requested = 's3';
}
$summaryDate = clone $today;
if ($requested === 's3' && $hour < 23) $summaryDate->modify('-1 day');
$labels = array('s1' => '07:00–15:00 vardiyası', 's2' => '15:00–23:00 vardiyası', 's3' => '23:00–07:00 vardiyası', 'day' => '00:00–24:00');

$rows = $db->exec(
    "SELECT LoomId, StopCount, StopSeconds, UpdatedAt
     FROM loom_stop_window_summary
     WHERE SummaryDate = ? AND WindowCode = ?",
    array(1 => $summaryDate->format('Y-m-d'), 2 => $requested)
);
$stops = array(); $updatedAt = null;
foreach ($rows as $row) {
    $stops[(string) $row['LoomId']] = array('count' => (int) $row['StopCount'], 'seconds' => (int) $row['StopSeconds']);
    if ($updatedAt === null || $row['UpdatedAt'] > $updatedAt) $updatedAt = $row['UpdatedAt'];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'window' => $requested, 'summaryDate' => $summaryDate->format('Y-m-d'),
    'label' => $labels[$requested], 'generatedAt' => $updatedAt, 'stops' => $stops
), JSON_UNESCAPED_UNICODE);

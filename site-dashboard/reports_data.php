<?php
// Duruş raporu yalnızca toplayıcının hazırladığı özet tablolardan okunur.
// Böylece rapor ekranı milyonlarca ham evento satırını taramaz.
if (session_status() === PHP_SESSION_NONE) {
    session_name('ITEMA');
    session_start();
}
if (!isset($_SESSION['name'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Oturum gerekli.'));
    exit;
}

ob_start();
include('head.php');
ob_end_clean();

function reportDate($value, $fallback)
{
    $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
    $errors = DateTime::getLastErrors();
    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return new DateTime($fallback);
    }
    return $date;
}

function reportExec($db, $sql, $values)
{
    $parameters = array();
    foreach (array_values($values) as $index => $value) {
        $parameters[$index + 1] = $value;
    }
    return $db->exec($sql, $parameters);
}

$from = reportDate(filter_input(INPUT_GET, 'from'), 'today');
$to = reportDate(filter_input(INPUT_GET, 'to'), 'today');
if ($to < $from) {
    $to = clone $from;
}
$loomId = filter_input(INPUT_GET, 'loom', FILTER_VALIDATE_INT);
$loomId = $loomId !== false && $loomId !== null && $loomId > 0 ? $loomId : 0;
$searchedLoomIds = array();
$loomList = filter_input(INPUT_GET, 'looms');
if ($loomList !== null && $loomList !== false) {
    foreach (preg_split('/[^0-9]+/', (string) $loomList) as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $searchedLoomIds[$id] = $id;
        }
    }
}
$loomMode = filter_input(INPUT_GET, 'loomMode') === 'exclude' ? 'exclude' : 'include';
$loomIds = $loomMode === 'include' ? $searchedLoomIds : array();
if ($loomMode === 'include' && $loomId > 0) $loomIds[$loomId] = $loomId;
$causeCode = filter_input(INPUT_GET, 'cause', FILTER_VALIDATE_INT);
$causeCode = $causeCode !== false && $causeCode !== null && $causeCode > 0 && $causeCode <= 20 ? $causeCode : 0;
$windowCode = filter_input(INPUT_GET, 'window');
$windowCode = in_array($windowCode, array('s1', 's2', 's3', 'day'), true) ? $windowCode : 'day';
$windowLabels = array('s1' => '07:00–15:00 vardiyası', 's2' => '15:00–23:00 vardiyası', 's3' => '23:00–07:00 vardiyası', 'day' => '24 saat (00:00–24:00)');
$fromValue = $from->format('Y-m-d');
$toValue = $to->format('Y-m-d');

// Seçim kutusu her zaman tüm tezgâhları, seçili tarihteki duruş süresine göre
// büyükten küçüğe gösterir. Diğer filtreler bu listeyi daraltmaz.
$loomRankingRows = reportExec($db,
    "SELECT t.IdTelaio AS loomId, t.NomeTelaio AS loomName,
            COALESCE(SUM(s.StopCount), 0) AS stopCount,
            COALESCE(SUM(s.StopSeconds), 0) AS stopSeconds
     FROM telaio AS t
     LEFT JOIN loom_stop_window_summary AS s
       ON s.LoomId = t.IdTelaio
      AND s.WindowCode = ? AND s.SummaryDate >= ? AND s.SummaryDate <= ?
     WHERE t.EnableTelaio = 1
     GROUP BY t.IdTelaio, t.NomeTelaio
     ORDER BY stopSeconds DESC, stopCount DESC, t.IdTelaio ASC",
    array($windowCode, $fromValue, $toValue)
);
$loomRanking = array();
$loomNames = array();
foreach ($loomRankingRows as $row) {
    $id = (int) $row['loomId'];
    $loomNames[$id] = $row['loomName'];
    $loomRanking[] = array('loomId' => $id, 'loomName' => $row['loomName'], 'count' => (int) $row['stopCount'], 'seconds' => (int) $row['stopSeconds']);
}

$loomFilterSql = '';
$loomFilterValues = array();
if ($loomMode === 'exclude') {
    // Seçim kutusundan tek tezgâh seçilirse bu ana kapsam olur; arama
    // alanındaki ID'ler ise bu kapsamdan (veya tüm makinelerden) çıkarılır.
    if ($loomId > 0) {
        $loomFilterSql .= ' AND c.LoomId = ?';
        $loomFilterValues[] = $loomId;
    }
    if (count($searchedLoomIds) > 0) {
        $loomFilterSql .= ' AND c.LoomId NOT IN (' . implode(',', array_fill(0, count($searchedLoomIds), '?')) . ')';
        $loomFilterValues = array_merge($loomFilterValues, array_values($searchedLoomIds));
    }
} elseif (count($loomIds) > 0) {
    $loomFilterSql = ' AND c.LoomId IN (' . implode(',', array_fill(0, count($loomIds), '?')) . ')';
    $loomFilterValues = array_values($loomIds);
}

// Nedene göre özet, seçili tezgâh(lar) için alınır. Duruş nedeni seçimi bu
// listeyi etkilemez; kullanıcı filtreyi tekrar tıklayarak kaldırabilir.
$causeRows = reportExec($db,
    "SELECT c.LoomId AS loomId, c.StatusCode AS statusCode,
            COALESCE(NULLIF(MAX(l.Frase), ''), CONCAT('Duruş kodu ', c.StatusCode)) AS cause,
            COALESCE(SUM(c.StopCount), 0) AS stopCount,
            COALESCE(SUM(c.StopSeconds), 0) AS stopSeconds
     FROM loom_stop_window_cause_summary AS c
     INNER JOIN telaio AS t ON t.IdTelaio = c.LoomId AND t.EnableTelaio = 1
     LEFT JOIN stato_produzione AS sp ON sp.IdStatoProduzione = c.StatusCode
     LEFT JOIN lingua AS l ON l.CodePhrase = sp.CodePhrase AND l.IdLingua = ?
     WHERE c.WindowCode = ? AND c.SummaryDate >= ? AND c.SummaryDate <= ?" . $loomFilterSql . "
     GROUP BY c.LoomId, c.StatusCode
     ORDER BY stopSeconds DESC, stopCount DESC, c.StatusCode ASC",
    array_merge(array($_SESSION['lang'], $windowCode, $fromValue, $toValue), $loomFilterValues)
);

$causeRanking = array();
$causes = array();
$details = array();
$summaryCount = 0;
$summarySeconds = 0;
foreach ($causeRows as $row) {
    $statusCode = (int) $row['statusCode'];
    $rowLoomId = (int) $row['loomId'];
    $count = (int) $row['stopCount'];
    $seconds = (int) $row['stopSeconds'];
    $cause = $row['cause'];
    if (!isset($causeRanking[$statusCode])) {
        $causeRanking[$statusCode] = array('code' => $statusCode, 'cause' => $cause, 'count' => 0, 'seconds' => 0);
    }
    $causeRanking[$statusCode]['count'] += $count;
    $causeRanking[$statusCode]['seconds'] += $seconds;
    if ($causeCode > 0 && $statusCode !== $causeCode) {
        continue;
    }
    $summaryCount += $count;
    $summarySeconds += $seconds;
    if (!isset($causes[$cause])) {
        $causes[$cause] = array('cause' => $cause, 'count' => 0, 'seconds' => 0);
    }
    $causes[$cause]['count'] += $count;
    $causes[$cause]['seconds'] += $seconds;
    $detailKey = $rowLoomId . '|' . $statusCode;
    if (!isset($details[$detailKey])) {
        $details[$detailKey] = array('loomId' => $rowLoomId, 'loomName' => isset($loomNames[$rowLoomId]) ? $loomNames[$rowLoomId] : ('Tezgâh ' . $rowLoomId), 'cause' => $cause, 'count' => 0, 'seconds' => 0);
    }
    $details[$detailKey]['count'] += $count;
    $details[$detailKey]['seconds'] += $seconds;
}

$causeRanking = array_values($causeRanking);
usort($causeRanking, function ($left, $right) {
    if ($left['seconds'] !== $right['seconds']) return $left['seconds'] < $right['seconds'] ? 1 : -1;
    if ($left['count'] !== $right['count']) return $left['count'] < $right['count'] ? 1 : -1;
    return $left['code'] < $right['code'] ? -1 : 1;
});
$causeList = array_values($causes);
usort($causeList, function ($left, $right) {
    if ($left['seconds'] !== $right['seconds']) return $left['seconds'] < $right['seconds'] ? 1 : -1;
    return strcmp($left['cause'], $right['cause']);
});
$details = array_values($details);
usort($details, function ($left, $right) {
    if ($left['seconds'] !== $right['seconds']) return $left['seconds'] < $right['seconds'] ? 1 : -1;
    return $left['loomId'] < $right['loomId'] ? -1 : 1;
});

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'from' => $fromValue,
    'to' => $toValue,
    'window' => $windowCode,
    'windowLabel' => $windowLabels[$windowCode],
    'loomMode' => $loomMode,
    'loomIds' => array_values($loomIds),
    'excludedLoomIds' => $loomMode === 'exclude' ? array_values($searchedLoomIds) : array(),
    'summary' => array('count' => $summaryCount, 'seconds' => $summarySeconds, 'causeCount' => count($causeList)),
    'causes' => $causeList,
    'details' => $details,
    'loomRanking' => $loomRanking,
    'causeRanking' => $causeRanking
), JSON_UNESCAPED_UNICODE);

<?php
// Yönetim Analizi yalnız hızlı özet tabloları ve güncel dato_rt verisini kullanır.
if (session_status() === PHP_SESSION_NONE) { session_name('ITEMA'); session_start(); }
if (!isset($_SESSION['name'])) { http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('error' => 'Oturum gerekli.')); exit; }
ob_start(); include('head.php'); ob_end_clean();

function managementDate($value)
{
    $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
    return $date ? $date : new DateTime('today');
}
function managementExec($db, $sql, $values)
{
    $parameters = array();
    foreach (array_values($values) as $index => $value) $parameters[$index + 1] = $value;
    return $db->exec($sql, $parameters);
}
function managementDuration($seconds)
{
    $seconds = (int) $seconds;
    $hours = floor($seconds / 3600); $minutes = floor(($seconds % 3600) / 60); $rest = $seconds % 60;
    return ($hours ? $hours . ' sa ' : '') . ($minutes ? $minutes . ' dk ' : '') . ($rest || (!$hours && !$minutes) ? $rest . ' sn' : '');
}
function managementCategory($cause)
{
    $text = function_exists('mb_strtolower') ? mb_strtolower((string) $cause, 'UTF-8') : strtolower((string) $cause);
    if (strpos($text, 'koni') !== false || strpos($text, 'rocca') !== false) return 'Koni';
    if (strpos($text, 'çözg') !== false || strpos($text, 'ordito') !== false) return 'Çözgü';
    if (strpos($text, 'atk') !== false || strpos($text, 'rapier') !== false || strpos($text, 'trama') !== false) return 'Atkı';
    if (strpos($text, 'elle') !== false || strpos($text, 'manuel') !== false) return 'Manuel';
    return 'Diğer';
}

$date = managementDate(filter_input(INPUT_GET, 'date'));
$window = filter_input(INPUT_GET, 'window');
$window = in_array($window, array('all', 's1', 's2', 's3', 'day'), true) ? $window : 'all';
$loomId = filter_input(INPUT_GET, 'loom', FILTER_VALIDATE_INT);
$loomId = $loomId !== false && $loomId !== null && $loomId > 0 ? $loomId : 0;
$excludedLoomIds = array();
$excludeList = filter_input(INPUT_GET, 'exclude');
if ($excludeList !== null && $excludeList !== false) {
    foreach (preg_split('/[^0-9]+/', (string) $excludeList) as $excludedId) {
        $excludedId = (int) $excludedId;
        if ($excludedId > 0) $excludedLoomIds[$excludedId] = $excludedId;
    }
}
$dateValue = $date->format('Y-m-d');
$windowCodes = $window === 'all' ? array('s1', 's2', 's3') : array($window);
$windowPlaceholders = implode(',', array_fill(0, count($windowCodes), '?'));
$windowLabels = array('s1' => '07:00–15:00', 's2' => '15:00–23:00', 's3' => '23:00–07:00', 'day' => '24 saat');
$excludePlaceholders = count($excludedLoomIds) ? implode(',', array_fill(0, count($excludedLoomIds), '?')) : '';
$summaryLoomSql = $loomId > 0 ? ' AND s.LoomId=?' : '';
$causeLoomSql = $loomId > 0 ? ' AND c.LoomId=?' : '';
$machineLoomSql = $loomId > 0 ? ' AND t.IdTelaio=?' : '';
$summaryLoomValues = $loomId > 0 ? array($loomId) : array();
$causeLoomValues = $loomId > 0 ? array($loomId) : array();
$machineLoomValues = $loomId > 0 ? array($loomId) : array();
if ($excludePlaceholders) {
    $summaryLoomSql .= ' AND s.LoomId NOT IN (' . $excludePlaceholders . ')';
    $causeLoomSql .= ' AND c.LoomId NOT IN (' . $excludePlaceholders . ')';
    $machineLoomSql .= ' AND t.IdTelaio NOT IN (' . $excludePlaceholders . ')';
    $summaryLoomValues = array_merge($summaryLoomValues, array_values($excludedLoomIds));
    $causeLoomValues = array_merge($causeLoomValues, array_values($excludedLoomIds));
    $machineLoomValues = array_merge($machineLoomValues, array_values($excludedLoomIds));
}

$shiftRows = managementExec($db,
    "SELECT s.WindowCode AS windowCode, COALESCE(SUM(s.StopCount),0) AS stopCount, COALESCE(SUM(s.StopSeconds),0) AS stopSeconds, COUNT(DISTINCT s.LoomId) AS loomCount
     FROM loom_stop_window_summary s INNER JOIN telaio t ON t.IdTelaio=s.LoomId AND t.EnableTelaio=1
     WHERE s.SummaryDate=? AND s.WindowCode IN (" . $windowPlaceholders . ")" . $summaryLoomSql . " GROUP BY s.WindowCode",
    array_merge(array($dateValue), $windowCodes, $summaryLoomValues));
$shifts = array(); foreach ($shiftRows as $row) $shifts[$row['windowCode']] = array('code' => $row['windowCode'], 'label' => $windowLabels[$row['windowCode']], 'count' => (int) $row['stopCount'], 'seconds' => (int) $row['stopSeconds'], 'looms' => (int) $row['loomCount']);
$shiftResult = array(); foreach ($windowCodes as $code) $shiftResult[] = isset($shifts[$code]) ? $shifts[$code] : array('code' => $code, 'label' => $windowLabels[$code], 'count' => 0, 'seconds' => 0, 'looms' => 0);

$problemRows = managementExec($db,
    "SELECT t.IdTelaio AS loomId,t.NomeTelaio AS loomName,COALESCE(SUM(s.StopCount),0) AS stopCount,COALESCE(SUM(s.StopSeconds),0) AS stopSeconds
     FROM loom_stop_window_summary s INNER JOIN telaio t ON t.IdTelaio=s.LoomId AND t.EnableTelaio=1
     WHERE s.SummaryDate=? AND s.WindowCode IN (" . $windowPlaceholders . ")" . $summaryLoomSql . "
     GROUP BY t.IdTelaio,t.NomeTelaio ORDER BY stopSeconds DESC,stopCount DESC,t.IdTelaio ASC",
    array_merge(array($dateValue), $windowCodes, $summaryLoomValues));
$problems = array(); foreach ($problemRows as $row) $problems[] = array('loomId'=>(int)$row['loomId'], 'loomName'=>$row['loomName'], 'count'=>(int)$row['stopCount'], 'seconds'=>(int)$row['stopSeconds']);

$causeRows = managementExec($db,
    "SELECT c.StatusCode AS statusCode,COALESCE(NULLIF(MAX(l.Frase),''),CONCAT('Duruş kodu ',c.StatusCode)) AS cause,COALESCE(SUM(c.StopCount),0) AS stopCount,COALESCE(SUM(c.StopSeconds),0) AS stopSeconds
     FROM loom_stop_window_cause_summary c INNER JOIN telaio t ON t.IdTelaio=c.LoomId AND t.EnableTelaio=1
     LEFT JOIN stato_produzione sp ON sp.IdStatoProduzione=c.StatusCode LEFT JOIN lingua l ON l.CodePhrase=sp.CodePhrase AND l.IdLingua=?
     WHERE c.SummaryDate=? AND c.WindowCode IN (" . $windowPlaceholders . ")" . $causeLoomSql . "
     GROUP BY c.StatusCode ORDER BY stopSeconds DESC,stopCount DESC,c.StatusCode ASC",
    array_merge(array($_SESSION['lang'], $dateValue), $windowCodes, $causeLoomValues));
$causes = array(); $categoryMap = array();
foreach ($causeRows as $row) {
    $item = array('code'=>(int)$row['statusCode'], 'cause'=>$row['cause'], 'count'=>(int)$row['stopCount'], 'seconds'=>(int)$row['stopSeconds']); $causes[] = $item;
    $category = managementCategory($item['cause']); if (!isset($categoryMap[$category])) $categoryMap[$category] = array('category'=>$category, 'count'=>0, 'seconds'=>0);
    $categoryMap[$category]['count'] += $item['count']; $categoryMap[$category]['seconds'] += $item['seconds'];
}
$categories = array_values($categoryMap); usort($categories, function($left,$right) { return $left['seconds'] === $right['seconds'] ? 0 : ($left['seconds'] < $right['seconds'] ? 1 : -1); });

$history = array(); $historyName = '';
if ($loomId > 0 && !isset($excludedLoomIds[$loomId])) {
    $loomRows = managementExec($db, "SELECT NomeTelaio FROM telaio WHERE IdTelaio=?", array($loomId));
    if (count($loomRows)) $historyName = $loomId . ' ' . $loomRows[0]['NomeTelaio'];
    $historyStart = clone $date; $historyStart->modify('-13 days');
    $historyRows = managementExec($db,
        "SELECT SummaryDate AS summaryDate,COALESCE(SUM(StopCount),0) AS stopCount,COALESCE(SUM(StopSeconds),0) AS stopSeconds
         FROM loom_stop_window_summary WHERE LoomId=? AND SummaryDate>=? AND SummaryDate<=? AND WindowCode IN ('s1','s2','s3')
         GROUP BY SummaryDate ORDER BY SummaryDate ASC", array($loomId, $historyStart->format('Y-m-d'), $dateValue));
    foreach ($historyRows as $row) $history[] = array('date'=>$row['summaryDate'], 'count'=>(int)$row['stopCount'], 'seconds'=>(int)$row['stopSeconds']);
}

$offlineRows = managementExec($db,
    "SELECT t.IdTelaio AS loomId,t.NomeTelaio AS loomName,t.Online AS online,r.Timestamp AS lastUpdate,
            CASE WHEN r.Timestamp IS NULL THEN NULL ELSE TIMESTAMPDIFF(SECOND,r.Timestamp,NOW()) END AS ageSeconds
     FROM telaio t LEFT JOIN dato_rt r ON r.IdTelaio=t.IdTelaio WHERE t.EnableTelaio=1" . $machineLoomSql . " AND (t.Online<>1 OR r.Timestamp IS NULL OR TIMESTAMPDIFF(SECOND,r.Timestamp,NOW())>60)
     ORDER BY ageSeconds DESC,t.IdTelaio ASC", $machineLoomValues);
$offline = array(); foreach ($offlineRows as $row) $offline[] = array('loomId'=>(int)$row['loomId'], 'loomName'=>$row['loomName'], 'lastUpdate'=>$row['lastUpdate'], 'ageSeconds'=>$row['ageSeconds'] === null ? null : (int)$row['ageSeconds']);

$goalRows = managementExec($db,
    "SELECT t.IdTelaio AS loomId,t.NomeTelaio AS loomName,r.ProduzioneEffettuata AS actualValue,r.ProduzioneDesiderata AS targetValue
     FROM dato_rt r INNER JOIN telaio t ON t.IdTelaio=r.IdTelaio AND t.EnableTelaio=1 WHERE COALESCE(r.ProduzioneDesiderata,0)>0" . $machineLoomSql . "
     ORDER BY (COALESCE(r.ProduzioneEffettuata,0)/NULLIF(r.ProduzioneDesiderata,0)) ASC,t.IdTelaio ASC", $machineLoomValues);
$goals = array(); foreach ($goalRows as $row) { $target=(float)$row['targetValue']; $actual=(float)$row['actualValue']; $goals[] = array('loomId'=>(int)$row['loomId'], 'loomName'=>$row['loomName'], 'actual'=>$actual, 'target'=>$target, 'percent'=>$target>0 ? round($actual*100/$target,1) : 0); }

$patternRows = managementExec($db,
    "SELECT COALESCE(NULLIF(r.Armatura1,''),'Tanımsız') AS patternName,COUNT(*) AS loomCount,ROUND(AVG(r.Velocita),1) AS averageSpeed,ROUND(AVG(r.Resa)/100,2) AS averageEfficiency
     FROM dato_rt r INNER JOIN telaio t ON t.IdTelaio=r.IdTelaio AND t.EnableTelaio=1 WHERE 1=1" . $machineLoomSql . " GROUP BY COALESCE(NULLIF(r.Armatura1,''),'Tanımsız')
     ORDER BY loomCount DESC,averageEfficiency DESC", $machineLoomValues);
$patterns = array(); foreach ($patternRows as $row) $patterns[] = array('pattern'=>$row['patternName'], 'looms'=>(int)$row['loomCount'], 'speed'=>(float)$row['averageSpeed'], 'efficiency'=>(float)$row['averageEfficiency']);

$totalCount=0; $totalSeconds=0; foreach ($shiftResult as $item) { $totalCount += $item['count']; $totalSeconds += $item['seconds']; }
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('date'=>$dateValue,'window'=>$window,'loomId'=>$loomId,'excludedLoomIds'=>array_values($excludedLoomIds),'summary'=>array('count'=>$totalCount,'seconds'=>$totalSeconds,'offlineCount'=>count($offline),'goalCount'=>count($goals)),'shifts'=>$shiftResult,'problems'=>$problems,'causes'=>$causes,'categories'=>$categories,'history'=>array('loomName'=>$historyName,'rows'=>$history),'offline'=>$offline,'goals'=>$goals,'patterns'=>$patterns), JSON_UNESCAPED_UNICODE);

<?php
// Günlük metraj, makinenin olay kayıtlarındaki üretim sayacı farkından alınır.
if (session_status() === PHP_SESSION_NONE) { session_name('ITEMA'); session_start(); }
if (!isset($_SESSION['name'])) { http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('error'=>'Oturum gerekli.')); exit; }
ob_start(); include('head.php'); ob_end_clean();

function meterageDate($value)
{
    $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
    return $date ? $date : new DateTime('today');
}
function meterageExec($db, $sql, $values)
{
    $parameters = array(); foreach (array_values($values) as $index => $value) $parameters[$index + 1] = $value;
    return $db->exec($sql, $parameters);
}

$from = meterageDate(filter_input(INPUT_GET, 'from'));
$to = meterageDate(filter_input(INPUT_GET, 'to'));
if ($to < $from) $to = clone $from;
$loomId = filter_input(INPUT_GET, 'loom', FILTER_VALIDATE_INT);
$loomId = $loomId !== false && $loomId !== null && $loomId > 0 ? $loomId : 0;
$excludedLoomIds = array(); $excludeList = filter_input(INPUT_GET, 'exclude');
if ($excludeList !== null && $excludeList !== false) foreach (preg_split('/[^0-9]+/', (string) $excludeList) as $item) { $item=(int)$item; if ($item>0) $excludedLoomIds[$item]=$item; }
$filterSql = ''; $filterValues = array();
if ($loomId > 0) { $filterSql .= ' AND ev.IdTelaio=?'; $filterValues[] = $loomId; }
if (count($excludedLoomIds)) { $filterSql .= ' AND ev.IdTelaio NOT IN (' . implode(',', array_fill(0, count($excludedLoomIds), '?')) . ')'; $filterValues = array_merge($filterValues, array_values($excludedLoomIds)); }
$startValue = $from->format('Y-m-d 00:00:00'); $end = clone $to; $end->modify('+1 day'); $endValue = $end->format('Y-m-d 00:00:00');

$rows = meterageExec($db,
    "SELECT DATE(ev.Timestamp) AS reportDate,ev.IdTelaio AS loomId,
            GREATEST(MAX(ev.LunghezzaDaInizio)-MIN(ev.LunghezzaDaInizio),0)/1000 AS meters
     FROM evento ev FORCE INDEX (index_evento_Timestamp)
     WHERE ev.Timestamp>=? AND ev.Timestamp<?" . $filterSql . "
     GROUP BY DATE(ev.Timestamp),ev.IdTelaio ORDER BY reportDate ASC,ev.IdTelaio ASC",
    array_merge(array($startValue, $endValue), $filterValues));

$dailyMap = array();
foreach ($rows as $row) {
    $day = $row['reportDate']; if (!isset($dailyMap[$day])) $dailyMap[$day] = array('date'=>$day,'meters'=>0,'loomIds'=>array());
    $meters = (float)$row['meters']; $dailyMap[$day]['meters'] += $meters;
    if ($meters > 0) $dailyMap[$day]['loomIds'][(int)$row['loomId']] = true;
}
$daily = array(); $cursor = clone $from; $totalMeters = 0; $productionLooms = array();
while ($cursor <= $to) {
    $day = $cursor->format('Y-m-d'); $item = isset($dailyMap[$day]) ? $dailyMap[$day] : array('date'=>$day,'meters'=>0,'loomIds'=>array());
    $item['meters'] = round($item['meters'], 2); $item['loomCount'] = count($item['loomIds']); unset($item['loomIds']);
    $daily[] = $item; $totalMeters += $item['meters']; foreach (isset($dailyMap[$day]) ? $dailyMap[$day]['loomIds'] : array() as $id => $value) $productionLooms[$id] = true;
    $cursor->modify('+1 day');
}
$dayCount = count($daily); $totalMeters = round($totalMeters, 2);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d'),'loomId'=>$loomId,'excludedLoomIds'=>array_values($excludedLoomIds),'daily'=>$daily,'summary'=>array('meters'=>$totalMeters,'average'=>$dayCount ? round($totalMeters/$dayCount,2) : 0,'loomCount'=>count($productionLooms),'dayCount'=>$dayCount)), JSON_UNESCAPED_UNICODE);

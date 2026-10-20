<?php
// Saatlik üretim, olay kayıtlarındaki sayaç farkının tezgâh ve saat bazındaki toplamıdır.
if (session_status() === PHP_SESSION_NONE) { session_name('ITEMA'); session_start(); }
if (!isset($_SESSION['name'])) { http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('error'=>'Oturum gerekli.')); exit; }
ob_start(); include('head.php'); ob_end_clean();

function hourlyProductionDate($value)
{
    $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
    return $date ? $date : new DateTime('today');
}
function hourlyProductionExec($db, $sql, $values)
{
    $parameters = array(); foreach (array_values($values) as $index => $value) $parameters[$index + 1] = $value;
    return $db->exec($sql, $parameters);
}

$date = hourlyProductionDate(filter_input(INPUT_GET, 'date'));
$loomId = filter_input(INPUT_GET, 'loom', FILTER_VALIDATE_INT);
$loomId = $loomId !== false && $loomId !== null && $loomId > 0 ? $loomId : 0;
$excludedLoomIds = array(); $excludeList = filter_input(INPUT_GET, 'exclude');
if ($excludeList !== null && $excludeList !== false) foreach (preg_split('/[^0-9]+/', (string) $excludeList) as $item) { $item=(int)$item; if ($item>0) $excludedLoomIds[$item]=$item; }
$filterSql = ''; $filterValues = array();
if ($loomId > 0) { $filterSql .= ' AND ev.IdTelaio=?'; $filterValues[] = $loomId; }
if (count($excludedLoomIds)) { $filterSql .= ' AND ev.IdTelaio NOT IN (' . implode(',', array_fill(0, count($excludedLoomIds), '?')) . ')'; $filterValues = array_merge($filterValues, array_values($excludedLoomIds)); }
$startValue = $date->format('Y-m-d 00:00:00'); $end = clone $date; $end->modify('+1 day'); $endValue = $end->format('Y-m-d 00:00:00');

$rows = hourlyProductionExec($db,
    "SELECT HOUR(ev.Timestamp) AS reportHour, ev.IdTelaio AS loomId,
            GREATEST(MAX(ev.LunghezzaDaInizio)-MIN(ev.LunghezzaDaInizio),0)/1000 AS meters
     FROM evento ev FORCE INDEX (index_evento_Timestamp)
     WHERE ev.Timestamp>=? AND ev.Timestamp<?" . $filterSql . "
     GROUP BY HOUR(ev.Timestamp), ev.IdTelaio ORDER BY reportHour ASC, ev.IdTelaio ASC",
    array_merge(array($startValue, $endValue), $filterValues));

$hours = array(); for ($hour=0; $hour<24; $hour++) $hours[$hour] = array('hour'=>$hour, 'meters'=>0, 'loomIds'=>array());
foreach ($rows as $row) {
    $hour=(int)$row['reportHour']; $meters=(float)$row['meters'];
    $hours[$hour]['meters'] += $meters;
    if ($meters > 0) $hours[$hour]['loomIds'][(int)$row['loomId']] = true;
}
$hourly=array(); $totalMeters=0; $productionLooms=array(); $peak=null;
foreach ($hours as $item) {
    $item['meters']=round($item['meters'],2); $item['loomCount']=count($item['loomIds']); unset($item['loomIds']);
    $hourly[]=$item; $totalMeters += $item['meters'];
    if ($peak === null || $item['meters'] > $peak['meters']) $peak=$item;
}
foreach ($rows as $row) if ((float)$row['meters']>0) $productionLooms[(int)$row['loomId']]=true;
$totalMeters=round($totalMeters,2);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('date'=>$date->format('Y-m-d'),'loomId'=>$loomId,'excludedLoomIds'=>array_values($excludedLoomIds),'hourly'=>$hourly,'summary'=>array('meters'=>$totalMeters,'average'=>round($totalMeters/24,2),'loomCount'=>count($productionLooms),'peakHour'=>$peak ? $peak['hour'] : null,'peakMeters'=>$peak ? $peak['meters'] : 0)), JSON_UNESCAPED_UNICODE);

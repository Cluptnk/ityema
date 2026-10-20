<?php
if (session_status() === PHP_SESSION_NONE) { session_name('ITEMA'); session_start(); }
if (!isset($_SESSION['name'])) { http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('error' => 'Oturum gerekli.')); exit; }
ob_start(); include('head.php'); ob_end_clean();

function performanceDate($value) { $date = DateTime::createFromFormat('!Y-m-d', (string) $value); return $date ? $date : new DateTime('today'); }
$date = performanceDate(filter_input(INPUT_GET, 'date'));
$window = filter_input(INPUT_GET, 'window'); $window = in_array($window, array('s1','s2','s3','day'), true) ? $window : 'day';
$labels = array('s1'=>'07:00–15:00 vardiyası','s2'=>'15:00–23:00 vardiyası','s3'=>'23:00–07:00 vardiyası','day'=>'24 saat (00:00–24:00)');
$start = clone $date;
if ($window === 's1') $start->setTime(7,0,0); elseif ($window === 's2') $start->setTime(15,0,0); elseif ($window === 's3') $start->setTime(23,0,0); else $start->setTime(0,0,0);
$end = clone $start; $end->modify($window === 's3' ? '+8 hours' : ($window === 'day' ? '+1 day' : '+8 hours'));
$loomIds = array(); $loomList = filter_input(INPUT_GET, 'looms');
foreach (preg_split('/[^0-9]+/', (string) $loomList) as $id) { $id = (int) $id; if ($id > 0) $loomIds[$id] = $id; }
$loomFilter = ''; $parameters = array(1=>$start->format('Y-m-d H:i:s'),2=>$end->format('Y-m-d H:i:s'),3=>$date->format('Y-m-d'),4=>$window);
if (count($loomIds)) { $loomFilter = ' AND t.IdTelaio IN (' . implode(',', array_fill(0,count($loomIds),'?')) . ')'; foreach(array_values($loomIds) as $id) $parameters[]=$id; }
$rows = $db->exec(
 "SELECT t.IdTelaio AS loomId,t.NomeTelaio AS loomName,
   COALESCE(e.lengthValue,0) AS lengthValue,COALESCE(e.picks,0) AS picks,
   COALESCE(e.totalSeconds,0) AS totalSeconds,COALESCE(e.weavingSeconds,0) AS weavingSeconds,
   COALESCE(s.StopCount,0) AS stopCount,COALESCE(s.StopSeconds,0) AS stopSeconds
 FROM telaio t
 LEFT JOIN (
   SELECT ev.IdTelaio,
     -- ILCA bu alanı olay bazında doldurmuyor. Gerçek üretim sayacı
     -- LunghezzaDaInizio içinde milimetre olarak tutulur; dönem farkını
     -- metreye çevirerek rapora aktarırız.
     GREATEST(MAX(ev.LunghezzaDaInizio)-MIN(ev.LunghezzaDaInizio), 0)/1000 AS lengthValue,
     SUM(ev.Battute) AS picks,
     SUM(ev.TempoEvento) AS totalSeconds,
     SUM(CASE WHEN MOD(ev.StatoProduzione,256)=0 THEN ev.TempoEvento ELSE 0 END) AS weavingSeconds
   FROM evento AS ev FORCE INDEX (index_evento_Timestamp)
   WHERE ev.Timestamp>=? AND ev.Timestamp<?
   GROUP BY ev.IdTelaio
 ) e ON e.IdTelaio=t.IdTelaio
 LEFT JOIN loom_stop_window_summary s ON s.LoomId=t.IdTelaio AND s.SummaryDate=? AND s.WindowCode=?
 WHERE t.EnableTelaio=1" . $loomFilter . " ORDER BY lengthValue DESC, weavingSeconds DESC, t.IdTelaio ASC", $parameters);
$result=array(); $length=0; $picks=0; $total=0; $weaving=0; $stopCount=0; $stopSeconds=0;
foreach($rows as $row){$item=array('loomId'=>(int)$row['loomId'],'loomName'=>$row['loomName'],'length'=>(float)$row['lengthValue'],'picks'=>(int)$row['picks'],'totalSeconds'=>(int)$row['totalSeconds'],'weavingSeconds'=>(int)$row['weavingSeconds'],'stopCount'=>(int)$row['stopCount'],'stopSeconds'=>(int)$row['stopSeconds']);$item['speed']=$item['weavingSeconds']>0?round($item['picks']*60/$item['weavingSeconds'],1):0;$item['efficiency']=$item['totalSeconds']>0?round($item['weavingSeconds']*100/$item['totalSeconds'],2):0;$result[]=$item;$length+=$item['length'];$picks+=$item['picks'];$total+=$item['totalSeconds'];$weaving+=$item['weavingSeconds'];$stopCount+=$item['stopCount'];$stopSeconds+=$item['stopSeconds'];}
header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('date'=>$date->format('Y-m-d'),'window'=>$window,'label'=>$labels[$window],'rows'=>$result,'summary'=>array('length'=>$length,'picks'=>$picks,'speed'=>$weaving>0?round($picks*60/$weaving,1):0,'efficiency'=>$total>0?round($weaving*100/$total,2):0,'stopCount'=>$stopCount,'stopSeconds'=>$stopSeconds)),JSON_UNESCAPED_UNICODE);

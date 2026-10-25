<!DOCTYPE html>
<html>
<?php include('head.php');
require_once __DIR__.'/report_engine.php';
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();set_time_limit(120);
$date=auditDate(filter_input(INPUT_GET,'date'));list($start,$end)=auditWindow($date,'day');
$report=auditMeasure(auditDataset($db,$start,$end),$start,$end);$summary=auditSummary($report);
$rows=array();$totalMeters=$summary['length'];$estimatedLostMeters=0.0;$lossReliable=false;$lossCount=0;$lossBase=0.0;
foreach($report['rows'] as $r){$lost=$r['lengthReliable']?($r['stopSeconds']==0?0:($r['weavingSeconds']>0?$r['length']*$r['stopSeconds']/$r['weavingSeconds']:null)):null;
if($lost!==null){$estimatedLostMeters+=$lost;$lossBase+=$r['length'];$lossCount++;$lossReliable=true;}
if($r['stopCount']||$r['stopSeconds'])$rows[]=array('loomId'=>$r['loomId'],'loomName'=>$r['loomName'],'stopCount'=>$r['stopCount'],'stopSeconds'=>$r['stopSeconds'],'lostMeters'=>$lost);}
if(!$lossReliable)$estimatedLostMeters=null;
$meterLossPercent=$lossReliable&&$lossBase+$estimatedLostMeters>0?round($estimatedLostMeters*100/($lossBase+$estimatedLostMeters),2):null;
function lossDuration($s){$s=(int)$s;return floor($s/3600).' sa '.floor(($s%3600)/60).' dk';}
?>
<body class="management-report-page"><div id="wrapper"><nav id="nave" class="navbar navbar-inverse navbar-fixed-top"><?php include('header.php');include('sidebar.php');?></nav><div id="page-wrapper" class="management-report-wrapper"><main class="management-report-shell">
<header class="management-report-heading"><div><span>ÜRETİM YÖNETİMİ</span><h1>Verim Kaybı Analizi</h1><p>Duruşlardan kaynaklanan kayıp çalışma zamanını gösterir.</p></div></header>
<form class="management-report-controls"><label>Tarih<input name="date" type="date" value="<?php echo $date->format('Y-m-d');?>"></label><button><i class="fa fa-refresh"></i> Analizi getir</button></form>
<?php echo auditQualityHtml($report['quality']);?><section class="management-kpis"><article><span>Etkilenen tezgâh</span><strong><?php echo count($rows);?></strong></article><article><span>Tahmini Kayıp Oranı</span><strong><?php echo auditNumber($meterLossPercent);?> %</strong><small><?php echo $lossCount;?> tezgâhın kayıtlı dokuma hızına göre</small></article><article><span>Kayıtlı duruşlardan tahmini kayıp</span><strong><?php echo auditNumber($estimatedLostMeters);?> m</strong></article><article><span>Kayıtlı gerçekleşen metraj</span><strong><?php echo auditNumber($totalMeters);?> m</strong></article></section>
<section class="management-panel"><header><h2>Tezgâh bazlı kayıp</h2><small>Metraj kaybı, doğrulanmış metrajın kayıtlı dokuma süresine oranı üzerinden tahmini hesaplanır.</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Duruş</th><th>Kayıp zaman</th><th>Tahmini metraj kaybı</th></tr></thead><tbody><?php foreach($rows as $row){$lost=$row['lostMeters'];?><tr><td><b><?php echo (int)$row['loomId'];?></b> <?php echo htmlspecialchars($row['loomName']);?></td><td><?php echo (int)$row['stopCount'];?></td><td><?php echo lossDuration($row['stopSeconds']);?></td><td><?php echo auditNumber($lost);?> m</td></tr><?php }?></tbody></table></div></section>
</main></div></div><?php include('footer.php');?><link rel="stylesheet" href="css/management-reports.css"><link rel="stylesheet" href="css/report-table-sort.css"><script src="js/report-table-sort.js"></script></body></html>

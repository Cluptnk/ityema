<!DOCTYPE html>
<html>
<?php include('head.php');
function patternReportDate($value){$date=DateTime::createFromFormat('!Y-m-d',(string)$value);return $date?$date:new DateTime('today');}
$date=patternReportDate(filter_input(INPUT_GET,'date'));$start=$date->format('Y-m-d 00:00:00');$endDate=clone $date;$endDate->modify('+1 day');$end=$endDate->format('Y-m-d 00:00:00');
// Aynı tezgâhtaki ardışık olayların Armatura1 değeri değiştiğinde gerçek bir desen değişimi satırı üretilir.
$sql="SELECT changed.loomId,changed.loomName,changed.previousPattern,changed.patternName,changed.changedAt FROM (
 SELECT ordered.loomId,ordered.loomName,ordered.patternName,ordered.changedAt,
 @previousPattern:=IF(@previousLoom=ordered.loomId,@lastPattern,NULL) AS previousPattern,
 @previousLoom:=ordered.loomId AS trackedLoom,@lastPattern:=ordered.patternName AS trackedPattern
 FROM (
   SELECT ev.IdTelaio AS loomId,t.NomeTelaio AS loomName,TRIM(ev.Armatura1) AS patternName,ev.Timestamp AS changedAt,ev.IdEvento AS eventId
   FROM evento ev JOIN telaio t ON t.IdTelaio=ev.IdTelaio AND t.EnableTelaio=1
   WHERE ev.Timestamp>=? AND ev.Timestamp<? AND NULLIF(TRIM(ev.Armatura1),'') IS NOT NULL
   ORDER BY ev.IdTelaio ASC,ev.Timestamp ASC,ev.IdEvento ASC
 ) ordered CROSS JOIN (SELECT @previousLoom:=NULL,@lastPattern:=NULL) variables
) changed WHERE changed.previousPattern IS NOT NULL AND changed.previousPattern<>changed.patternName ORDER BY changed.changedAt DESC";
$changes=$db->exec($sql,array(1=>$start,2=>$end));$countByLoom=array();foreach($changes as $change){$id=(int)$change['loomId'];$countByLoom[$id]=isset($countByLoom[$id])?$countByLoom[$id]+1:1;}
?>
<body class="management-report-page"><div id="wrapper"><nav id="nave" class="navbar navbar-inverse navbar-fixed-top"><?php include('header.php');include('sidebar.php');?></nav><div id="page-wrapper" class="management-report-wrapper"><main class="management-report-shell">
<header class="management-report-heading"><div><span>ÜRETİM YÖNETİMİ</span><h1>Desen Değişim Analizi</h1><p>Seçilen tarihteki gerçek desen geçişlerini izleyin.</p></div></header>
<form class="management-report-controls"><label>Tarih<input name="date" type="date" value="<?php echo $date->format('Y-m-d');?>"></label><button><i class="fa fa-refresh"></i> Raporu getir</button></form>
<section class="management-kpis"><article><span>Toplam desen değişimi</span><strong><?php echo count($changes);?></strong></article><article><span>Değişim olan tezgâh</span><strong><?php echo count($countByLoom);?></strong></article><article><span>En çok değişim</span><strong><?php echo $countByLoom?max($countByLoom):0;?></strong></article><article><span>Rapor tarihi</span><strong><?php echo $date->format('d.m.Y');?></strong></article></section>
<section class="management-panel"><header><h2>Desen değişim kayıtları</h2><small>Her satırda tezgâhın önceki deseni, yeni deseni ve değişimin alındığı zaman görünür.</small></header><div class="management-table-scroll"><table data-report-table><thead><tr><th>Tezgâh</th><th>Eski desen</th><th>Yeni desen</th><th>Değişim zamanı</th></tr></thead><tbody><?php foreach($changes as $change){?><tr><td><b><?php echo (int)$change['loomId'];?></b> <?php echo htmlspecialchars($change['loomName'],ENT_QUOTES,'UTF-8');?></td><td><?php echo htmlspecialchars($change['previousPattern'],ENT_QUOTES,'UTF-8');?></td><td><b><?php echo htmlspecialchars($change['patternName'],ENT_QUOTES,'UTF-8');?></b></td><td><?php echo htmlspecialchars($change['changedAt'],ENT_QUOTES,'UTF-8');?></td></tr><?php }if(!count($changes)){?><tr><td colspan="4" class="management-empty">Bu gün için desen değişimi bulunamadı.</td></tr><?php }?></tbody></table></div></section>
</main></div></div><?php include('footer.php');?><link rel="stylesheet" href="css/management-reports.css?v=20260923-1"><link rel="stylesheet" href="css/report-table-sort.css?v=20260923-1"><script src="js/report-table-sort.js?v=20260923-2"></script></body></html>

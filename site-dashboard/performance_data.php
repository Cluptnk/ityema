<?php
require __DIR__.'/report_api.php';
$date=auditDate(filter_input(INPUT_GET,'date'));
$window=filter_input(INPUT_GET,'window');$window=in_array($window,array('s1','s2','s3','day'),true)?$window:'day';
$labels=array('s1'=>'07:00–15:00 vardiyası','s2'=>'15:00–23:00 vardiyası','s3'=>'23:00–07:00 vardiyası','day'=>'24 saat (00:00–24:00)');
list($start,$end)=auditWindow($date,$window);
$report=auditMeasure(auditDataset($db,$start,$end,auditIds(filter_input(INPUT_GET,'looms'))),$start,$end);
$rows=array();foreach($report['rows'] as $r){$r['picks']=$r['hasRecords']?(int)round($r['picks']):null;$rows[]=$r;}
echo json_encode(array('date'=>$date->format('Y-m-d'),'window'=>$window,'label'=>$labels[$window],'rows'=>$rows,'summary'=>auditSummary($report),'quality'=>$report['quality']),JSON_UNESCAPED_UNICODE);
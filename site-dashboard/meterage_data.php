<?php
require __DIR__.'/report_api.php';
$from=auditDate(filter_input(INPUT_GET,'from'));$to=auditDate(filter_input(INPUT_GET,'to'));auditRange($from,$to);
$loomId=auditLoom(filter_input(INPUT_GET,'loom'));$excluded=auditIds(filter_input(INPUT_GET,'exclude'));

$validDays=0;$daily=array();$total=0.0;$reliable=false;$production=array();$cursor=clone $from;$qualities=array();$predecessors=null;
while($cursor<=$to){$next=clone $cursor;$next->modify('+1 day');unset($dataset);$dataset=auditDataset($db,$cursor,$next,$loomId?array($loomId):array(),$excluded,$predecessors);$predecessors=auditPredecessors($dataset);$report=auditMeasure($dataset,$cursor,$next);$s=auditSummary($report);$ids=array();foreach($report['rows'] as $r)if($r['length']!==null&&$r['length']>0){$ids[$r['loomId']]=true;$production[$r['loomId']]=true;}
$daily[]=array('date'=>$cursor->format('Y-m-d'),'meters'=>$s['length'],'loomCount'=>count($ids),'lengthReliable'=>$s['lengthReliable']);if($s['length']!==null)$validDays++;$total+=$s['length']===null?0:auditRawMeters($report);$reliable=$reliable||$s['lengthReliable'];$qualities[]=$report['quality'];$cursor=$next;}
$quality=auditMergeQuality($qualities);$days=count($daily);echo json_encode(array('from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d'),'loomId'=>$loomId,'excludedLoomIds'=>$excluded,'daily'=>$daily,'summary'=>array('meters'=>$reliable?round($total,2):null,'average'=>$reliable&&$validDays?round($total/$validDays,2):null,'loomCount'=>count($production),'dayCount'=>$days,'recordedDayCount'=>$validDays),'quality'=>$quality),JSON_UNESCAPED_UNICODE);
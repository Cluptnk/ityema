<?php
require __DIR__.'/report_api.php';
$from=auditDate(filter_input(INPUT_GET,'from'));$to=auditDate(filter_input(INPUT_GET,'to'));auditRange($from,$to);
$window=filter_input(INPUT_GET,'window');$window=in_array($window,array('s1','s2','s3','day'),true)?$window:'day';
$labels=array('s1'=>'07:00–15:00 vardiyası','s2'=>'15:00–23:00 vardiyası','s3'=>'23:00–07:00 vardiyası','day'=>'24 saat (00:00–24:00)');
$mode=filter_input(INPUT_GET,'loomMode')==='exclude'?'exclude':'include';$searched=auditIds(filter_input(INPUT_GET,'looms'));$loom=auditLoom(filter_input(INPUT_GET,'loom'));
$include=$mode==='include'?array_values(array_unique(array_merge($searched,$loom?array($loom):array()))):($loom?array($loom):array());$excluded=$mode==='exclude'?$searched:array();
$cause=max(0,(int)filter_input(INPUT_GET,'cause',FILTER_VALIDATE_INT));
$names=auditCauseNames($db);$causeRanking=array();$details=array();$ranking=array();$messages=array();$complete=true;$fleetComplete=true;$hasRecords=false;$predecessors=null;
for($cursor=clone $from;$cursor<=$to;$cursor->modify('+1 day')){list($a,$b)=auditWindow($cursor,$window);unset($dataset,$scope);$chunkEnd=clone $a;$chunkEnd->modify('+1 day');$dataset=auditDataset($db,$a,$chunkEnd,array(),array(),$predecessors);$predecessors=auditPredecessors($dataset);$scope=$dataset;$scope['machines']=array_values(array_filter($dataset['machines'],function($m)use($include,$excluded){$id=(int)$m['IdTelaio'];return (!$include||in_array($id,$include,true))&&!in_array($id,$excluded,true);}));$report=auditMeasure($dataset,$a,$b);$fleetComplete=$fleetComplete&&$report['quality']['complete'];$scopeReport=$scope['machines']===$dataset['machines']?$report:auditMeasure($scope,$a,$b);$hasRecords=$hasRecords||$scopeReport['quality']['hasRecords'];$messages=array_merge($messages,$scopeReport['quality']['messages']);$complete=$complete&&$scopeReport['quality']['complete'];
foreach($report['rows'] as $r){$id=$r['loomId'];if(!isset($ranking[$id]))$ranking[$id]=array('loomId'=>$id,'loomName'=>$r['loomName'],'count'=>0,'seconds'=>0);$ranking[$id]['count']+=$r['stopCount'];$ranking[$id]['seconds']+=$r['stopSeconds'];
if(($include&&!in_array($id,$include,true))||in_array($id,$excluded,true))continue;
foreach($r['causes'] as $code=>$value){$text=!empty($names[$code])?$names[$code]:'Duruş kodu '.$code;
if(!isset($causeRanking[$code]))$causeRanking[$code]=array('code'=>$code,'cause'=>$text,'count'=>0,'seconds'=>0);
$causeRanking[$code]['count']+=$value['count'];$causeRanking[$code]['seconds']+=$value['seconds'];
if($cause&&$cause!==$code)continue;$key=$id.':'.$code;if(!isset($details[$key]))$details[$key]=array('loomId'=>$id,'loomName'=>$r['loomName'],'cause'=>$text,'count'=>0,'seconds'=>0);
$details[$key]['count']+=$value['count'];$details[$key]['seconds']+=$value['seconds'];}}}
$causes=array();$count=0;$seconds=0;foreach($causeRanking as $code=>$r){if($cause&&$cause!==$code)continue;$causes[]=$r;$count+=$r['count'];$seconds+=$r['seconds'];}
if(!$hasRecords){$count=null;$seconds=null;}
$rank=array_values($ranking);$causeRank=array_values($causeRanking);$detailRows=array_values($details);
// An incomplete fleet must not produce a misleading worst-machine ranking.
if($fleetComplete)usort($rank,function($a,$b){return $b['seconds']-$a['seconds'];});
if($complete)usort($causeRank,function($a,$b){return $b['seconds']-$a['seconds'];});
echo json_encode(array('from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d'),'window'=>$window,'windowLabel'=>$labels[$window],'loomMode'=>$mode,'loomIds'=>$include,'excludedLoomIds'=>$excluded,'summary'=>array('count'=>$count,'seconds'=>$seconds,'causeCount'=>count($causes)),'causes'=>$causes,'details'=>$detailRows,'loomRanking'=>$rank,'causeRanking'=>$causeRank,'quality'=>array('complete'=>$complete,'messages'=>array_values(array_unique($messages)))),JSON_UNESCAPED_UNICODE);

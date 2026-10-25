<?php
require_once __DIR__.'/../site-dashboard/report_engine.php';
function assertQuality($test,$name){if(!$test)throw new RuntimeException($name);}
$day=auditDate('2026-10-22');$start=$day->getTimestamp();$end=clone $day;$end->modify('+1 hour');
assertQuality(auditDate('2026-11-01')->getOffset()===10800,'Legacy PHP must keep Turkey on UTC+3 after October.');
$base=array('IdEvento'=>1,'NumeroSerie'=>1,'NumeroPezza'=>1,'previousPiece'=>1,'previousLength'=>0,'LunghezzaDaInizio'=>1000,'eventTimestamp'=>$start,'TempoEvento'=>3600,'Battute'=>18000,'StatoProduzione'=>0,'IsShiftSameEvent'=>0,'stopStart'=>false,'sequenceGap'=>false);
$dataset=array('machines'=>array(array('IdTelaio'=>14,'NomeTelaio'=>'Test')),'events'=>array(14=>array($base)),'now'=>$start+7200,'source'=>'fixture','reconciled'=>false);
$r=auditMeasure($dataset,$day,$end);assertQuality($r['quality']['complete']&&$r['rows'][0]['length']===1.0,'Complete confirmed period must produce a number.');
$dataset['events'][14][0]['TempoEvento']=60;$dataset['events'][14][0]['Battute']=300;$r=auditMeasure($dataset,$day,$end);assertQuality(!$r['quality']['complete']&&auditSummary($r)['length']===1.0&&!auditSummary($r)['lengthComplete']&&$r['rows'][0]['speed']===300.0,'Sparse records must expose recorded measurements without claiming a complete period.');
$dataset['machines'][]=array('IdTelaio'=>44,'NomeTelaio'=>'Missing');$mix=auditMeasure($dataset,$day,$end);assertQuality(auditSummary($mix)['length']===1.0&&auditSummary($mix)['meterLoomCount']===1&&auditSummary($mix)['selectedLoomCount']===2&&!auditSummary($mix)['lengthComplete'],'One missing loom must not hide another loom; subtotal scope must stay explicit.');$dataset['machines']=array(array('IdTelaio'=>14,'NomeTelaio'=>'Test'));
$dataset['events'][14]=array();$r=auditMeasure($dataset,$day,$end);assertQuality(auditSummary($r)['length']===null,'Missing data is not zero production.');
$future=clone $day;$future->modify('+1 day');$futureEnd=clone $future;$futureEnd->modify('+1 hour');$r=auditMeasure($dataset,$future,$futureEnd);assertQuality($r['elapsedSeconds']===0&&auditSummary($r)['length']===null,'Future periods must not fabricate zeros.');
$rejected=false;try{auditDate('2026-02-31');}catch(InvalidArgumentException $error){$rejected=true;}assertQuality($rejected,'Invalid dates must not silently move to another month.');
$rejected=false;try{auditRange(auditDate('2026-10-22'),auditDate('2026-10-21'));}catch(InvalidArgumentException $error){$rejected=true;}assertQuality($rejected,'Reversed ranges must not silently change scope.');
$rejected=false;try{auditIds('wrong ID');}catch(InvalidArgumentException $error){$rejected=true;}assertQuality($rejected,'Invalid machine text must not become all machines.');
$rejected=false;try{auditLoom('abc');}catch(InvalidArgumentException $error){$rejected=true;}assertQuality($rejected,'Invalid machine selection must not become all machines.');
$rejected=false;try{auditLoom('100');}catch(InvalidArgumentException $error){$rejected=true;}assertQuality($rejected,'PiCANOL 100 must not be manually selected.');
$dataset['machines']=array(array('IdTelaio'=>100,'NomeTelaio'=>'PiCANOL'));$dataset['events'][100]=array($base);$r=auditMeasure($dataset,$day,$end);assertQuality(count($r['rows'])===0&&auditSummary($r)['length']===null,'PiCANOL 100 must never enter calculations.');
echo "Report quality checks passed.\n";

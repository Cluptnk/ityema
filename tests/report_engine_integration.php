<?php
// Read-only integration checks against the actual completed event records.
require_once __DIR__.'/../site-dashboard/report_engine.php';
$options=getopt('',array('config:','host:'));
if(empty($options['config']))throw new RuntimeException('--config is required.');
$config=file_get_contents($options['config']);
foreach(array('dsn','user','pw') as $key){if(!preg_match('/\$'.$key.'\s*=\s*\x27([^\x27]*)\x27/',$config,$m))throw new RuntimeException('Connection configuration cannot be parsed.');$$key=$m[1];}
$dsn=preg_replace('/dbname=ilmsdb(?:_dev)?(?=;|$)/','dbname=ilmsdb_dev',$dsn);
if(isset($options['host']))$dsn=preg_replace('/host=[^;]+/','host='.$options['host'],$dsn);
$pdo=new PDO($dsn,$user,$pw,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10));
$pdo->exec('SET SESSION MAX_EXECUTION_TIME=20000');$pdo->exec('SET TRANSACTION READ ONLY');$pdo->beginTransaction();
$db=new class($pdo){private $pdo;public $queries=0;function __construct($pdo){$this->pdo=$pdo;}function exec($sql,$values=array()){if(!preg_match('/^\s*(SELECT|SHOW)\b/i',$sql))throw new RuntimeException('Non-read query rejected.');$this->queries++;$q=$this->pdo->prepare($sql);$q->execute(array_values($values));return $q->fetchAll(PDO::FETCH_ASSOC);}};
function verify($condition,$message){if(!$condition)throw new RuntimeException($message);}
$_SESSION=array('lang'=>'tr');
$zone=$db->exec("SELECT UNIX_TIMESTAMP('2026-11-01 07:00:00') expectedTime");$november=auditDate('2026-11-01');$november->setTime(7,0);
verify((int)$zone[0]['expectedTime']===$november->getTimestamp(),'PHP and MySQL must agree on Turkey UTC+3 after October.');
$date=auditDate('2026-10-22');list($start,$end)=auditWindow($date,'s1');$started=microtime(true);
$data=auditDataset($db,$start,$end,array(14));$report=auditMeasure($data,$start,$end);$summary=auditSummary($report);
verify($data['source']==='ilmsdb.evento','Completed authoritative source must be explicit.');
verify($summary['length']===67.22,'Loom 14 verified piece counters must give 67.22 m.');
$hourSum=0;for($i=7;$i<15;$i++){$a=clone $date;$a->setTime($i,0);$b=clone $a;$b->modify('+1 hour');$part=auditMeasure($data,$a,$b);$hourSum+=$part['rows'][0]['lengthValue'];}
verify(abs($hourSum-$report['rows'][0]['lengthValue'])<0.000001,'Hourly allocation must conserve shift production.');
$causes=auditCauseRows($db,$report);verify(array_sum(array_column($causes,'stopSeconds'))===$summary['stopSeconds'],'Cause durations must match total stops.');
verify(array_sum(array_column($causes,'stopCount'))===$summary['stopCount'],'Cause counts must match total stops.');
$bad=auditMeasure(auditDataset($db,$start,$end,array(6)),$start,$end);verify(!$bad['rows'][0]['lengthReliable']&&$bad['rows'][0]['length']!==null,'A bad counter interval must remain flagged while other recorded production remains visible.');
list($nightStart,$nightEnd)=auditWindow($date,'s3');verify($nightStart->format('Y-m-d H:i')==='2026-10-22 23:00'&&$nightEnd->format('Y-m-d H:i')==='2026-10-23 07:00','Night shift boundary must cross the day.');
list($allStart,$allEnd)=auditWindow($date,'all');verify($allStart->format('H:i')==='07:00'&&$allEnd->format('Y-m-d H:i')==='2026-10-23 07:00','All shifts must cover 07:00 to next 07:00.');
$fleet=auditMeasure(auditDataset($db,$start,$end),$start,$end);
$dayEnd=clone $date;$dayEnd->modify('+1 day');$whole=auditDataset($db,$date,$dayEnd);$dayReport=auditMeasure($whole,$date,$dayEnd);$recordedSum=0;for($i=0;$i<24;$i++){$a=clone $date;$a->setTime($i,0);$b=clone $a;$b->modify('+1 hour');$part=auditRawMeters(auditMeasure($whole,$a,$b));if($part!==null)$recordedSum+=$part;}verify(abs($recordedSum-auditRawMeters($dayReport))<0.000001,'Fleet hourly recorded meters must conserve daily recorded meters, including incomplete counters.');

verify(!in_array(100,array_column($fleet['rows'],'loomId')), 'PiCANOL 100 must be excluded from fleet results.');
$excluded=auditDataset($db,$start,$end,array(100));verify(count($excluded['machines'])===0,'Explicit include cannot override permanent exclusion.');
echo json_encode(array('verifiedLoom14Meters'=>$summary['length'],'fleetMachines'=>count($fleet['rows']),'fleetIncomplete'=>$fleet['quality']['incompleteLooms'],'fleetUncertainMeters'=>$fleet['quality']['uncertainLengthLooms'],'queries'=>$db->queries,'seconds'=>round(microtime(true)-$started,3)),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
$pdo->rollBack();echo "Read-only integration checks passed.\n";

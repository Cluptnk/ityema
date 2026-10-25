<?php
// Read-only equivalence audit: installed engine vs local optimization, one snapshot.
require __DIR__.'/../site-dashboard/report_engine.php';
$options=getopt('',array('config:','host:','from:','to:','baseline:','window:'));
foreach(array('config','baseline','from','to') as $required)if(empty($options[$required]))throw new RuntimeException('Missing --'.$required);
$config=file_get_contents($options['config']);foreach(array('dsn','user','pw') as $key){preg_match('/\$'.$key.'\s*=\s*\x27([^\x27]*)\x27/',$config,$m);$$key=$m[1];}
$dsn=preg_replace('/dbname=ilmsdb(?:_dev)?(?=;|$)/','dbname=ilmsdb_dev',$dsn);if(isset($options['host']))$dsn=preg_replace('/host=[^;]+/','host='.$options['host'],$dsn);
$pdo=new PDO($dsn,$user,$pw,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10));$pdo->exec('SET SESSION MAX_EXECUTION_TIME=20000');$pdo->exec('SET TRANSACTION READ ONLY');$pdo->beginTransaction();
class OrderAuditDB {private $pdo;private $clock=null;function __construct($pdo){$this->pdo=$pdo;}function exec($sql,$values=array()){if(!preg_match('/^\s*SELECT\b/i',$sql))throw new RuntimeException('Non-read statement rejected.');if(strpos($sql,'SELECT DATABASE()')===0&&$this->clock!==null)return $this->clock;$q=$this->pdo->prepare($sql);$q->execute(array_values($values));$rows=$q->fetchAll(PDO::FETCH_ASSOC);if(strpos($sql,'SELECT DATABASE()')===0)$this->clock=$rows;return $rows;}}
$db=new OrderAuditDB($pdo);
$baseline=file_get_contents($options['baseline']);$local=file_get_contents(__DIR__.'/../site-dashboard/report_engine.php');
// Only data loading may change; every calculation and quality function must match.
$withoutLoader=function($source){$a=strpos($source,'function auditDataset(');$b=strpos($source,'function auditPredecessors(');return str_replace("\r\n","\n",substr($source,0,$a).substr($source,$b));};
if($withoutLoader($baseline)!==$withoutLoader($local))throw new RuntimeException('Calculation functions changed.');
if(hash_file('sha256',dirname($options['baseline']).'/performance_calculation.php')!==hash_file('sha256',__DIR__.'/../site-dashboard/performance_calculation.php'))throw new RuntimeException('Accumulator changed.');
$baseline=preg_replace('/\baudit([A-Z][A-Za-z0-9_]*)/','baselineAudit$1',$baseline);
$baseline=str_replace("__DIR__ . '/performance_calculation.php'",var_export(realpath(__DIR__.'/../site-dashboard/performance_calculation.php'),true),$baseline);
eval('?>'.$baseline);
$from=auditDate($options['from']);$to=auditDate($options['to']);auditRange($from,$to);$oldCarry=null;$newCarry=null;$days=0;$meters=0;$count=0;$seconds=0;$started=microtime(true);$oldSeconds=0;$newSeconds=0;$window=isset($options['window'])?$options['window']:'day';
for($d=clone $from;$d<=$to;$d->modify('+1 day')) {
    list($begin,$finish)=auditWindow($d,$window);$end=clone $begin;$end->modify('+1 day');unset($old,$new,$oldData,$newData);$tick=microtime(true);
    $oldData=baselineAuditDataset($db,$begin,$end,array(),array(),$oldCarry);$oldCarry=baselineAuditPredecessors($oldData);$old=baselineAuditMeasure($oldData,$begin,$finish);$oldSeconds+=microtime(true)-$tick;$tick=microtime(true);
    $newData=auditDataset($db,$begin,$end,array(),array(),$newCarry);$newCarry=auditPredecessors($newData);$new=auditMeasure($newData,$begin,$finish);$newSeconds+=microtime(true)-$tick;
    if($oldData!==$newData)throw new RuntimeException('Raw event or boundary mismatch on '.$d->format('Y-m-d'));
    if($old!==$new)throw new RuntimeException('Row, counter or quality mismatch on '.$d->format('Y-m-d'));
    $a=baselineAuditSummary($old);$b=auditSummary($new);if($a!==$b)throw new RuntimeException('Summary mismatch.');
    $raw=auditRawMeters($new);if($raw!==null)$meters+=$raw;$count+=$b['stopCount'];$seconds+=$b['stopSeconds'];$days++;
}
$pdo->rollBack();echo json_encode(array('equivalent'=>true,'days'=>$days,'window'=>$window,'baselineSeconds'=>round($oldSeconds,3),'candidateSeconds'=>round($newSeconds,3),'recordedMeters'=>round($meters,2),'stopCount'=>$count,'stopSeconds'=>$seconds,'seconds'=>round(microtime(true)-$started,3),'databaseWrites'=>0,'loomCommands'=>0),JSON_UNESCAPED_UNICODE).PHP_EOL;

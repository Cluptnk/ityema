<?php
// Executes the real endpoint/page body with a SELECT-only database adapter.
require_once __DIR__.'/../site-dashboard/report_engine.php';
$options=getopt('',array('config:','host:','file:','date:','from:','to:','window:','loom:','looms:','exclude:','cause:'));
if(empty($options['config'])||empty($options['file']))throw new RuntimeException('--config and --file required.');
$config=file_get_contents($options['config']);foreach(array('dsn','user','pw') as $key){preg_match('/\$'.$key.'\s*=\s*\x27([^\x27]*)\x27/',$config,$m);$$key=$m[1];}
$dsn=preg_replace('/dbname=ilmsdb(?:_dev)?(?=;|$)/','dbname=ilmsdb_dev',$dsn);if(isset($options['host']))$dsn=preg_replace('/host=[^;]+/','host='.$options['host'],$dsn);
$pdo=new PDO($dsn,$user,$pw,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10));$pdo->exec('SET SESSION MAX_EXECUTION_TIME=20000');$pdo->exec('SET TRANSACTION READ ONLY');$pdo->beginTransaction();
class AuditReadDatabase {private $pdo;public $queries=0;public $profile=array();function __construct($pdo){$this->pdo=$pdo;}function exec($sql,$values=array()){if(!preg_match('/^\s*(SELECT|SHOW)\b/i',$sql))throw new RuntimeException('Non-read query rejected.');$this->queries++;$started=microtime(true);$q=$this->pdo->prepare($sql);$q->execute(array_values($values));$rows=$q->fetchAll(PDO::FETCH_ASSOC);$kind=strpos($sql,'LIMIT 2')!==false?'predecessor':(strpos($sql,'eventTimestamp')!==false?'events':(strpos($sql,'DATABASE()')!==false?'clock':'metadata'));if(!isset($this->profile[$kind]))$this->profile[$kind]=array('queries'=>0,'seconds'=>0,'rows'=>0);$this->profile[$kind]['queries']++;$this->profile[$kind]['seconds']+=microtime(true)-$started;$this->profile[$kind]['rows']+=count($rows);return $rows;}}
$db=new AuditReadDatabase($pdo);$_SESSION=array('name'=>'Local report audit','lang'=>'tr');$_SERVER['REQUEST_METHOD']='GET';
function auditTestInput($type,$name,$filter=null){global $options;return isset($options[$name])?$options[$name]:null;}
$file=basename($options['file']);$path=__DIR__.'/../site-dashboard/'.$file;if(!is_file($path))throw new RuntimeException('Unknown report file.');
$source=file_get_contents($path);$source=str_replace("require __DIR__.'/report_api.php';",'',$source);
$source=str_replace("__DIR__.'/report_engine.php'",var_export(realpath(__DIR__.'/../site-dashboard/report_engine.php'),true),$source);
$source=preg_replace('/include\(\x27(?:head|header|sidebar|footer)\.php\x27\);/','',$source);
$source=str_replace('filter_input(','auditTestInput(',$source);
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$testStarted=microtime(true);ob_start();eval('?>'.$source);$body=ob_get_clean();restore_error_handler();
$isJson=strpos($file,'_data.php')!==false;
if($isJson){$payload=json_decode($body,true);if(!is_array($payload)||json_last_error()!==JSON_ERROR_NONE)throw new RuntimeException('Invalid report JSON.');if(!isset($payload['quality']))throw new RuntimeException('Quality information missing.');if($file==='performance_data.php'&&isset($options['looms'])&&$options['looms']==='14'&&$options['window']==='s1'&&$payload['summary']['length']!==67.22)throw new RuntimeException('Verified counter regression.');}
else {if(strpos($body,'report-data-quality')===false)throw new RuntimeException('Page is missing its quality notice.');}
$metrics=array();if(!$isJson&&preg_match('/class="management-kpis"[^>]*>(.*?)<\/section>/s',$body,$section)){preg_match_all('/<strong[^>]*>(.*?)<\/strong>/s',$section[1],$matches);foreach($matches[1] as $value)$metrics[]=trim(html_entity_decode(strip_tags($value),ENT_QUOTES,'UTF-8'));}
$pdo->rollBack();echo json_encode(array('renderedMetrics'=>$metrics,'file'=>$file,'pass'=>true,'queries'=>$db->queries,'seconds'=>round(microtime(true)-$testStarted,3),'profile'=>$db->profile,'peakMemoryMiB'=>round(memory_get_peak_usage(true)/1048576,2),'summary'=>$isJson&&isset($payload['summary'])?$payload['summary']:null),JSON_UNESCAPED_UNICODE).PHP_EOL;

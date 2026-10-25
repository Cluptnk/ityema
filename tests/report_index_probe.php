<?php
// Read-only index inventory; never prints connection settings.
$options=getopt('',array('config:','host:'));
$config=file_get_contents($options['config']);foreach(array('dsn','user','pw') as $key){preg_match('/\$'.$key.'\s*=\s*\x27([^\x27]*)\x27/',$config,$m);$$key=$m[1];}
$dsn=preg_replace('/dbname=ilmsdb(?:_dev)?(?=;|$)/','dbname=ilmsdb_dev',$dsn);if(isset($options['host']))$dsn=preg_replace('/host=[^;]+/','host='.$options['host'],$dsn);
$pdo=new PDO($dsn,$user,$pw,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10));
$rows=$pdo->query('SHOW INDEX FROM ilmsdb.evento')->fetchAll(PDO::FETCH_ASSOC);
$indexes=array();foreach($rows as $r)$indexes[]=array('name'=>$r['Key_name'],'column'=>$r['Column_name'],'position'=>$r['Seq_in_index'],'cardinality'=>$r['Cardinality']);
echo json_encode($indexes).PHP_EOL;

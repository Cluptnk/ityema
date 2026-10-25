<?php
function auditBackupStatus($statusFile,$now=null) {
    $now=$now===null?time():$now;
    $result=array('configured'=>null,'schedule'=>null,'retentionDays'=>null,'lastBackup'=>null,'verified'=>false,'message'=>'Yedekleme durumu doğrulanamadı.');
    if(!is_file($statusFile)||!is_readable($statusFile))return $result;
    $text=file_get_contents($statusFile);if(substr($text,0,3)==="\xEF\xBB\xBF")$text=substr($text,3);
    $json=json_decode($text,true);
    if(!is_array($json))return $result;
    $finished=isset($json['FinishedAt'])?strtotime($json['FinishedAt']):false;
    $root=realpath(dirname($statusFile));$filesOk=true;$actual=array();
    foreach(array('Database','Site') as $kind){
        $path=isset($json[$kind.'File'])?realpath($json[$kind.'File']):false;
        $within=$path&&$root&&strpos(strtolower($path),strtolower($root.DIRECTORY_SEPARATOR))===0;
        $bytes=$within&&is_file($path)?filesize($path):0;
        $actual[$kind]=$bytes;
        if(!$bytes||$bytes!==(int)(isset($json[$kind.'Bytes'])?$json[$kind.'Bytes']:0))$filesOk=false;
    }
    $fresh=$finished!==false&&$finished<=$now+60&&$now-$finished<=36*3600;
    $success=isset($json['Success'])&&$json['Success']===true&&$filesOk&&$fresh;
    $result['verified']=$success;
    $message=$success?'Son yedek kaydı ve dosya boyutları doğrulandı.':(!$filesOk?'Yedek dosyaları eksik veya boyutları kayıtla uyuşmuyor.':(!$fresh?'Yedek tarihi eski veya geçersiz; güncel yedek doğrulanamadı.':'Son yedekleme başarısız olarak kaydedilmiş.'));
    $result['lastBackup']=array('success'=>$success,'finishedAt'=>isset($json['FinishedAt'])?$json['FinishedAt']:null,'databaseFile'=>isset($json['DatabaseFile'])?basename($json['DatabaseFile']):null,'databaseBytes'=>$actual['Database'],'siteFile'=>isset($json['SiteFile'])?basename($json['SiteFile']):null,'siteBytes'=>$actual['Site'],'message'=>$message);
    $result['message']=$message;return $result;
}

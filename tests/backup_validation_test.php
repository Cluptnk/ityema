<?php
require_once __DIR__.'/../site-dashboard/backup_validation.php';
date_default_timezone_set('Europe/Istanbul');
$dir=sys_get_temp_dir().'/itema-backup-check-'.uniqid();mkdir($dir);
$status=$dir.'/last-backup.json';$database=$dir.'/database.sql';$site=$dir.'/site.zip';
try {
    if(auditBackupStatus($status)['verified'])throw new RuntimeException('Missing status cannot be verified.');
    file_put_contents($database,'sql');file_put_contents($site,'zip');
    $record=array('Success'=>true,'FinishedAt'=>date('Y-m-d H:i:s',100000),'DatabaseFile'=>$database,'DatabaseBytes'=>3,'SiteFile'=>$site,'SiteBytes'=>3);
    file_put_contents($status,json_encode($record));
    if(!auditBackupStatus($status,100001)['verified'])throw new RuntimeException('Present matching files should verify.');
    if(auditBackupStatus($status,100000+37*3600)['verified'])throw new RuntimeException('Stale status must not be green.');
    $record['DatabaseBytes']=100;file_put_contents($status,json_encode($record));
    if(auditBackupStatus($status,100001)['verified'])throw new RuntimeException('Size mismatch must not be green.');
    unlink($site);if(auditBackupStatus($status,100001)['verified'])throw new RuntimeException('Missing archive must not be green.');
    file_put_contents($status,'not json');if(auditBackupStatus($status)['verified'])throw new RuntimeException('Corrupt status must not be green.');
    echo "Backup validation checks passed.\n";
} finally {foreach(array($status,$database,$site) as $file)if(is_file($file))unlink($file);rmdir($dir);}

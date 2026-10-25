<?php
if(session_status()===PHP_SESSION_NONE){session_name('ITEMA');session_start();}
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(!isset($_SESSION['name'])){http_response_code(401);echo json_encode(array('error'=>'Oturum gerekli.'));exit;}
date_default_timezone_set('Etc/GMT-3');
require_once __DIR__.'/backup_validation.php';
echo json_encode(auditBackupStatus('C:\ILCA\Backups\last-backup.json'),JSON_UNESCAPED_UNICODE);

<?php
if (session_status() === PHP_SESSION_NONE) { session_name('ITEMA'); session_start(); }
if (!isset($_SESSION['name'])) { http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('error'=>'Oturum gerekli.')); exit; }
$statusFile = 'C:\\ILCA\\Backups\\last-backup.json';
$result = array('configured'=>true,'schedule'=>'Her gün 02:15','retentionDays'=>30,'lastBackup'=>null);
if (is_file($statusFile) && is_readable($statusFile)) {
    $json = json_decode(file_get_contents($statusFile), true);
    if (is_array($json)) $result['lastBackup'] = array(
        'success'=>!empty($json['Success']), 'finishedAt'=>isset($json['FinishedAt'])?$json['FinishedAt']:null,
        'databaseFile'=>isset($json['DatabaseFile'])?basename($json['DatabaseFile']):null,
        'databaseBytes'=>isset($json['DatabaseBytes'])?(int)$json['DatabaseBytes']:0,
        'siteFile'=>isset($json['SiteFile'])?basename($json['SiteFile']):null,
        'siteBytes'=>isset($json['SiteBytes'])?(int)$json['SiteBytes']:0,
        'message'=>isset($json['Message'])?$json['Message']:null
    );
}
header('Content-Type: application/json; charset=utf-8'); echo json_encode($result, JSON_UNESCAPED_UNICODE);

<?php
if(session_status()===PHP_SESSION_NONE){session_name('ITEMA');session_start();}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if(!isset($_SESSION['name'])){http_response_code(401);echo json_encode(array('error'=>'Oturum gerekli.'));exit;}
if(!isset($_SERVER['HTTP_X_REPORT_VERSION'])||$_SERVER['HTTP_X_REPORT_VERSION']!=='20261022-6'){
    http_response_code(409);echo json_encode(array('error'=>'Rapor hesapları güncellendi. Rakamları kullanmadan önce sayfayı yenileyin.'),JSON_UNESCAPED_UNICODE);exit;
}
set_exception_handler(function($error){
    http_response_code($error instanceof InvalidArgumentException?400:503);
    echo json_encode(array('error'=>$error instanceof InvalidArgumentException?$error->getMessage():'Rapor verisi doğrulanamadı. Önceki rakamlar kullanılmamalıdır.'),JSON_UNESCAPED_UNICODE);
});
ob_start();include('head.php');ob_end_clean();
// Long reports must not hold the user's PHP session lock and block other tabs.
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
set_time_limit(120);
require_once __DIR__.'/report_engine.php';

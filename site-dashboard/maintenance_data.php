<?php
// Bakım kayıtları duruş özetinden bağımsız saklanır; web isteği ham olay tablosunu taramaz.
if (session_status() === PHP_SESSION_NONE) { session_name('ITEMA'); session_start(); }
if (!isset($_SESSION['name'])) { http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('error'=>'Oturum gerekli.')); exit; }
ob_start(); include('head.php'); ob_end_clean();

function maintenanceExec($db, $sql, $values = array()) { $parameters=array(); foreach (array_values($values) as $index=>$value) $parameters[$index+1]=$value; return $db->exec($sql,$parameters); }
function maintenanceDate($value) { $date=DateTime::createFromFormat('!Y-m-d',(string)$value); return $date ? $date : new DateTime('today'); }
function maintenanceWindowCodes($window) { return $window === 'all' ? array('s1','s2','s3') : array($window); }
function maintenancePriority($count,$seconds) { if ($seconds>=1800 || $count>=10) return 'critical'; if ($seconds>=600 || $count>=4) return 'high'; return 'normal'; }
function maintenanceEnsureTable($db) {
    maintenanceExec($db, "CREATE TABLE IF NOT EXISTS maintenance_followups (
        Id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, SummaryDate DATE NOT NULL, WindowCode VARCHAR(8) NOT NULL,
        LoomId INT NOT NULL, StatusCode TINYINT UNSIGNED NOT NULL, CauseText VARCHAR(255) NOT NULL,
        StopCount INT UNSIGNED NOT NULL DEFAULT 0, StopSeconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
        Priority VARCHAR(12) NOT NULL DEFAULT 'normal', WorkflowStatus VARCHAR(16) NOT NULL DEFAULT 'open',
        Assignee VARCHAR(100) NULL, Notes TEXT NULL, CreatedBy VARCHAR(100) NOT NULL, CreatedAt DATETIME NOT NULL,
        UpdatedAt DATETIME NOT NULL, ResolvedAt DATETIME NULL, PRIMARY KEY (Id),
        UNIQUE KEY uq_maintenance_followup (SummaryDate,WindowCode,LoomId,StatusCode),
        KEY ix_maintenance_status (WorkflowStatus,Priority,UpdatedAt), KEY ix_maintenance_loom (LoomId,SummaryDate)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function maintenanceCauseRows($db,$dateValue,$window,$loomId=0,$statusCode=0) {
    $codes=maintenanceWindowCodes($window); $placeholders=implode(',',array_fill(0,count($codes),'?')); $loomSql=$loomId>0?' AND c.LoomId=?':''; $statusSql=$statusCode>0?' AND c.StatusCode=?':'';
    $values=array_merge(array($_SESSION['lang'],$dateValue),$codes); if($loomId>0)$values[]=$loomId; if($statusCode>0)$values[]=$statusCode;
    return maintenanceExec($db, "SELECT c.LoomId AS loomId,t.NomeTelaio AS loomName,c.StatusCode AS statusCode,
        COALESCE(NULLIF(MAX(l.Frase),''),CONCAT('Duruş kodu ',c.StatusCode)) AS cause,
        COALESCE(SUM(c.StopCount),0) AS stopCount,COALESCE(SUM(c.StopSeconds),0) AS stopSeconds
        FROM loom_stop_window_cause_summary c INNER JOIN telaio t ON t.IdTelaio=c.LoomId AND t.EnableTelaio=1
        LEFT JOIN stato_produzione sp ON sp.IdStatoProduzione=c.StatusCode LEFT JOIN lingua l ON l.CodePhrase=sp.CodePhrase AND l.IdLingua=?
        WHERE c.SummaryDate=? AND c.WindowCode IN ($placeholders)$loomSql$statusSql
        GROUP BY c.LoomId,t.NomeTelaio,c.StatusCode ORDER BY stopSeconds DESC,stopCount DESC,c.LoomId ASC",$values);
}
function maintenanceDuration($seconds) { $seconds=(int)$seconds; $hours=floor($seconds/3600); $minutes=floor(($seconds%3600)/60); $rest=$seconds%60; return ($hours?$hours.' sa ':'').($minutes?$minutes.' dk ':'').($rest||(!$hours&&!$minutes)?$rest.' sn':''); }

maintenanceEnsureTable($db);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    header('Content-Type: application/json; charset=utf-8');
    $payload=json_decode(file_get_contents('php://input'),true); if(!is_array($payload))$payload=array();
    if (empty($payload['csrf']) || empty($_SESSION['maintenanceCsrf']) || !hash_equals($_SESSION['maintenanceCsrf'],(string)$payload['csrf'])) { http_response_code(403); echo json_encode(array('error'=>'Güvenlik doğrulaması geçersiz.')); exit; }
    $action=isset($payload['action'])?(string)$payload['action']:''; $id=isset($payload['id'])?(int)$payload['id']:0;
    if ($action==='take') {
        $date=maintenanceDate(isset($payload['date'])?$payload['date']:null); $window=isset($payload['window'])?(string)$payload['window']:'all'; if(!in_array($window,array('all','s1','s2','s3','day'),true)){http_response_code(400);echo json_encode(array('error'=>'Geçersiz dönem.'));exit;}
        $loomId=isset($payload['loomId'])?(int)$payload['loomId']:0; $statusCode=isset($payload['statusCode'])?(int)$payload['statusCode']:0; $rows=maintenanceCauseRows($db,$date->format('Y-m-d'),$window,$loomId,$statusCode);
        if(!count($rows)){http_response_code(404);echo json_encode(array('error'=>'Bu duruş kaydı bulunamadı.'));exit;}
        $row=$rows[0]; $priority=maintenancePriority((int)$row['stopCount'],(int)$row['stopSeconds']); $note=trim(substr(isset($payload['note'])?(string)$payload['note']:'',0,1000)); $user=substr((string)$_SESSION['name'],0,100);
        maintenanceExec($db,"INSERT INTO maintenance_followups (SummaryDate,WindowCode,LoomId,StatusCode,CauseText,StopCount,StopSeconds,Priority,WorkflowStatus,Assignee,Notes,CreatedBy,CreatedAt,UpdatedAt)
            VALUES (?,?,?,?,?,?,?,?, 'in_progress',?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE CauseText=VALUES(CauseText),StopCount=VALUES(StopCount),StopSeconds=VALUES(StopSeconds),Priority=VALUES(Priority),WorkflowStatus='in_progress',Assignee=VALUES(Assignee),Notes=CASE WHEN VALUES(Notes)='' THEN Notes ELSE VALUES(Notes) END,UpdatedAt=NOW(),ResolvedAt=NULL",
            array($date->format('Y-m-d'),$window,(int)$row['loomId'],(int)$row['statusCode'],$row['cause'],(int)$row['stopCount'],(int)$row['stopSeconds'],$priority,$user,$note,$user));
        echo json_encode(array('ok'=>true,'message'=>'Bakım işlemi sizin üzerinize alındı.'),JSON_UNESCAPED_UNICODE); exit;
    }
    if (($action==='resolve' || $action==='reopen') && $id>0) {
        $newStatus=$action==='resolve'?'resolved':'in_progress'; $note=trim(substr(isset($payload['note'])?(string)$payload['note']:'',0,1000));
        maintenanceExec($db,"UPDATE maintenance_followups SET WorkflowStatus=?,Notes=CASE WHEN ?='' THEN Notes ELSE ? END,UpdatedAt=NOW(),ResolvedAt=".($action==='resolve'?'NOW()':'NULL')." WHERE Id=?",array($newStatus,$note,$note,$id));
        echo json_encode(array('ok'=>true,'message'=>$action==='resolve'?'Bakım kaydı çözüldü olarak kapatıldı.':'Bakım kaydı yeniden işleme alındı.'),JSON_UNESCAPED_UNICODE); exit;
    }
    http_response_code(400); echo json_encode(array('error'=>'Geçersiz bakım işlemi.')); exit;
}

$date=maintenanceDate(filter_input(INPUT_GET,'date')); $window=filter_input(INPUT_GET,'window'); $window=in_array($window,array('all','s1','s2','s3','day'),true)?$window:'all';
$loomId=filter_input(INPUT_GET,'loom',FILTER_VALIDATE_INT); $loomId=$loomId!==false&&$loomId!==null&&$loomId>0?$loomId:0; $dateValue=$date->format('Y-m-d');
$candidateRows=maintenanceCauseRows($db,$dateValue,$window,$loomId); $candidates=array(); $critical=0;$high=0;
$ticketRows=maintenanceExec($db,"SELECT Id,LoomId,StatusCode,WorkflowStatus,Assignee,Notes FROM maintenance_followups WHERE SummaryDate=? AND WindowCode=?".($loomId>0?' AND LoomId=?':''),$loomId>0?array($dateValue,$window,$loomId):array($dateValue,$window));
$ticketMap=array();foreach($ticketRows as $ticket)$ticketMap[(int)$ticket['LoomId'].':'.(int)$ticket['StatusCode']]=$ticket;
foreach($candidateRows as $row){$priority=maintenancePriority((int)$row['stopCount'],(int)$row['stopSeconds']);if($priority==='critical')$critical++;if($priority==='high')$high++;$key=(int)$row['loomId'].':'.(int)$row['statusCode'];$ticket=isset($ticketMap[$key])?$ticketMap[$key]:null;$candidates[]=array('loomId'=>(int)$row['loomId'],'loomName'=>$row['loomName'],'statusCode'=>(int)$row['statusCode'],'cause'=>$row['cause'],'count'=>(int)$row['stopCount'],'seconds'=>(int)$row['stopSeconds'],'duration'=>maintenanceDuration($row['stopSeconds']),'priority'=>$priority,'ticketId'=>$ticket?(int)$ticket['Id']:0,'workflowStatus'=>$ticket?$ticket['WorkflowStatus']:'open','assignee'=>$ticket?$ticket['Assignee']:null,'notes'=>$ticket?$ticket['Notes']:null);}
$openRows=maintenanceExec($db,"SELECT f.Id AS id,f.SummaryDate AS summaryDate,f.WindowCode AS windowCode,f.LoomId AS loomId,t.NomeTelaio AS loomName,f.CauseText AS cause,f.StopCount AS stopCount,f.StopSeconds AS stopSeconds,f.Priority AS priority,f.WorkflowStatus AS workflowStatus,f.Assignee AS assignee,f.Notes AS notes,f.CreatedAt AS createdAt,f.UpdatedAt AS updatedAt
    FROM maintenance_followups f LEFT JOIN telaio t ON t.IdTelaio=f.LoomId WHERE f.WorkflowStatus<>'resolved'".($loomId>0?' AND f.LoomId=?':'')." ORDER BY FIELD(f.Priority,'critical','high','normal'),f.UpdatedAt DESC,f.Id DESC",$loomId>0?array($loomId):array());
$open=array();$openCount=0;$progressCount=0;foreach($openRows as $row){if($row['workflowStatus']==='open')$openCount++;if($row['workflowStatus']==='in_progress')$progressCount++;$row['id']=(int)$row['id'];$row['loomId']=(int)$row['loomId'];$row['stopCount']=(int)$row['stopCount'];$row['stopSeconds']=(int)$row['stopSeconds'];$open[]=$row;}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('date'=>$dateValue,'window'=>$window,'loomId'=>$loomId,'summary'=>array('critical'=>$critical,'high'=>$high,'open'=>$openCount,'progress'=>$progressCount),'candidates'=>$candidates,'openItems'=>$open),JSON_UNESCAPED_UNICODE);

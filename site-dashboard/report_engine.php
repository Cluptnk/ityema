<?php
require_once __DIR__ . '/performance_calculation.php';
// PHP 5.6's bundled timezone database predates Turkey's permanent UTC+3.
// These factory reports cover current production and must not revert to UTC+2.
date_default_timezone_set('Etc/GMT-3');

function auditExec($db, $sql, $values = array()) {
    $parameters=array(); foreach(array_values($values) as $i=>$v) $parameters[$i+1]=$v;
    return $db->exec($sql,$parameters);
}
function auditDate($value, $fallback='today') {
    if ($value===null || $value===false) return new DateTime($fallback);
    $date=DateTime::createFromFormat('!Y-m-d',(string)$value);
    if (!$date || $date->format('Y-m-d')!==(string)$value) {
        http_response_code(400); throw new InvalidArgumentException('Geçersiz rapor tarihi.');
    }
    return $date;
}
function auditIds($text) {
    $text=trim((string)$text);if($text==='')return array();
    if(preg_match('/[^0-9,;\s]/u',$text))throw new InvalidArgumentException('Tezgâh ID alanına yalnız pozitif numaralar yazın.');
    $ids=array(); foreach(preg_split('/[,;\s]+/',$text) as $id){if($id==='')continue;if(!ctype_digit($id)||(float)$id>2147483647||(int)$id<=0)throw new InvalidArgumentException('Geçersiz tezgâh numarası.');$ids[(int)$id]=(int)$id;}
    return array_values($ids);
}
function auditLoom($value) {
    if($value===null||$value==='0'||$value===0)return 0;
    if(!ctype_digit((string)$value)||(float)$value>2147483647||(int)$value<=0)throw new InvalidArgumentException('Geçersiz tezgâh seçimi.');
    if((int)$value===100)throw new InvalidArgumentException('100 PiCANOL hesaplamalara kalıcı olarak dahil edilmez.');
    return (int)$value;
}
function auditWindow($date,$code) {
    $start=clone $date;
    $hour=$code==='s1'||$code==='all'?7:($code==='s2'?15:($code==='s3'?23:0));
    $start->setTime($hour,0,0); $end=clone $start;
    $end->modify($code==='day'||$code==='all'?'+1 day':'+8 hours');
    return array($start,$end);
}
function auditRange($from,$to,$maxDays=31) {
    if($to<$from || $from->diff($to)->days>=$maxDays) {
        http_response_code(400); throw new InvalidArgumentException('Tarih aralığı 1–'.$maxDays.' gün olmalıdır.');
    }
}
function auditDataset($db,$start,$end,$include=array(),$exclude=array(),$predecessors=null) {
    auditRange($start,$end,33);
    $where='';$args=array();
    if($include){$where.=' AND IdTelaio IN ('.implode(',',array_fill(0,count($include),'?')).')';$args=array_values($include);}
    if($exclude){$where.=' AND IdTelaio NOT IN ('.implode(',',array_fill(0,count($exclude),'?')).')';$args=array_merge($args,array_values($exclude));}
    $machines=auditExec($db,'SELECT IdTelaio,NomeTelaio FROM telaio WHERE EnableTelaio=1 AND IdTelaio<>100'.$where.' ORDER BY IdTelaio',$args);
    $clock=auditExec($db,'SELECT DATABASE() dbName,UNIX_TIMESTAMP(NOW()) nowTime');
    // The development collector historically froze counters on first receipt.
    // Read completed events from the established collector without changing it.
    $table=$clock[0]['dbName']==='ilmsdb_dev'?'ilmsdb.evento':'evento';
    $result=array('machines'=>$machines,'events'=>array(),'now'=>(int)$clock[0]['nowTime'],'source'=>$table,'reconciled'=>$table==='ilmsdb.evento');
    if(!$machines)return $result;
    $ids=array();foreach($machines as $m)$ids[]=(int)$m['IdTelaio'];
    $columns='IdTelaio,IdEvento,UNIX_TIMESTAMP(Timestamp) eventTimestamp,NumeroSerie,NumeroPezza,StatoProduzione,TempoEvento,Battute,LunghezzaDaInizio,IsShiftSameEvent,Armatura1';
    $rows=auditExec($db,'SELECT '.$columns.' FROM '.$table.' FORCE INDEX (index_evento_Timestamp) WHERE Timestamp>=? AND Timestamp<? AND IdTelaio IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY Timestamp,IdEvento',array_merge(array($start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s')),$ids));
    foreach($rows as $row)$result['events'][(int)$row['IdTelaio']][]=$row;
    unset($rows,$row);
    // Batch the same per-machine boundary lookups; retain their logical-event
    // ordering and LIMIT 2 independently, including machines with bad clocks.
    $missing=array();foreach($machines as $m){$id=(int)$m['IdTelaio'];if($predecessors===null||!isset($predecessors[$id]))$missing[]=$id;}
    $boundary=array();
    if($missing){
        $parts=array();$parameters=array();
        // Filter on index-covered keys first; fetch payloads only for the two
        // accepted events, rather than every newer event examined backwards.
        foreach($missing as $id){$parts[]='(SELECT IdTelaio,IdEvento,Timestamp FROM '.$table.' FORCE INDEX (index_evento_IdTelaio) WHERE IdTelaio=? AND Timestamp<? ORDER BY IdEvento DESC,Timestamp DESC LIMIT 2)';$parameters[]=$id;$parameters[]=$start->format('Y-m-d H:i:s');}
        $priorRows=auditExec($db,'SELECT '.$columns.' FROM '.$table.' JOIN ('.implode(' UNION ALL ',$parts).') boundaryEvents USING (IdTelaio,IdEvento,Timestamp) ORDER BY IdTelaio,IdEvento DESC,Timestamp DESC',$parameters);
        foreach($priorRows as $row)$boundary[(int)$row['IdTelaio']][]=$row;
        unset($priorRows,$row);
    }
    foreach($machines as $m){
        $id=(int)$m['IdTelaio'];
        // InnoDB's machine index includes the primary event number. Read the
        // latest logical events without sorting/scanning a machine's history.
        // Sequence gaps or clock disorder are rejected by coverage validation.
        if($predecessors!==null&&isset($predecessors[$id]))$prior=$predecessors[$id];
        else $prior=isset($boundary[$id])?array_reverse($boundary[$id]):array();
        $events=array_merge($prior,isset($result['events'][$id])?$result['events'][$id]:array());
        // Drop the original owner before adding derived fields (PHP 5 COW).
        unset($result['events'][$id],$prior);
        $previous=null;
        foreach($events as &$event){
            $same=$previous!==null && $previous['NumeroSerie']==$event['NumeroSerie'];
            $event['previousLength']=$same?$previous['LunghezzaDaInizio']:null;
            $event['previousPiece']=$same?$previous['NumeroPezza']:null;
            $event['sequenceGap']=$same && (int)$event['IdEvento']!==(int)$previous['IdEvento']+1;
            $code=(int)$event['StatoProduzione']%256;
            $previousCode=$same?(int)$previous['StatoProduzione']%256:null;
            $gap=$same?(int)$event['eventTimestamp']-((int)$previous['eventTimestamp']+max(0,(int)$previous['TempoEvento'])):PHP_INT_MAX;
            $event['stopStart']=$code>=1&&$code<=20&&!$event['IsShiftSameEvent']&&($previousCode!==$code||$gap>2);
            $previous=$event;
        }
        unset($event);$result['events'][$id]=$events;
    }
    return $result;
}
// Carry only the two boundary events between contiguous daily chunks. This
// bounds PHP 5 memory and avoids rescanning historical predecessors each day.
function auditPredecessors($dataset) {
    $prior=array();foreach($dataset['machines'] as $m){$id=(int)$m['IdTelaio'];$events=isset($dataset['events'][$id])?$dataset['events'][$id]:array();$prior[$id]=array_slice($events,-2);}
    return $prior;
}
function auditMeasure($dataset,$start,$end) {
    // Permanent business rule: never include PiCANOL 100, including caller-built datasets.
    $dataset['machines']=array_values(array_filter($dataset['machines'],function($m){return (int)$m['IdTelaio']!==100;}));
    $begin=$start->getTimestamp();$finish=min($end->getTimestamp(),$dataset['now']);$elapsed=max(0,$finish-$begin);
    $rows=array();$quality=array('complete'=>true,'lengthReliable'=>true,'hasRecords'=>false,'source'=>$dataset['source'],'asOf'=>date('Y-m-d H:i:s',$dataset['now']),'incompleteLooms'=>array(),'uncertainLengthLooms'=>array(),'messages'=>array());
    if(!$dataset['machines'])$quality['complete']=$quality['lengthReliable']=false;
    foreach($dataset['machines'] as $m){
        $id=(int)$m['IdTelaio'];$events=isset($dataset['events'][$id])?$dataset['events'][$id]:array();
        $value=performanceAccumulate($events,$begin,$finish);
        $covered=0;$coveredUntil=$begin;$gap=false;$causes=array();$clockSkew=false;$hasRecords=false;
        foreach($events as $event){
            $t=(int)$event['eventTimestamp'];$duration=max(0,(int)$event['TempoEvento']);
            if($t>$dataset['now']+60)$clockSkew=true;
            $a=max($begin,$t);$b=min($finish,$t+$duration);
            if(($t>=$begin&&$t<$finish)||$a<$b)$hasRecords=true;
            if($a<$b){$covered+=max(0,$b-max($a,$coveredUntil));$coveredUntil=max($coveredUntil,$b);if($event['sequenceGap'])$gap=true;}
            $code=(int)$event['StatoProduzione']%256;
            if($code<1||$code>20)continue;
            $count=$t>=$begin&&$t<$finish&&$event['stopStart']?1:0;
            if(!$count&&$a>=$b)continue;
            if(!isset($causes[$code]))$causes[$code]=array('count'=>0,'seconds'=>0);
            $causes[$code]['count']+=$count;$causes[$code]['seconds']+=max(0,$b-$a);
        }
        $complete=$elapsed>0 && $covered>0 && $elapsed-$covered<=max(30,$elapsed*0.001) && !$gap && !$clockSkew;
        $quality['hasRecords']=$quality['hasRecords']||$hasRecords;
        if(!$hasRecords){$value['stopCount']=null;$value['stopSeconds']=null;}
        $value['lengthReliable']=$value['lengthReliable']&&$hasRecords&&$value['totalSeconds']>0&&!$gap&&!$clockSkew;
        if(!$complete)$quality['incompleteLooms'][]=$id;
        if(!$value['lengthReliable'])$quality['uncertainLengthLooms'][]=$id;
        $quality['complete']=$quality['complete']&&$complete;$quality['lengthReliable']=$quality['lengthReliable']&&$value['lengthReliable'];
        $value['hasRecords']=$hasRecords;$value['partial']=!$complete;$value['loomId']=$id;$value['loomName']=$m['NomeTelaio'];$value['complete']=$complete;$value['coveredSeconds']=$covered;$value['causes']=$causes;
        $value['length']=$hasRecords&&$value['lengthSamples']>0?round($value['lengthValue'],2):null;
        $value['speed']=$hasRecords&&$value['weavingSeconds']>0?round($value['weavingPicks']*60/$value['weavingSeconds'],1):null;
        $value['efficiency']=$hasRecords&&$value['totalSeconds']>0?round($value['weavingSeconds']*100/$value['totalSeconds'],2):null;
        $rows[]=$value;
    }
    $quality['selectedLoomCount']=count($rows);$quality['recordedLoomCount']=0;$quality['meterLoomCount']=0;foreach($rows as $row){if($row['hasRecords'])$quality['recordedLoomCount']++;if($row['length']!==null)$quality['meterLoomCount']++;}
    $quality['messages']=auditQualityMessages($quality);
    return array('rows'=>$rows,'quality'=>$quality,'elapsedSeconds'=>$elapsed);
}
function auditQualityMessages($quality) {
    $messages=array();
    if($quality['source']==='ilmsdb.evento')$messages[]='Hesaplar ana sistemin tamamlanmış olay kayıtlarından salt okunur alınır; devam eden olaylar henüz dahil olmayabilir.';
    $messages[]='100 numaralı PiCANOL tüm hesaplardan kalıcı olarak çıkarılmıştır. Kapsam diğer etkin tezgâhlardır. Sınırı aşan olaylarda metraj süre oranıyla paylaştırılır; bu değer ölçüm değil dönemsel tahmindir. Toplamlar yuvarlanmamış değerlerden hesaplanır; satırların yuvarlanması küçük farklar oluşturabilir.';
    if(!$quality['hasRecords'])$messages[]='Seçili dönemde doğrulanabilen olay kaydı yok. Boş alanlar sıfır üretim veya sıfır duruş değildir.';
    if(!$quality['complete'])$messages[]='Dönemin tamamını kapsamayan kayıtlar var. Duruşlar yalnız kayıtlı sürelerdir; eksik veri sıfır üretim veya çalışan performansı olarak yorumlanamaz. Tezgâhlar: '.implode(', ',$quality['incompleteLooms']);
    if(!$quality['lengthReliable']){$corrections=array_diff($quality['uncertainLengthLooms'],$quality['incompleteLooms']);$messages[]='Kayıtlı metraj yalnız hesaplanabilen olay sayaçlarının toplamıdır. Açıklanamayan sayaç düşüşleri ve sıra boşluğu olan olaylar üretime eklenmez; bu alanların üretimi bilinmediği için toplam dönem üretiminin alt kapsamıdır.'.($corrections?' Ek sayaç belirsizliği olan tezgâhlar: '.implode(', ',$corrections):'');}
    return $messages;
}
function auditMergeQuality($qualities) {
    $q=array('source'=>'','asOf'=>'','complete'=>count($qualities)>0,'lengthReliable'=>count($qualities)>0,'hasRecords'=>false,'incompleteLooms'=>array(),'uncertainLengthLooms'=>array());
    foreach($qualities as $part){$q['source']=$part['source'];$q['asOf']=max($q['asOf'],$part['asOf']);$q['complete']=$q['complete']&&$part['complete'];$q['lengthReliable']=$q['lengthReliable']&&$part['lengthReliable'];$q['hasRecords']=$q['hasRecords']||$part['hasRecords'];foreach(array('incompleteLooms','uncertainLengthLooms') as $key)$q[$key]=array_values(array_unique(array_merge($q[$key],$part[$key])));}
    foreach(array('selectedLoomCount','recordedLoomCount','meterLoomCount') as $key){$q[$key]=0;foreach($qualities as $part)if(isset($part[$key]))$q[$key]=max($q[$key],$part[$key]);}
    sort($q['incompleteLooms']);sort($q['uncertainLengthLooms']);$q['messages']=auditQualityMessages($q);return $q;
}
function auditSummary($report) {
    $s=array('length'=>0.0,'picks'=>0,'weavingPicks'=>0,'totalSeconds'=>0,'weavingSeconds'=>0,'stopCount'=>0,'stopSeconds'=>0,'lengthReliable'=>false,'lengthComplete'=>$report['quality']['complete']&&$report['quality']['lengthReliable'],'meterLoomCount'=>0,'recordedLoomCount'=>0,'selectedLoomCount'=>count($report['rows']),'excludedMeterLooms'=>array());
    foreach($report['rows'] as $r){
        if($r['length']!==null){$s['length']+=$r['lengthValue'];$s['meterLoomCount']++;}else $s['excludedMeterLooms'][]=$r['loomId'];
        if(!empty($r['hasRecords']))$s['recordedLoomCount']++;
        foreach(array('picks','weavingPicks','totalSeconds','weavingSeconds','stopCount','stopSeconds') as $key)$s[$key]+=$r[$key];
    }
    $s['lengthReliable']=$s['meterLoomCount']>0;$s['length']=$s['lengthReliable']?round($s['length'],2):null;
    $s['picks']=$report['quality']['hasRecords']?(int)round($s['picks']):null;
    $s['speed']=$s['weavingSeconds']>0?round($s['weavingPicks']*60/$s['weavingSeconds'],1):null;
    $s['efficiency']=$s['totalSeconds']>0?round($s['weavingSeconds']*100/$s['totalSeconds'],2):null;
    if(!$report['quality']['hasRecords']){$s['stopCount']=null;$s['stopSeconds']=null;}
    return $s;
}
function auditRawMeters($report) {
    $meters=0.0;$found=false;foreach($report['rows'] as $row)if($row['length']!==null){$meters+=$row['lengthValue'];$found=true;}return $found?$meters:null;
}
function auditCauseNames($db) {
    $rows=auditExec($db,"SELECT sp.IdStatoProduzione code,MAX(l.Frase) cause FROM stato_produzione sp LEFT JOIN lingua l ON l.CodePhrase=sp.CodePhrase AND l.IdLingua=? WHERE sp.IdStatoProduzione BETWEEN 1 AND 20 GROUP BY sp.IdStatoProduzione",array(isset($_SESSION['lang'])?$_SESSION['lang']:'tr'));
    $names=array();foreach($rows as $r)$names[(int)$r['code']]=$r['cause'];return $names;
}
function auditCauseRows($db,$report) {
    $names=auditCauseNames($db);$rows=array();
    foreach($report['rows'] as $r)foreach($r['causes'] as $code=>$value)$rows[]=array('loomId'=>$r['loomId'],'loomName'=>$r['loomName'],'statusCode'=>$code,'cause'=>!empty($names[$code])?$names[$code]:'Duruş kodu '.$code,'stopCount'=>$value['count'],'stopSeconds'=>$value['seconds']);
    return $rows;
}
function auditQualityHtml($quality) {
    return '<p style="margin:8px 0;color:#64748b">Kayıtlı veriler · cihaz saatine göre dönem'.(isset($quality['recordedLoomCount'])?' · '.(int)$quality['recordedLoomCount'].' / '.(int)$quality['selectedLoomCount'].' tezgâhta kayıt, '.(int)$quality['meterLoomCount'].' tezgâhta metraj':'').'</p><details class="report-data-quality" style="margin:10px 0;color:#64748b;font-size:13px;line-height:1.5"><summary style="cursor:pointer">Veri ayrıntıları</summary><p>'.htmlspecialchars(implode(' ',$quality['messages']),ENT_QUOTES,'UTF-8').'</p></details>';
}
function auditNumber($value,$suffix='') { return $value===null?'—':number_format($value,2,',','.').$suffix; }

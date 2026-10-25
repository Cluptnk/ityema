<?php
// Bu uç nokta yalnızca geliştirme sitesinin oturum açmış kullanıcılarına veri verir.
if (session_status() === PHP_SESSION_NONE) {
    session_name('ITEMA');
    session_start();
}
if (!isset($_SESSION['name'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Oturum gerekli.'));
    exit;
}

ob_start();
include('head.php');
ob_end_clean();

$rows = $db->exec(
    "SELECT
        telaio.IdTelaio,
        telaio.NomeTelaio,
        telaio.Online,
        dato_rt.`Timestamp` AS LastUpdate,
        dato_rt.Velocita,
        dato_rt.Resa,
        dato_rt.StatoProduzione,
        dato_rt.Warnings,
        CASE
            WHEN dato_rt.`Timestamp` IS NULL THEN NULL
            ELSE TIMESTAMPDIFF(SECOND, dato_rt.`Timestamp`, NOW())
        END AS AgeSeconds
     FROM telaio
     LEFT JOIN dato_rt ON dato_rt.IdTelaio = telaio.IdTelaio
     WHERE telaio.EnableTelaio = 1 AND telaio.IdTelaio<>100
     ORDER BY telaio.IdTelaio"
);

$summary = array('total' => 0, 'running' => 0, 'stopped' => 0, 'stale' => 0, 'offline' => 0, 'totalRpm' => 0, 'efficiencyTotal' => 0, 'efficiencyCount' => 0);
$alerts = array();
$looms = array();

// Warnings alanı bir bit maskesidir. Alarm adlarını, mevcut sistemin kendi
// warning + lingua tablolarından Türkçe olarak alırız; müşteriye ham kod gösterilmez.
$warningDefinitions = array();
$warningRows = $db->exec(
    "SELECT w.IdWarning, COALESCE(NULLIF(l.Frase, ''), w.codePhrase) AS Message
     FROM warning w
     LEFT JOIN lingua l ON l.CodePhrase = w.codePhrase AND l.IdLingua = 'tr'
     ORDER BY w.IdWarning"
);
foreach ($warningRows as $warningRow) {
    $warningDefinitions[(int) $warningRow['IdWarning']] = trim($warningRow['Message']);
}

// Üst menüdeki mevcut Uyarı Merkezi ile aynı kaynak: her aktif bit, ayrı
// bir uyarıdır. Böylece birden fazla aktif uyarı tek bir satıra sıkışmaz.
function warning_messages($warningValue, $definitions) {
    $warningValue = (int) $warningValue;
    if ($warningValue <= 0) {
        return array();
    }

    $bits = strrev(decbin($warningValue));
    $messages = array();
    for ($bit = 0; $bit < strlen($bits); $bit++) {
        if ($bits[$bit] !== '1') {
            continue;
        }
        if (isset($definitions[$bit]) && $definitions[$bit] !== '') {
            $messages[] = $definitions[$bit];
        } else {
            $messages[] = 'Tanımlanmamış tezgâh uyarısı';
        }
    }
    return array_values(array_unique($messages));
}

function warning_message($warningValue, $definitions) {
    return implode(' · ', warning_messages($warningValue, $definitions));
}

$productionDefinitions = array();
$productionRows = $db->exec(
    "SELECT sp.IdStatoProduzione, COALESCE(NULLIF(l.Frase, ''), sp.CodePhrase) AS Message
     FROM stato_produzione sp
     LEFT JOIN lingua l ON l.CodePhrase = sp.CodePhrase AND l.IdLingua = 'tr'"
);
foreach ($productionRows as $productionRow) {
    $productionDefinitions[(int) $productionRow['IdStatoProduzione']] = trim($productionRow['Message']);
}

foreach ($rows as $row) {
    $summary['total']++;
    $age = isset($row['AgeSeconds']) ? (int)$row['AgeSeconds'] : null;
    $online = isset($row['Online']) && (int)$row['Online'] === 1;
    $rpm = isset($row['Velocita']) ? (int)$row['Velocita'] : 0;
    $efficiency = isset($row['Resa']) ? (int)$row['Resa'] : 0;
    $warning = isset($row['Warnings']) ? (int)$row['Warnings'] : 0;
    $productionState = isset($row['StatoProduzione']) ? (int)$row['StatoProduzione'] : 0;
    $warningMessage = warning_message($warning, $warningDefinitions);
    $eventTime = isset($row['LastUpdate']) ? $row['LastUpdate'] : '';

    if (!$online) {
        $status = 'offline';
        $statusText = 'Çevrimdışı';
        $summary['offline']++;
        $alerts[] = array('level' => 'critical', 'kind' => 'Sistem', 'loom' => $row['IdTelaio'] . ' ' . $row['NomeTelaio'], 'message' => 'Tezgâh çevrimdışı görünüyor.', 'timestamp' => $eventTime);
    } elseif ($age === null || $age > 60) {
        $status = 'offline';
        $statusText = 'Çevrimdışı';
        $summary['offline']++;
        $alerts[] = array('level' => $age !== null && $age > 180 ? 'critical' : 'warning', 'kind' => 'Sistem', 'loom' => $row['IdTelaio'] . ' ' . $row['NomeTelaio'], 'message' => $age === null ? 'Henüz veri alınmadı.' : $age . ' saniyedir yeni veri yok.', 'timestamp' => $eventTime);
    } elseif ($rpm > 0) {
        $status = 'running';
        $statusText = 'Çalışıyor';
        $summary['running']++;
    } else {
        $status = 'stopped';
        $statusText = 'Duruyor';
        $summary['stopped']++;
    }

    // Bu iki bölüm, JSONmsg.php içindeki getEventsMonitor() ile aynı canlı
    // alarm listesidir: aktif uyarı bitleri ve üretim duruş/alarm durumu.
    if ($online) {
        foreach (warning_messages($warning, $warningDefinitions) as $message) {
            $alerts[] = array('level' => 'warning', 'kind' => 'Uyarı', 'loom' => $row['IdTelaio'] . ' ' . $row['NomeTelaio'], 'message' => $message, 'timestamp' => $eventTime);
        }
        if ($productionState !== 0) {
            $productionMessage = isset($productionDefinitions[$productionState]) && $productionDefinitions[$productionState] !== ''
                ? $productionDefinitions[$productionState]
                : 'Tanımlanmamış üretim alarmı';
            $alerts[] = array('level' => 'critical', 'kind' => 'Alarm', 'loom' => $row['IdTelaio'] . ' ' . $row['NomeTelaio'], 'message' => $productionMessage, 'timestamp' => $eventTime);
        }
    }
    if ($status === 'running') {
        $summary['totalRpm'] += $rpm;
        $summary['efficiencyTotal'] += $efficiency;
        $summary['efficiencyCount']++;
    }

    $looms[] = array(
        'id' => (int)$row['IdTelaio'],
        'name' => $row['NomeTelaio'],
        'status' => $status,
        'statusText' => $statusText,
        'rpm' => $rpm,
        'efficiency' => $efficiency,
        'lastUpdate' => $row['LastUpdate'],
        'ageSeconds' => $age,
        'warnings' => $warning,
        'warningMessage' => $warningMessage
    );
}

usort($alerts, function ($left, $right) {
    return strcmp((string) $right['timestamp'], (string) $left['timestamp']);
});

$summary['averageEfficiency'] = $summary['efficiencyCount'] > 0 ? round($summary['efficiencyTotal'] / $summary['efficiencyCount'], 1) : 0;
$summary['alertCount'] = count($alerts);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('generatedAt' => date('Y-m-d H:i:s'), 'summary' => $summary, 'alerts' => $alerts, 'looms' => $looms), JSON_UNESCAPED_UNICODE);

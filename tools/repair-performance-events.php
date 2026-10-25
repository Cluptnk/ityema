<?php
// CLI only. Dry run by default; source ilmsdb is always read-only.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$options = getopt('', array('config:', 'from:', 'to:', 'apply'));
try {
    if (empty($options['config']) || empty($options['from']) || empty($options['to'])) {
        throw new RuntimeException('Usage: php repair-performance-events.php --config=connectionDB.php --from=YYYY-MM-DD --to=YYYY-MM-DD [--apply]');
    }
    foreach (array('from', 'to') as $key) {
        $date = DateTime::createFromFormat('!Y-m-d', $options[$key]);
        if (!$date || $date->format('Y-m-d') !== $options[$key]) throw new RuntimeException('Invalid date: ' . $key);
    }
    if ($options['to'] < $options['from']) throw new RuntimeException('Invalid date range.');
    $config = file_get_contents($options['config']);
    foreach (array('dsn', 'user', 'pw') as $key) {
        if (!preg_match('/\$' . $key . '\s*=\s*\x27([^\x27]*)\x27/', $config, $match)) throw new RuntimeException('Cannot read connection settings.');
        $$key = $match[1];
    }
    if (!preg_match('/(?:^|;)dbname=ilmsdb(?:_dev)?(?:;|$)/', $dsn)) throw new RuntimeException('Expected ilmsdb or ilmsdb_dev configuration.');
    $dsn = preg_replace('/dbname=ilmsdb(?:_dev)?(?=;|$)/', 'dbname=ilmsdb_dev', $dsn);
    $db = new PDO($dsn, $user, $pw, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10));
    $values = array($options['from'] . ' 00:00:00', (new DateTime($options['to']))->modify('+1 day')->format('Y-m-d 00:00:00'));
    $join = ' FROM ilmsdb_dev.evento d INNER JOIN ilmsdb.evento p ON p.IdEvento=d.IdEvento AND p.Timestamp=d.Timestamp AND p.IdTelaio=d.IdTelaio AND p.NumeroSerie=d.NumeroSerie AND p.StatoProduzione=d.StatoProduzione';
    $where = ' WHERE d.Timestamp>=? AND d.Timestamp<? AND p.TempoEvento>=d.TempoEvento AND (p.TempoEvento<>d.TempoEvento OR p.Battute<>d.Battute OR p.LunghezzaDaInizio<>d.LunghezzaDaInizio)';
    $count = $db->prepare('SELECT COUNT(*)' . $join . $where);
    $count->execute($values);
    $affected = (int) $count->fetchColumn();
    echo json_encode(array('mode' => isset($options['apply']) ? 'apply' : 'dry-run', 'target' => 'ilmsdb_dev', 'matchingRows' => $affected)) . PHP_EOL;
    if (!isset($options['apply']) || !$affected) exit(0);
    $backup = 'performance_event_backup_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(3));
    $db->exec('CREATE TABLE ilmsdb_dev.' . $backup . ' LIKE ilmsdb_dev.evento');
    $db->beginTransaction();
    try {
        $save = $db->prepare('INSERT INTO ilmsdb_dev.' . $backup . ' SELECT d.*' . $join . $where);
        $save->execute($values);
        // Restrict updates to the exact keys captured in the backup.
        $update = $db->prepare('UPDATE ilmsdb_dev.evento d INNER JOIN ilmsdb_dev.' . $backup . ' b ON b.IdEvento=d.IdEvento AND b.Timestamp=d.Timestamp AND b.IdTelaio=d.IdTelaio INNER JOIN ilmsdb.evento p ON p.IdEvento=d.IdEvento AND p.Timestamp=d.Timestamp AND p.IdTelaio=d.IdTelaio AND p.NumeroSerie=d.NumeroSerie AND p.StatoProduzione=d.StatoProduzione SET d.TempoEvento=p.TempoEvento,d.Battute=p.Battute,d.LunghezzaDaInizio=p.LunghezzaDaInizio WHERE p.TempoEvento>=d.TempoEvento');
        $update->execute();
        $changed = $update->rowCount();
        $db->commit();
        echo json_encode(array('updatedRows' => $changed, 'backupTable' => 'ilmsdb_dev.' . $backup)) . PHP_EOL;
    } catch (Throwable $error) { $db->rollBack(); throw $error; }
} catch (Throwable $error) {
    // Do not expose connection credentials in exception details.
    fwrite(STDERR, $error instanceof PDOException ? 'Database operation failed (code ' . $error->getCode() . ').' . PHP_EOL : $error->getMessage() . PHP_EOL);
    exit(1);
}

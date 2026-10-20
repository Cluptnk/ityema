$ErrorActionPreference = 'Stop'
$script:backupRoot = 'C:\ILCA\Backups'
$script:databaseFolder = Join-Path $script:backupRoot 'database'
$script:siteFolder = Join-Path $script:backupRoot 'site'
$script:statusFile = Join-Path $script:backupRoot 'last-backup.json'
$script:connectionFile = 'C:\Apache24\htdocs-dev\connectionDB.php'
$script:siteRoot = 'C:\Apache24\htdocs-dev'
$script:dumpTool = 'C:\Program Files (x86)\MySQL\MySQL Server 5.7\bin\mysqldump.exe'
$script:mysqlTool = 'C:\Program Files (x86)\MySQL\MySQL Server 5.7\bin\mysql.exe'

function Get-ConfigValue([string]$content, [string]$name) {
    $pattern = '\$' + [regex]::Escape($name) + "\s*=\s*'([^']*)'"
    $match = [regex]::Match($content, $pattern)
    if (-not $match.Success) { throw "Bağlantı yapılandırmasında $name bulunamadı." }
    return $match.Groups[1].Value
}
function Write-BackupStatus([bool]$success, [string]$message, [string]$databaseFile = '', [string]$siteFile = '') {
    $result = [ordered]@{ Success=$success; FinishedAt=(Get-Date).ToString('yyyy-MM-dd HH:mm:ss'); Message=$message; DatabaseFile=$databaseFile; DatabaseBytes=0; SiteFile=$siteFile; SiteBytes=0 }
    if ($databaseFile -and (Test-Path -LiteralPath $databaseFile)) { $result.DatabaseBytes=(Get-Item -LiteralPath $databaseFile).Length }
    if ($siteFile -and (Test-Path -LiteralPath $siteFile)) { $result.SiteBytes=(Get-Item -LiteralPath $siteFile).Length }
    [System.IO.File]::WriteAllText($script:statusFile, ($result | ConvertTo-Json), (New-Object System.Text.UTF8Encoding($false)))
}

try {
    New-Item -ItemType Directory -Force -Path $script:databaseFolder,$script:siteFolder | Out-Null
    if (-not (Test-Path -LiteralPath $script:connectionFile)) { throw 'Site bağlantı dosyası bulunamadı.' }
    if (-not (Test-Path -LiteralPath $script:dumpTool)) { throw 'mysqldump aracı bulunamadı.' }
    if (-not (Test-Path -LiteralPath $script:mysqlTool)) { throw 'mysql istemcisi bulunamadı.' }
    $config = Get-Content -LiteralPath $script:connectionFile -Raw
    $dsn = Get-ConfigValue $config 'dsn'; $user = Get-ConfigValue $config 'user'; $password = Get-ConfigValue $config 'pw'
    $dsnMatch = [regex]::Match($dsn, 'mysql:host=([^;]+);port=([0-9]+);dbname=([^;]+)')
    if (-not $dsnMatch.Success) { throw 'MySQL bağlantı bilgisi okunamadı.' }
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $databaseFile = Join-Path $script:databaseFolder ("perdekor-db-$stamp.sql")
    $siteFile = Join-Path $script:siteFolder ("perdekor-site-$stamp.zip")
    $env:MYSQL_PWD = $password
    $connectionArgs = @("--host=$($dsnMatch.Groups[1].Value)","--port=$($dsnMatch.Groups[2].Value)","--user=$user")
    $databaseName = $dsnMatch.Groups[3].Value
    & $script:mysqlTool @connectionArgs $databaseName -e 'SELECT 1' | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'MySQL bağlantı testi başarısız oldu.' }
    $dumpErrorFile = "$databaseFile.error.txt"
    & $script:dumpTool @connectionArgs '--single-transaction' '--quick' '--routines' '--events' '--triggers' '--add-drop-table' '--set-gtid-purged=OFF' $databaseName 1> $databaseFile 2> $dumpErrorFile
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $databaseFile) -or (Get-Item -LiteralPath $databaseFile).Length -lt 1024) { throw 'MySQL tam yedeği oluşturulamadı.' }
    Remove-Item -LiteralPath $dumpErrorFile -Force -ErrorAction SilentlyContinue
    Compress-Archive -Path (Join-Path $script:siteRoot '*') -DestinationPath $siteFile -CompressionLevel Optimal -Force
    if (-not (Test-Path -LiteralPath $siteFile) -or (Get-Item -LiteralPath $siteFile).Length -lt 1024) { throw 'Site yedeği oluşturulamadı.' }
    Get-ChildItem -LiteralPath $script:databaseFolder -Filter '*.sql' | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } | Remove-Item -Force
    Get-ChildItem -LiteralPath $script:siteFolder -Filter '*.zip' | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } | Remove-Item -Force
    Write-BackupStatus $true 'Tam veritabanı ve site yedeği başarıyla oluşturuldu.' $databaseFile $siteFile
    exit 0
}
catch {
    New-Item -ItemType Directory -Force -Path $script:backupRoot | Out-Null
    Write-BackupStatus $false $_.Exception.Message
    exit 1
}
finally { Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue }

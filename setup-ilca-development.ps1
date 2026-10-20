[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$apacheRoot = 'C:\Apache24'
$htdocsSource = Join-Path $apacheRoot 'htdocs'
$htdocsDevelopment = Join-Path $apacheRoot 'htdocs-dev'
$connectionFile = Join-Path $htdocsSource 'connectionDB.php'
$httpdConfig = Join-Path $apacheRoot 'conf\httpd.conf'
$developmentConfig = Join-Path $apacheRoot 'conf\extra\httpd-ilca-development.conf'
$httpd = Join-Path $apacheRoot 'bin\httpd.exe'
$mysqlBin = 'C:\Program Files (x86)\MySQL\MySQL Server 5.7\bin'
$mysql = Join-Path $mysqlBin 'mysql.exe'
$mysqldump = Join-Path $mysqlBin 'mysqldump.exe'
$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'

foreach ($path in @($apacheRoot, $htdocsSource, $connectionFile, $httpdConfig, $httpd, $mysql, $mysqldump)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required path was not found: $path"
    }
}

$connection = Get-Content -LiteralPath $connectionFile -Raw
$userMatch = [regex]::Match($connection, '\$user\s*=\s*''([^'']+)''')
$passwordMatch = [regex]::Match($connection, '\$pw\s*=\s*''([^'']+)''')
$dsnMatch = [regex]::Match($connection, '\$dsn\s*=\s*''([^'']+)''')

if (-not ($userMatch.Success -and $passwordMatch.Success -and $dsnMatch.Success)) {
    throw 'The application MySQL configuration could not be parsed.'
}

$databaseUser = $userMatch.Groups[1].Value
$env:MYSQL_PWD = $passwordMatch.Groups[1].Value
$sourceDatabase = ([regex]::Match($dsnMatch.Groups[1].Value, '(?i)(?:^|;)dbname=([^;]+)')).Groups[1].Value

if ($sourceDatabase -notmatch '^[A-Za-z0-9_]+$') {
    throw 'The production database name is missing or contains unsupported characters.'
}

$developmentDatabase = "${sourceDatabase}_dev"
$developmentDsn = $dsnMatch.Groups[1].Value -replace ('(?i)dbname=' + [regex]::Escape($sourceDatabase)), "dbname=$developmentDatabase"

if (Test-Path -LiteralPath $htdocsDevelopment) {
    throw "Development document root already exists: $htdocsDevelopment"
}

try {
    $databaseExists = & $mysql '--default-character-set=utf8' '--protocol=tcp' '--connect-timeout=10' '--host=127.0.0.1' "--user=$databaseUser" '--batch' '--skip-column-names' '-e' "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$developmentDatabase'" 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw 'Could not query MySQL database metadata with the application account.'
    }
    if ($databaseExists -contains $developmentDatabase) {
        throw "Development database already exists: $developmentDatabase"
    }

    & $mysql '--default-character-set=utf8' '--protocol=tcp' '--connect-timeout=10' '--host=127.0.0.1' "--user=$databaseUser" '-e' "CREATE DATABASE ``$developmentDatabase`` CHARACTER SET utf8 COLLATE utf8_general_ci"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not create development database: $developmentDatabase"
    }

    $copyCommand = '"{0}" --default-character-set=utf8 --protocol=tcp --host=127.0.0.1 --user="{1}" --single-transaction --quick --routines --events --triggers --hex-blob --no-tablespaces "{2}" | "{3}" --default-character-set=utf8 --protocol=tcp --connect-timeout=10 --host=127.0.0.1 --user="{1}" "{4}"' -f $mysqldump, $databaseUser, $sourceDatabase, $mysql, $developmentDatabase
    & cmd.exe /d /c $copyCommand
    if ($LASTEXITCODE -ne 0) {
        throw 'Could not copy production data to the development database.'
    }

    Copy-Item -LiteralPath $htdocsSource -Destination $htdocsDevelopment -Recurse
    $developmentConnectionFile = Join-Path $htdocsDevelopment 'connectionDB.php'
    $developmentConnection = Get-Content -LiteralPath $developmentConnectionFile -Raw
    $developmentConnection = $developmentConnection -replace [regex]::Escape($dsnMatch.Groups[1].Value), $developmentDsn
    Set-Content -LiteralPath $developmentConnectionFile -Value $developmentConnection -Encoding UTF8

    $configBackup = "$httpdConfig.$timestamp.bak"
    Copy-Item -LiteralPath $httpdConfig -Destination $configBackup

    @"
# ILCA development environment. Created $timestamp.
Listen 81

<VirtualHost *:81>
    ServerName 192.168.2.5:81
    DocumentRoot "`${SRVROOT}/htdocs-dev"

    <Directory "`${SRVROOT}/htdocs-dev">
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    ErrorLog "logs/ilca-development-error.log"
    CustomLog "logs/ilca-development-access.log" common
</VirtualHost>
"@ | Set-Content -LiteralPath $developmentConfig -Encoding ASCII

    $includeLine = 'Include conf/extra/httpd-ilca-development.conf'
    if (-not ((Get-Content -LiteralPath $httpdConfig) -contains $includeLine)) {
        Add-Content -LiteralPath $httpdConfig -Value "`r`n# ILCA development environment`r`n$includeLine"
    }

    & $httpd '-t'
    if ($LASTEXITCODE -ne 0) {
        Copy-Item -LiteralPath $configBackup -Destination $httpdConfig -Force
        throw 'Apache configuration validation failed. The main configuration was restored.'
    }

    & $httpd '-k' 'restart'
    if ($LASTEXITCODE -ne 0) {
        Copy-Item -LiteralPath $configBackup -Destination $httpdConfig -Force
        & $httpd '-k' 'restart'
        throw 'Apache restart failed. The previous main configuration was restored and restarted.'
    }

    [pscustomobject]@{
        DevelopmentUrl = 'http://192.168.2.5:81/'
        DevelopmentRoot = $htdocsDevelopment
        DevelopmentDatabase = $developmentDatabase
        ApacheConfigBackup = $configBackup
    } | Format-List
}
finally {
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
}

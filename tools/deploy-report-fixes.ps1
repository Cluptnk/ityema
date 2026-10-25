[CmdletBinding()]
param([switch]$Apply)
$ErrorActionPreference='Stop'
$workspace=Split-Path $PSScriptRoot -Parent
$sourceRoot=Join-Path $workspace 'site-dashboard'
$targetRoot='\\192.168.2.5\c\Apache24\htdocs-dev'
$changed=@(& git -c core.safecrlf=false -C $workspace diff --name-only -- site-dashboard)
$changed+=@(& git -C $workspace ls-files --others --exclude-standard -- site-dashboard)
$relativeFiles=@($changed | Sort-Object -Unique | ForEach-Object {$_ -replace '^site-dashboard/',''})
if(!$relativeFiles.Count){throw 'No report files to deploy.'}
$entries=@()
foreach($relative in $relativeFiles){
    $source=[IO.Path]::GetFullPath((Join-Path $sourceRoot $relative))
    $target=[IO.Path]::GetFullPath((Join-Path $targetRoot $relative))
    if(!$source.StartsWith($sourceRoot+[IO.Path]::DirectorySeparatorChar,[StringComparison]::OrdinalIgnoreCase) -or !$target.StartsWith($targetRoot+[IO.Path]::DirectorySeparatorChar,[StringComparison]::OrdinalIgnoreCase)){throw 'Path escaped report roots.'}
    if(!(Test-Path -LiteralPath $source -PathType Leaf)){throw "Source missing: $relative"}
    $exists=Test-Path -LiteralPath $target -PathType Leaf
    $oldHash=if($exists){(Get-FileHash -LiteralPath $target -Algorithm SHA256).Hash}else{$null}
    $newHash=(Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash
    if($exists -and $oldHash -eq $newHash){continue}
    $entries+= [pscustomobject]@{File=$relative;Source=$source;Target=$target;Existed=$exists;OldHash=$oldHash;NewHash=$newHash}
}
if(!$entries.Count){Write-Output 'Server report files already match.';return}
if(!$Apply){$entries | Select-Object File,Existed;return}
$stamp=Get-Date -Format 'yyyyMMdd-HHmmss'
$backupRoot=Join-Path '\\192.168.2.5\c\ILCA\Backups' ('report-fix-'+$stamp)
New-Item -ItemType Directory -Path $backupRoot | Out-Null
foreach($entry in $entries){
    $old=Join-Path (Join-Path $backupRoot 'original') $entry.File
    $staged=Join-Path (Join-Path $backupRoot 'updated') $entry.File
    New-Item -ItemType Directory -Path (Split-Path $staged) -Force | Out-Null
    Copy-Item -LiteralPath $entry.Source -Destination $staged
    if($entry.Existed){New-Item -ItemType Directory -Path (Split-Path $old) -Force | Out-Null;Copy-Item -LiteralPath $entry.Target -Destination $old}
}
$entries | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath (Join-Path $backupRoot 'manifest.json') -Encoding UTF8
$installed=@()
try {
    # Install dependencies first. API version checks reject stale clients.
    $order=$entries | Sort-Object @{Expression={if($_.File -in @('performance_calculation.php','report_engine.php','report_api.php','backup_validation.php','js/report-quality.js')){0}elseif($_.File -like '*_data.php'){1}elseif($_.File -like 'js/*'){2}else{3}}},File
    foreach($entry in $order){
        if($entry.Existed -and (Get-FileHash -LiteralPath $entry.Target -Algorithm SHA256).Hash -ne $entry.OldHash){throw "Concurrent server modification: $($entry.File)"}
        $temp=Join-Path (Split-Path $entry.Target) ('.report-stage-'+$stamp+'-'+[IO.Path]::GetFileName($entry.Target))
        Copy-Item -LiteralPath (Join-Path (Join-Path $backupRoot 'updated') $entry.File) -Destination $temp
        if($entry.Existed){[IO.File]::Replace($temp,$entry.Target,[System.Management.Automation.Language.NullString]::Value)}else{[IO.File]::Move($temp,$entry.Target)}
        $installed+=$entry
        if((Get-FileHash -LiteralPath $entry.Target -Algorithm SHA256).Hash -ne $entry.NewHash){throw "Deployment hash mismatch: $($entry.File)"}
    }
}catch{
    if($temp -and (Test-Path -LiteralPath $temp)){Remove-Item -LiteralPath $temp}
    foreach($entry in $installed){
        if($entry.Existed){Copy-Item -LiteralPath (Join-Path (Join-Path $backupRoot 'original') $entry.File) -Destination $entry.Target}
        elseif((Get-FileHash -LiteralPath $entry.Target -Algorithm SHA256).Hash -eq $entry.NewHash){Remove-Item -LiteralPath $entry.Target}
    }
    throw
}
[pscustomobject]@{Files=$entries.Count;Backup=$backupRoot;VerifiedHashes=$true} | ConvertTo-Json

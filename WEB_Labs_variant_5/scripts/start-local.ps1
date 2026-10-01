param([string]$PhpPath='php',[string]$NodePath='node',[string]$ExtensionDir='')
$ErrorActionPreference='Stop'
$taskRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$taskData=Join-Path $taskRoot 'data'
New-Item -ItemType Directory -Force -Path $taskData | Out-Null
$pidFile=Join-Path $taskData 'processes.json'
foreach($port in @(8087,8091,8092)){if(Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue){throw "Port $port is occupied. Existing processes were not changed."}}
$phpExe=(Get-Command $PhpPath -ErrorAction Stop).Source
$nodeExe=(Get-Command $NodePath -ErrorAction Stop).Source
$phpOptions=@();if($ExtensionDir){$phpOptions=@('-d',('extension_dir="'+$ExtensionDir+'"'),'-d','extension=pdo_sqlite','-d','extension=sqlite3','-d','extension=openssl')}
$records=@()
function Start-LabProcess($exe,$arguments,$label){$p=Start-Process -FilePath $exe -ArgumentList $arguments -WorkingDirectory $taskRoot -WindowStyle Hidden -RedirectStandardOutput (Join-Path $taskData "$label-out.log") -RedirectStandardError (Join-Path $taskData "$label-error.log") -PassThru;$script:records+=@{id=$p.Id;started=$p.StartTime.ToUniversalTime().ToString('o');executable=$exe;label=$label}}
try{
Start-LabProcess $nodeExe @('services/sensor-api.mjs') 'sensor'
Start-LabProcess $nodeExe @('services/supplier-api.mjs') 'supplier'
Start-LabProcess $phpExe ($phpOptions+@('-S','127.0.0.1:8087','router.php')) 'web'
$env:PHP_BIN=$phpExe
$env:PHP_ARGS=($phpOptions -join '|').Replace('"','')
Start-LabProcess $nodeExe @('scripts/backup-loop.mjs') 'backup'
$records | ConvertTo-Json | Set-Content -LiteralPath $pidFile -Encoding utf8
Start-Sleep -Seconds 2
if(!(Invoke-RestMethod 'http://127.0.0.1:8087/api.php?resource=health').ok){throw 'Server failed health check'}
Write-Output 'Open http://127.0.0.1:8087/ in your browser. Stop with scripts/stop-local.ps1.'
}catch{foreach($record in $records){Stop-Process -Id $record.id -ErrorAction SilentlyContinue};throw}

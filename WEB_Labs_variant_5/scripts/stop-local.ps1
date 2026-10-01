$ErrorActionPreference='Stop'
$taskRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$pidFile=Join-Path $taskRoot 'data/processes.json'
if(!(Test-Path -LiteralPath $pidFile)){Write-Output 'No recorded lab processes.';exit}
foreach($record in (Get-Content -LiteralPath $pidFile -Raw | ConvertFrom-Json)){
$p=Get-Process -Id $record.id -ErrorAction SilentlyContinue
if($p -and $p.StartTime.ToUniversalTime().Ticks -eq ([datetime]$record.started).ToUniversalTime().Ticks -and $p.Path -eq $record.executable){Stop-Process -Id $p.Id;Write-Output ("Stopped lab process: "+$record.label)}
}
Remove-Item -LiteralPath $pidFile

# Local convenience launcher for this PC. Not part of the GitHub source upload.
$ErrorActionPreference='Stop'
$taskProject=Join-Path $PSScriptRoot 'WEB_Labs_variant_5'
$taskPhp=Join-Path $env:USERPROFILE 'Documents\Codex\2026-09-27\new-chat\work\runtime\php\php.exe'
$taskNode='C:\Program Files\nodejs\node.exe'
if(!(Test-Path -LiteralPath $taskProject)){throw 'Розпакуй архів у цю папку, щоб поруч була WEB_Labs_variant_5.'}
if(!(Test-Path -LiteralPath $taskPhp)){throw 'PHP не знайдено. Дивись інструкцію README.md у папці лабораторних.'}
if(Get-NetTCPConnection -State Listen -LocalPort 8087 -ErrorAction SilentlyContinue){
 $taskReady=Invoke-RestMethod 'http://127.0.0.1:8087/api.php?resource=health'
 if(!$taskReady.ok -or $taskReady.database -ne 'SQLite'){throw 'Порт 8087 зайнятий іншим застосунком.'}
}else{& (Join-Path $taskProject 'scripts/start-local.ps1') -PhpPath $taskPhp -ExtensionDir (Join-Path (Split-Path $taskPhp) 'ext') -NodePath $taskNode}
Start-Process 'http://127.0.0.1:8087/'

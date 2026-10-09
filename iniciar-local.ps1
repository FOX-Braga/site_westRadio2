# Executa o servidor local com os limites equivalentes aos da hospedagem (.user.ini).
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
& php -d post_max_size=30M -d upload_max_filesize=25M -d memory_limit=256M -d max_execution_time=120 -d display_errors=0 -d log_errors=1 -S 127.0.0.1:8000 -t $PSScriptRoot (Join-Path $PSScriptRoot 'router.php')
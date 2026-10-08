param([int]$Port = 8010)
$ErrorActionPreference = 'Stop'
$epRoot = $PSScriptRoot
$epPhp = Join-Path $env:TEMP 'exampractice-php84\php.exe'
if (-not (Test-Path -LiteralPath $epPhp)) {
    $epDetected = Get-Command php -ErrorAction SilentlyContinue
    if ($epDetected) { $epPhp = $epDetected.Source }
    else { throw 'PHP 8.4+ is required. Install PHP or restore the isolated runtime, then try again.' }
}
$epVersion = & $epPhp -r 'echo PHP_VERSION_ID;'
if ([int]$epVersion -lt 80400) { throw 'This application requires PHP 8.4 or newer.' }
Set-Location -LiteralPath $epRoot
Write-Host "ExamPractice: http://127.0.0.1:$Port"
& $epPhp artisan serve --host=127.0.0.1 "--port=$Port" --no-reload

#Requires -Version 5.1
<#
  Menjalankan CeLOE LMS secara lokal TANPA Docker (PHP + SQLite).
  Buka http://localhost:8000 setelah server berjalan. Ctrl+C untuk berhenti.
  -ResetDb : hapus dan isi ulang data demo.
#>
param([switch]$ResetDb)

$php = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $php) { $php = "$env:USERPROFILE\.gemini\antigravity\brain\760620b4-efad-4d1f-acbe-1c42b82c7cab\scratch\php\php.exe" }
if (-not (Test-Path $php)) { throw "PHP tidak ditemukan. Install PHP 8.4 atau tambahkan ke PATH." }

Set-Location "$PSScriptRoot\src"
if ($ResetDb) { & $php artisan migrate:fresh --seed --force }
Write-Host "CeLOE LMS: http://localhost:8000" -ForegroundColor Green
& $php artisan serve --host=127.0.0.1 --port=8000

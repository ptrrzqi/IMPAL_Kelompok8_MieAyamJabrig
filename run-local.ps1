#Requires -Version 5.1
<#
  Menjalankan CeLOE LMS secara lokal TANPA Docker (PHP + SQLite).
  Otomatis menangani setup pertama kali (Composer, .env SQLite, APP_KEY, Vite build, migrate & seed).
  Buka http://localhost:8000 setelah server berjalan. Ctrl+C untuk berhenti.
  -ResetDb : hapus dan isi ulang data demo.
#>
param([switch]$ResetDb)

$ErrorActionPreference = 'Stop'

function Invoke-Checked {
    param(
        [string]$FilePath,
        [string[]]$Arguments,
        [string]$ErrorMessage
    )
    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw $ErrorMessage
    }
}

# 1. Cari PHP (>= 8.3)
$php = $null
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if ($phpCmd) { $php = $phpCmd.Source }

if (-not $php) {
    $candidates = @()
    if (Test-Path "C:\laragon\bin\php") {
        $candidates += Get-ChildItem "C:\laragon\bin\php\php-*\php.exe" -ErrorAction SilentlyContinue |
            Sort-Object FullName -Descending | Select-Object -ExpandProperty FullName
    }
    $candidates += @(
        "C:\xampp\php\php.exe",
        "$env:USERPROFILE\.gemini\antigravity\brain\760620b4-efad-4d1f-acbe-1c42b82c7cab\scratch\php\php.exe"
    )
    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            $php = $candidate
            break
        }
    }
}

if (-not $php -or -not (Test-Path $php)) {
    throw "PHP tidak ditemukan. Install PHP 8.3+ (disarankan 8.4) dan tambahkan ke PATH Windows."
}

$phpVersion = (& $php -r "echo PHP_VERSION;")
& $php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    throw "PHP versi $phpVersion terlalu lama (terdeteksi di $php). Laravel 13 membutuhkan PHP >= 8.3."
}

& $php -r "exit((extension_loaded('pdo_sqlite') && extension_loaded('sqlite3')) ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    throw "Ekstensi PHP 'pdo_sqlite' dan 'sqlite3' belum aktif. Aktifkan di php.ini (hapus tanda ';' pada extension=pdo_sqlite dan extension=sqlite3)."
}

Push-Location "$PSScriptRoot\src"
try {
    # 2. Install dependensi PHP (Composer) jika vendor belum ada
    if (-not (Test-Path "vendor\autoload.php")) {
        Write-Host "[Setup] Menginstall dependensi PHP (composer install)..." -ForegroundColor Cyan

        $composerCmd = Get-Command composer -ErrorAction SilentlyContinue
        if ($composerCmd) {
            Invoke-Checked -FilePath $composerCmd.Source -Arguments @("install", "--no-interaction") -ErrorMessage "composer install gagal."
        }
        elseif (Test-Path "C:\ProgramData\ComposerSetup\bin\composer.bat") {
            Invoke-Checked -FilePath "C:\ProgramData\ComposerSetup\bin\composer.bat" -Arguments @("install", "--no-interaction") -ErrorMessage "composer install gagal."
        }
        else {
            $composerPhar = Join-Path $env:TEMP "composer.phar"
            if (-not (Test-Path $composerPhar)) {
                Write-Host "[Setup] Composer tidak ditemukan di PATH. Mengunduh composer.phar..." -ForegroundColor Yellow
                [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
                Invoke-WebRequest -Uri "https://getcomposer.org/composer-stable.phar" -OutFile $composerPhar -UseBasicParsing
            }
            Invoke-Checked -FilePath $php -Arguments @($composerPhar, "install", "--no-interaction") -ErrorMessage "composer install gagal."
        }
    }

    # 3. Siapkan .env lokal (SQLite) jika belum ada
    if (-not (Test-Path ".env")) {
        Write-Host "[Setup] Membuat file .env untuk mode lokal (SQLite)..." -ForegroundColor Cyan
        $envContent = Get-Content ".env.example" -Raw
        $envContent = $envContent -replace '(?m)^APP_URL=.*$', 'APP_URL=http://localhost:8000'
        $envContent = $envContent -replace '(?m)^DB_CONNECTION=.*$', 'DB_CONNECTION=sqlite'
        $envContent = $envContent -replace '(?m)^(DB_(?:HOST|PORT|DATABASE|USERNAME|PASSWORD)=.*)$', '# $1'
        [System.IO.File]::WriteAllText((Join-Path (Get-Location) ".env"), $envContent, (New-Object System.Text.UTF8Encoding $false))
    }

    # 4. Generate APP_KEY jika masih kosong
    $currentEnv = Get-Content ".env" -Raw
    if ($currentEnv -match '(?m)^APP_KEY=\s*$') {
        Write-Host "[Setup] Membuat APP_KEY..." -ForegroundColor Cyan
        Invoke-Checked -FilePath $php -Arguments @("artisan", "key:generate", "--force") -ErrorMessage "Gagal membuat APP_KEY."
    }

    # 5. Build aset frontend (Vite) jika public/build belum ada
    if (-not (Test-Path "public\build\manifest.json")) {
        $npmCmd = Get-Command npm -ErrorAction SilentlyContinue
        if (-not $npmCmd) {
            throw "Node.js / npm tidak ditemukan, padahal aset frontend (public/build) belum di-build. Install Node.js LTS (https://nodejs.org) lalu jalankan ulang script ini."
        }
        if (-not (Test-Path "node_modules")) {
            Write-Host "[Setup] Menginstall dependensi frontend (npm install)..." -ForegroundColor Cyan
            Invoke-Checked -FilePath $npmCmd.Source -Arguments @("install") -ErrorMessage "npm install gagal."
        }
        Write-Host "[Setup] Mem-build aset frontend (npm run build)..." -ForegroundColor Cyan
        Invoke-Checked -FilePath $npmCmd.Source -Arguments @("run", "build") -ErrorMessage "npm run build gagal."
    }

    # 6. Siapkan database SQLite & data demo
    $sqlitePath = "database\database.sqlite"
    $isNewDb = -not (Test-Path $sqlitePath)
    if ($isNewDb) {
        Write-Host "[Setup] Membuat database SQLite & mengisi data demo..." -ForegroundColor Cyan
        New-Item -ItemType File -Path $sqlitePath -Force | Out-Null
    }

    if ($ResetDb -or $isNewDb) {
        Invoke-Checked -FilePath $php -Arguments @("artisan", "migrate:fresh", "--seed", "--force") -ErrorMessage "Migrasi & seeding database gagal."
    }

    if (-not (Test-Path "public\storage")) {
        & $php artisan storage:link 2>$null | Out-Null
    }

    Write-Host "CeLOE LMS: http://localhost:8000" -ForegroundColor Green
    & $php artisan serve --host=127.0.0.1 --port=8000
}
finally {
    Pop-Location
}

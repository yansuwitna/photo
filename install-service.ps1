# PHOTOBOOTH PRO - PowerShell Setup & Installer Script
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "         PHOTOBOOTH PRO - INSTALASI SISTEM LOKAL         " -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

$baseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $baseDir

Write-Host "[1/6] Memeriksa Database MySQL..." -ForegroundColor Green
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS photobooth_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Write-Host "[2/6] Menjalankan Migration & Database Seeder..." -ForegroundColor Green
php artisan migrate:fresh --seed --force

Write-Host "[3/6] Menghubungkan Storage Symlink..." -ForegroundColor Green
php artisan storage:link

Write-Host "[4/6] Melakukan Kompilasi Aset Frontend (Vite)..." -ForegroundColor Green
npm run build

Write-Host "[5/6] Menguji Abstraksi Kamera & Printer..." -ForegroundColor Green
php tests\test_lifecycle.php

Write-Host "[6/6] Membuat Pintasan Desktop PHOTOBOOTH PRO..." -ForegroundColor Green
$WshShell = New-Object -ComObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut("$([Environment]::GetFolderPath('Desktop'))\PHOTOBOOTH PRO.lnk")
$Shortcut.TargetPath = "$baseDir\start-photobooth.bat"
$Shortcut.WorkingDirectory = "$baseDir"
$Shortcut.Description = "PHOTOBOOTH PRO Professional System"
$Shortcut.Save()

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   INSTALASI BERHASIL! Klik ganda pintasan di Desktop.   " -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor Cyan
@echo off
title PhotoBooth Pro - Web Print Station (Silent Print Launcher)
color 0b

echo ======================================================================
echo           PHOTOBOOTH PRO - WEB PRINT STATION (SILENT MODE)
echo ======================================================================
echo.
echo Mode Silent Kiosk Print memungkinkan komputer ini mencetak secara
echo 100%% otomatis ke Printer Default Windows tanpa pop-up dialog print!
echo.

:: URL Target Server PhotoBooth
set "TARGET_URL=https://photo.smkn1abang.sch.id/print-station?booth=STAND-01"

:: Cek apakah pengguna memasukkan parameter URL/Stand custom (contoh: start-print-station.bat STAND-02)
if not "%~1"=="" (
    echo %~1 | findstr /i "http" >nul
    if errorlevel 1 (
        set "TARGET_URL=https://photo.smkn1abang.sch.id/print-station?booth=%~1"
    ) else (
        set "TARGET_URL=%~1"
    )
)

echo Menghubungkan ke : %TARGET_URL%
echo.

:: 1. Cari path instalasi Google Chrome
set "BROWSER_EXE="
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_EXE=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
) else if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_EXE=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
) else if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_EXE=%LocalAppData%\Google\Chrome\Application\chrome.exe"
)

:: 2. Jika Chrome tidak ada, gunakan Microsoft Edge (bawaan Windows 10/11)
if "%BROWSER_EXE%"=="" (
    if exist "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" (
        set "BROWSER_EXE=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe"
    ) else if exist "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" (
        set "BROWSER_EXE=%ProgramFiles%\Microsoft\Edge\Application\msedge.exe"
    )
)

:: 3. Eksekusi Browser dengan parameter silent kiosk printing
if not "%BROWSER_EXE%"=="" (
    echo [OK] Browser terdeteksi: "%BROWSER_EXE%"
    echo [OK] Menjalankan Print Station dengan flag --kiosk-printing...
    echo.
    echo Jendela browser akan terbuka sekarang.
    echo Jangan tutup jendela browser selama stand aktif beroperasi.
    start "" "%BROWSER_EXE%" --kiosk-printing --app="%TARGET_URL%"
) else (
    echo [INFO] Menjalankan perintah global chrome.exe...
    start chrome.exe --kiosk-printing --app="%TARGET_URL%" 2>nul || (
        echo [INFO] Menjalankan perintah global msedge.exe...
        start msedge.exe --kiosk-printing --app="%TARGET_URL%"
    )
)

echo.
echo Selesai. Print Station sedang berjalan di latar depan.
timeout /t 5 >nul
exit

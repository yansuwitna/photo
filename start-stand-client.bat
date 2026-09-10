@echo off
title PHOTOBOOTH PRO - CLIENT STAND LAUNCHER
color 0b
echo ========================================================
echo        PHOTOBOOTH PRO - CLIENT STAND LAUNCHER (LAN)     
echo ========================================================
echo.
set /p SERVER_IP="Masukkan IP Laptop Server Pusat [Default: 192.168.1.100]: "
if "%SERVER_IP%"=="" set SERVER_IP=192.168.1.100

set /p STAND_ID="Masukkan ID Stand ini (misal: STAND-02, STAND-03) [Default: STAND-02]: "
if "%STAND_ID%"=="" set STAND_ID=STAND-02

echo.
echo Menghubungkan ke Server: http://%SERVER_IP%:8000
echo Mengatur Stand sebagai: %STAND_ID%
echo.

echo [1/2] Membuka Auto-Print Background untuk %STAND_ID%...
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing --app="http://%SERVER_IP%:8000/print-station?booth=%STAND_ID%"
) else if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --kiosk-printing --app="http://%SERVER_IP%:8000/print-station?booth=%STAND_ID%"
)
timeout /t 1 /nobreak >nul

echo [2/2] Membuka Layar Kiosk Fullscreen untuk %STAND_ID%...
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --app="http://%SERVER_IP%:8000/?booth=%STAND_ID%" --disable-pinch
) else if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --kiosk "http://%SERVER_IP%:8000/?booth=%STAND_ID%" --edge-kiosk-type=fullscreen
) else (
    start http://%SERVER_IP%:8000/?booth=%STAND_ID%
)

echo.
echo %STAND_ID% SIAP DIGUNAKAN DENGAN AUTO-PRINT DEDICATED!
echo.
pause

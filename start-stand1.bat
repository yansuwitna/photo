@echo off
title PHOTOBOOTH PRO - STAND 1 LAUNCHER
color 0a
echo ========================================================
echo             PHOTOBOOTH PRO - STAND 1 (HOST + PRINT)     
echo ========================================================
echo.
echo [1/4] Memeriksa Layanan Database MySQL...
net start mysql >nul 2>&1

echo [2/4] Memulai Server PHP Backend...
start /b php artisan serve --host=0.0.0.0 --port=8000
timeout /t 2 /nobreak >nul

echo [3/4] Membuka Auto-Print Background untuk STAND-01...
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing --app="http://localhost:8000/print-station?booth=STAND-01"
) else if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --kiosk-printing --app="http://localhost:8000/print-station?booth=STAND-01"
)
timeout /t 1 /nobreak >nul

echo [4/4] Membuka Layar Foto Kiosk (STAND-01)...
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --app="http://localhost:8000/?booth=STAND-01" --disable-pinch
) else if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --kiosk "http://localhost:8000/?booth=STAND-01" --edge-kiosk-type=fullscreen
) else (
    start http://localhost:8000/?booth=STAND-01
)

echo.
echo STAND 1 BERJALAN DENGAN AUTO-PRINT MANDIRI!
echo.
pause

@echo off
title PHOTOBOOTH PRO - Studio Launcher
color 0b
echo ========================================================
echo               PHOTOBOOTH PRO STUDIO HOST                
echo ========================================================
echo.
echo [1/3] Memeriksa Layanan Database MySQL...
net start mysql >nul 2>&1
echo [2/3] Memulai Server PHP Backend PHOTOBOOTH PRO...
start /b php artisan serve --host=0.0.0.0 --port=8000
timeout /t 2 /nobreak >nul
echo [3/3] Membuka Mode Kiosk Layar Sentuh...
echo.
echo Aplikasi berjalan di: http://localhost:8000
echo Remote Operator Controller: http://localhost:8000/controller
echo.

:: Coba buka Chrome / Edge dalam Kiosk Mode
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --app=http://localhost:8000 --disable-pinch
) else if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --kiosk http://localhost:8000 --edge-kiosk-type=fullscreen
) else (
    start http://localhost:8000
)

echo PHOTOBOOTH PRO SIAP DIGUNAKAN!
pause
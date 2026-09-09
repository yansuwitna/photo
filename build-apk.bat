@echo off
title PHOTOBOOTH PRO - Android APK Builder
color 0b

echo =======================================================
echo         PHOTOBOOTH PRO - ANDROID APK BUILDER
echo =======================================================
echo.

cd /d "%~dp0"

echo [1/3] Menyinkronkan aset web ke project Android...
if not exist "android\app\src\main\assets\public" mkdir "android\app\src\main\assets\public"

copy /y "public\index.html" "android\app\src\main\assets\public\index.html" >nul
copy /y "public\manifest.json" "android\app\src\main\assets\public\manifest.json" >nul
copy /y "public\sw.js" "android\app\src\main\assets\public\sw.js" >nul
copy /y "public\icon-192.png" "android\app\src\main\assets\public\icon-192.png" >nul
copy /y "public\icon-512.png" "android\app\src\main\assets\public\icon-512.png" >nul

echo [OK] Aset web berhasil disinkronkan!
echo.

echo [2/3] Memeriksa Android SDK dan Gradle...
where javac >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] Java JDK / Android SDK belum terdaftar di system PATH.
    echo.
    echo =======================================================
    echo   CARA KOMPILASI APK MENGGUNAKAN ANDROID STUDIO:
    echo =======================================================
    echo   1. Buka aplikasi Android Studio di komputer Anda.
    echo   2. Pilih "Open an Existing Project".
    echo   3. Buka folder: %~dp0android
    echo   4. Tunggu Gradle Sync selesai.
    echo   5. Klik menu: Build ^> Build Bundle(s) / APK(s) ^> Build APK(s)
    echo   6. File APK Anda siap digunakan di:
    echo      %~dp0android\app\build\outputs\apk\debug\app-debug.apk
    echo.
    echo   CATATAN: Anda juga bisa langsung menggunakan versi PWA
    echo   (buka Google Chrome di HP/Tablet Android lalu klik "Install App").
    echo =======================================================
    echo.
    pause
    exit /b 0
)

echo [3/3] Menjalankan Gradle Assemble Debug...
cd android
call gradlew.bat assembleDebug

if exist "app\build\outputs\apk\debug\app-debug.apk" (
    copy /y "app\build\outputs\apk\debug\app-debug.apk" "..\PHOTOBOOTH-PRO-debug.apk" >nul
    echo.
    echo =======================================================
    echo [SUKSES] APK Berhasil Dibuat!
    echo Lokasi: %~dp0PHOTOBOOTH-PRO-debug.apk
    echo =======================================================
) else (
    echo.
    echo [PERHATIAN] Gradle selesai, tetapi APK tidak ditemukan di folder output debug.
    echo Silakan buka folder 'android' di Android Studio untuk kompilasi visual.
)

cd ..
echo.
pause

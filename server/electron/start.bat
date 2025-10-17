@echo off
chcp 65001 >nul
title Zenith - Yeni Nesil Oyun Menusu

echo.
echo ===============================================
echo  Zenith - Yeni Nesil Oyun Menusu baslatlliyor...
echo ===============================================
echo.

echo Flask gereksinimleri kontrol ediliyor...
cd /d "%~dp0\.."
if not exist requirements.txt (
    echo HATA: requirements.txt bulunamadi!
    echo Lutfen server klasorunde oldugunuzdan emin olun.
    pause
    exit /b 1
)

echo Electron uygulamasi baslatiliyor...
cd /d "%~dp0"

REM Performans optimizasyonlari
echo Performans optimizasyonlari uygulanliyor...
set NODE_OPTIONS=--max-old-space-size=8192
set ELECTRON_ENABLE_STACK_DUMPING=false
set ELECTRON_ENABLE_LOGGING=false
set ELECTRON_DISABLE_SECURITY_WARNINGS=true

if not exist node_modules (
    echo NPM bagimliliklari kuruluyor...
    npm install
    if errorlevel 1 (
        echo HATA: NPM bagimliliklari kurulamadi!
        echo Lutfen Node.js'in yuklu oldugundan emin olun.
        pause
        exit /b 1
    )
)

echo.
echo Uygulama baslatiliyor... Lutfen bekleyin.
echo Performans ayarlari: GPU acceleration, Memory optimization aktif
npm start

if errorlevel 1 (
    echo.
    echo HATA: Uygulama baslatilirken bir sorun olustu!
    echo Lutfen asagidaki kontrolleri yapin:
    echo - Python yuklu ve PATH'te tanimli mi?
    echo - Node.js yuklu ve calisir durumda mi?
    echo - server/ klasorunde requirements.txt var mi?
    echo.
    pause
)
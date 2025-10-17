@echo off
chcp 65001 >nul
title Zenith - Yeni Nesil Oyun Menüsü

echo.
echo ===============================================
echo  Zenith - Yeni Nesil Oyun Menüsü başlatılıyor...
echo ===============================================
echo.

echo Flask gereksinimleri kontrol ediliyor...
cd /d "%~dp0\.."
if not exist requirements.txt (
    echo HATA: requirements.txt bulunamadı!
    echo Lütfen server klasöründe olduğunuzdan emin olun.
    pause
    exit /b 1
)

echo Electron uygulaması başlatılıyor...
cd /d "%~dp0"

if not exist node_modules (
    echo NPM bağımlılıkları kuruluyor...
    npm install
    if errorlevel 1 (
        echo HATA: NPM bağımlılıkları kurulamadı!
        echo Lütfen Node.js'in yüklü olduğundan emin olun.
        pause
        exit /b 1
    )
)

echo.
echo Uygulama başlatılıyor... Lütfen bekleyin.
npm start

if errorlevel 1 (
    echo.
    echo HATA: Uygulama başlatılırken bir sorun oluştu!
    echo Lütfen aşağıdaki kontrolleri yapın:
    echo - Python yüklü ve PATH'te tanımlı mı?
    echo - Node.js yüklü ve çalışır durumda mı?
    echo - server/ klasöründe requirements.txt var mı?
    echo.
    pause
)
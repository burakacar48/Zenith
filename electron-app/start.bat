@echo off
echo Zenith Cafe Manager baslatiliyor...
echo.

REM Node.js kurulu mu kontrol et
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo HATA: Node.js bulunamadi!
    echo Lutfen Node.js'i yukleyip PATH'e ekleyin.
    echo https://nodejs.org adresinden indirebilirsiniz.
    pause
    exit /b 1
)

REM NPM paketleri kurulu mu kontrol et
if not exist "node_modules" (
    echo NPM paketleri yukleniyor...
    npm install
    if %errorlevel% neq 0 (
        echo HATA: NPM paketleri yuklenemedi!
        pause
        exit /b 1
    )
)

REM Python kurulu mu kontrol et
where python >nul 2>&1
if %errorlevel% neq 0 (
    echo UYARI: Python bulunamadi!
    echo Flask sunucusu baslatilmayabilir.
    echo Python'i yuklemek icin: https://python.org
    echo.
)

REM Flask gereksinimlerini kontrol et
if exist "..\server\requirements.txt" (
    echo Flask gereksinimleri kontrol ediliyor...
    cd ..\server
    pip install -r requirements.txt >nul 2>&1
    cd ..\electron-app
)

REM Electron uygulamasini baslat
echo Electron uygulamasi baslatiliyor...
npm start

if %errorlevel% neq 0 (
    echo.
    echo HATA: Uygulama baslatilirken hata olustu!
    echo Lutfen asagidaki komutlari manuel olarak calistiriniz:
    echo   1. npm install
    echo   2. npm start
    echo.
    pause
)
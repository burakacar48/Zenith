@echo off
:: ============================================
:: Zenith Cafe Manager - Server Start Script
:: ============================================
:: Bu script Tauri uygulamasını başlatır
:: Flask sunucusu otomatik olarak başlatılır
:: ============================================

title Zenith Cafe Manager - Baslatiliyor...

echo.
echo ================================================
echo   Zenith Cafe Manager Baslatiliyor...
echo ================================================
echo.

:: Renk ve karakter seti ayarları
chcp 65001 >nul 2>&1

:: Rust ve Cargo yolunu PATH'e ekle
set PATH=%USERPROFILE%\.cargo\bin;%PATH%

:: Tauri uygulama dizinine git
cd tauri-app

echo [1/3] Rust ve Cargo kontrol ediliyor...
where cargo >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [HATA] Cargo bulunamadi!
    echo Lutfen Rust'i yukleyin: https://rustup.rs/
    echo.
    pause
    exit /b 1
)

echo [OK] Cargo bulundu: 
cargo --version

echo.
echo [2/3] Node.js kontrol ediliyor...
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [HATA] npm bulunamadi!
    echo Lutfen Node.js yukleyin: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo [OK] Node.js bulundu:
node --version

echo.
echo [3/3] Tauri uygulamasi baslatiliyor...
echo.
echo ================================================
echo   Loading ekrani goruntulenecek
echo   Uygulama hazir olunca ana pencere acilacak
echo ================================================
echo.

:: Tauri uygulamasını başlat
npm run dev

:: Hata kontrolü
if %errorlevel% neq 0 (
    echo.
    echo [HATA] Uygulama baslatilamadi!
    echo Lutfen hata mesajlarini kontrol edin.
    echo.
    pause
    exit /b 1
)

:: Normal çıkış
echo.
echo Uygulama kapatildi.
pause
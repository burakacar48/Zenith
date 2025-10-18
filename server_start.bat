@echo off
:: ============================================
:: Zenith Cafe Manager - Server Start Script
:: ============================================
:: Bu script Flask server'ı başlatır ve 
:: Tauri uygulaması ile admin panelini açar
:: ============================================

title Zenith Cafe Manager Server - Baslatiliyor...

echo.
echo ================================================
echo   Zenith Server ve Admin Paneli Baslatiliyor...
echo ================================================
echo.

:: Renk ve karakter seti ayarları
chcp 65001 >nul 2>&1

:: Python kontrolü
echo [1/4] Python kontrol ediliyor...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [HATA] Python bulunamadi!
    echo Lutfen Python'u yukleyin: https://python.org/
    echo.
    pause
    exit /b 1
)

echo [OK] Python bulundu:
python --version

:: Server dizinine git ve dependencies kontrol et
echo.
echo [2/4] Server dependencies kontrol ediliyor...
cd server
pip install -r requirements.txt >nul 2>&1

:: Flask server'ı arka planda başlat
echo.
echo [3/4] Flask server baslatiliyor...
echo Server adresi: http://127.0.0.1:5001
start /B python app.py

:: Server'ın başlamasını bekle
echo Server baslatiliyor, lutfen bekleyin...
timeout /t 5 >nul 2>&1

:: Server'ın çalıştığını kontrol et
echo Server baglantisi kontrol ediliyor...
netstat -an | findstr "127.0.0.1:5001" >nul 2>&1
if %errorlevel% neq 0 (
    echo [UYARI] Server henuz hazir degil, biraz daha bekleniyor...
    timeout /t 3 >nul 2>&1
)

:: Ana dizine geri dön
cd ..

:: Rust ve Cargo kontrolü
echo.
echo [4/4] Tauri admin paneli baslatiliyor...
set PATH=%USERPROFILE%\.cargo\bin;%PATH%

where cargo >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [HATA] Cargo bulunamadi!
    echo Lutfen Rust'i yukleyin: https://rustup.rs/
    echo.
    echo Alternatif: Admin panelini tarayicida acabilirsiniz:
    echo http://127.0.0.1:5001
    echo.
    pause
    exit /b 1
)

:: Tauri uygulama dizinine git
cd tauri-app

echo Tauri admin paneli aciliyor...
echo Admin Panel: http://127.0.0.1:5001
echo.

:: Node.js dependencies hızlı kontrol
echo Node.js paketleri kontrol ediliyor...
if not exist "node_modules" (
    echo [INFO] Node modules kuruluyor...
    npm install --silent
) else (
    echo [OK] Node modules zaten mevcut.
)

:: Tauri uygulamasını birleşik config ile başlat (hem admin hem client)
echo.
echo Tauri dev komutunu calistiriyorum...
echo Komut: npm run tauri dev -- --config src-tauri/tauri.combined.conf.json
echo.
echo [INFO] Hem Admin Panel hem de Client Menüsü açılacak!
echo.
npm run tauri dev -- --config src-tauri/tauri.combined.conf.json

:: Hata kontrolü
if %errorlevel% neq 0 (
    echo.
    echo [HATA] Tauri admin paneli baslatilamadi!
    echo.
    echo Alternatif: Admin panelini tarayicida acabilirsiniz:
    echo http://127.0.0.1:5001
    echo.
    pause
    exit /b 1
)

echo.
echo Server ve Admin Paneli kapatildi.
pause
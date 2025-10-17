@echo off
echo ========================================
echo   Zenith Client - Tauri Uygulamasi
echo ========================================
echo.

cd client

echo [1/3] Bagimliliklari kontrol ediliyor...
if not exist "node_modules\" (
    echo Node modulleri bulunamadi, yukleniyor...
    call npm install
    if errorlevel 1 (
        echo HATA: npm install basarisiz oldu!
        pause
        exit /b 1
    )
) else (
    echo Node modulleri mevcut.
)

echo.
echo [2/3] Tauri gelistirme sunucusu baslatiliyor...
echo.
echo DIKKAT: Flask sunucusunun http://127.0.0.1:5001 adresinde calistiginden emin olun!
echo.

echo [3/3] Uygulama aciliyor...
call npm run dev

if errorlevel 1 (
    echo.
    echo HATA: Tauri uygulamasi baslatılamadi!
    echo.
    echo Olasi cozumler:
    echo - Rust toolchain yuklu oldugundan emin olun (rustup update)
    echo - Flask sunucusu http://127.0.0.1:5001 adresinde calistigini kontrol edin
    echo - client/src-tauri dizininde 'cargo clean' komutu calistirin
    echo.
    pause
    exit /b 1
)

pause
@echo off
echo ========================================
echo    Zenith Flask Server Baslat tool
echo ========================================
echo.

REM Mevcut dizini kontrol et
echo [1/4] Dizin kontrolu yapiliyor...
cd /d "%~dp0"
if not exist "app.py" (
    echo HATA: app.py dosyasi bulunamadi!
    echo Lutfen bu bat dosyasini server klasorunde calistirin.
    pause
    exit /b 1
)
echo Dizin dogrulandi: %CD%
echo.

REM Python kurulumunu kontrol et
echo [2/4] Python kurulumu kontrol ediliyor...
python --version >nul 2>&1
if errorlevel 1 (
    echo HATA: Python bulunamadi!
    echo Lutfen Python'un yuklu olduguna ve PATH'e eklendigine emin olun.
    pause
    exit /b 1
)
python --version
echo Python kurulu
echo.

REM Gerekli paketlerin kurulu olup olmadigini kontrol et
echo [3/4] Gerekli paketler kontrol ediliyor...
if not exist "requirements.txt" (
    echo UYARI: requirements.txt bulunamadi!
    echo Paket kontrolu atlanıyor...
) else (
    echo Paketler yukleniyor...
    pip install -r requirements.txt
    if errorlevel 1 (
        echo.
        echo UYARI: Bazi paketler yuklenemedi olabilir.
        echo Devam etmek icin bir tusa basin...
        pause >nul
    ) else (
        echo Tum paketler yuklendi
    )
)
echo.

REM Flask sunucusunu baslat
echo [4/4] Flask sunucusu baslatiliyor...
echo.
echo ========================================
echo    Server Bilgileri:
echo    - URL: http://localhost:5088
echo    - Admin Panel: http://localhost:5088/admin
echo    - Admin Login: http://localhost:5088/admin/login
echo ========================================
echo.
echo Server calisiyor... Durdurmak icin Ctrl+C tuslayın.
echo.

python app.py

REM Hata durumunda
if errorlevel 1 (
    echo.
    echo ========================================
    echo HATA: Server baslatilamadi!
    echo ========================================
    echo.
    echo Olasi cozumler:
    echo 1. Gerekli paketlerin kurulu olduguna emin olun: pip install -r requirements.txt
    echo 2. Port 5088'in baska bir uygulama tarafindan kullanilmadigina emin olun
    echo 3. app.py dosyasinda syntax hatasi olmadigini kontrol edin
    echo.
    pause
    exit /b 1
)

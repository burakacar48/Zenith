@echo off
:: ============================================
:: Zenith Cafe Manager - Client Start Script
:: ============================================
:: Bu script client arayüzünü tarayıcıda açar
:: Server'ın çalışması gereklidir
:: ============================================

title Zenith Cafe Manager - Client Baslatiliyor...

echo.
echo ================================================
echo   Zenith Client Baslatiliyor...
echo ================================================
echo.

:: Renk ve karakter seti ayarları
chcp 65001 >nul 2>&1

echo [INFO] Server ve Tauri uygulamasinin calisiyor olmasi gerekir.
echo [INFO] Eger server_start.bat calistirilmadiysa, once onu calistirin.
echo.
echo [1/1] Client arayuzu tarayicida aciliyor...

:: Server kontrolü
netstat -an | findstr "127.0.0.1:5001" >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [UYARI] Server calisiyor gibi gorunmuyor!
    echo Lutfen once server_start.bat ile serveri baslatiniz.
    echo.
    pause
    exit /b 1
)

echo [OK] Server baglantisi mevcut
echo.
echo ================================================
echo   Web tarayicisinda client arayuzu acilacak
echo   Adres: http://127.0.0.1:5001/client
echo ================================================
echo.

:: Client arayüzünü varsayılan tarayıcıda aç
start http://127.0.0.1:5001/client

:: Kısa bekleme
timeout /t 2 >nul 2>&1

echo.
echo Client arayuzu tarayicida acildi!
echo Tauri uygulamasi icin server_start.bat kullanin.
echo Bu pencereyi kapatabilirsiniz.
echo.
pause
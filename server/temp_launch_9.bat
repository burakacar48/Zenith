@echo off
set _EXE_PATH="%%"

:: *****************************************************************
:: * OYUN BAŞLATMA OTOMATİK SCRIPTİ
:: * Sunucu Tarafından Otomatik Üretilmiştir.
:: * %% (Tam Yol) ve %% (Argümanlar) değişkenleri kullanılmıştır.
:: *****************************************************************

:: 1. Kaynak Disk ve Dosya Yolu Bilgisi
echo Disk: %%~d_EXE_PATH%%
echo Oyun Klasörü: %%~p_EXE_PATH%%
echo EXE: %%~nx_EXE_PATH%%
echo Argümanlar: %%

:: 2. Oyunu Başlat
start "" "%%" %%

:: NOT: Programı çalıştırmadan önce ek komutlar ekleyebilirsiniz.
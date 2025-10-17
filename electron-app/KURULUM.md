# Zenith Cafe Manager - Kurulum Talimatları

Bu rehber, Zenith Internet Kafe Yönetim Sistemi'nin Electron uygulamasını kurmanız ve çalıştırmanız için gerekli adımları açıklar.

## 🚀 Hızlı Başlangıç

### Windows Kullanıcıları İçin
1. `start.bat` dosyasına çift tıklayın
2. Uygulama otomatik olarak gerekli kontrolleri yapacak ve başlatılacak

### Manuel Kurulum

## 📋 Gereksinimler

Aşağıdaki yazılımların bilgisayarınızda kurulu olması gerekir:

### 1. Node.js (Zorunlu)
- **Versiyon**: 14.0 veya üzeri
- **İndirme**: https://nodejs.org
- **Kontrol**: Terminal/CMD'de `node --version` komutu çalıştırın

### 2. Python (Zorunlu)
- **Versiyon**: 3.7 veya üzeri
- **İndirme**: https://python.org
- **Kontrol**: Terminal/CMD'de `python --version` komutu çalıştırın
- **Not**: PATH'e eklenmiş olmalı

### 3. Git (Opsiyonel)
- Kaynak kodunu indirmek için

## 🔧 Kurulum Adımları

### Adım 1: Bağımlılıkları Yükleyin

Electron-app klasöründe terminal açın ve şu komutu çalıştırın:

```bash
npm install
```

### Adım 2: Flask Gereksinimlerini Yükleyin

Server klasöründe terminal açın ve şu komutu çalıştırın:

```bash
cd ../server
pip install -r requirements.txt
cd ../electron-app
```

### Adım 3: Uygulamayı Başlatın

```bash
npm start
```

## 🎯 Kullanım

### Geliştirici Modu
Debug araçları ile birlikte çalıştırmak için:
```bash
npm run dev
```

### Executable Oluşturma
Windows için .exe dosyası oluşturmak için:
```bash
npm run build-win
```

## 🔍 Sorun Giderme

### Problem: "Node.js bulunamadı"
**Çözüm**: 
1. Node.js'i https://nodejs.org adresinden indirip kurun
2. Kurulum sırasında "Add to PATH" seçeneğini işaretleyin
3. Bilgisayarı yeniden başlatın

### Problem: "Python bulunamadı"
**Çözüm**:
1. Python'u https://python.org adresinden indirip kurun
2. Kurulum sırasında "Add Python to PATH" seçeneğini işaretleyin
3. Terminal/CMD'yi yeniden açın

### Problem: "Flask sunucusu başlamıyor"
**Çözüm**:
1. Server klasöründe `pip install -r requirements.txt` çalıştırın
2. `app.py` dosyasının mevcut olduğunu kontrol edin
3. Python'un PATH'de olduğunu kontrol edin

### Problem: "Port 5001 kullanımda"
**Çözüm**:
1. Başka bir Flask uygulaması kapatın
2. Bilgisayarı yeniden başlatın
3. Görev yöneticisinden python.exe işlemlerini sonlandırın

### Problem: Beyaz ekran
**Çözüm**:
1. F12 ile developer tools açın
2. Console'da hata mesajlarını kontrol edin
3. `Ctrl+R` ile sayfayı yeniden yükleyin

## 📁 Dosya Yapısı

```
electron-app/
├── main.js              # Ana Electron process
├── preload.js           # Güvenlik katmanı
├── loading.html         # Yükleme sayfası
├── package.json         # NPM konfigürasyonu
├── start.bat           # Windows başlatma scripti
├── assets/             # İkonlar ve görseller
│   ├── icon.svg
│   └── icon.png
├── README.md           # Genel dokümantasyon
└── KURULUM.md          # Bu dosya

server/                 # Flask uygulaması
├── app.py             # Ana Flask uygulaması
├── database.py        # Veritabanı fonksiyonları
├── requirements.txt   # Python bağımlılıkları
└── ...               # Diğer Flask dosyaları
```

## 🔒 Güvenlik Notları

- Uygulama local ağda çalışır (127.0.0.1:5001)
- Dış bağlantılara kapalıdır
- Flask sunucusu sadece Electron uygulaması tarafından erişilebilir
- Uygulama kapatıldığında Flask sunucusu da otomatik kapanır

## 🆘 Destek

Sorun yaşıyorsanız:

1. **Log dosyalarını kontrol edin**: Console (F12) açın
2. **Gereksinimlerinizi kontrol edin**: Node.js ve Python versiyonları
3. **Port çakışması**: 5001 portunu kullanan başka uygulama var mı?
4. **Yeniden kurulum**: `node_modules` klasörünü silin ve `npm install` çalıştırın

## 🚀 Performans İpuçları

- SSD kullanıyorsanız uygulama daha hızlı başlar
- Antivirüs yazılımları Python ve Node.js'i engelleyebilir
- Windows Defender'ın real-time protection özelliğini geçici olarak kapatabilirsiniz
- İlk çalıştırmada npm paketleri indirilir, ikinci seferden sonra daha hızlı açılır

## 📊 Sistem Gereksinimleri

**Minimum**:
- RAM: 2 GB
- Disk: 500 MB boş alan
- İşletim Sistemi: Windows 7/8/10/11

**Önerilen**:
- RAM: 4 GB veya üzeri
- Disk: 1 GB boş alan
- İşletim Sistemi: Windows 10/11
- SSD disk
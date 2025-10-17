# Zenith Internet Cafe Manager - Electron Desktop Application

Bu aplikasyon, Zenith Internet Kafe Yönetim Sistemi'nin Electron tabanlı masaüstü versiyonudur. Flask sunucusunu otomatik olarak başlatır ve browser güvenlik kısıtlamaları olmadan çalışır.

## 🚀 Hızlı Başlangıç

### Windows (Önerilen)
```bash
# Çift tıklayın
start.bat
```

### Manuel Başlatma
```bash
# 1. Bağımlılıkları kurun
npm install

# 2. Uygulamayı başlatın
npm start
```

## 📋 Gereksinimler

- **Node.js** (v16 veya üzeri)
- **Python** (v3.7 veya üzeri) 
- **Flask bağımlılıkları** (server/requirements.txt)

## 📁 Proje Yapısı

```
server/electron/
├── main.js              # Ana Electron process
├── preload.js           # Güvenlik katmanı
├── loading.html         # Yükleme sayfası
├── package.json         # NPM yapılandırması
├── start.bat           # Windows başlatıcısı
├── assets/             # İkonlar ve görseller
│   ├── icon.png
│   └── icon.svg
└── README.md           # Bu dosya
```

## ⚡ Özellikler

- ✅ **Otomatik Flask Başlatma**: Python sunucusunu otomatik başlatır/durdurur
- ✅ **Browser Güvenlik Bypass**: CORS ve local sunucu kısıtlamaları yok
- ✅ **Professional UI**: Loading ekranı ve sistem menüleri
- ✅ **Hata Yönetimi**: Kapsamlı hata kontrolü ve kullanıcı geri bildirimi
- ✅ **Cross-Platform**: Windows, macOS, Linux desteği

## 🔧 Geliştirici Araçları

```bash
# Development modunda başlat (DevTools açık)
npm run dev

# Executable oluştur
npm run build

# Windows installer oluştur  
npm run build-win
```

## 🛠️ Sorun Giderme

### "Flask sunucusu başlatılamadı" Hatası
1. Python'un yüklü olduğunu kontrol edin: `python --version`
2. Flask bağımlılıklarını kurun: `cd ../.. && pip install -r requirements.txt`
3. Port 5001'in boş olduğunu kontrol edin

### "Electron modülü bulunamadı" Hatası
1. Node.js sürümünü kontrol edin: `node --version` (v16+)
2. NPM cache temizleyin: `npm cache clean --force`
3. Node modules'ü yeniden kurun: `rm -rf node_modules && npm install`

### GPU Process Hataları
Bu hatalar normal ve uygulamanın çalışmasını etkilemez. Grafik kartı sürücülerinizi güncellemeyi deneyin.

## 📞 Destek

Herhangi bir sorunla karşılaştığınızda:

1. **Logları kontrol edin**: Terminal çıktısını inceleyin
2. **Gereksinimleri kontrol edin**: Python ve Node.js kurulu mu?
3. **Port çakışması**: Başka uygulama port 5001 kullanıyor mu?

## 📝 Sürüm Notları

### v1.0.0
- İlk stabil sürüm
- Otomatik Flask entegrasyonu
- Windows, macOS, Linux desteği
- Browser güvenlik sorunları çözüldü
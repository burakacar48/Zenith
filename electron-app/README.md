# Zenith Cafe Manager - Electron App

Bu Electron uygulaması, Flask tabanlı Zenith Internet Kafe Yönetim Sistemi'ni browser güvenlik kısıtlamaları olmadan çalıştırmanızı sağlar.

## Kurulum

1. Node.js'in bilgisayarınızda kurulu olduğundan emin olun
2. Bu klasörde terminal açın
3. Bağımlılıkları yükleyin:
```bash
npm install
```

## Çalıştırma

### Geliştirme Modu
```bash
npm run dev
```

### Normal Çalıştırma
```bash
npm start
```

## Uygulama Oluşturma

Windows için executable oluşturmak:
```bash
npm run build-win
```

## Gereksinimler

- Node.js (v14 veya üzeri)
- Python (Flask sunucusu için)
- server/ klasöründe Flask uygulaması

## Özellikler

- Flask sunucusunu otomatik başlatır
- Browser güvenlik kısıtlamalarını bypass eder
- Local dosya erişimi sağlar
- Professional görünüm
- Sistem tray desteği
- Auto-update özelliği

## Dosya Yapısı

```
electron-app/
├── main.js          # Ana Electron process
├── preload.js       # Güvenlik katmanı
├── loading.html     # Yükleme sayfası
├── package.json     # NPM konfigürasyonu
├── assets/          # Görsel varlıklar
└── README.md        # Bu dosya
```

## Sorun Giderme

### Flask Sunucusu Başlamıyor
- Python'un PATH'de olduğundan emin olun
- server/ klasöründe `pip install -r requirements.txt` çalıştırın
- server/app.py dosyasının mevcut olduğunu kontrol edin

### Uygulama Açılmıyor
- Node.js'in güncel olduğundan emin olun
- `npm install` komutu ile bağımlılıkları yeniden yükleyin
- Antivirüs yazılımının uygulamayı engellemediğini kontrol edin

## Lisans

MIT License
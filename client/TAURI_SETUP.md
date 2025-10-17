# Tauri Kurulum ve Yapılandırma Özeti

## ✅ Tamamlanan İşlemler

### 1. Proje Yapısı
```
client/
├── electron/              # Eski Electron dosyaları (yedek)
│   ├── main.js
│   ├── package.json
│   └── src/
├── src/                   # Tauri frontend dosyaları
│   ├── index.html
│   ├── css/
│   ├── js/
│   │   └── renderer.js   # Tauri API ile güncellendi
│   └── assets/
├── src-tauri/            # Tauri backend (Rust)
│   ├── src/
│   │   ├── main.rs
│   │   └── lib.rs        # Oyun başlatma komutları
│   ├── Cargo.toml
│   ├── tauri.conf.json
│   ├── build.rs
│   └── icons/
├── package.json
└── README.md
```

### 2. Oluşturulan Dosyalar

#### Frontend
- ✅ [`src/index.html`](client/src/index.html) - Ana HTML dosyası
- ✅ [`src/js/renderer.js`](client/src/js/renderer.js) - Tauri API entegrasyonu
- ✅ [`src/css/styles.css`](client/src/css/styles.css) - Stil dosyası (kopyalandı)
- ✅ [`src/assets/`](client/src/assets/) - Font ve görsel dosyaları

#### Backend (Rust)
- ✅ [`src-tauri/src/main.rs`](client/src-tauri/src/main.rs) - Rust ana dosyası
- ✅ [`src-tauri/src/lib.rs`](client/src-tauri/src/lib.rs) - Tauri komutları ve iş mantığı
- ✅ [`src-tauri/Cargo.toml`](client/src-tauri/Cargo.toml) - Rust bağımlılıkları
- ✅ [`src-tauri/tauri.conf.json`](client/src-tauri/tauri.conf.json) - Tauri yapılandırması
- ✅ [`src-tauri/build.rs`](client/src-tauri/build.rs) - Build script

#### Yapılandırma
- ✅ [`package.json`](client/package.json) - Node.js bağımlılıkları
- ✅ [`README.md`](client/README.md) - Proje dokümantasyonu

### 3. Temel Özellikler

#### Tauri Komutları
[`lib.rs`](client/src-tauri/src/lib.rs) dosyasında tanımlı:
- `launch_game` - Oyun başlatma (EXE ve Steam desteği)
- Lisans kontrolü (Flask API entegrasyonu)

#### Frontend API Kullanımı
[`renderer.js`](client/src/js/renderer.js:489-495) dosyasında:
```javascript
// Tauri API kullanarak oyun başlat
if (window.__TAURI__) {
    window.__TAURI__.core.invoke('launch_game', { game: JSON.stringify(game) })
        .then(result => console.log('Oyun başlatıldı:', result))
        .catch(error => console.error('Oyun başlatma hatası:', error));
}
```

## 🚀 Kullanım

### Geliştirme Modu
```bash
cd client
npm run dev
```

### Üretim Build
```bash
cd client
npm run build
```

Build çıktısı: `src-tauri/target/release/`

## 🔧 Yapılandırma Detayları

### Tauri Yapılandırması
[`tauri.conf.json`](client/src-tauri/tauri.conf.json):
- Frontend: `../electron/src` (geliştirme için)
- Dev URL: `http://127.0.0.1:5000` (Flask sunucusu)
- Pencere boyutu: 1280x720 (maksimize başlar)

### Rust Bağımlılıkları
[`Cargo.toml`](client/src-tauri/Cargo.toml):
- `tauri` v2 - Ana framework
- `serde` - JSON serileştirme
- `reqwest` - HTTP istekleri (lisans kontrolü için)

## 📝 Electron'dan Farklar

| Özellik | Electron | Tauri |
|---------|----------|-------|
| API Çağrısı | `window.electronAPI.launchGame()` | `window.__TAURI__.core.invoke('launch_game')` |
| Backend | Node.js (JavaScript) | Rust |
| Bundle Boyutu | ~120 MB | ~15 MB |
| Bellek Kullanımı | Yüksek | Düşük |
| Performans | İyi | Mükemmel |

## ⚠️ Önemli Notlar

1. **Flask Sunucusu**: Uygulamanın çalışması için Flask sunucusunun `http://127.0.0.1:5001` adresinde aktif olması gerekir.

2. **Rust Kurulumu**: İlk build için Rust toolchain yüklü olmalıdır:
   ```bash
   rustup update
   ```

3. **İlk Build**: Rust bağımlılıklarını derlemesi nedeniyle ilk build 5-10 dakika sürebilir.

4. **Electron Yedek**: Eski Electron implementasyonu [`client/electron/`](client/electron/) klasöründe saklanmıştır.

## 🔍 Sorun Giderme

### Build Hatası
```bash
cd client/src-tauri
cargo clean
cd ..
npm run build
```

### Rust Toolchain
```bash
rustup toolchain install stable-msvc
rustup default stable-msvc
```

### Port Çakışması
Flask sunucusunun 5001 portunda çalıştığından emin olun:
```bash
cd server
python app.py
```

## 📚 Ek Kaynaklar

- [Tauri Dokümantasyonu](https://tauri.app/)
- [Rust Öğrenme](https://www.rust-lang.org/learn)
- [Tauri API Referansı](https://tauri.app/v1/api/js/)

---

**Oluşturulma Tarihi**: 17 Ekim 2025  
**Tauri Versiyonu**: 2.x  
**Rust Versiyonu**: 1.77.2+
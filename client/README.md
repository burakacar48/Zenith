# Zenith Client - Tauri Application

Bu, Zenith Internet Cafe sistemi için Tauri tabanlı masaüstü istemci uygulamasıdır.

## 📋 Gereksinimler

- **Node.js** (v16 veya üzeri)
- **Rust** (1.77.2 veya üzeri)
- **Windows Build Tools** (Windows için)

## Kurulum

1. Bağımlılıkları yükleyin:
```bash
npm install
```

2. Rust toolchain'i yükleyin (eğer yoksa):
```bash
rustup update
```

## 💻 Geliştirme

Geliştirme modunda çalıştırmak için:

```bash
npm run dev
```

Bu komut:
- Tauri dev sunucusunu başlatır
- Otomatik yeniden yükleme ile uygulamayı açar
- Flask sunucusunun `http://127.0.0.1:5001` adresinde çalıştığını varsayar

## 📦 Üretim Build

Üretim için build almak için:

```bash
npm run build
```

Build dosyaları `src-tauri/target/release` klasöründe oluşturulacaktır.

## 🏗️ Proje Yapısı

```
client/
├── src/                    # Frontend dosyaları
│   ├── index.html         # Ana HTML dosyası
│   ├── css/               # Stil dosyaları
│   ├── js/                # JavaScript dosyaları
│   └── assets/            # Statik dosyalar
├── src-tauri/             # Tauri backend
│   ├── src/
│   │   ├── main.rs       # Rust ana dosyası
│   │   └── lib.rs        # Rust kütüphane dosyası
│   ├── Cargo.toml        # Rust bağımlılıkları
│   ├── tauri.conf.json   # Tauri yapılandırması
│   └── icons/            # Uygulama ikonları
├── electron/              # Eski Electron dosyaları (yedek)
└── package.json           # Node.js bağımlılıkları
```

## 🔧 Yapılandırma

### Tauri Yapılandırması

`src-tauri/tauri.conf.json` dosyasında:
- `build.devUrl`: Geliştirme sunucusu URL'si
- `build.frontendDist`: Frontend build klasörü
- `app.windows`: Pencere ayarları

### Rust Komutları

`src-tauri/src/lib.rs` dosyasında tanımlanan komutlar:
- `launch_game`: Oyun başlatma

## 📝 Notlar

- Flask sunucusunun `http://127.0.0.1:5000` adresinde çalıştığından emin olun
- İlk build işlemi biraz zaman alabilir (Rust bağımlılıkları derleniyor)
- Electron versiyonu `client/electron` klasöründe yedek olarak saklanmıştır

## 🔄 Electron'dan Tauri'ye Geçiş

Eski Electron implementasyonu `client/electron` klasöründe saklanmıştır. Tauri versiyonu:
- Daha küçük dosya boyutu
- Daha iyi performans
- Daha güvenli
- Rust backend desteği

Ana farklar:
- `window.electronAPI` → `window.__TAURI__.core.invoke()`
- IPC komutları → Rust komutları

## 🆘 Sorun Giderme

### Build Hataları

Eğer build hatası alırsanız:

```bash
# Cargo cache'i temizle
cd src-tauri
cargo clean

# Tekrar dene
cd ..
npm run build
```

### Rust Kurulum Sorunları

Windows'ta:
```bash
rustup toolchain install stable-msvc
rustup default stable-msvc
```

## 📄 Lisans

Bu proje Zenith Team tarafından geliştirilmiştir.
# Zenith Cafe Manager - Kurulum Rehberi

## 🚀 Hızlı Kurulum (Windows)

### 1. Gerekli Yazılımları Kurun

#### Python 3.7+ Kurulumu
1. [Python.org](https://www.python.org/downloads/) adresinden Python'u indirin
2. Kurulum sırasında **"Add Python to PATH"** seçeneğini işaretleyin
3. Kurulumu tamamlayın

#### Node.js 16+ Kurulumu  
1. [NodeJS.org](https://nodejs.org/) adresinden LTS sürümü indirin
2. Varsayılan ayarlarla kurun
3. Kurulumu tamamlayın

### 2. Flask Bağımlılıklarını Kurun

```bash
# Server klasörüne gidin
cd server

# Python bağımlılıklarını kurun
pip install -r requirements.txt
```

### 3. Electron Uygulamasını Kurun

```bash
# Electron klasörüne gidin  
cd electron

# NPM bağımlılıklarını kurun
npm install
```

### 4. Uygulamayı Başlatın

```bash
# Otomatik başlatma (Önerilen)
start.bat

# Veya manuel
npm start
```

## 🔧 Detaylı Kurulum Adımları

### Sistem Gereksinimleri

| Yazılım | Minimum Sürüm | Önerilen |
|---------|---------------|----------|
| Windows | 10 | 11 |
| Python | 3.7 | 3.11+ |
| Node.js | 16 | 18+ |
| NPM | 7 | 9+ |
| RAM | 4GB | 8GB+ |
| Disk | 2GB | 5GB+ |

### Python Kurulum Kontrolü

```bash
# Python sürümünü kontrol edin
python --version

# Pip sürümünü kontrol edin  
pip --version

# Bağımlılıkları test edin
python -c "import flask; print('Flask OK')"
```

### Node.js Kurulum Kontrolü

```bash
# Node.js sürümünü kontrol edin
node --version

# NPM sürümünü kontrol edin
npm --version

# Global paketleri listeleyin
npm list -g --depth=0
```

### Electron Bağımlılıkları Kurulumu

```bash
cd server/electron

# Package.json'ı kontrol edin
cat package.json

# Bağımlılıkları kurun
npm install

# Kurulumu test edin
npm run --silent start --version
```

## 🔍 Sorun Giderme

### Python Sorunları

#### "python: command not found"
```bash
# Windows'ta PATH'e Python ekleyin
set PATH=%PATH%;C:\Python311;C:\Python311\Scripts

# Kalıcı olarak eklemek için Sistem > Gelişmiş Ayarlar > Ortam Değişkenleri
```

#### "No module named 'flask'"  
```bash
# Pip'i güncelleyin
python -m pip install --upgrade pip

# Flask'ı yeniden kurun
pip install flask

# Requirements.txt'i kurun
pip install -r requirements.txt
```

### Node.js Sorunları

#### "npm: command not found"
```bash
# Node.js'i yeniden kurun
# https://nodejs.org/ adresinden LTS sürümü indirin

# NPM cache temizleyin
npm cache clean --force
```

#### "gyp ERR! build error"
```bash
# Windows Build Tools kurun
npm install --global windows-build-tools

# Veya Visual Studio Build Tools kurun
npm install --global @microsoft/rush-stack-compiler-3.9
```

### Electron Sorunları

#### "Electron failed to install correctly"
```bash
# Electron'u yeniden kurun
npm uninstall electron
npm install electron

# Cache temizleyin
npm cache clean --force
rm -rf node_modules
npm install
```

#### "GPU process exited unexpectedly"
Bu hata normal ve uygulamanın çalışmasını etkilemez. Grafik kartı sürücülerinizi güncellemeyi deneyin.

### Port Sorunları

#### "Port 5001 already in use"
```bash
# Port'u kullanan process'i bulun
netstat -ano | findstr :5001

# Process'i sonlandırın
taskkill /PID <process_id> /F

# Veya farklı port kullanın (main.js'te değiştirin)
```

## 📝 Yapılandırma

### Flask Ayarları (server/app.py)
```python
# Port değiştirmek için
app.run(debug=False, host='0.0.0.0', port=5002)  # 5002 kullan
```

### Electron Ayarları (electron/main.js)
```javascript
// Port değiştirmek için
const FLASK_PORT = 5002;  // Flask ile aynı port
```

### NPM Scripts Özelleştirme
```json
{
  "scripts": {
    "start": "electron .",
    "dev": "electron . --dev",
    "build": "electron-builder",
    "test": "echo \"Test: OK\" && exit 0"
  }
}
```

## 🔄 Güncelleme

### Electron Güncellemesi
```bash
cd server/electron
npm update electron
```

### Python Paketleri Güncellemesi  
```bash
cd server
pip install --upgrade -r requirements.txt
```

## 📞 Destek

Kurulum sırasında sorun yaşarsanız:

1. **Logları kontrol edin**: Terminal çıktısını inceleyin
2. **Gereksinimleri doğrulayın**: Sürümleri kontrol edin
3. **Cache temizleyin**: NPM ve pip cache'lerini temizleyin
4. **Yeniden başlatın**: Sistem yeniden başlatması gerekebilir

## ✅ Kurulum Doğrulama Checklist

- [ ] Python 3.7+ kurulu ve PATH'te
- [ ] Node.js 16+ kurulu ve çalışıyor  
- [ ] Pip çalışıyor ve güncel
- [ ] NPM çalışıyor ve güncel
- [ ] Flask requirements.txt kurulu
- [ ] Electron node_modules kurulu
- [ ] Port 5001 boş ve kullanılabilir
- [ ] start.bat çalıştırılabilir
- [ ] Uygulama başlatılıyor ve Flask bağlanıyor

Tüm maddeleri işaretlediyseniz kurulum tamamdır! 🎉
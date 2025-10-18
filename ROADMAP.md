# Zenith İstemci Geliştirme Yol Haritası

Bu doküman, Zenith oyun menüsü istemcisinin geliştirilmesi için izlenecek adımları ve teknik planı özetlemektedir.

## 1. Teknoloji Seçimi ve Mevcut Durum

- **İstemci Teknolojisi:** Projenin hafif, hızlı ve modern olması amacıyla **Tauri** (Rust tabanlı) kullanılacaktır. Mevcut `tauri-app` yapılandırması bu karar için sağlam bir temel oluşturmaktadır.
- **Sunucu Entegrasyonu:** İstemci, arka planda çalışan ve tüm veriyi sağlayan Python (Flask) sunucusu ile haberleşecektir. Tauri, geliştirme ortamında (`devUrl`) zaten `http://127.0.0.1:5001` adresine yönlendirilmiştir.
- **Arayüz:** `client_demo_ui.html` dosyasındaki tasarım, ana referans noktası olarak kullanılacak ve sunucudan gelen dinamik verilerle çalışacak şekilde yeniden yapılandırılacaktır.

## 2. Geliştirme Adımları

### Adım 1: Arayüz Şablonu Oluşturma
- Flask sunucusunun `templates` klasörü içinde, istemcinin ana arayüzünü barındıracak `game_menu.html` adında yeni bir HTML şablonu oluşturulacak.
- `client_demo_ui.html` dosyasının HTML yapısı ve CSS stilleri, bu yeni şablona aktarılacak.
- Sunucudaki `app.py` dosyasına `/client` adında yeni bir route (rota) eklenecek ve bu rota, `game_menu.html` şablonunu render edecektir.

### Adım 2: Tauri Yapılandırmasını Güncelleme
- `tauri-app/src-tauri/tauri.conf.json` dosyasındaki ana pencerenin (`main`) URL'si, yeni oluşturulan `/client` rotasına işaret edecek şekilde `http://127.0.0.1:5001/client` olarak güncellenecektir.
- Bu sayede Tauri uygulaması açıldığında doğrudan oyun menüsü arayüzünü gösterecektir.

### Adım 3: Verileri Dinamik Hale Getirme
- `game_menu.html` içerisindeki JavaScript bölümü yeniden yazılacaktır.
- Sabit olarak tanımlanmış `games` dizisi kaldırılacak.
- Sayfa yüklendiğinde, `fetch` API'si kullanılarak sunucunun `/api/games`, `/api/categories`, ve `/api/slider` gibi endpoint'lerine istekler atılacak.
- Sunucudan gelen JSON verisi işlenerek oyun kartları, kategoriler ve diğer dinamik içerikler arayüzde oluşturulacaktır.

### Adım 4: Geliştirme Ortamını Başlatma
- Projeyi çalıştırmak için iki ana bileşenin başlatılması gerekecektir:
  1. **Python Sunucusu:** `server` klasöründe `python app.py` komutu ile Flask sunucusu başlatılır.
  2. **Tauri İstemcisi:** `tauri-app` klasöründe `npm run tauri dev` komutu ile Tauri geliştirme sunucusu başlatılır.
- Bu iki komut çalıştırıldığında, masaüstü uygulaması açılacak ve sunucuya bağlanarak oyun menüsünü gösterecektir.

## 3. Gelecek Geliştirmeler (Opsiyonel)

- Kullanıcı girişi (login) ve favori oyunlar gibi özelliklerin tam entegrasyonu.
- Oyun başlatma (`exe` çalıştırma) gibi işlemler için Tauri'nin Rust tarafındaki yeteneklerinin kullanılması.
- Ayarlar ve destek gibi sayfaların oluşturulması.

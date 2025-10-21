# 🎯 Lisans Yönetim Paneli - Kurulum ve Kullanım Kılavuzu

## 📋 İçindekiler
1. [Genel Bakış](#genel-bakış)
2. [Özellikler](#özellikler)
3. [Kurulum](#kurulum)
4. [Veritabanı Yapısı](#veritabanı-yapısı)
5. [Modüller](#modüller)
6. [Güvenlik](#güvenlik)
7. [API Kullanımı](#api-kullanımı)
8. [Sorun Giderme](#sorun-giderme)

---

## 🎨 Genel Bakış

Modern, kurumsal ve kullanıcı dostu bir lisans yönetim sistemi. Tailwind CSS ile tasarlanmış, responsive ve şık bir arayüze sahiptir.

### Teknolojiler
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:** Tailwind CSS 3.x, Lucide Icons
- **JavaScript:** Vanilla JS (AJAX)

---

## ✨ Özellikler

### 👥 Müşteri Yönetimi
- ✅ Otomatik 5 haneli müşteri ID oluşturma
- ✅ Otomatik 6 karakterli şifre oluşturma
- ✅ Müşteri lisans takibi
- ✅ Bayi atama sistemi
- ✅ Detaylı müşteri bilgileri (adres, şehir, ilçe, vs.)

### 🔑 Lisans Yönetimi
- ✅ Benzersiz lisans anahtarı üretimi
- ✅ HWID (Donanım ID) kilitleme
- ✅ IP adresi kilitleme
- ✅ Lisans süresi yönetimi
- ✅ Lisans uzatma (1-3 yıl)
- ✅ HWID sıfırlama
- ✅ 3 lisans tipi: Standard, Premium, Enterprise

### 🏢 Bayi Yönetimi
- ✅ Bayi ekleme/düzenleme/silme
- ✅ Otomatik bayi kodu oluşturma (D1000-D9999)
- ✅ Komisyon oranı tanımlama
- ✅ Bayi bazlı müşteri takibi
- ✅ Bayi durum yönetimi (Aktif/Pasif/Askıda)

### 💰 Ödeme Yönetimi
- ✅ Ödeme kaydı oluşturma
- ✅ Çoklu ödeme yöntemi (Nakit, Havale, Kredi Kartı, Diğer)
- ✅ Fatura numarası takibi
- ✅ Ödeme durumu (Beklemede, Tamamlandı, Başarısız, İade)
- ✅ Gelir raporlama

### 👤 Müşteri Paneli
- ✅ Müşteri girişi (5 haneli ID + şifre)
- ✅ Lisans bilgilerini görüntüleme
- ✅ Kalan süre takibi
- ✅ HWID ve IP bilgisi görüntüleme

### 🔐 API Sistemi
- ✅ Lisans doğrulama API
- ✅ HWID kilitleme kontrolü
- ✅ IP adresi doğrulama
- ✅ Admin authentication API
- ✅ JSON yanıtlar

---

## 🚀 Kurulum

### 1. Sistem Gereksinimleri

```
- PHP 7.4 veya üzeri
- MySQL 5.7+ / MariaDB 10.3+
- Apache / Nginx web sunucusu
- mod_rewrite (Apache için)
```

### 2. Dosyaları Yükleme

```bash
# Panel dosyalarını web sunucunuzun kök dizinine yükleyin
/var/www/html/panel/
```

### 3. Veritabanı Kurulumu

#### Adım 1: Veritabanı Şemasını Oluşturun

```bash
mysql -u oyunmenu -p oyunmenu < complete_database_schema.sql
```

**VEYA** phpMyAdmin kullanarak:

1. phpMyAdmin'e giriş yapın
2. `oyunmenu` veritabanını seçin
3. SQL sekmesine gidin
4. `complete_database_schema.sql` dosyasının içeriğini yapıştırın
5. "Go" butonuna tıklayın

#### Adım 2: Admin Kullanıcısı

Varsayılan admin hesabı:
```
Kullanıcı Adı: admin
Şifre: admin123
```

**ÖNEMLİ:** İlk girişten sonra şifrenizi değiştirin!

Şifre değiştirmek için:
```sql
UPDATE admins SET password_hash = '$2y$10$YourNewHashedPassword' WHERE username = 'admin';
```

`hash.php` dosyasını kullanarak yeni hash oluşturabilirsiniz.

### 4. Yapılandırma

`config.php` dosyasını düzenleyin:

```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'oyunmenu');  // Veritabanı kullanıcı adınız
define('DB_PASSWORD', 'your_password');  // Veritabanı şifreniz
define('DB_NAME', 'oyunmenu');  // Veritabanı adınız
```

### 5. İzinler

```bash
chmod 755 panel/
chmod 644 panel/*.php
chmod 600 panel/config.php  # Güvenlik için
```

### 6. İlk Giriş

1. Tarayıcınızda `https://yourdomain.com/panel/` adresine gidin
2. Kullanıcı adı: `admin`
3. Şifre: `admin123`
4. Giriş yapın!

---

## 🗄️ Veritabanı Yapısı

### Tablolar

#### 1. `admins` - Yönetici Kullanıcıları
```sql
- id (AUTO_INCREMENT)
- username (UNIQUE)
- password_hash
- email
- full_name
- role (super_admin, admin, viewer)
- created_at
- last_login
```

#### 2. `customers` - Müşteriler
```sql
- id (AUTO_INCREMENT)
- customer_id (5 haneli, UNIQUE)
- customer_password (hashed)
- name
- email
- phone
- company
- address
- city
- district
- dealer_id (FK -> dealers)
- created_at
- updated_at
```

#### 3. `licenses` - Lisanslar
```sql
- id (AUTO_INCREMENT)
- customer_id (FK -> customers)
- license_key (UNIQUE)
- license_type (Standard, Premium, Enterprise)
- start_date
- end_date
- status (active, pending, expired, cancelled)
- licensed_ip
- hwid
- created_at
- updated_at
```

#### 4. `dealers` - Bayiler
```sql
- id (AUTO_INCREMENT)
- dealer_code (UNIQUE, D1000-D9999)
- dealer_name
- contact_person
- email
- phone
- address
- city
- district
- commission_rate (DECIMAL)
- status (active, inactive, suspended)
- created_at
- updated_at
```

#### 5. `payments` - Ödemeler
```sql
- id (AUTO_INCREMENT)
- customer_id (FK -> customers)
- license_id (FK -> licenses)
- dealer_id (FK -> dealers)
- amount (DECIMAL)
- currency (TRY, USD, EUR)
- payment_method (cash, bank_transfer, credit_card, other)
- payment_date
- payment_status (pending, completed, failed, refunded)
- invoice_number
- notes
- created_at
- updated_at
```

#### 6. `admin_sessions` - Admin Oturumları
```sql
- id (AUTO_INCREMENT)
- customer_id (FK -> customers)
- session_token (UNIQUE)
- created_at
- expires_at
```

#### 7. `activity_logs` - Aktivite Logları
```sql
- id (AUTO_INCREMENT)
- user_id
- user_type (admin, customer)
- action
- description
- ip_address
- created_at
```

#### 8. `system_settings` - Sistem Ayarları
```sql
- id (AUTO_INCREMENT)
- setting_key (UNIQUE)
- setting_value
- setting_type (string, number, boolean, json)
- description
- updated_at
```

---

## 📦 Modüller

### 1. **Dashboard (`index.php`)**
- Genel istatistikler
- Toplam müşteri sayısı
- Aktif lisans sayısı
- Süresi dolan lisanslar
- Aylık gelir özeti

### 2. **Müşteri Yönetimi**

#### Müşteri Listesi (`customers.php`)
- Tüm müşterileri görüntüleme
- Arama ve filtreleme
- Müşteri kartları (avatar, bilgiler, durum)
- Hızlı düzenleme/silme

#### Müşteri Ekleme (`add_customer.php`)
- Otomatik ID ve şifre üretimi
- Bayi atama
- Lisans oluşturma
- Adres bilgileri

#### Müşteri Düzenleme (`edit_customer.php`)
- Bilgileri güncelleme
- Tüm alanların düzenlenmesi

#### Lisans Düzenleme (`edit_license.php`)
- Lisans detaylarını görüntüleme
- IP adresi kilitleme
- HWID yönetimi
- Süre uzatma (1-3 yıl)
- Durum değiştirme

### 3. **Bayi Yönetimi**

#### Bayi Listesi (`dealers.php`)
- Tüm bayiler
- Müşteri sayısı
- Komisyon oranı
- Durum yönetimi

#### Bayi Ekleme (`add_dealer.php`)
- Otomatik bayi kodu
- Komisyon tanımlama
- İletişim bilgileri

### 4. **Ödeme Yönetimi**

#### Ödeme Listesi (`payments.php`)
- Gelir istatistikleri
- Tüm ödemeler
- Filtreleme
- Fatura numarası takibi

#### Ödeme Ekleme (`add_payment.php`)
- Müşteri seçimi
- Lisans bağlama (opsiyonel)
- Bayi bağlama (opsiyonel)
- Ödeme detayları
- Not ekleme

### 5. **Müşteri Paneli**

#### Müşteri Girişi (`customer_login.php`)
- 5 haneli ID ile giriş
- Şifre doğrulama
- Oturum yönetimi

#### Müşteri Dashboard (`customer_dashboard.php`)
- Lisans bilgileri
- Kalan süre
- HWID ve IP bilgisi
- Müşteri bilgileri

---

## 🔐 Güvenlik

### Uygulanan Güvenlik Önlemleri

1. **Şifre Güvenliği**
   - Bcrypt hashing (`PASSWORD_DEFAULT`)
   - Salt kullanımı
   - Şifreler asla plain text olarak saklanmaz

2. **SQL Injection Koruması**
   - Tüm sorgularda prepared statements
   - Input sanitization (`trim`, `htmlspecialchars`)
   - Type validation (`FILTER_VALIDATE_INT`)

3. **Session Güvenliği**
   - Güvenli session yönetimi
   - Admin ve müşteri oturumları ayrı
   - Session timeout (8 saat)

4. **CSRF Koruması**
   - Form validation
   - POST metodları

5. **XSS Koruması**
   - Output escaping (`htmlspecialchars`)
   - Input sanitization

### Öneriler

```php
// config.php dosyasını koruyun
chmod 600 config.php

// HTTPS kullanın
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

// Production ortamında error_reporting kapatın
ini_set('display_errors', 0);
error_reporting(0);
```

---

## 🔌 API Kullanımı

### 1. Lisans Doğrulama API (`api.php`)

#### Endpoint
```
POST /panel/api.php
```

#### Request
```json
{
  "license_key": "KA-STD-ABCD-1234",
  "hwid": "BFEBFBFF000906E9",
  "client_ip": "192.168.1.100"
}
```

#### Response (Başarılı)
```json
{
  "status": "valid",
  "message": "Lisans doğrulandı.",
  "license_details": {
    "customer_name": "Ahmet Yılmaz",
    "company": "ABC Kafe",
    "address_details": "Ankara Çankaya",
    "start_date": "01.01.2025 00:00",
    "end_date": "01.01.2026 00:00"
  },
  "debug": {
    "client_ip_incoming": "192.168.1.100",
    "hwid_incoming": "BFEBFBFF000906E9",
    "license_key": "KA-STD-ABCD-1234",
    "hwid_db": "BFEBFBFF000906E9",
    "licensed_ip_db": "192.168.1.100",
    "validation_step": "HWID ve IP Eşleşti"
  }
}
```

#### Response (Hatalı)
```json
{
  "status": "invalid",
  "reason": "( Geçersiz Lisans Anahtarı )"
}
```

### 2. Admin Authentication API (`admin_auth_api.php`)

#### Endpoint
```
POST /panel/admin_auth_api.php
```

#### Request
```json
{
  "customer_id": "12345",
  "password": "Abc123"
}
```

#### Response
```json
{
  "success": true,
  "message": "Giriş başarılı",
  "data": {
    "customer_id": "12345",
    "name": "Ahmet Yılmaz",
    "company": "ABC Kafe",
    "session_token": "a1b2c3d4..."
  }
}
```

### 3. Müşteri Lisansları API (`get_customer_licenses.php`)

#### Endpoint
```
GET /panel/get_customer_licenses.php?customer_id=1
```

#### Response
```json
{
  "success": true,
  "licenses": [
    {
      "id": "1",
      "license_key": "KA-STD-ABCD-1234",
      "license_type": "Standard"
    }
  ]
}
```

---

## 🐛 Sorun Giderme

### Yaygın Hatalar

#### 1. **Veritabanı Bağlantı Hatası**
```
HATA: Veritabanı bağlantısı kurulamadı.
```

**Çözüm:**
- `config.php` bilgilerini kontrol edin
- MySQL servisinin çalıştığından emin olun
- Kullanıcı izinlerini kontrol edin

#### 2. **Lisans Ekleme Hatası**
```
Lisans oluşturulurken bir hata oluştu.
```

**Çözüm:**
- `licenses` tablosunun var olduğundan emin olun
- Foreign key constraints kontrolü
- Benzersiz lisans anahtarı çakışması

#### 3. **Session Hatası**
```
Session başlatılamadı.
```

**Çözüm:**
```bash
# Session klasörü izinleri
chmod 777 /var/lib/php/sessions
# VEYA php.ini'de
session.save_path = "/tmp"
```

#### 4. **AJAX Çalışmıyor**
```
Lisanslar yüklenemiyor
```

**Çözüm:**
- `get_customer_licenses.php` dosyasının erişilebilir olduğunu kontrol edin
- JavaScript console'da hata kontrolü
- Network sekmesinde API yanıtlarını kontrol edin

### Debug Modu

Geliştirme ortamında hata mesajlarını görmek için:

```php
// config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

---

## 📱 Responsive Tasarım

Panel tüm cihazlarda çalışacak şekilde tasarlanmıştır:

- 📱 **Mobile:** 320px - 768px
- 💻 **Tablet:** 768px - 1024px
- 🖥️ **Desktop:** 1024px+
- 🖥️ **Large Desktop:** 1920px+ (Boxed layout)

---

## 🎨 Tasarım Sistemi

### Renkler
- **Primary:** Blue (#3B82F6)
- **Success:** Green (#10B981)
- **Warning:** Orange (#F59E0B)
- **Error:** Red (#EF4444)
- **Gray Scale:** Tailwind Gray Palette

### Tipografi
- **Font Family:** Inter (Google Fonts)
- **Sizes:** text-xs, text-sm, text-base, text-lg, text-xl, text-2xl

### Iconlar
- **Lucide Icons** - Modern, lightweight SVG icon library

---

## 📝 Changelog

### v1.0.0 (2025-10-21)
- ✨ İlk sürüm
- ✅ Müşteri yönetimi
- ✅ Lisans yönetimi
- ✅ Bayi sistemi
- ✅ Ödeme takibi
- ✅ Müşteri paneli
- ✅ API sistemi
- ✅ Responsive tasarım

---

## 🤝 Destek

Sorularınız için:
- 📧 Email: support@yourdomain.com
- 📱 Telefon: +90 XXX XXX XX XX

---

## 📄 Lisans

Bu yazılım [Your Company] tarafından geliştirilmiştir.  
© 2025 Tüm hakları saklıdır.

---

**Oluşturulma Tarihi:** 21 Ekim 2025  
**Versiyon:** 1.0.0  
**Son Güncelleme:** 21 Ekim 2025

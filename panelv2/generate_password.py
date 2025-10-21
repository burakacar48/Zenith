#!/usr/bin/env python3
"""
Admin Şifre Hash Oluşturucu
PHP password_hash() ile uyumlu bcrypt hash oluşturur
"""

import bcrypt
import sys

def generate_password_hash(password):
    """
    Verilen şifre için PHP uyumlu bcrypt hash oluşturur
    
    Args:
        password (str): Hash'lenecek şifre
        
    Returns:
        str: Bcrypt hash (PHP password_hash ile uyumlu)
    """
    # Şifreyi bytes'a çevir
    password_bytes = password.encode('utf-8')
    
    # Bcrypt hash oluştur (cost factor: 10, PHP default)
    salt = bcrypt.gensalt(rounds=10)
    hashed = bcrypt.hashpw(password_bytes, salt)
    
    # Bytes'tan string'e çevir
    return hashed.decode('utf-8')

def main():
    print("\n" + "=" * 70)
    print("  🔐 PANELV2 - ADMIN ŞİFRE HASH OLUŞTURUCU")
    print("=" * 70)
    print()
    
    # Kullanıcıdan şifre al
    while True:
        password = input("Yeni şifrenizi girin: ").strip()
        
        if not password:
            print("❌ Şifre boş olamaz!\n")
            continue
        
        if len(password) < 6:
            print("❌ Şifre en az 6 karakter olmalıdır!\n")
            continue
            
        confirm = input("Şifreyi tekrar girin: ").strip()
        if password != confirm:
            print("❌ Şifreler eşleşmiyor! Tekrar deneyin.\n")
            continue
            
        break
    
    print("\n⏳ Hash oluşturuluyor...\n")
    
    # Hash oluştur
    password_hash = generate_password_hash(password)
    
    print("=" * 70)
    print("  ✅ HASH BAŞARIYLA OLUŞTURULDU!")
    print("=" * 70)
    print()
    print("📋 YENİ HASH KODUNUZ:")
    print("-" * 70)
    print()
    print(password_hash)
    print()
    print("-" * 70)
    print()
    print("💾 VERİTABANI GÜNCELLEME KOMUTU:")
    print("-" * 70)
    print()
    print(f"UPDATE admins SET password_hash = '{password_hash}' WHERE username = 'admin';")
    print()
    print("=" * 70)
    print()
    print("📝 NASIL KULLANILIR:")
    print("-" * 70)
    print("1. Yukarıdaki hash kodunu kopyalayın")
    print("2. phpMyAdmin'e giriş yapın")
    print("3. 'oyunmenu' veritabanını seçin")
    print("4. 'admins' tablosunu açın")
    print("5. Admin kullanıcınızın 'password_hash' sütununu güncelleyin")
    print()
    print("YA DA")
    print()
    print("SQL sekmesinde yukarıdaki UPDATE komutunu çalıştırın")
    print("=" * 70)
    print()
    print("✅ Güncelleme sonrası yeni şifrenizle giriş yapabilirsiniz!")
    print("📍 Panel: http://yourdomain.com/panelv2/login.php")
    print()

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n❌ İşlem iptal edildi.\n")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Hata: {e}\n")
        sys.exit(1)

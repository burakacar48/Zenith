#!/usr/bin/env python3
"""
Save dosyası sistemi test scripti
Yeni format: user_saves/user_id/id-oyun_ismi.zip
"""

import os
import json

def get_gallery_folder_name(game_name, game_id=None):
    """Generate gallery folder name in {id}-{name} format if game_id is provided"""
    char_map = {
        'ğ': 'g', 'ı': 'i', 'ş': 's', 'ç': 'c', 'ö': 'o', 'ü': 'u',
        'Ğ': 'G', 'İ': 'I', 'Ş': 'S', 'Ç': 'C', 'Ö': 'O', 'Ü': 'U',
    }
    cleaned_name = "".join(char_map.get(char, char) for char in game_name)
    illegal_chars = ' <>:"/\\|?*'
    final_name = "".join(c for c in cleaned_name if c not in illegal_chars)
    final_name = final_name.strip()
    
    # If game_id is provided, use {id}-{name} format
    if game_id is not None:
        return f"{game_id}-{final_name}"
    
    return final_name

def test_save_format():
    """Save dosyası formatını test et"""
    
    # Test verileri
    test_user_id = 1
    test_games = [
        {'id': 1, 'oyun_adi': 'Grand Theft Auto V'},
        {'id': 2, 'oyun_adi': 'The Witcher 3: Wild Hunt'},
        {'id': 3, 'oyun_adi': 'Counter-Strike 2'},
        {'id': 4, 'oyun_adi': 'Call of Duty: Modern Warfare II'}
    ]
    
    print("=== SAVE DOSYASI FORMAT TEST ===")
    print(f"Test Kullanıcı ID: {test_user_id}")
    print()
    
    # Test klasörü oluştur
    test_dir = f"test_saves/{test_user_id}"
    os.makedirs(test_dir, exist_ok=True)
    
    print("YENİ FORMAT DOSYA İSİMLERİ:")
    print("-" * 50)
    
    for game in test_games:
        # Yeni format dosya ismi oluştur
        clean_name = get_gallery_folder_name(game['oyun_adi']).replace(' ', '')
        new_filename = f"{game['id']}-{clean_name}.zip"
        
        print(f"Oyun: {game['oyun_adi']}")
        print(f"  → Dosya: {new_filename}")
        print(f"  → Tam yol: {test_dir}/{new_filename}")
        
        # Test dosyası oluştur
        test_file_path = os.path.join(test_dir, new_filename)
        with open(test_file_path, 'w') as f:
            f.write(f"Test save data for game {game['id']}")
        
        print(f"  ✓ Test dosyası oluşturuldu")
        print()
    
    # Klasör içeriğini listele
    print("KLASÖR İÇERİĞİ:")
    print("-" * 30)
    for filename in sorted(os.listdir(test_dir)):
        file_path = os.path.join(test_dir, filename)
        size = os.path.getsize(file_path)
        print(f"{filename} ({size} byte)")
    
    print()
    print("✅ Test tamamlandı!")
    print(f"📁 Test dosyaları: {os.path.abspath(test_dir)}")

def test_filename_parsing():
    """Dosya ismi ayrıştırma testleri"""
    
    print("\n=== DOSYA İSMİ AYRIŞTIRMA TEST ===")
    
    test_files = [
        "1-GrandTheftAutoV.zip",
        "2-TheWitcher3WildHunt.zip", 
        "3-CounterStrike2.zip",
        "4-CallofDutyModernWarfareII.zip",
        "1.zip",  # Eski format
        "5.zip"   # Eski format
    ]
    
    for filename in test_files:
        print(f"\nDosya: {filename}")
        
        if filename.endswith('.zip'):
            # Yeni format: id-oyun_ismi.zip
            if '-' in filename:
                try:
                    game_id = int(filename.split('-')[0])
                    game_name_part = filename.split('-', 1)[1].replace('.zip', '')
                    print(f"  ✓ Yeni format - Game ID: {game_id}, İsim kısmı: {game_name_part}")
                except (ValueError, IndexError):
                    print(f"  ❌ Yeni format parse hatası")
            
            # Eski format: game_id.zip
            elif filename.split('.')[0].isdigit():
                try:
                    game_id = int(filename.split('.')[0])
                    print(f"  ⚠️ Eski format - Game ID: {game_id} (dönüştürülmeli)")
                except ValueError:
                    print(f"  ❌ Eski format parse hatası")
            else:
                print(f"  ❌ Tanınmayan format")

if __name__ == "__main__":
    test_save_format()
    test_filename_parsing()
    
    print("\n" + "="*60)
    print("ÖZET:")
    print("• Yeni format: user_saves/user_id/id-oyun_ismi.zip")
    print("• Eski dosyalar otomatik olarak yeniden adlandırılacak")
    print("• Geriye uyumlu okuma desteği mevcut")
    print("• Oyun isimleri güvenli karakterlere dönüştürülüyor")
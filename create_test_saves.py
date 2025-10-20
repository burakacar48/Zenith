#!/usr/bin/env python3
"""
TestUser kullanıcısı için veritabanındaki tüm oyunlar için test save dosyaları oluştur
"""

import os
import sqlite3
import zipfile
import json
from datetime import datetime

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

def create_test_saves():
    """TestUser için tüm oyunlar için test save dosyaları oluştur"""
    
    print("=== TEST SAVE DOSYALARI OLUŞTURUCU ===")
    print(f"Başlangıç zamanı: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    # Veritabanına bağlan
    database_path = "server/kafe.db"
    if not os.path.exists(database_path):
        print(f"❌ Veritabanı bulunamadı: {database_path}")
        return
    
    conn = sqlite3.connect(database_path)
    conn.row_factory = sqlite3.Row
    
    try:
        # TestUser kullanıcısını bul
        user = conn.execute("SELECT id, username FROM users WHERE username = 'testuser'").fetchone()
        if not user:
            print("❌ 'testuser' kullanıcısı bulunamadı!")
            print("Mevcut kullanıcılar:")
            users = conn.execute("SELECT id, username FROM users ORDER BY username").fetchall()
            for u in users:
                print(f"  - {u['id']}: {u['username']}")
            return
        
        user_id = user['id']
        print(f"✓ Kullanıcı bulundu: {user['username']} (ID: {user_id})")
        
        # Tüm oyunları getir
        games = conn.execute("SELECT id, oyun_adi, aciklama FROM games ORDER BY id").fetchall()
        if not games:
            print("❌ Veritabanında oyun bulunamadı!")
            return
        
        print(f"✓ {len(games)} oyun bulundu")
        print()
        
        # User saves klasörünü oluştur
        user_save_dir = os.path.join("user_saves", str(user_id))
        os.makedirs(user_save_dir, exist_ok=True)
        print(f"📁 Save klasörü: {os.path.abspath(user_save_dir)}")
        print()
        
        created_count = 0
        
        for game in games:
            game_id = game['id']
            game_name = game['oyun_adi']
            game_desc = game['aciklama'] or "Test save dosyası"
            
            # Yeni format dosya ismi oluştur
            clean_name = get_gallery_folder_name(game_name).replace(' ', '')
            save_filename = f"{game_id}-{clean_name}.zip"
            save_path = os.path.join(user_save_dir, save_filename)
            
            # Save dosyası içeriği (örnek)
            save_content = {
                "game_id": game_id,
                "game_name": game_name,
                "user_id": user_id,
                "save_date": datetime.now().isoformat(),
                "save_data": {
                    "level": 1,
                    "score": 1000,
                    "completed": False,
                    "settings": {
                        "difficulty": "normal",
                        "sound": 100,
                        "graphics": "high"
                    }
                },
                "description": f"Test save file for {game_name}"
            }
            
            # ZIP dosyası oluştur
            with zipfile.ZipFile(save_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
                # save.json dosyası ekle
                zipf.writestr("save.json", json.dumps(save_content, indent=2, ensure_ascii=False))
                
                # Örnek save dosyaları ekle
                zipf.writestr("savegame.dat", f"Binary save data for game {game_id}")
                zipf.writestr("config.ini", f"[Settings]\nGame={game_name}\nVersion=1.0\n")
                zipf.writestr("readme.txt", f"Test save file for {game_name}\nCreated: {datetime.now()}")
            
            print(f"✓ {save_filename} oluşturuldu ({game_name})")
            created_count += 1
        
        print()
        print(f"🎉 Başarılı! {created_count} save dosyası oluşturuldu")
        
        # Klasör içeriğini listele
        print("\n📋 OLUŞTURULAN SAVE DOSYALARI:")
        print("-" * 60)
        
        total_size = 0
        for filename in sorted(os.listdir(user_save_dir)):
            if filename.endswith('.zip'):
                file_path = os.path.join(user_save_dir, filename)
                file_size = os.path.getsize(file_path)
                total_size += file_size
                
                # Dosya boyutunu formatla
                if file_size < 1024:
                    size_str = f"{file_size} B"
                elif file_size < 1024 * 1024:
                    size_str = f"{file_size / 1024:.1f} KB"
                else:
                    size_str = f"{file_size / (1024 * 1024):.1f} MB"
                
                print(f"{filename:<40} {size_str:>10}")
        
        # Toplam boyut
        if total_size < 1024:
            total_str = f"{total_size} B"
        elif total_size < 1024 * 1024:
            total_str = f"{total_size / 1024:.1f} KB"
        else:
            total_str = f"{total_size / (1024 * 1024):.1f} MB"
        
        print("-" * 60)
        print(f"{'TOPLAM:':<40} {total_str:>10}")
        
        # Test için birkaç dosyayı kontrol et
        print("\n🔍 ÖRNEK SAVE İÇERİĞİ:")
        print("-" * 30)
        
        sample_files = sorted([f for f in os.listdir(user_save_dir) if f.endswith('.zip')])[:3]
        for filename in sample_files:
            file_path = os.path.join(user_save_dir, filename)
            with zipfile.ZipFile(file_path, 'r') as zipf:
                print(f"\n📁 {filename}:")
                for info in zipf.infolist():
                    print(f"  └─ {info.filename} ({info.file_size} bytes)")
        
    except Exception as e:
        print(f"❌ Hata oluştu: {e}")
        
    finally:
        conn.close()
        print(f"\nBitiş zamanı: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

if __name__ == "__main__":
    create_test_saves()
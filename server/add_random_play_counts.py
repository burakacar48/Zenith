#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Veritabanındaki oyunlara rastgele oynanma sayısı ekleme scripti
"""

import sqlite3
import random
import sys

def add_random_play_counts():
    """Veritabanındaki tüm oyunlara rastgele play_count değerleri ekler"""
    
    try:
        # Veritabanına bağlan
        conn = sqlite3.connect('kafe.db')
        cursor = conn.cursor()
        
        # Mevcut oyunları al
        cursor.execute('SELECT id, oyun_adi, play_count FROM games')
        games = cursor.fetchall()
        
        if not games:
            print("Veritabanında hiç oyun bulunamadı!")
            return
        
        print(f"Toplam {len(games)} oyun bulundu.")
        print("\nMevcut oynanma sayıları:")
        for game_id, oyun_adi, play_count in games:
            print(f"  {game_id:2d}. {oyun_adi:30s} - {play_count:3d} kez")
        
        # Kullanıcıdan onay al
        print("\nRastgele oynanma sayıları atanacak. Devam etmek istiyor musunuz? (e/h): ", end="")
        choice = input().lower().strip()
        
        if choice not in ['e', 'evet', 'y', 'yes']:
            print("İşlem iptal edildi.")
            return
        
        # Rastgele sayı aralığını belirle
        print("\nRastgele oynanma sayısı aralığını seçin:")
        print("1. Düşük (1-50)")
        print("2. Orta (10-200)")
        print("3. Yüksek (50-500)")
        print("4. Çok Yüksek (100-1000)")
        print("5. Özel aralık gir")
        
        range_choice = input("Seçiminiz (1-5): ").strip()
        
        if range_choice == '1':
            min_count, max_count = 1, 50
        elif range_choice == '2':
            min_count, max_count = 10, 200
        elif range_choice == '3':
            min_count, max_count = 50, 500
        elif range_choice == '4':
            min_count, max_count = 100, 1000
        elif range_choice == '5':
            try:
                min_count = int(input("Minimum değer: "))
                max_count = int(input("Maksimum değer: "))
                if min_count < 0 or max_count < min_count:
                    print("Geçersiz aralık! Varsayılan (10-200) kullanılacak.")
                    min_count, max_count = 10, 200
            except ValueError:
                print("Geçersiz sayı! Varsayılan (10-200) kullanılacak.")
                min_count, max_count = 10, 200
        else:
            print("Geçersiz seçim! Varsayılan (10-200) kullanılacak.")
            min_count, max_count = 10, 200
        
        print(f"\nOynanma sayıları {min_count}-{max_count} aralığında rastgele atanacak...")
        
        # Her oyun için rastgele sayı üret ve güncelle
        updated_games = []
        for game_id, oyun_adi, current_play_count in games:
            new_play_count = random.randint(min_count, max_count)
            
            cursor.execute('UPDATE games SET play_count = ? WHERE id = ?', 
                         (new_play_count, game_id))
            
            updated_games.append((game_id, oyun_adi, current_play_count, new_play_count))
        
        # Değişiklikleri kaydet
        conn.commit()
        
        print("\n✅ Rastgele oynanma sayıları başarıyla eklendi!")
        print("\nGüncellenen oyunlar:")
        print("ID  Oyun Adı                        Eski → Yeni")
        print("-" * 60)
        
        for game_id, oyun_adi, old_count, new_count in updated_games:
            print(f"{game_id:2d}. {oyun_adi:30s} {old_count:3d} → {new_count:3d}")
        
        # İstatistikleri göster
        total_plays = sum(new_count for _, _, _, new_count in updated_games)
        avg_plays = total_plays / len(updated_games)
        
        print(f"\n📊 İstatistikler:")
        print(f"   Toplam oynanma: {total_plays:,}")
        print(f"   Ortalama oynanma: {avg_plays:.1f}")
        print(f"   En az oynanma: {min(new_count for _, _, _, new_count in updated_games)}")
        print(f"   En çok oynanma: {max(new_count for _, _, _, new_count in updated_games)}")
        
    except sqlite3.Error as e:
        print(f"❌ Veritabanı hatası: {e}")
        return False
    except Exception as e:
        print(f"❌ Genel hata: {e}")
        return False
    finally:
        if conn:
            conn.close()
    
    return True

def reset_play_counts():
    """Tüm oynanma sayılarını sıfırlar"""
    
    try:
        conn = sqlite3.connect('kafe.db')
        cursor = conn.cursor()
        
        # Mevcut oyunları kontrol et
        cursor.execute('SELECT COUNT(*) FROM games WHERE play_count > 0')
        games_with_plays = cursor.fetchone()[0]
        
        if games_with_plays == 0:
            print("Zaten tüm oyunların oynanma sayısı 0.")
            return
        
        print(f"{games_with_plays} oyunun oynanma sayısı sıfırlanacak.")
        print("Devam etmek istiyor musunuz? (e/h): ", end="")
        choice = input().lower().strip()
        
        if choice not in ['e', 'evet', 'y', 'yes']:
            print("İşlem iptal edildi.")
            return
        
        # Tüm play_count değerlerini sıfırla
        cursor.execute('UPDATE games SET play_count = 0')
        updated_count = cursor.rowcount
        conn.commit()
        
        print(f"✅ {updated_count} oyunun oynanma sayısı sıfırlandı.")
        
    except sqlite3.Error as e:
        print(f"❌ Veritabanı hatası: {e}")
    except Exception as e:
        print(f"❌ Genel hata: {e}")
    finally:
        if conn:
            conn.close()

def show_current_stats():
    """Mevcut oynanma istatistiklerini gösterir"""
    
    try:
        conn = sqlite3.connect('kafe.db')
        cursor = conn.cursor()
        
        # Oyun istatistiklerini al
        cursor.execute('''
            SELECT 
                COUNT(*) as total_games,
                SUM(play_count) as total_plays,
                AVG(play_count) as avg_plays,
                MIN(play_count) as min_plays,
                MAX(play_count) as max_plays
            FROM games
        ''')
        
        stats = cursor.fetchone()
        total_games, total_plays, avg_plays, min_plays, max_plays = stats
        
        if total_games == 0:
            print("Veritabanında hiç oyun bulunamadı!")
            return
        
        print(f"\n📊 Mevcut Oynanma İstatistikleri:")
        print(f"   Toplam oyun sayısı: {total_games}")
        print(f"   Toplam oynanma: {total_plays:,}")
        print(f"   Ortalama oynanma: {avg_plays:.1f}")
        print(f"   En az oynanma: {min_plays}")
        print(f"   En çok oynanma: {max_plays}")
        
        # En çok oynanan 5 oyunu göster
        cursor.execute('''
            SELECT oyun_adi, play_count 
            FROM games 
            WHERE play_count > 0 
            ORDER BY play_count DESC 
            LIMIT 5
        ''')
        
        top_games = cursor.fetchall()
        
        if top_games:
            print(f"\n🏆 En Çok Oynanan Oyunlar:")
            for i, (oyun_adi, play_count) in enumerate(top_games, 1):
                print(f"   {i}. {oyun_adi:30s} - {play_count:3d} kez")
        
    except sqlite3.Error as e:
        print(f"❌ Veritabanı hatası: {e}")
    except Exception as e:
        print(f"❌ Genel hata: {e}")
    finally:
        if conn:
            conn.close()

def main():
    """Ana fonksiyon - menü sistemi"""
    
    print("🎮 Oyun Oynanma Sayısı Yönetim Aracı")
    print("=" * 40)
    
    while True:
        print("\nSeçenekler:")
        print("1. Rastgele oynanma sayıları ekle")
        print("2. Mevcut istatistikleri göster")
        print("3. Tüm oynanma sayılarını sıfırla")
        print("4. Çıkış")
        
        choice = input("\nSeçiminiz (1-4): ").strip()
        
        if choice == '1':
            add_random_play_counts()
        elif choice == '2':
            show_current_stats()
        elif choice == '3':
            reset_play_counts()
        elif choice == '4':
            print("Programdan çıkılıyor...")
            break
        else:
            print("❌ Geçersiz seçim! Lütfen 1-4 arasında bir sayı girin.")

if __name__ == '__main__':
    main()
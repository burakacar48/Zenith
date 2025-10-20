import psycopg2
from psycopg2.extras import RealDictCursor
from werkzeug.security import generate_password_hash
import os

# Database configuration
DB_CONFIG = {
    'dbname': 'zenith_db',
    'user': 'postgres',
    'password': '',  # No password for 127.0.0.1 trust authentication
    'host': '127.0.0.1',
    'port': 5499
}

def get_db_connection():
    """Create a new database connection with RealDictCursor"""
    conn = psycopg2.connect(**DB_CONFIG, cursor_factory=RealDictCursor)
    return conn

def init_db():
    """Initialize PostgreSQL database with all required tables"""
    print("PostgreSQL veritabanı kontrol ediliyor ve eksik tablolar oluşturuluyor...")
    
    try:
        conn = get_db_connection()
        cursor = conn.cursor()

        # Users table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password_hash TEXT NOT NULL
        )
        ''')

        # Admin users table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS admin_users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        ''')

        # Settings table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS settings (
            key VARCHAR(255) PRIMARY KEY,
            value TEXT
        )
        ''')

        # Categories table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            icon TEXT
        )
        ''')

        # Games table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS games (
            id SERIAL PRIMARY KEY,
            oyun_adi VARCHAR(255) NOT NULL,
            aciklama TEXT,
            cover_image TEXT,
            youtube_id TEXT,
            save_yolu TEXT,
            calistirma_tipi VARCHAR(50) NOT NULL,
            calistirma_verisi TEXT NOT NULL,
            cikis_yili VARCHAR(20),
            pegi VARCHAR(20),
            oyun_dili VARCHAR(100),
            average_rating REAL NOT NULL DEFAULT 0,
            rating_count INTEGER NOT NULL DEFAULT 0,
            click_count INTEGER NOT NULL DEFAULT 0,
            launch_script TEXT,
            yuzde_yuz_save_path TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        ''')

        # Game categories junction table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS game_categories (
            game_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            PRIMARY KEY (game_id, category_id),
            FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
        )
        ''')

        # Slider table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS slider (
            id SERIAL PRIMARY KEY,
            game_id INTEGER,
            badge_text TEXT,
            title TEXT,
            description TEXT,
            background_image TEXT,
            is_active INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            FOREIGN KEY (game_id) REFERENCES games (id)
        )
        ''')

        # User played games table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS user_played_games (
            user_id INTEGER NOT NULL,
            game_id INTEGER NOT NULL,
            played_at TIMESTAMP NOT NULL,
            PRIMARY KEY (user_id, game_id),
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE
        )
        ''')

        # User ratings table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS user_ratings (
            user_id INTEGER NOT NULL,
            game_id INTEGER NOT NULL,
            rating REAL NOT NULL,
            PRIMARY KEY (user_id, game_id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (game_id) REFERENCES games(id)
        )
        ''')

        # User favorites table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS user_favorites (
            user_id INTEGER NOT NULL,
            game_id INTEGER NOT NULL,
            PRIMARY KEY (user_id, game_id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (game_id) REFERENCES games(id)
        )
        ''')

        # Gallery images table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS gallery_images (
            id SERIAL PRIMARY KEY,
            game_id INTEGER NOT NULL,
            image_path TEXT NOT NULL,
            FOREIGN KEY (game_id) REFERENCES games (id)
        )
        ''')

        # Disk settings table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS disk_settings (
            drive_letter VARCHAR(10) PRIMARY KEY,
            custom_name TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        ''')

        # Languages table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS languages (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            code VARCHAR(10) NOT NULL UNIQUE,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        ''')

        # Insert default admin user if not exists
        cursor.execute("SELECT COUNT(*) FROM admin_users")
        if cursor.fetchone()['count'] == 0:
            admin_password_hash = generate_password_hash('admin123')
            cursor.execute('INSERT INTO admin_users (username, password_hash) VALUES (%s, %s)', ('admin', admin_password_hash))
            print("Admin kullanıcısı oluşturuldu: admin / admin123")

        # Insert default test user if not exists
        cursor.execute("SELECT COUNT(*) FROM users")
        if cursor.fetchone()['count'] == 0:
            password_hash = generate_password_hash('12345')
            cursor.execute('INSERT INTO users (username, password_hash) VALUES (%s, %s)', ('testuser', password_hash))

        # Insert default categories if not exists
        cursor.execute("SELECT COUNT(*) FROM categories")
        if cursor.fetchone()['count'] == 0:
            sample_categories = [('FPS', '🎯'), ('RPG', '📜'), ('MOBA', '⚔️'), ('Strateji', '🧠'), ('Online Oyunlar', '🌐')]
            cursor.executemany('INSERT INTO categories (name, icon) VALUES (%s, %s)', sample_categories)

        # Insert default settings if not exists
        cursor.execute("SELECT COUNT(*) FROM settings")
        if cursor.fetchone()['count'] == 0:
            sample_settings = [
                ('cafe_name', 'Zenka Internet Cafe'), ('slogan', 'Hazırsan, oyun başlasın.'),
                ('background_type', 'default'), ('background_file', ''), ('background_opacity_factor', '1.0'),
                ('primary_color_start', '#667eea'), ('primary_color_end', '#764ba2'), ('welcome_modal_enabled', '1'),
                ('welcome_modal_text', 'Oyun ilerlemeni kaydetmek, oyunları favorilerine eklemek ve puanlamak için hemen üye girişi yap veya kayıt ol.'),
                ('social_google', 'https://www.google.com'), ('social_instagram', 'https://www.instagram.com'),
                ('social_facebook', 'https://www.facebook.com'), ('social_youtube', 'https://www.youtube.com'),
            ]
            cursor.executemany('INSERT INTO settings (key, value) VALUES (%s, %s)', sample_settings)

        # Insert default languages if not exists
        cursor.execute("SELECT COUNT(*) FROM languages")
        if cursor.fetchone()['count'] == 0:
            sample_languages = [
                ('Türkçe', 'tr'), ('English', 'en'), ('Deutsch', 'de'), ('Français', 'fr'),
                ('Español', 'es'), ('Italiano', 'it'), ('Português', 'pt'), ('Русский', 'ru'),
                ('中文', 'zh'), ('日本語', 'ja'), ('한국어', 'ko'), ('العربية', 'ar')
            ]
            cursor.executemany('INSERT INTO languages (name, code) VALUES (%s, %s)', sample_languages)

        conn.commit()
        cursor.close()
        conn.close()
        
        print("PostgreSQL veritabanı kontrolü tamamlandı.")
        
    except Exception as e:
        print(f"PostgreSQL veritabanı başlatılırken bir hata oluştu: {e}")
        raise

if __name__ == '__main__':
    init_db()

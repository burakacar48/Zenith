from flask import Flask, jsonify, render_template, request, redirect, url_for, send_from_directory, Response, session, flash
from flask_cors import CORS
import sqlite3
import os
import json
import shutil
from werkzeug.security import generate_password_hash, check_password_hash
from werkzeug.utils import secure_filename
import jwt
from datetime import datetime, timedelta
from functools import wraps
from database import init_db
from PIL import Image
import requests
import uuid
import subprocess

app = Flask(__name__)
app.config['SECRET_KEY'] = 'bu-cok-gizli-bir-anahtar-kimse-bilmemeli'
app.config['SAVE_FOLDER'] = os.path.join(os.getcwd(), 'user_saves')
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
app.config['UPLOAD_FOLDER_COVERS'] = os.path.join(BASE_DIR, 'static/images/covers')
app.config['UPLOAD_FOLDER_GALLERY'] = os.path.join(BASE_DIR, 'static/images/gallery')
app.config['UPLOAD_FOLDER_SLIDER'] = os.path.join(BASE_DIR, 'static/images/slider')
app.config['UPLOAD_FOLDER_BG'] = os.path.join(BASE_DIR, 'static/images/backgrounds')
app.config['UPLOAD_FOLDER_100_SAVES'] = os.path.join(BASE_DIR, 'yuzde_yuz_saves')
app.config['UPLOAD_FOLDER_LOGOS'] = os.path.join(BASE_DIR, 'static/images/logos')

DATABASE = 'kafe.db'

def json_response(data, status_code=200):
    response_data = json.dumps(data, ensure_ascii=False)
    return Response(response_data, status=status_code, content_type='application/json; charset=utf-8')

def get_db_connection():
    conn = sqlite3.connect(DATABASE, check_same_thread=False)
    conn.row_factory = sqlite3.Row
    return conn

def token_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        token = None
        if 'Authorization' in request.headers:
            token = request.headers['Authorization'].split(" ")[1]
        if not token:
            return jsonify({'mesaj': 'Token eksik!'}), 401
        try:
            data = jwt.decode(token, app.config['SECRET_KEY'], algorithms=["HS256"])
            current_user_id = data['user_id']
        except:
            return jsonify({'mesaj': 'Token geçersiz veya süresi dolmuş!'}), 401
        return f(current_user_id, *args, **kwargs)
    return decorated

def get_all_settings():
    conn = get_db_connection()
    settings_cursor = conn.execute('SELECT key, value FROM settings').fetchall()
    conn.close()
    return {row['key']: row['value'] for row in settings_cursor}

def set_setting(key, value):
    conn = get_db_connection()
    conn.execute('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)', (key, value))
    conn.commit()
    conn.close()

def convert_to_webp(file, upload_folder, sub_folder=None):
    upload_folder = os.path.normpath(upload_folder)
    filename = secure_filename(file.filename)
    base, ext = os.path.splitext(filename)
    webp_filename = f"{base}.webp"
    
    if sub_folder and upload_folder == os.path.normpath(app.config['UPLOAD_FOLDER_GALLERY']):
        final_dir = os.path.join(upload_folder, sub_folder)
        if not os.path.exists(final_dir):
            os.makedirs(final_dir)
        file_path = os.path.join(final_dir, webp_filename)
        stored_path = os.path.join(sub_folder, webp_filename).replace('\\', '/')
    else:
        file_path = os.path.join(upload_folder, webp_filename)
        stored_path = webp_filename
        
    image = Image.open(file.stream)
    image.save(file_path, 'webp', quality=85)
    
    return stored_path

def get_gallery_folder_name(game_name):
    char_map = {
        'ğ': 'g', 'ı': 'i', 'ş': 's', 'ç': 'c', 'ö': 'o', 'ü': 'u',
        'Ğ': 'G', 'İ': 'I', 'Ş': 'S', 'Ç': 'C', 'Ö': 'O', 'Ü': 'U',
    }
    cleaned_name = "".join(char_map.get(char, char) for char in game_name)
    illegal_chars = ' <>:"/\\|?*'
    final_name = "".join(c for c in cleaned_name if c not in illegal_chars)
    return final_name.strip()

# --- LİSANS YÖNETİMİ BÖLÜMÜ ---
license_status_cache = {"status": None, "reason": "Henüz kontrol edilmedi", "last_checked": None}

def get_hwid():
    try:
        command = ['powershell', '-Command', 'Get-CimInstance -ClassName Win32_BaseBoard | Select-Object SerialNumber']
        process = subprocess.run(command, capture_output=True, text=True, check=True, creationflags=subprocess.CREATE_NO_WINDOW)
        lines = [line.strip() for line in process.stdout.splitlines() if line.strip()]
        if len(lines) >= 3 and lines[2] and lines[2] not in ['To be filled by O.E.M.', 'None']:
            return lines[2].replace(" ", "").strip()
    except Exception as e:
        print(f"Anakart Seri Numarası alınamadı: {e}")
    return ""

def get_windows_drives():
    """Windows disklerini listeler (C: hariç)"""
    try:
        # JSON formatında çıktı almak için daha detaylı PowerShell komutu
        command = [
            'powershell', '-Command',
            'Get-WmiObject -Class Win32_LogicalDisk | Where-Object {$_.DriveType -eq 3 -and $_.DeviceID -ne "C:"} | ForEach-Object { [PSCustomObject]@{ DeviceID=$_.DeviceID; VolumeName=if($_.VolumeName){$_.VolumeName}else{"Yerel Disk"}; Size=$_.Size; FreeSpace=$_.FreeSpace } } | ConvertTo-Json'
        ]
        process = subprocess.run(command, capture_output=True, text=True, check=True, creationflags=subprocess.CREATE_NO_WINDOW)
        
        drives = []
        output = process.stdout.strip()
        
        if output and output != 'null':
            import json
            try:
                # JSON çıktısını parse et
                drive_data = json.loads(output)
                
                # Tek disk varsa liste yapmak için
                if isinstance(drive_data, dict):
                    drive_data = [drive_data]
                
                for drive in drive_data:
                    drives.append({
                        'device_id': drive.get('DeviceID', ''),
                        'volume_name': drive.get('VolumeName', 'Yerel Disk'),
                        'size': int(drive.get('Size', 0)) if drive.get('Size') else None,
                        'free_space': int(drive.get('FreeSpace', 0)) if drive.get('FreeSpace') else None
                    })
                    
            except json.JSONDecodeError:
                # JSON parse edilemezse eski yöntemi kullan
                print("JSON parse hatası, manuel parsing yapılıyor...")
                lines = output.split('\n')
                for line in lines:
                    if ':' in line and any(letter in line for letter in 'DEFGHIJKLMNOPQRSTUVWXYZ'):
                        parts = line.strip().split()
                        if parts:
                            drives.append({
                                'device_id': parts[0] if parts else 'Bilinmiyor',
                                'volume_name': 'Yerel Disk',
                                'size': None,
                                'free_space': None
                            })
        
        return drives
    except Exception as e:
        print(f"Disk listesi alınamadı: {e}")
        # Hata durumunda test disk'i döndür
        return [{
            'device_id': 'D:',
            'volume_name': 'Test Disk',
            'size': None,
            'free_space': None
        }]

def get_public_ip_from_server():
    try:
        response = requests.get('https://api.ipify.org?format=json', timeout=5)
        if response.status_code == 200: return response.json()['ip']
    except requests.RequestException as e:
        print(f"Sunucu harici IP alınamadı: {e}")
    return ""

def check_license(license_key, hwid, client_ip):
    global license_status_cache
    api_url = "https://onurmedya.tr/burak/api.php"
    payload = {"license_key": license_key, "hwid": hwid, "client_ip": client_ip}
    try:
        response = requests.post(api_url, json=payload, timeout=10)
        if response.status_code == 200:
            data = response.json()
            license_status_cache.update({
                'status': data.get('status'), 
                'reason': data.get('reason') or data.get('message', 'Durum bilinmiyor.'), 
                'full_api_response': data
            })
        else:
            license_status_cache.update({
                'status': 'invalid', 
                'reason': f'API sunucusuna ulaşılamadı (HTTP {response.status_code}).', 
                'full_api_response': {'debug': 'HTTP Status Error'}
            })
    except requests.RequestException as e:
        license_status_cache.update({
            'status': 'invalid', 
            'reason': f'API bağlantı hatası: {e}', 
            'full_api_response': {'debug': f'Request Exception: {str(e)}'}
        })
    license_status_cache['last_checked'] = datetime.now()
    return license_status_cache

def license_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        global license_status_cache
        settings = get_all_settings()
        license_key = settings.get('license_key', '')
        last_checked = license_status_cache.get('last_checked')
        time_to_recheck = not last_checked or (datetime.now() - last_checked) > timedelta(hours=1)
        
        if license_status_cache.get('status') != 'valid' or not license_key or time_to_recheck:
            if license_key:
                check_license(license_key, get_hwid(), get_public_ip_from_server())
            else:
                license_status_cache.update({'status': 'invalid', 'reason': 'Lisans Anahtarı Ayarlanmamış'})
                
        if license_status_cache.get('status') != 'valid':
            return redirect(url_for('license_management'))
        return f(*args, **kwargs)
    return decorated

# Admin Authentication Functions
def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if not session.get('admin_logged_in'):
            return redirect(url_for('admin_login'))
        return f(*args, **kwargs)
    return decorated

# API Endpoints
@app.route('/api/register', methods=['POST'])
def register():
    data = request.get_json()
    if not data or not data.get('username') or not data.get('password'): 
        return jsonify({"mesaj": "Kullanıcı adı veya şifre eksik"}), 400
    if len(data['username']) < 3: 
        return jsonify({"mesaj": "Kullanıcı adı en az 3 karakter olmalıdır."}), 400
    if len(data['password']) < 5: 
        return jsonify({"mesaj": "Şifre en az 5 karakter olmalıdır."}), 400
    
    conn = get_db_connection()
    if conn.execute('SELECT * FROM users WHERE username = ?', (data['username'],)).fetchone():
        conn.close()
        return jsonify({"mesaj": "Bu kullanıcı adı zaten alınmış."}), 409
    
    conn.execute('INSERT INTO users (username, password_hash) VALUES (?, ?)', (data['username'], generate_password_hash(data['password'])))
    conn.commit()
    conn.close()
    return jsonify({"mesaj": "Kullanıcı başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz."}), 201

@app.route('/api/login', methods=['POST'])
def login():
    auth_data = request.get_json()
    conn = get_db_connection()
    user = conn.execute('SELECT * FROM users WHERE username = ?', (auth_data['username'],)).fetchone()
    conn.close()
    if not user or not check_password_hash(user['password_hash'], auth_data['password']): 
        return jsonify({"mesaj": "Hatalı kullanıcı adı veya şifre"}), 401
    
    token = jwt.encode({'user_id': user['id'], 'exp': datetime.utcnow() + timedelta(hours=8)}, app.config['SECRET_KEY'], algorithm="HS256")
    return jsonify({'token': token})
    
@app.route('/api/games', methods=['GET'])
def get_games():
    conn = get_db_connection()
    limit = request.args.get('limit', type=int)
    
    query = """
        SELECT g.*, GROUP_CONCAT(c.name) as kategoriler
        FROM games g
        LEFT JOIN game_categories gc ON g.id = gc.game_id
        LEFT JOIN categories c ON gc.category_id = c.id
    """
    
    if limit:
        query += ' GROUP BY g.id ORDER BY g.id DESC LIMIT ?'
        games_cursor = conn.execute(query, (limit,)).fetchall()
    else:
        query += ' GROUP BY g.id ORDER BY g.oyun_adi ASC'
        games_cursor = conn.execute(query).fetchall()
        
    games_list = []
    for game in games_cursor:
        game_dict = dict(game)
        
        # DÜZELTİLMİŞ MANTIK
        kategoriler_str = game_dict.get('kategoriler')
        if kategoriler_str:
            game_dict['kategoriler'] = kategoriler_str.split(',')
        else:
            game_dict['kategoriler'] = []
            
        gallery_cursor = conn.execute('SELECT image_path FROM gallery_images WHERE game_id = ?', (game['id'],))
        game_dict['galeri'] = [row['image_path'] for row in gallery_cursor.fetchall()]
        games_list.append(game_dict)
        
    conn.close()
    return json_response(games_list)

@app.route('/api/categories', methods=['GET'])
def get_categories():
    conn = get_db_connection()
    categories_list = [dict(row) for row in conn.execute('SELECT id, name, icon FROM categories ORDER BY name ASC').fetchall()]
    conn.close()
    return json_response(categories_list)

@app.route('/api/slider', methods=['GET'])
def get_slider_data():
    conn = get_db_connection()
    sliders = [dict(row) for row in conn.execute('SELECT s.*, g.oyun_adi FROM slider s LEFT JOIN games g ON s.game_id = g.id WHERE s.is_active = 1 ORDER BY s.display_order ASC').fetchall()]
    conn.close()
    return json_response(sliders)

@app.route('/api/settings', methods=['GET'])
def get_settings_for_client():
    settings = get_all_settings()
    client_settings = {
        key: settings.get(key, '') for key in 
        ['cafe_name', 'slogan', 'logo_file', 'background_type', 'background_file', 'background_opacity_factor', 
         'primary_color_start', 'primary_color_end', 'welcome_modal_enabled', 'welcome_modal_text', 
         'social_google', 'social_instagram', 'social_facebook', 'social_youtube']
    }
    return json_response(client_settings)

@app.route('/api/user/ratings', methods=['GET'])
@token_required
def get_user_ratings(current_user_id):
    conn = get_db_connection()
    user_ratings = {str(row['game_id']): row['rating'] for row in conn.execute('SELECT game_id, rating FROM user_ratings WHERE user_id = ?', (current_user_id,)).fetchall()}
    conn.close()
    return json_response(user_ratings)

@app.route('/api/user/favorites', methods=['GET'])
@token_required
def get_user_favorites(current_user_id):
    conn = get_db_connection()
    user_favorites = [row['game_id'] for row in conn.execute('SELECT game_id FROM user_favorites WHERE user_id = ?', (current_user_id,)).fetchall()]
    conn.close()
    return json_response(user_favorites)

@app.route('/api/user/recently_played', methods=['GET'])
@token_required
def get_recently_played(current_user_id):
    conn = get_db_connection()
    query = """
        SELECT g.*, GROUP_CONCAT(c.name) as kategoriler
        FROM user_played_games upg
        JOIN games g ON upg.game_id = g.id
        LEFT JOIN game_categories gc ON g.id = gc.game_id
        LEFT JOIN categories c ON gc.category_id = c.id
        WHERE upg.user_id = ?
        GROUP BY g.id
        ORDER BY upg.played_at DESC
        LIMIT 24
    """
    games_cursor = conn.execute(query, (current_user_id,)).fetchall()
    games_list = []
    for game in games_cursor:
        game_dict = dict(game)
        kategoriler_str = game_dict.get('kategoriler')
        game_dict['kategoriler'] = kategoriler_str.split(',') if kategoriler_str else []
        games_list.append(game_dict)
    conn.close()
    return json_response(games_list)

@app.route('/api/user/saves', methods=['GET'])
@token_required
def get_user_saves(current_user_id):
    user_save_dir = os.path.join(app.config['SAVE_FOLDER'], str(current_user_id))
    if not os.path.exists(user_save_dir): 
        return jsonify([])
    try:
        saved_games = [int(f.split('.')[0]) for f in os.listdir(user_save_dir) if f.endswith('.zip') and f.split('.')[0].isdigit()]
        return json_response(saved_games)
    except Exception as e:
        return jsonify({'mesaj': f'Kayıtlar okunurken bir hata oluştu: {e}'}), 500

@app.route('/api/games/<int:game_id>/click', methods=['POST'])
def increment_click_count(game_id):
    conn = get_db_connection()
    conn.execute('UPDATE games SET click_count = click_count + 1 WHERE id = ?', (game_id,))
    conn.commit()
    conn.close()
    return jsonify({'mesaj': 'Tıklama sayısı artırıldı.'}), 200

@app.route('/api/games/<int:game_id>/played', methods=['POST'])
@token_required
def record_played_game(current_user_id, game_id):
    conn = get_db_connection()
    conn.execute('''
        INSERT OR REPLACE INTO user_played_games (user_id, game_id, played_at)
        VALUES (?, ?, ?)
    ''', (current_user_id, game_id, datetime.utcnow()))
    conn.commit()
    conn.close()
    return jsonify({'mesaj': 'Oynanma zamanı kaydedildi.'}), 200

@app.route('/api/games/<int:game_id>/rate', methods=['POST'])
@token_required
def rate_game(current_user_id, game_id):
    rating = request.get_json().get('rating')
    if rating is None or not (0.5 <= rating <= 5): 
        return jsonify({'mesaj': 'Geçersiz puan değeri.'}), 400
    
    conn = get_db_connection()
    conn.execute('INSERT OR REPLACE INTO user_ratings (user_id, game_id, rating) VALUES (?, ?, ?)', (current_user_id, game_id, rating))
    stats = conn.execute('SELECT AVG(rating), COUNT(rating) FROM user_ratings WHERE game_id = ?', (game_id,)).fetchone()
    new_avg, new_count = (stats[0] or 0, stats[1])
    conn.execute('UPDATE games SET average_rating = ?, rating_count = ? WHERE id = ?', (new_avg, new_count, game_id))
    conn.commit()
    conn.close()
    return jsonify({'mesaj': 'Puan kaydedildi.', 'average_rating': round(new_avg, 1), 'rating_count': new_count})

@app.route('/api/games/<int:game_id>/favorite', methods=['POST'])
@token_required
def toggle_favorite(current_user_id, game_id):
    conn = get_db_connection()
    if conn.execute('SELECT * FROM user_favorites WHERE user_id = ? AND game_id = ?', (current_user_id, game_id)).fetchone():
        conn.execute('DELETE FROM user_favorites WHERE user_id = ? AND game_id = ?', (current_user_id, game_id))
        new_status, message = False, "Oyun favorilerden kaldırıldı."
    else:
        conn.execute('INSERT INTO user_favorites (user_id, game_id) VALUES (?, ?)', (current_user_id, game_id))
        new_status, message = True, "Oyun favorilere eklendi."
    conn.commit()
    conn.close()
    return jsonify({'mesaj': message, 'is_favorite': new_status})

@app.route('/api/games/<int:game_id>/100save', methods=['GET'])
@token_required
def download_100_save(current_user_id, game_id):
    conn = get_db_connection()
    game = conn.execute('SELECT yuzde_yuz_save_path FROM games WHERE id = ?', (game_id,)).fetchone()
    conn.close()
    if not game or not game['yuzde_yuz_save_path']: 
        return jsonify({'mesaj': '%100 save dosyası bulunamadı.'}), 404
    try:
        return send_from_directory(app.config['UPLOAD_FOLDER_100_SAVES'], game['yuzde_yuz_save_path'], as_attachment=True)
    except FileNotFoundError:
        return jsonify({'mesaj': 'Save dosyası sunucuda bulunamadı.'}), 404

@app.route('/')
def root():
    # Eğer admin giriş yapmışsa admin panele, yapmamışsa login sayfasına yönlendir
    if session.get('admin_logged_in'):
        return redirect(url_for('admin_index'))
    else:
        return redirect(url_for('admin_login'))

# Admin Authentication Routes
@app.route('/admin/login', methods=['GET', 'POST'])
def admin_login():
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        password = request.form.get('password', '')
        
        if not username or not password:
            flash('Kullanıcı adı ve şifre gereklidir.', 'error')
            return render_template('admin_login.html')
        
        # Admin kullanıcısını kontrol et
        conn = get_db_connection()
        try:
            admin_user = conn.execute('SELECT * FROM admin_users WHERE username = ?', (username,)).fetchone()
            
            if admin_user and check_password_hash(admin_user['password_hash'], password):
                session['admin_logged_in'] = True
                session['admin_username'] = admin_user['username']
                session['admin_id'] = admin_user['id']
                flash('Başarıyla giriş yaptınız!', 'success')
                return redirect(url_for('admin_index'))
            else:
                flash('Geçersiz kullanıcı adı veya şifre.', 'error')
                
        finally:
            conn.close()
    
    return render_template('admin_login.html')

@app.route('/admin/logout')
def admin_logout():
    session.clear()
    flash('Başarıyla çıkış yaptınız.', 'info')
    return redirect(url_for('admin_login'))

# Admin Panel Routes
@app.route('/admin')
@app.route('/admin/')
@admin_required
@license_required
def admin_index():
    conn = get_db_connection()
    stats = {
        'game_count': conn.execute('SELECT COUNT(*) FROM games').fetchone()[0],
        'category_count': conn.execute('SELECT COUNT(*) FROM categories').fetchone()[0],
        'user_count': conn.execute('SELECT COUNT(*) FROM users').fetchone()[0]
    }
    total_clicks = conn.execute('SELECT SUM(click_count) FROM games').fetchone()[0] or 0
    query = "SELECT g.id, g.oyun_adi, g.cover_image, g.created_at, GROUP_CONCAT(c.name) as category_names FROM games g LEFT JOIN game_categories gc ON g.id = gc.game_id LEFT JOIN categories c ON gc.category_id = c.id WHERE g.id IS NOT NULL GROUP BY g.id ORDER BY g.id DESC LIMIT 5"
    recent_games = [dict(g) for g in conn.execute(query).fetchall() if g['id'] is not None]
    
    # Disk bilgilerini al
    available_drives = get_windows_drives()
    custom_names = {}
    custom_names_result = conn.execute('SELECT drive_letter, custom_name FROM disk_settings').fetchall()
    for row in custom_names_result:
        custom_names[row['drive_letter']] = row['custom_name']
    
    # Diskleri hazırla
    for drive in available_drives:
        drive_letter = drive['device_id']
        drive['custom_name'] = custom_names.get(drive_letter, '')
        
        # Boyut bilgilerini formatla
        if drive['size']:
            drive['size_gb'] = round(drive['size'] / (1024**3), 1)
            drive['used_gb'] = round((drive['size'] - drive['free_space']) / (1024**3), 1)
            drive['usage_percent'] = round(((drive['size'] - drive['free_space']) / drive['size']) * 100, 1)
        else:
            drive['size_gb'] = 'Bilinmiyor'
            drive['used_gb'] = 'Bilinmiyor'
            drive['usage_percent'] = 0
            
        if drive['free_space']:
            drive['free_space_gb'] = round(drive['free_space'] / (1024**3), 1)
        else:
            drive['free_space_gb'] = 'Bilinmiyor'
    
    conn.close()
    return render_template('index.html', stats=stats, recent_games=recent_games, total_clicks=total_clicks, drives=available_drives)

@app.route('/admin/games')
@admin_required
@license_required
def list_games():
    conn = get_db_connection()
    try:
        # Filtre parametrelerini al
        search_query = request.args.get('q', '')
        category_filter = request.args.get('category', '')
        year_filter = request.args.get('year', '')
        language_filter = request.args.get('language', '')
        
        # Sayfalama parametrelerini al
        page = request.args.get('page', 1, type=int)
        per_page = request.args.get('per_page', 8, type=int)  # Varsayılan 8 oyun
        
        # Geçerli per_page değerlerini kontrol et (16'nın katları)
        valid_per_page = [8, 16, 32, 48]
        if per_page not in valid_per_page:
            per_page = 8
            
        offset = (page - 1) * per_page
        
        # Ana sorgu - toplam sayım için
        count_query = """
            SELECT COUNT(DISTINCT g.id)
            FROM games g
            LEFT JOIN game_categories gc ON g.id = gc.game_id
            LEFT JOIN categories c ON gc.category_id = c.id
        """
        
        # Ana sorgu - oyunları getirmek için
        query = """
            SELECT
                g.id, g.oyun_adi, g.aciklama, g.cover_image, g.youtube_id, g.save_yolu, g.calistirma_tipi,
                g.calistirma_verisi, g.cikis_yili, g.pegi, g.oyun_dili, g.launch_script, g.yuzde_yuz_save_path,
                g.average_rating, g.rating_count, g.click_count, g.created_at,
                (
                    SELECT GROUP_CONCAT(c2.name)
                    FROM game_categories gc2
                    JOIN categories c2 ON gc2.category_id = c2.id
                    WHERE gc2.game_id = g.id
                ) AS category_names
            FROM games g
            LEFT JOIN game_categories gc ON g.id = gc.game_id
            LEFT JOIN categories c ON gc.category_id = c.id
        """
        
        # Filtre koşullarını oluştur
        where_conditions = []
        params = []
        
        if search_query:
            where_conditions.append("g.oyun_adi LIKE ?")
            params.append('%' + search_query + '%')
            
        if category_filter:
            where_conditions.append("c.id = ?")
            params.append(category_filter)
            
        if year_filter:
            where_conditions.append("g.cikis_yili = ?")
            params.append(year_filter)
            
        if language_filter:
            where_conditions.append("g.oyun_dili = ?")
            params.append(language_filter)
        
        # WHERE koşullarını ekle
        if where_conditions:
            where_clause = " WHERE " + " AND ".join(where_conditions)
            count_query += where_clause
            query += where_clause
        
        # Toplam oyun sayısını al
        total_games = conn.execute(count_query, params).fetchone()[0]
        
        # Ana sorguyu tamamla
        query += " GROUP BY g.id ORDER BY g.id DESC LIMIT ? OFFSET ?"
        params.extend([per_page, offset])
        
        games_raw = conn.execute(query, params).fetchall()
        
        # Row objelerini dict'e çevir ve NULL ID'leri filtrele
        games_filtered = []
        for g_row in games_raw:
             g_dict = dict(g_row)
             if g_dict['id'] is not None:
                 games_filtered.append(g_dict)

        # Sayfalama bilgilerini hesapla
        total_pages = (total_games + per_page - 1) // per_page
        has_prev = page > 1
        has_next = page < total_pages
        
        # Filtre seçenekleri için verileri al
        categories = conn.execute('SELECT id, name FROM categories ORDER BY name ASC').fetchall()
        years = conn.execute('SELECT DISTINCT cikis_yili FROM games WHERE cikis_yili IS NOT NULL AND cikis_yili != "" ORDER BY cikis_yili DESC').fetchall()
        languages = conn.execute('SELECT DISTINCT oyun_dili FROM games WHERE oyun_dili IS NOT NULL AND oyun_dili != "" ORDER BY oyun_dili ASC').fetchall()

        return render_template('manage_games.html',
                             games=games_filtered,
                             search_query=search_query,
                             category_filter=category_filter,
                             year_filter=year_filter,
                             language_filter=language_filter,
                             categories=categories,
                             years=years,
                             languages=languages,
                             page=page,
                             per_page=per_page,
                             total_pages=total_pages,
                             total_games=total_games,
                             has_prev=has_prev,
                             has_next=has_next,
                             valid_per_page=valid_per_page)
    finally:
        if conn:
            conn.close()

@app.route('/admin/add', methods=['GET', 'POST'])
@admin_required
@license_required
def add_game():
    conn = get_db_connection()
    try: # Bağlantıyı güvence altına al
        if request.method == 'POST':
            form_data = request.form
            oyun_adi, aciklama, youtube_id, save_yolu, calistirma_tipi, cikis_yili, pegi, oyun_dili, launch_script = \
                (form_data.get(k) for k in ['oyun_adi', 'aciklama', 'youtube_id', 'save_yolu', 'calistirma_tipi', 'cikis_yili', 'pegi', 'oyun_dili', 'launch_script'])
            category_ids = request.form.getlist('category_ids')
            
            cover_image_filename = ''
            if 'cover_image' in request.files and request.files['cover_image'].filename:
                cover_image_filename = convert_to_webp(request.files['cover_image'], app.config['UPLOAD_FOLDER_COVERS'])
                
            yuzde_yuz_save_filename = ''
            if 'yuzde_yuz_save_file' in request.files and request.files['yuzde_yuz_save_file'].filename:
                file = request.files['yuzde_yuz_save_file']
                yuzde_yuz_save_filename = secure_filename(file.filename)
                file.save(os.path.join(app.config['UPLOAD_FOLDER_100_SAVES'], yuzde_yuz_save_filename))
                
            if calistirma_tipi == 'exe':
                calistirma_verisi = json.dumps({'yol': form_data.get('exe_yol'), 'argumanlar': form_data.get('exe_argumanlar', '')})
                
                # BASIT VARSAYILAN BETİK TANIMI
                DEFAULT_LAUNCH_SCRIPT_CLEAN = """@echo off
set _EXE_PATH="%%EXE_YOLU%%"

:: *****************************************************************
:: * OYUN BAŞLATMA OTOMATİK SCRIPTİ
:: * Sunucu Tarafından Otomatik Üretilmiştir.
:: * %%EXE_YOLU%% (Tam Yol) ve %%EXE_ARGS%% (Argümanlar) değişkenleri kullanılmıştır.
:: *****************************************************************

:: 1. Kaynak Disk ve Dosya Yolu Bilgisi
echo Disk: %%~d_EXE_PATH%%
echo Oyun Klasörü: %%~p_EXE_PATH%%
echo EXE: %%~nx_EXE_PATH%%
echo Argümanlar: %%EXE_ARGS%%

:: 2. Oyunu Başlat
start "" "%%EXE_YOLU%%" %%EXE_ARGS%%

:: NOT: Programı çalıştırmadan önce ek komutlar ekleyebilirsiniz.
"""

                # Basit varsayılan
                simple_old_default = 'start "" "%EXE_YOLU%" %EXE_ARGS%'
                
                # Eğer betik boşsa veya basit varsayılan ise yeni betiği kullan
                if not launch_script or launch_script.strip() == simple_old_default:
                    launch_script = DEFAULT_LAUNCH_SCRIPT_CLEAN.strip()
            else: # steam
                calistirma_verisi, launch_script = json.dumps({'app_id': form_data.get('steam_app_id')}), None
                
            cursor = conn.cursor()
            cursor.execute(''' INSERT INTO games(oyun_adi, aciklama, cover_image, youtube_id, save_yolu, calistirma_tipi, calistirma_verisi, cikis_yili, pegi, oyun_dili, launch_script, yuzde_yuz_save_path) 
                               VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ''', 
                           (oyun_adi, aciklama, cover_image_filename, youtube_id, save_yolu, calistirma_tipi, calistirma_verisi, cikis_yili, pegi, oyun_dili, launch_script, yuzde_yuz_save_filename))
            new_game_id = cursor.lastrowid
            
            # HATA DÜZELTME: category_ids listesindeki tekrar eden ID'leri kaldır ve int'e çevir
            if category_ids:
                # String ID'leri integer'a çevirip set ile benzersiz yap
                unique_category_ids = {int(cat_id) for cat_id in category_ids}
                conn.executemany('INSERT INTO game_categories (game_id, category_id) VALUES (?, ?)', [(new_game_id, cat_id) for cat_id in unique_category_ids])
            
            if 'gallery_images' in request.files:
                folder_name = get_gallery_folder_name(oyun_adi)
                for file in request.files.getlist('gallery_images'):
                    if file and file.filename:
                        path = convert_to_webp(file, app.config['UPLOAD_FOLDER_GALLERY'], sub_folder=folder_name)
                        conn.execute('INSERT INTO gallery_images (game_id, image_path) VALUES (?, ?)', (new_game_id, path))
            
            conn.commit()
            return redirect(url_for('list_games'))
            
        categories = conn.execute('SELECT * FROM categories ORDER BY name ASC').fetchall()
        languages = conn.execute('SELECT * FROM languages WHERE is_active = 1 ORDER BY name ASC').fetchall()
        return render_template('add_game.html', categories=categories, languages=languages)
    finally: # Bağlantı, ne olursa olsun burada kapatılır
        if conn:
            conn.close()

@app.route('/admin/edit/<int:game_id>', methods=['GET', 'POST'])
@admin_required
@license_required
def edit_game(game_id):
    conn = get_db_connection()
    try: # Bağlantıyı güvence altına al
        if request.method == 'POST':
            form_data = request.form
            oyun_adi, aciklama, youtube_id, save_yolu, calistirma_tipi, cikis_yili, pegi, oyun_dili, launch_script = \
                (form_data.get(k) for k in ['oyun_adi', 'aciklama', 'youtube_id', 'save_yolu', 'calistirma_tipi', 'cikis_yili', 'pegi', 'oyun_dili', 'launch_script'])
            category_ids = request.form.getlist('category_ids')
            
            cover_image_filename = form_data['current_cover_image']
            if 'cover_image' in request.files and request.files['cover_image'].filename:
                cover_image_filename = convert_to_webp(request.files['cover_image'], app.config['UPLOAD_FOLDER_COVERS'])
                
            yuzde_yuz_save_filename = form_data['current_yuzde_yuz_save_file']
            if 'yuzde_yuz_save_file' in request.files and request.files['yuzde_yuz_save_file'].filename:
                file = request.files['yuzde_yuz_save_file']
                yuzde_yuz_save_filename = secure_filename(file.filename)
                file.save(os.path.join(app.config['UPLOAD_FOLDER_100_SAVES'], yuzde_yuz_save_filename))
                
            folder_name = get_gallery_folder_name(oyun_adi)
            
            # Seçili görselleri sil
            delete_gallery_images = form_data.get('delete_gallery_images', '')
            if delete_gallery_images:
                image_paths = [path.strip() for path in delete_gallery_images.split(',') if path.strip()]
                for path in image_paths:
                    conn.execute('DELETE FROM gallery_images WHERE image_path = ? AND game_id = ?', (path, game_id))
                    try:
                        os.remove(os.path.join(app.config['UPLOAD_FOLDER_GALLERY'], path))
                    except OSError as e:
                        print(f"Dosya silinemedi: {e}")
            
            # Eski delete_gallery işlemini de koru (geriye uyumluluk için)
            if request.form.getlist('delete_gallery'):
                for path in request.form.getlist('delete_gallery'):
                    conn.execute('DELETE FROM gallery_images WHERE image_path = ? AND game_id = ?', (path, game_id))
                    try: os.remove(os.path.join(app.config['UPLOAD_FOLDER_GALLERY'], path))
                    except OSError as e: print(f"Dosya silinemedi: {e}")
                    
            if 'gallery_images' in request.files:
                for file in request.files.getlist('gallery_images'):
                    if file and file.filename:
                        path = convert_to_webp(file, app.config['UPLOAD_FOLDER_GALLERY'], sub_folder=folder_name)
                        conn.execute('INSERT INTO gallery_images (game_id, image_path) VALUES (?, ?)', (game_id, path))
            
            if calistirma_tipi == 'exe':
                calistirma_verisi = json.dumps({'yol': form_data.get('exe_yol'), 'argumanlar': form_data.get('exe_argumanlar', '')})
                
                # BASIT VARSAYILAN BETİK TANIMI
                DEFAULT_LAUNCH_SCRIPT_CLEAN = """@echo off
set _EXE_PATH="%%EXE_YOLU%%"

:: *****************************************************************
:: * OYUN BAŞLATMA OTOMATİK SCRIPTİ
:: * Sunucu Tarafından Otomatik Üretilmiştir.
:: * %%EXE_YOLU%% (Tam Yol) ve %%EXE_ARGS%% (Argümanlar) değişkenleri kullanılmıştır.
:: *****************************************************************

:: 1. Kaynak Disk ve Dosya Yolu Bilgisi
echo Disk: %%~d_EXE_PATH%%
echo Oyun Klasörü: %%~p_EXE_PATH%%
echo EXE: %%~nx_EXE_PATH%%
echo Argümanlar: %%EXE_ARGS%%

:: 2. Oyunu Başlat
start "" "%%EXE_YOLU%%" %%EXE_ARGS%%

:: NOT: Programı çalıştırmadan önce ek komutlar ekleyebilirsiniz.
"""

                # Basit varsayılan
                simple_old_default = 'start "" "%EXE_YOLU%" %EXE_ARGS%'
                
                # Eğer betik boşsa veya basit varsayılan ise yeni betiği kullan
                if not launch_script or launch_script.strip() == simple_old_default:
                    launch_script = DEFAULT_LAUNCH_SCRIPT_CLEAN.strip()
            else: # steam
                calistirma_verisi, launch_script = json.dumps({'app_id': form_data.get('steam_app_id')}), None
                
            sql = ''' UPDATE games SET oyun_adi=?, aciklama=?, cover_image=?, youtube_id=?, save_yolu=?, calistirma_tipi=?, calistirma_verisi=?, cikis_yili=?, pegi=?, oyun_dili=?, launch_script=?, yuzde_yuz_save_path=? WHERE id=? '''
            conn.execute(sql, (oyun_adi, aciklama, cover_image_filename, youtube_id, save_yolu, calistirma_tipi, calistirma_verisi, cikis_yili, pegi, oyun_dili, launch_script, yuzde_yuz_save_filename, game_id))
            
            conn.execute('DELETE FROM game_categories WHERE game_id = ?', (game_id,))
            # HATA DÜZELTME: category_ids listesindeki tekrar eden ID'leri kaldır ve int'e çevir
            if category_ids:
                unique_category_ids = {int(cat_id) for cat_id in category_ids} # Set comprehension ile benzersiz ID'leri topla
                conn.executemany('INSERT INTO game_categories (game_id, category_id) VALUES (?, ?)', [(game_id, cat_id) for cat_id in unique_category_ids])
            # HATA DÜZELTME SONU
                    
            conn.commit()
            return redirect(url_for('edit_game', game_id=game_id))
            
        game = conn.execute('SELECT * FROM games WHERE id = ?', (game_id,)).fetchone()
        if not game: return "Oyun bulunamadı!", 404
        
        game_data = dict(game)
        game_data['calistirma_verisi_dict'] = json.loads(game['calistirma_verisi'] or '{}')
        categories = conn.execute('SELECT * FROM categories ORDER BY name ASC').fetchall()
        languages = conn.execute('SELECT * FROM languages WHERE is_active = 1 ORDER BY name ASC').fetchall()
        gallery_images = conn.execute('SELECT * FROM gallery_images WHERE game_id = ?', (game_id,)).fetchall()
        game_category_ids = {row['category_id'] for row in conn.execute('SELECT category_id FROM game_categories WHERE game_id = ?', (game_id,)).fetchall()}
        
        return render_template('edit_game.html', game=game_data, categories=categories, languages=languages, gallery=gallery_images, game_category_ids=game_category_ids)
    finally: # Bağlantı, ne olursa olsun burada kapatılır
        if conn:
            conn.close()

@app.route('/admin/delete/<int:game_id>', methods=['POST'])
@admin_required
@license_required
def delete_game(game_id):
    conn = get_db_connection()
    try:
        conn.execute('DELETE FROM games WHERE id = ?', (game_id,))
        conn.commit()
        return redirect(url_for('list_games'))
    finally:
        if conn:
            conn.close()


@app.route('/admin/categories', methods=['GET', 'POST'])
@admin_required
@license_required
def manage_categories():
    conn = get_db_connection()
    try:
        if request.method == 'POST':
            category_name = request.form['name']
            category_icon = request.form.get('icon', '🎮')
            if category_name:
                try:
                    conn.execute('INSERT INTO categories (name, icon) VALUES (?, ?)', (category_name, category_icon))
                    conn.commit()
                except sqlite3.IntegrityError: pass
            return redirect(url_for('manage_categories'))
        categories = conn.execute('SELECT * FROM categories ORDER BY name ASC').fetchall()
        return render_template('manage_categories.html', categories=categories)
    finally:
        if conn:
            conn.close()

@app.route('/admin/categories/edit/<int:category_id>', methods=['GET', 'POST'])
@admin_required
@license_required
def edit_category(category_id):
    conn = get_db_connection()
    try:
        if request.method == 'POST':
            new_name = request.form['name']
            new_icon = request.form.get('icon', '🎮')
            if new_name:
                conn.execute('UPDATE categories SET name = ?, icon = ? WHERE id = ?', (new_name, new_icon, category_id))
                conn.commit()
            return redirect(url_for('manage_categories'))
        category = conn.execute('SELECT * FROM categories WHERE id = ?', (category_id,)).fetchone()
        if not category: return "Kategori bulunamadı!", 404
        return render_template('edit_category.html', category=category)
    finally:
        if conn:
            conn.close()

@app.route('/admin/categories/delete/<int:category_id>', methods=['POST'])
@admin_required
@license_required
def delete_category(category_id):
    conn = get_db_connection()
    try:
        conn.execute('DELETE FROM categories WHERE id = ?', (category_id,))
        conn.commit()
        return redirect(url_for('manage_categories'))
    finally:
        if conn:
            conn.close()

@app.route('/admin/users')
@admin_required
@license_required
def manage_users():
    conn = get_db_connection()
    try:
        users = conn.execute('SELECT id, username FROM users ORDER BY username ASC').fetchall()
        return render_template('manage_users.html', users=users)
    finally:
        if conn:
            conn.close()

@app.route('/admin/users/edit/<int:user_id>', methods=['GET', 'POST'])
@admin_required
@license_required
def edit_user(user_id):
    conn = get_db_connection()
    try:
        if request.method == 'POST':
            new_username = request.form.get('username', '').strip()
            new_password = request.form.get('new_password', '')
            if new_username and len(new_username) >= 3:
                if not conn.execute('SELECT id FROM users WHERE username = ? AND id != ?', (new_username, user_id)).fetchone():
                    conn.execute('UPDATE users SET username = ? WHERE id = ?', (new_username, user_id))
                    conn.commit()
            if new_password and len(new_password) >= 5:
                conn.execute('UPDATE users SET password_hash = ? WHERE id = ?', (generate_password_hash(new_password), user_id))
                conn.commit()
            return redirect(url_for('manage_users'))
        user = conn.execute('SELECT id, username FROM users WHERE id = ?', (user_id,)).fetchone()
        if not user: return "Kullanıcı bulunamadı!", 404
        return render_template('edit_user.html', user=user)
    finally:
        if conn:
            conn.close()

@app.route('/admin/users/delete/<int:user_id>', methods=['POST'])
@admin_required
@license_required
def delete_user(user_id):
    if user_id == 1: 
        return redirect(url_for('manage_users'))
    conn = get_db_connection()
    try:
        conn.execute('DELETE FROM users WHERE id = ?', (user_id,))
        conn.commit()
        user_save_dir = os.path.join(app.config['SAVE_FOLDER'], str(user_id))
        if os.path.exists(user_save_dir): 
            shutil.rmtree(user_save_dir)
        return redirect(url_for('manage_users'))
    finally:
        if conn:
            conn.close()

@app.route('/admin/sliders')
@admin_required
@license_required
def manage_sliders():
    conn = get_db_connection()
    try:
        sliders = conn.execute('SELECT s.*, g.oyun_adi FROM slider s LEFT JOIN games g ON s.game_id = g.id ORDER BY s.display_order ASC').fetchall()
        return render_template('manage_sliders.html', sliders=sliders)
    finally:
        if conn:
            conn.close()

@app.route('/admin/slider/add', methods=['GET', 'POST'])
@admin_required
@license_required
def add_slider():
    conn = get_db_connection()
    try:
        if request.method == 'POST':
            form = request.form
            bg_image = ''
            if 'background_image' in request.files and request.files['background_image'].filename:
                bg_image = convert_to_webp(request.files['background_image'], app.config['UPLOAD_FOLDER_SLIDER'])
            
            conn.execute('''INSERT INTO slider (game_id, badge_text, title, description, background_image, is_active, display_order) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)''', 
                           (form.get('game_id') or None, form.get('badge_text'), form.get('title'), form.get('description'), 
                            bg_image, 1 if 'is_active' in form else 0, form.get('display_order', 0)))
            conn.commit()
            return redirect(url_for('manage_sliders'))
        
        games = conn.execute('SELECT id, oyun_adi FROM games ORDER BY oyun_adi ASC').fetchall()
        return render_template('add_slider.html', games=games)
    finally:
        if conn:
            conn.close()

@app.route('/admin/slider/edit/<int:slider_id>', methods=['GET', 'POST'])
@admin_required
@license_required
def edit_slider(slider_id):
    conn = get_db_connection()
    try:
        if request.method == 'POST':
            form = request.form
            bg_image = form.get('current_background_image')
            if 'background_image' in request.files and request.files['background_image'].filename:
                if bg_image:
                    try: os.remove(os.path.join(app.config['UPLOAD_FOLDER_SLIDER'], bg_image))
                    except OSError: pass
                bg_image = convert_to_webp(request.files['background_image'], app.config['UPLOAD_FOLDER_SLIDER'])
                
            conn.execute('''UPDATE slider SET game_id=?, badge_text=?, title=?, description=?, background_image=?, is_active=?, display_order=? 
                            WHERE id=?''', 
                           (form.get('game_id') or None, form.get('badge_text'), form.get('title'), form.get('description'), 
                            bg_image, 1 if 'is_active' in form else 0, form.get('display_order', 0), slider_id))
            conn.commit()
            return redirect(url_for('manage_sliders'))
        
        slider = conn.execute('SELECT * FROM slider WHERE id = ?', (slider_id,)).fetchone()
        games = conn.execute('SELECT id, oyun_adi FROM games ORDER BY oyun_adi ASC').fetchall()
        return render_template('edit_slider.html', slider=slider, games=games)
    finally:
        if conn:
            conn.close()

@app.route('/admin/slider/delete/<int:slider_id>', methods=['POST'])
@admin_required
@license_required
def delete_slider(slider_id):
    conn = get_db_connection()
    try:
        slider = conn.execute('SELECT background_image FROM slider WHERE id = ?', (slider_id,)).fetchone()
        if slider and slider['background_image']:
            try: os.remove(os.path.join(app.config['UPLOAD_FOLDER_SLIDER'], slider['background_image']))
            except OSError: pass
        conn.execute('DELETE FROM slider WHERE id = ?', (slider_id,))
        conn.commit()
        return redirect(url_for('manage_sliders'))
    finally:
        if conn:
            conn.close()

@app.route('/admin/ratings')
@admin_required
@license_required
def manage_ratings():
    sort_by, order = request.args.get('sort_by', 'oyun_adi'), request.args.get('order', 'asc')
    if sort_by not in ['oyun_adi', 'average_rating', 'rating_count']: sort_by = 'oyun_adi'
    if order not in ['asc', 'desc']: order = 'asc'
    conn = get_db_connection()
    try:
        games = conn.execute(f"SELECT id, oyun_adi, average_rating, rating_count FROM games ORDER BY {sort_by} {order}").fetchall()
        return render_template('manage_ratings.html', games=games, sort_by=sort_by, next_order='desc' if order == 'asc' else 'asc')
    finally:
        if conn:
            conn.close()

@app.route('/admin/statistics')
@admin_required
@license_required
def manage_statistics():
    sort_by, order = request.args.get('sort_by', 'click_count'), request.args.get('order', 'desc')
    if sort_by not in ['oyun_adi', 'click_count']: sort_by = 'click_count'
    if order not in ['asc', 'desc']: order = 'desc'
    conn = get_db_connection()
    try:
        games = conn.execute(f"SELECT id, oyun_adi, click_count FROM games ORDER BY {sort_by} {order}").fetchall()
        return render_template('manage_statistics.html', games=games, sort_by=sort_by, next_order='desc' if order == 'asc' else 'asc')
    finally:
        if conn:
            conn.close()

@app.route('/admin/statistics/reset_all', methods=['POST'])
@admin_required
@license_required
def reset_all_clicks():
    conn = get_db_connection()
    try:
        conn.execute('UPDATE games SET click_count = 0')
        conn.commit()
        return redirect(url_for('manage_statistics'))
    finally:
        if conn:
            conn.close()

@app.route('/admin/ratings/reset_all', methods=['POST'])
@admin_required
@license_required
def reset_all_ratings():
    conn = get_db_connection()
    try:
        conn.execute('DELETE FROM user_ratings')
        conn.execute('UPDATE games SET average_rating = 0, rating_count = 0')
        conn.commit()
        return redirect(url_for('manage_ratings'))
    finally:
        if conn:
            conn.close()

@app.route('/admin/download_games')
@admin_required
@license_required
def download_games():
    return render_template('download_games.html')

@app.route('/admin/license_management', methods=['GET', 'POST'])
def license_management():
    settings = get_all_settings()
    license_key = settings.get('license_key', '')
    server_hwid, server_public_ip, message = get_hwid(), get_public_ip_from_server(), None
    
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'save_and_check':
            license_key = request.form.get('license_key', '')
            set_setting('license_key', license_key)
            check_license(license_key, server_hwid, server_public_ip)
            message = "Lisans kaydedildi ve doğrulandı!" if license_status_cache['status'] == 'valid' else f"Doğrulanamadı: {license_status_cache['reason']}"
        elif action == 'check_only':
            if license_key:
                check_license(license_key, server_hwid, server_public_ip)
                message = f"Durum: {license_status_cache['reason']}"
            else:
                message = "Önce bir lisans anahtarı kaydedin."
                
    return render_template('license_management.html', 
                           license_key=license_key, 
                           license_status=license_status_cache.get('status'), 
                           license_reason=license_status_cache.get('reason'), 
                           server_hwid=server_hwid, message=message, 
                           full_license_data=license_status_cache.get('full_api_response', {}), 
                           last_check_time=license_status_cache.get('last_checked'))

@app.route('/admin/social_media', methods=['GET', 'POST'])
@admin_required
@license_required
def social_media_settings():
    if request.method == 'POST':
        for key in ['social_google', 'social_instagram', 'social_facebook', 'social_youtube']:
            set_setting(key, request.form.get(key, ''))
        return redirect(url_for('social_media_settings'))
    
    settings = get_all_settings()
    return render_template('social_media_settings.html', settings=settings)

@app.route('/admin/settings', methods=['GET', 'POST'])
@admin_required
@license_required
def general_settings():
    if request.method == 'POST':
        if 'delete_logo' in request.form:
            logo_to_delete = get_all_settings().get('logo_file')
            if logo_to_delete:
                try: os.remove(os.path.join(app.config['UPLOAD_FOLDER_LOGOS'], logo_to_delete))
                except OSError as e: print(f"Logo silinemedi: {e}")
            set_setting('logo_file', '')
        else:
            for key in ['cafe_name', 'slogan', 'background_type', 'primary_color_start', 'primary_color_end', 'welcome_modal_text']:
                set_setting(key, request.form.get(key))
                
            set_setting('welcome_modal_enabled', '1' if 'welcome_modal_enabled' in request.form else '0')
            try:
                opacity = f"{max(0.1, min(1.0, float(request.form.get('background_opacity_factor', '1.0')))):.1f}"
                set_setting('background_opacity_factor', opacity)
            except ValueError:
                set_setting('background_opacity_factor', '1.0')

            if 'logo_file' in request.files and request.files['logo_file'].filename:
                file = request.files['logo_file']
                old_file = get_all_settings().get('logo_file')
                if old_file:
                    try: os.remove(os.path.join(app.config['UPLOAD_FOLDER_LOGOS'], old_file))
                    except OSError: pass
                filename = secure_filename(file.filename)
                file.save(os.path.join(app.config['UPLOAD_FOLDER_LOGOS'], filename))
                set_setting('logo_file', filename)

            if request.form['background_type'] == 'custom_bg' and 'custom_background_file' in request.files and request.files['custom_background_file'].filename:
                file = request.files['custom_background_file']
                old_file = get_all_settings().get('background_file')
                if old_file:
                    try: os.remove(os.path.join(app.config['UPLOAD_FOLDER_BG'], old_file))
                    except OSError: pass
                filename = secure_filename(file.filename)
                file.save(os.path.join(app.config['UPLOAD_FOLDER_BG'], filename))
                set_setting('background_file', filename)
            elif request.form['background_type'] == 'default':
                old_file = get_all_settings().get('background_file')
                if old_file:
                    try: os.remove(os.path.join(app.config['UPLOAD_FOLDER_BG'], old_file))
                    except OSError: pass
                set_setting('background_file', '')
                
        return redirect(url_for('general_settings'))

    settings = get_all_settings()
    return render_template('general_settings.html', settings=settings)

@app.route('/admin/languages', methods=['GET', 'POST'])
@admin_required
@license_required
def manage_languages():
    conn = get_db_connection()
    try:
        if request.method == 'POST':
            action = request.form.get('action')
            
            if action == 'add':
                language_name = request.form.get('name', '').strip()
                language_code = request.form.get('code', '').strip().lower()
                if language_name and language_code:
                    try:
                        conn.execute('INSERT INTO languages (name, code) VALUES (?, ?)', (language_name, language_code))
                        conn.commit()
                    except sqlite3.IntegrityError:
                        pass  # Dil zaten mevcut
                        
            elif action == 'toggle_status':
                language_id = request.form.get('language_id')
                if language_id:
                    current_status = conn.execute('SELECT is_active FROM languages WHERE id = ?', (language_id,)).fetchone()
                    if current_status:
                        new_status = 0 if current_status['is_active'] else 1
                        conn.execute('UPDATE languages SET is_active = ? WHERE id = ?', (new_status, language_id))
                        conn.commit()
                        
            elif action == 'delete':
                language_id = request.form.get('language_id')
                if language_id:
                    conn.execute('DELETE FROM languages WHERE id = ?', (language_id,))
                    conn.commit()
                    
            return redirect(url_for('manage_languages'))
            
        languages = conn.execute('SELECT * FROM languages ORDER BY name ASC').fetchall()
        return render_template('manage_languages.html', languages=languages)
    finally:
        if conn:
            conn.close()

@app.route('/admin/disk_settings', methods=['GET', 'POST'])
@admin_required
@license_required
def disk_settings():
    conn = get_db_connection()
    try:
        if request.method == 'POST':
            action = request.form.get('action')
            
            if action == 'update_all_disk_names':
                # Tüm form verilerini işle
                for key, value in request.form.items():
                    if key.startswith('custom_name_'):
                        drive_suffix = key.replace('custom_name_', '')
                        drive_letter_key = f'drive_letter_{drive_suffix}'
                        drive_letter = request.form.get(drive_letter_key)
                        custom_name = value.strip()
                        
                        if drive_letter:
                            # Mevcut kaydı kontrol et
                            existing = conn.execute('SELECT * FROM disk_settings WHERE drive_letter = ?', (drive_letter,)).fetchone()
                            
                            if existing:
                                # Güncelle
                                conn.execute('UPDATE disk_settings SET custom_name = ? WHERE drive_letter = ?',
                                           (custom_name, drive_letter))
                            else:
                                # Yeni kayıt ekle
                                conn.execute('INSERT INTO disk_settings (drive_letter, custom_name) VALUES (?, ?)',
                                           (drive_letter, custom_name))
                
                conn.commit()
            
            return redirect(url_for('disk_settings'))
        
        # Sistem disklerini al
        available_drives = get_windows_drives()
        
        # Veritabanından özel isimleri al
        custom_names = {}
        custom_names_result = conn.execute('SELECT drive_letter, custom_name FROM disk_settings').fetchall()
        for row in custom_names_result:
            custom_names[row['drive_letter']] = row['custom_name']
        
        # Diskleri birleştir
        for drive in available_drives:
            drive_letter = drive['device_id']
            drive['custom_name'] = custom_names.get(drive_letter, '')
            
            # Boyut bilgilerini formatla
            if drive['size']:
                drive['size_gb'] = round(drive['size'] / (1024**3), 1)
            else:
                drive['size_gb'] = 'Bilinmiyor'
                
            if drive['free_space']:
                drive['free_space_gb'] = round(drive['free_space'] / (1024**3), 1)
            else:
                drive['free_space_gb'] = 'Bilinmiyor'
        
        return render_template('disk_settings.html', drives=available_drives)
        
    finally:
        if conn:
            conn.close()

@app.route('/admin/images')
@admin_required
@license_required
def manage_images():
    """Görsel yönetimi sayfası - tüm görsel dosyalarını listeler ve yönetim imkanı sağlar"""
    try:
        image_data = {}
        
        # Her klasör için görsel dosyalarını say ve listele
        image_folders = {
            'covers': app.config['UPLOAD_FOLDER_COVERS'],
            'gallery': app.config['UPLOAD_FOLDER_GALLERY'],
            'slider': app.config['UPLOAD_FOLDER_SLIDER'],
            'backgrounds': app.config['UPLOAD_FOLDER_BG'],
            'logos': app.config['UPLOAD_FOLDER_LOGOS']
        }
        
        for folder_name, folder_path in image_folders.items():
            if os.path.exists(folder_path):
                files = []
                for file in os.listdir(folder_path):
                    if file.lower().endswith(('.png', '.jpg', '.jpeg', '.webp', '.gif')):
                        file_path = os.path.join(folder_path, file)
                        try:
                            file_size = os.path.getsize(file_path)
                            # Dosya boyutunu okunaklı formata çevir
                            if file_size < 1024:
                                size_str = f"{file_size} B"
                            elif file_size < 1024 * 1024:
                                size_str = f"{file_size / 1024:.1f} KB"
                            else:
                                size_str = f"{file_size / (1024 * 1024):.1f} MB"
                            
                            # Dosya tarihini al
                            mod_time = os.path.getmtime(file_path)
                            date_str = datetime.fromtimestamp(mod_time).strftime('%d.%m.%Y')
                            
                            files.append({
                                'name': file,
                                'size': size_str,
                                'date': date_str,
                                'path': file_path
                            })
                        except OSError:
                            continue
                
                image_data[folder_name] = {
                    'count': len(files),
                    'files': sorted(files, key=lambda x: x['name'])
                }
            else:
                image_data[folder_name] = {'count': 0, 'files': []}
        
        return render_template('manage_images.html', images=image_data)
    
    except Exception as e:
        print(f"Görsel yönetimi sayfası hatası: {e}")
        return render_template('manage_images.html', images={}, error=str(e))

# Görsel Yönetimi API Endpoints
@app.route('/admin/images/upload/cover', methods=['POST'])
@admin_required
@license_required
def upload_cover_image():
    """Kapak görseli yükleme"""
    try:
        if 'cover_image' not in request.files:
            return jsonify({'success': False, 'error': 'Dosya seçilmedi'}), 400
        
        file = request.files['cover_image']
        if file.filename == '':
            return jsonify({'success': False, 'error': 'Dosya seçilmedi'}), 400
        
        if file and file.filename:
            # Dosya türü kontrolü
            allowed_extensions = {'.png', '.jpg', '.jpeg', '.webp'}
            file_ext = os.path.splitext(file.filename)[1].lower()
            if file_ext not in allowed_extensions:
                return jsonify({'success': False, 'error': 'Desteklenmeyen dosya türü'}), 400
            
            # WebP formatına çevir ve kaydet
            stored_path = convert_to_webp(file, app.config['UPLOAD_FOLDER_COVERS'])
            return jsonify({'success': True, 'filename': stored_path})
        
    except Exception as e:
        return jsonify({'success': False, 'error': f'Yükleme hatası: {str(e)}'}), 500

@app.route('/admin/images/upload/gallery', methods=['POST'])
@admin_required
@license_required
def upload_gallery_images():
    """Galeri görsellerini yükleme"""
    try:
        if 'gallery_images' not in request.files:
            return jsonify({'success': False, 'error': 'Dosya seçilmedi'}), 400
        
        files = request.files.getlist('gallery_images')
        if not files or all(f.filename == '' for f in files):
            return jsonify({'success': False, 'error': 'Dosya seçilmedi'}), 400
        
        uploaded_count = 0
        allowed_extensions = {'.png', '.jpg', '.jpeg', '.webp'}
        
        for file in files:
            if file and file.filename:
                file_ext = os.path.splitext(file.filename)[1].lower()
                if file_ext in allowed_extensions:
                    try:
                        # WebP formatına çevir ve genel galeri klasörüne kaydet
                        convert_to_webp(file, app.config['UPLOAD_FOLDER_GALLERY'])
                        uploaded_count += 1
                    except Exception as e:
                        print(f"Dosya yükleme hatası: {e}")
                        continue
        
        if uploaded_count > 0:
            return jsonify({'success': True, 'uploaded_count': uploaded_count})
        else:
            return jsonify({'success': False, 'error': 'Hiç dosya yüklenemedi'}), 400
        
    except Exception as e:
        return jsonify({'success': False, 'error': f'Yükleme hatası: {str(e)}'}), 500

@app.route('/admin/images/delete/cover', methods=['POST'])
@admin_required
@license_required
def delete_cover_image():
    """Kapak görseli silme"""
    try:
        data = request.get_json()
        if not data or 'image_name' not in data:
            return jsonify({'success': False, 'error': 'Görsel adı belirtilmedi'}), 400
        
        image_name = data['image_name']
        file_path = os.path.join(app.config['UPLOAD_FOLDER_COVERS'], image_name)
        
        if os.path.exists(file_path):
            os.remove(file_path)
            return jsonify({'success': True})
        else:
            return jsonify({'success': False, 'error': 'Dosya bulunamadı'}), 404
            
    except Exception as e:
        return jsonify({'success': False, 'error': f'Silme hatası: {str(e)}'}), 500

@app.route('/admin/images/delete/gallery', methods=['POST'])
@admin_required
@license_required
def delete_gallery_images():
    """Galeri görsellerini silme"""
    try:
        data = request.get_json()
        if not data or 'image_names' not in data:
            return jsonify({'success': False, 'error': 'Görsel adları belirtilmedi'}), 400
        
        image_names = data['image_names']
        if not isinstance(image_names, list):
            return jsonify({'success': False, 'error': 'Geçersiz veri formatı'}), 400
        
        deleted_count = 0
        for image_name in image_names:
            file_path = os.path.join(app.config['UPLOAD_FOLDER_GALLERY'], image_name)
            if os.path.exists(file_path):
                try:
                    os.remove(file_path)
                    deleted_count += 1
                except Exception as e:
                    print(f"Dosya silme hatası: {e}")
                    continue
        
        return jsonify({'success': True, 'deleted_count': deleted_count})
            
    except Exception as e:
        return jsonify({'success': False, 'error': f'Silme hatası: {str(e)}'}), 500

@app.route('/admin/images/data/covers', methods=['GET'])
@admin_required
@license_required
def get_cover_images_data():
    """Kapak görsellerini JSON olarak döndür"""
    try:
        covers_path = app.config['UPLOAD_FOLDER_COVERS']
        files = []
        
        if os.path.exists(covers_path):
            for file in os.listdir(covers_path):
                if file.lower().endswith(('.png', '.jpg', '.jpeg', '.webp', '.gif')):
                    file_path = os.path.join(covers_path, file)
                    try:
                        file_size = os.path.getsize(file_path)
                        size_str = f"{file_size / 1024:.1f} KB" if file_size < 1024 * 1024 else f"{file_size / (1024 * 1024):.1f} MB"
                        mod_time = os.path.getmtime(file_path)
                        date_str = datetime.fromtimestamp(mod_time).strftime('%d.%m.%Y')
                        
                        files.append({
                            'name': file,
                            'size': size_str,
                            'date': date_str
                        })
                    except OSError:
                        continue
        
        return jsonify({'success': True, 'files': sorted(files, key=lambda x: x['name'])})
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/disk_list', methods=['GET'])
@admin_required
@license_required
def get_disk_list_api():
    """Disk listesi API endpoint'i - oyun ayarlarında kullanılmak üzere"""
    try:
        conn = get_db_connection()
        
        # Sistem disklerini al
        available_drives = get_windows_drives()
        
        # Veritabanından özel isimleri al
        custom_names = {}
        custom_names_result = conn.execute('SELECT drive_letter, custom_name FROM disk_settings').fetchall()
        for row in custom_names_result:
            custom_names[row['drive_letter']] = row['custom_name']
        
        # Disk listesini hazırla
        disk_list = []
        for drive in available_drives:
            drive_letter = drive['device_id']
            display_name = custom_names.get(drive_letter, drive['volume_name'])
            
            disk_list.append({
                'drive_letter': drive_letter,
                'display_name': f"{display_name} ({drive_letter})",
                'volume_name': drive['volume_name'],
                'custom_name': custom_names.get(drive_letter, ''),
                'available': True
            })
        
        conn.close()
        return json_response({'success': True, 'disks': disk_list})
        
    except Exception as e:
        return jsonify({'success': False, 'error': f'Disk listesi alınamadı: {str(e)}'}), 500

@app.route('/api/internal/check_status')
def check_status_for_client():
    global license_status_cache
    last_checked = license_status_cache.get('last_checked')
    if not last_checked or (datetime.now() - last_checked) > timedelta(hours=1):
        license_key = get_all_settings().get('license_key', '')
        if license_key:
            check_license(license_key, get_hwid(), get_public_ip_from_server())

    if license_status_cache.get('status') == 'valid':
        return jsonify({"status": "ok"})
    else:
        full_response = license_status_cache.get('full_api_response', {})
        full_response['status'] = 'error'
        return jsonify(full_response), 403

if __name__ == '__main__':
    init_db()
    for folder_key in ['UPLOAD_FOLDER_COVERS', 'UPLOAD_FOLDER_GALLERY', 'SAVE_FOLDER', 'UPLOAD_FOLDER_BG', 'UPLOAD_FOLDER_100_SAVES', 'UPLOAD_FOLDER_LOGOS', 'UPLOAD_FOLDER_SLIDER']:
        folder_path = app.config.get(folder_key)
        if folder_path and not os.path.exists(folder_path):
            os.makedirs(folder_path)
    app.run(debug=True, host='0.0.0.0', port=5001)
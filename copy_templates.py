import os
import shutil

# Kaynak ve hedef klasörler
source_dir = 'server/templates/eski'
target_dir = 'server/templates'

# Hedef klasörü oluştur
os.makedirs(target_dir, exist_ok=True)

# Tüm dosyaları kopyala
copied_files = []
for root, dirs, files in os.walk(source_dir):
    for file in files:
        source_file = os.path.join(root, file)
        # Hedef dosya yolunu hesapla (eski klasör yolunu çıkar)
        rel_path = os.path.relpath(source_file, source_dir)
        target_file = os.path.join(target_dir, rel_path)
        
        # Hedef klasörü oluştur
        os.makedirs(os.path.dirname(target_file), exist_ok=True)
        
        # Dosyayı kopyala
        shutil.copy2(source_file, target_file)
        copied_files.append(file)
        print(f'Kopyalandı: {file}')

print(f'\n{len(copied_files)} dosya başarıyla kopyalandı.')


<?php
// Bu dosya, index.php tarafından çağrıldığı için config.php zaten dahil edilmiştir.

$name = $email = $phone = $company = $start_date = $end_date = "";
// YENİ ALANLAR EKLENDİ
$address = $city = $district = "";
$form_err = "";
$form_success = "";

// Form gönderildiğinde çalışacak kod bloğu
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customer'])) {

    // Form verilerini al
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $company = trim($_POST['company']);
    // YENİ EKLENEN ALANLAR ALINIYOR
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $license_type = $_POST['license_type'] ?? 'Standard';
    
    // Gelişmiş doğrulama
    $validation_errors = [];
    
    if (empty($name)) {
        $validation_errors[] = "Müşteri adı zorunludur";
    } elseif (strlen($name) < 2) {
        $validation_errors[] = "Müşteri adı en az 2 karakter olmalıdır";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = "Geçerli bir email adresi girin";
    }
    
    if (empty($start_date) || empty($end_date)) {
        $validation_errors[] = "Başlangıç ve bitiş tarihi zorunludur";
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $validation_errors[] = "Bitiş tarihi başlangıç tarihinden sonra olmalıdır";
    }
    
    if (!empty($validation_errors)) {
        $form_err = implode(", ", $validation_errors);
    } else {
        
        // 1. Müşteriyi veritabanına ekle
        $sql_customer = "INSERT INTO customers (name, email, phone, company, address, city, district) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt_customer = $mysqli->prepare($sql_customer)) {
            $stmt_customer->bind_param("sssssss", $name, $email, $phone, $company, $address, $city, $district);
            
            if ($stmt_customer->execute()) {
                // Başarılı olursa, eklenen müşterinin ID'sini al
                $customer_id = $stmt_customer->insert_id;

                // 2. Benzersiz bir lisans anahtarı oluştur
                $prefix_map = [
                    'Basic' => 'ZN-BSC',
                    'Standard' => 'ZN-STD', 
                    'Premium' => 'ZN-PRM',
                    'Enterprise' => 'ZN-ENT'
                ];
                $prefix = $prefix_map[$license_type] ?? 'ZN-STD';
                $license_key = $prefix . "-" . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4) . "-" . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);

                // 3. Lisansı veritabanına ekle
                $sql_license = "INSERT INTO licenses (customer_id, license_key, license_type, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, 'active')";
                
                if ($stmt_license = $mysqli->prepare($sql_license)) {
                    $stmt_license->bind_param("issss", $customer_id, $license_key, $license_type, $start_date, $end_date);
                    
                    if ($stmt_license->execute()) {
                        $form_success = "🎉 Müşteri ve lisansı başarıyla oluşturuldu! Lisans anahtarı: <strong>{$license_key}</strong>";
                        // Başarılı kayıt sonrası 3 saniye sonra yönlendir
                        echo "<script>setTimeout(() => window.location.href = 'index.php?tab=customers', 3000);</script>";
                    } else {
                        $form_err = "Lisans oluşturulurken bir hata oluştu.";
                    }
                    $stmt_license->close();
                }
            } else {
                $form_err = "Müşteri oluşturulurken bir hata oluştu.";
            }
            $stmt_customer->close();
        }
    }
}
?>

<!-- Form Header -->
<div class="mb-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
            <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-primary">Yeni Müşteri Kayıt Sistemi</h3>
            <p class="text-secondary text-sm">Müşteri bilgilerini girin ve otomatik lisans oluşturun</p>
        </div>
    </div>
    
    <!-- Progress Steps -->
    <div class="flex items-center gap-4 p-4 bg-black/20 rounded-lg border border-white/10">
        <div class="flex items-center gap-2 text-success">
            <div class="w-8 h-8 rounded-full bg-success flex items-center justify-center">
                <span class="text-white text-sm font-semibold">1</span>
            </div>
            <span class="text-sm font-medium">Müşteri Bilgileri</span>
        </div>
        <div class="w-8 h-px bg-border-primary"></div>
        <div class="flex items-center gap-2 text-muted">
            <div class="w-8 h-8 rounded-full bg-border-primary flex items-center justify-center">
                <span class="text-muted text-sm font-semibold">2</span>
            </div>
            <span class="text-sm font-medium">Lisans Ayarları</span>
        </div>
        <div class="w-8 h-px bg-border-primary"></div>
        <div class="flex items-center gap-2 text-muted">
            <div class="w-8 h-8 rounded-full bg-border-primary flex items-center justify-center">
                <span class="text-muted text-sm font-semibold">3</span>
            </div>
            <span class="text-sm font-medium">Onay & Kaydet</span>
        </div>
    </div>
</div>

<!-- Status Messages -->
<?php if(!empty($form_err)): ?>
    <div class="error-message mb-6">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
        <div>
            <strong>Hata!</strong>
            <span><?php echo $form_err; ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if(!empty($form_success)): ?>
    <div class="success-message mb-6">
        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <div>
            <strong>Başarılı!</strong>
            <span><?php echo $form_success; ?></span>
        </div>
        <div class="mt-2 text-xs">
            3 saniye içinde müşteri listesine yönlendiriliyorsunuz...
        </div>
    </div>
<?php endif; ?>

<!-- Main Form -->
<form action="index.php?tab=add_customer" method="post" id="customerForm" class="space-y-8">
    
    <!-- Müşteri Bilgileri Section -->
    <div class="form-section">
        <div class="form-section-header">
            <div class="form-section-icon">
                <i data-lucide="user" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="form-section-title">Müşteri Bilgileri</h4>
                <p class="form-section-description">Temel müşteri iletişim bilgilerini girin</p>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    Müşteri Adı
                </label>
                <input 
                    type="text" 
                    name="name" 
                    class="form-input" 
                    placeholder="Ad Soyad veya İşletme Adı"
                    value="<?php echo htmlspecialchars($name); ?>"
                    required
                    autocomplete="name"
                />
                <div class="form-hint">Müşterinin tam adı veya işletme adı</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                    Email Adresi
                </label>
                <input 
                    type="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="ornek@email.com"
                    value="<?php echo htmlspecialchars($email); ?>"
                    autocomplete="email"
                />
                <div class="form-hint">Bildirimler için email adresi (opsiyonel)</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                    Telefon Numarası
                </label>
                <input 
                    type="tel" 
                    name="phone" 
                    class="form-input" 
                    placeholder="0XXX XXX XX XX"
                    value="<?php echo htmlspecialchars($phone); ?>"
                    autocomplete="tel"
                />
                <div class="form-hint">İletişim için telefon numarası</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i data-lucide="building" class="w-4 h-4"></i>
                    Şirket / Kafe Adı
                </label>
                <input 
                    type="text" 
                    name="company" 
                    class="form-input" 
                    placeholder="İşletme veya kafe adı"
                    value="<?php echo htmlspecialchars($company); ?>"
                    autocomplete="organization"
                />
                <div class="form-hint">İşletme adı (opsiyonel)</div>
            </div>
        </div>
    </div>

    <!-- Adres Bilgileri Section -->
    <div class="form-section">
        <div class="form-section-header">
            <div class="form-section-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                <i data-lucide="map-pin" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="form-section-title">Adres Bilgileri</h4>
                <p class="form-section-description">Müşterinin konum bilgilerini girin</p>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">
                    <i data-lucide="map" class="w-4 h-4"></i>
                    İl
                </label>
                <input 
                    type="text" 
                    name="city" 
                    class="form-input" 
                    placeholder="İstanbul, Ankara, İzmir..."
                    value="<?php echo htmlspecialchars($city); ?>"
                />
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i data-lucide="navigation" class="w-4 h-4"></i>
                    İlçe
                </label>
                <input 
                    type="text" 
                    name="district" 
                    class="form-input" 
                    placeholder="İlçe adı"
                    value="<?php echo htmlspecialchars($district); ?>"
                />
            </div>
            
            <div class="form-group col-span-full">
                <label class="form-label">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    Açık Adres
                </label>
                <textarea 
                    name="address" 
                    rows="3" 
                    class="form-input" 
                    placeholder="Mahalle, sokak, bina no ve diğer adres detayları..."
                ><?php echo htmlspecialchars($address); ?></textarea>
                <div class="form-hint">Detaylı adres bilgisi (opsiyonel)</div>
            </div>
        </div>
    </div>

    <!-- Lisans Ayarları Section -->
    <div class="form-section">
        <div class="form-section-header">
            <div class="form-section-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i data-lucide="key" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="form-section-title">Lisans Ayarları</h4>
                <p class="form-section-description">Lisans türü ve geçerlilik süresi</p>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">
                    <i data-lucide="calendar-days" class="w-4 h-4"></i>
                    Başlangıç Tarihi
                </label>
                <input 
                    type="date" 
                    name="start_date" 
                    class="form-input" 
                    value="<?php echo date('Y-m-d'); ?>"
                    min="<?php echo date('Y-m-d'); ?>"
                    required
                />
                <div class="form-hint">Lisansın geçerli olacağı ilk tarih</div>
            
            </div>
            
            <div class="form-group">
                <label class="form-label required">
                    <i data-lucide="calendar-x" class="w-4 h-4"></i>
                    Bitiş Tarihi
                </label>
                <input 
                    type="date" 
                    name="end_date" 
                    class="form-input" 
                    value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>"
                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                    required
                />
                <div class="form-hint">Lisansın sona ereceği tarih</div>
            </div>
            
            <div class="form-group col-span-full">
                <label class="form-label required">
                    <i data-lucide="package" class="w-4 h-4"></i>
                    Lisans Türü
                </label>
                <div class="license-type-grid">
                    <div class="license-type-option">
                        <input type="radio" name="license_type" value="Basic" id="license_basic" class="license-radio">
                        <label for="license_basic" class="license-label">
                            <div class="license-icon" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold">Basic</h5>
                                <p class="text-sm text-muted">Temel özellikler</p>
                                <p class="text-xs text-muted mt-1">Küçük kafeler için</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="license-type-option">
                        <input type="radio" name="license_type" value="Standard" id="license_standard" class="license-radio" checked>
                        <label for="license_standard" class="license-label">
                            <div class="license-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                                <i data-lucide="star" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold">Standard</h5>
                                <p class="text-sm text-muted">Standart özellikler</p>
                                <p class="text-xs text-muted mt-1">Orta ölçekli işletmeler</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="license-type-option">
                        <input type="radio" name="license_type" value="Premium" id="license_premium" class="license-radio">
                        <label for="license_premium" class="license-label">
                            <div class="license-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                <i data-lucide="crown" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold">Premium</h5>
                                <p class="text-sm text-muted">Gelişmiş özellikler</p>
                                <p class="text-xs text-muted mt-1">Büyük işletmeler</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="license-type-option">
                        <input type="radio" name="license_type" value="Enterprise" id="license_enterprise" class="license-radio">
                        <label for="license_enterprise" class="license-label">
                            <div class="license-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                <i data-lucide="zap" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold">Enterprise</h5>
                                <p class="text-sm text-muted">Tüm özellikler</p>
                                <p class="text-xs text-muted mt-1">Kurumsal çözümler</p>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="form-hint">Müşterinin ihtiyacına uygun lisans türünü seçin</div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
        <div class="flex items-center justify-between">
            <a href="?tab=customers" class="btn btn-secondary">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Geri Dön
            </a>
            
            <div class="flex items-center gap-3">
                <button type="button" onclick="previewCustomer()" class="btn btn-secondary">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    Önizleme
                </button>
                
                <button type="submit" name="add_customer" class="btn btn-primary" id="submitBtn">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Müşteri ve Lisans Oluştur
                    <div class="loading" style="display: none;"></div>
                </button>
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg">
            <div class="flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5"></i>
                <div class="text-sm">
                    <p class="text-blue-400 font-medium mb-1">Bilgi</p>
                    <p class="text-secondary">
                        Müşteri kaydı oluşturulduktan sonra otomatik olarak benzersiz bir lisans anahtarı üretilecek ve 
                        seçilen tarih aralığında aktif olacaktır. Tüm bilgileri kontrol ettikten sonra kaydet butonuna tıklayın.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
/* Form-specific styles */
.form-section {
    background: var(--gradient-card);
    backdrop-filter: blur(20px);
    border: 1px solid var(--border-primary);
    border-radius: 16px;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.form-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--gradient-primary);
}

.form-section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-primary);
}

.form-section-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-md);
}

.form-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.form-section-description {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.col-span-full {
    grid-column: 1 / -1;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-label.required::after {
    content: '*';
    color: var(--error);
    margin-left: 0.25rem;
}

.form-hint {
    color: var(--text-muted);
    font-size: 0.75rem;
    margin-top: 0.5rem;
}

.license-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.license-type-option {
    position: relative;
}

.license-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.license-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-primary);
    border-radius: 12px;
    cursor: pointer;
    transition: all var(--transition-normal);
}

.license-label:hover {
    background: var(--border-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.license-radio:checked + .license-label {
    background: rgba(229, 9, 20, 0.1);
    border-color: var(--primary-red);
    box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
}

.license-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.form-actions {
    background: var(--gradient-card);
    backdrop-filter: blur(20px);
    border: 1px solid var(--border-primary);
    border-radius: 16px;
    padding: 2rem;
}

.error-message, .success-message {
    display: flex;
    align-items: start;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
}

.error-message {
    background: var(--error-bg);
    color: var(--error);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.success-message {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.2);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .license-type-grid {
        grid-template-columns: 1fr;
    }
    
    .form-section {
        padding: 1.5rem;
    }
    
    .form-actions .flex {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customerForm');
    const submitBtn = document.getElementById('submitBtn');
    const loading = submitBtn.querySelector('.loading');
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const name = form.querySelector('input[name="name"]').value.trim();
        const startDate = form.querySelector('input[name="start_date"]').value;
        const endDate = form.querySelector('input[name="end_date"]').value;
        
        if (!name || !startDate || !endDate) {
            e.preventDefault();
            alert('Lütfen zorunlu alanları doldurun!');
            return;
        }
        
        if (new Date(startDate) >= new Date(endDate)) {
            e.preventDefault();
            alert('Bitiş tarihi başlangıç tarihinden sonra olmalıdır!');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        loading.style.display = 'block';
    });
    
    // Auto-calculate end date based on license type
    const licenseRadios = document.querySelectorAll('input[name="license_type"]');
    const endDateInput = document.querySelector('input[name="end_date"]');
    
    licenseRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const startDate = new Date(document.querySelector('input[name="start_date"]').value);
            if (startDate) {
                let months = 12; // Default
                switch (this.value) {
                    case 'Basic': months = 6; break;
                    case 'Standard': months = 12; break;
                    case 'Premium': months = 18; break;
                    case 'Enterprise': months = 24; break;
                }
                
                const endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + months);
                endDateInput.value = endDate.toISOString().split('T')[0];
            }
        });
    });
});

function previewCustomer() {
    const form = document.getElementById('customerForm');
    const formData = new FormData(form);
    
    let preview = 'Müşteri Önizlemesi:\n\n';
    preview += `Adı: ${formData.get('name') || 'Belirtilmemiş'}\n`;
    preview += `Email: ${formData.get('email') || 'Belirtilmemiş'}\n`;
    preview += `Telefon: ${formData.get('phone') || 'Belirtilmemiş'}\n`;
    preview += `Şirket: ${formData.get('company') || 'Belirtilmemiş'}\n`;
    preview += `Şehir: ${formData.get('city') || 'Belirtilmemiş'}\n`;
    preview += `Lisans Türü: ${formData.get('license_type')}\n`;
    preview += `Başlangıç: ${formData.get('start_date')}\n`;
    preview += `Bitiş: ${formData.get('end_date')}\n`;
    
    alert(preview);
}
</script>
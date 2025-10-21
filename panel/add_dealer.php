<?php
$dealer_name = $contact_person = $email = $phone = $address = $city = $district = $commission_rate = "";
$form_err = "";
$form_success = "";

// Benzersiz dealer code oluşturma fonksiyonu
function generateUniqueDealerCode($mysqli) {
    do {
        $dealer_code = 'D' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $check = $mysqli->prepare("SELECT dealer_code FROM dealers WHERE dealer_code = ?");
        $check->bind_param("s", $dealer_code);
        $check->execute();
        $result = $check->get_result();
        $check->close();
    } while ($result->num_rows > 0);
    
    return $dealer_code;
}

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_dealer'])) {
    
    $dealer_name = trim($_POST['dealer_name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $commission_rate = trim($_POST['commission_rate']);
    $status = $_POST['status'];
    
    // Basit doğrulama
    if (empty($dealer_name)) {
        $form_err = "Bayi adı zorunludur.";
    } elseif (!is_numeric($commission_rate) || $commission_rate < 0 || $commission_rate > 100) {
        $form_err = "Geçerli bir komisyon oranı girin (0-100).";
    } else {
        
        $dealer_code = generateUniqueDealerCode($mysqli);
        
        $sql = "INSERT INTO dealers (dealer_code, dealer_name, contact_person, email, phone, address, city, district, commission_rate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssssssssds", $dealer_code, $dealer_name, $contact_person, $email, $phone, $address, $city, $district, $commission_rate, $status);
            
            if ($stmt->execute()) {
                $form_success = "Bayi başarıyla oluşturuldu! Bayi Kodu: <strong>$dealer_code</strong>";
                header("refresh:3;url=index.php?tab=dealers");
            } else {
                $form_err = "Bayi oluşturulurken bir hata oluştu.";
            }
            $stmt->close();
        }
    }
}
?>

<div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900 mb-2">Yeni Bayi Ekle</h3>
        <p class="text-sm text-gray-500">Bayi ağınıza yeni bir bayi ekleyin</p>
    </div>

    <?php 
    if(!empty($form_err)){
        echo '<div class="bg-red-50 text-red-600 border border-red-200 p-3 rounded-lg mb-4 text-sm flex items-start gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                <span>' . $form_err . '</span>
              </div>';
    }
    if(!empty($form_success)){
        echo '<div class="bg-green-50 text-green-600 border border-green-200 p-3 rounded-lg mb-4 text-sm flex items-start gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                <span>' . $form_success . '</span>
              </div>';
    }
    ?>

    <form action="index.php?tab=add_dealer" method="post">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Sol Kolon -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bayi Adı (*)</label>
                    <input type="text" name="dealer_name" placeholder="Bayi veya Firma Adı" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">İletişim Kişisi</label>
                    <input type="text" name="contact_person" placeholder="Ad Soyad" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" placeholder="email@example.com" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefon</label>
                    <input type="tel" name="phone" placeholder="0XXX XXX XX XX" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
            </div>
            
            <!-- Sağ Kolon -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">İl</label>
                    <input type="text" name="city" placeholder="Şehir" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">İlçe</label>
                    <input type="text" name="district" placeholder="İlçe" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Komisyon Oranı (*)</label>
                    <div class="relative">
                        <input type="number" name="commission_rate" placeholder="0.00" step="0.01" min="0" max="100" value="10.00" required class="w-full px-4 py-2.5 pr-12 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <span class="text-gray-500 font-medium">%</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Durum (*)</label>
                    <select name="status" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                        <option value="suspended">Askıda</option>
                    </select>
                </div>
            </div>
            
            <!-- Adres (Full Width) -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Adres</label>
                <textarea name="address" rows="3" placeholder="Açık Adres Bilgisi" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"></textarea>
            </div>
            
            <!-- Buttons -->
            <div class="md:col-span-2 flex items-center gap-3 pt-4 border-t border-gray-200">
                <button type="submit" name="add_dealer" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Bayi Oluştur</span>
                </button>
                <a href="index.php?tab=dealers" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    <span>İptal</span>
                </a>
            </div>
        </div>
    </form>
</div>

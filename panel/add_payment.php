<?php
$form_err = "";
$form_success = "";

// Müşterileri çek
$customers_sql = "SELECT id, name, company FROM customers ORDER BY name ASC";
$customers_result = $mysqli->query($customers_sql);

// Bayileri çek
$dealers_sql = "SELECT id, dealer_name FROM dealers WHERE status = 'active' ORDER BY dealer_name ASC";
$dealers_result = $mysqli->query($dealers_sql);

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment'])) {
    
    $customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
    $license_id = filter_input(INPUT_POST, 'license_id', FILTER_VALIDATE_INT);
    $dealer_id = filter_input(INPUT_POST, 'dealer_id', FILTER_VALIDATE_INT);
    $amount = trim($_POST['amount']);
    $currency = $_POST['currency'];
    $payment_method = $_POST['payment_method'];
    $payment_date = $_POST['payment_date'];
    $payment_status = $_POST['payment_status'];
    $invoice_number = trim($_POST['invoice_number']);
    $notes = trim($_POST['notes']);
    
    // Basit doğrulama
    if (!$customer_id) {
        $form_err = "Lütfen bir müşteri seçin.";
    } elseif (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $form_err = "Geçerli bir tutar girin.";
    } elseif (empty($payment_date)) {
        $form_err = "Ödeme tarihi gereklidir.";
    } else {
        
        $sql = "INSERT INTO payments (customer_id, license_id, dealer_id, amount, currency, payment_method, payment_date, payment_status, invoice_number, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("iiidssssss", 
                $customer_id, 
                $license_id, 
                $dealer_id, 
                $amount, 
                $currency, 
                $payment_method, 
                $payment_date, 
                $payment_status, 
                $invoice_number, 
                $notes
            );
            
            if ($stmt->execute()) {
                $form_success = "Ödeme kaydı başarıyla oluşturuldu!";
                header("refresh:2;url=index.php?tab=payments");
            } else {
                $form_err = "Ödeme kaydı oluşturulurken bir hata oluştu: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900 mb-2">Yeni Ödeme Ekle</h3>
        <p class="text-sm text-gray-500">Sisteme yeni bir ödeme kaydı ekleyin</p>
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

    <form action="index.php?tab=add_payment" method="post">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Sol Kolon -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Müşteri (*)</label>
                    <select name="customer_id" id="customer_id" required onchange="loadCustomerLicenses(this.value)" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">Müşteri Seçin</option>
                        <?php 
                        if($customers_result && $customers_result->num_rows > 0):
                            while($customer = $customers_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $customer['id']; ?>">
                            <?php echo htmlspecialchars($customer['name']); ?>
                            <?php echo $customer['company'] ? ' - ' . htmlspecialchars($customer['company']) : ''; ?>
                        </option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lisans</label>
                    <select name="license_id" id="license_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">Önce müşteri seçin</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Opsiyonel - Belirli bir lisansa bağlanabilir</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bayi</label>
                    <select name="dealer_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">Bayi Yok</option>
                        <?php 
                        if($dealers_result && $dealers_result->num_rows > 0):
                            while($dealer = $dealers_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $dealer['id']; ?>">
                            <?php echo htmlspecialchars($dealer['dealer_name']); ?>
                        </option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tutar (*)</label>
                    <div class="relative">
                        <input type="number" name="amount" placeholder="0.00" step="0.01" min="0" required class="w-full px-4 py-2.5 pr-16 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <select name="currency" class="bg-transparent border-none text-gray-600 text-sm font-medium focus:outline-none pr-8">
                                <option value="TRY">TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ödeme Yöntemi (*)</label>
                    <select name="payment_method" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="cash">Nakit</option>
                        <option value="bank_transfer">Havale/EFT</option>
                        <option value="credit_card">Kredi Kartı</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>
            </div>
            
            <!-- Sağ Kolon -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ödeme Tarihi (*)</label>
                    <input type="datetime-local" name="payment_date" required value="<?php echo date('Y-m-d\TH:i'); ?>" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ödeme Durumu (*)</label>
                    <select name="payment_status" required class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="pending">Beklemede</option>
                        <option value="completed" selected>Tamamlandı</option>
                        <option value="failed">Başarısız</option>
                        <option value="refunded">İade Edildi</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fatura Numarası</label>
                    <input type="text" name="invoice_number" placeholder="INV-2025-001" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notlar</label>
                    <textarea name="notes" rows="5" placeholder="Ödeme ile ilgili notlar..." class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"></textarea>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="md:col-span-2 flex items-center gap-3 pt-4 border-t border-gray-200">
                <button type="submit" name="add_payment" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Ödeme Kaydı Oluştur</span>
                </button>
                <a href="index.php?tab=payments" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    <span>İptal</span>
                </a>
            </div>
        </div>
    </form>
</div>

<script>
// Müşteri seçildiğinde lisanslarını yükle
function loadCustomerLicenses(customerId) {
    const licenseSelect = document.getElementById('license_id');
    
    if (!customerId) {
        licenseSelect.innerHTML = '<option value="">Önce müşteri seçin</option>';
        return;
    }
    
    licenseSelect.innerHTML = '<option value="">Yükleniyor...</option>';
    
    // AJAX ile lisansları çek
    fetch(`get_customer_licenses.php?customer_id=${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.licenses.length > 0) {
                let options = '<option value="">Lisans Seçin (Opsiyonel)</option>';
                data.licenses.forEach(license => {
                    options += `<option value="${license.id}">${license.license_key} - ${license.license_type}</option>`;
                });
                licenseSelect.innerHTML = options;
            } else {
                licenseSelect.innerHTML = '<option value="">Bu müşterinin lisansı yok</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            licenseSelect.innerHTML = '<option value="">Hata oluştu</option>';
        });
}
</script>

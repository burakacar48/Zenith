<?php
require_once "config.php";

// Müşteri giriş kontrolü
if(!isset($_SESSION["customer_loggedin"]) || $_SESSION["customer_loggedin"] !== true){
    header("location: customer_login.php");
    exit;
}

// Müşteri bilgilerini ve lisans bilgilerini çek
$customer_db_id = $_SESSION["customer_id"];

$sql = "SELECT c.*, l.license_key, l.license_type, l.start_date, l.end_date, l.status, l.licensed_ip, l.hwid 
        FROM customers c 
        LEFT JOIN licenses l ON c.id = l.customer_id 
        WHERE c.id = ?";

$customer_data = null;
if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $customer_db_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows == 1){
            $customer_data = $result->fetch_assoc();
        }
    }
    $stmt->close();
}

if(!$customer_data){
    echo "Müşteri bilgileri bulunamadı.";
    exit;
}

// Lisans durumu badge'i
function getStatusBadge($status) {
    switch ($status) {
        case 'active':
        case 'pending':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>';
        case 'expired':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Süresi Doldu</span>';
        case 'cancelled':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">İptal Edildi</span>';
        default:
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Bilinmiyor</span>';
    }
}

// Kalan gün hesaplama
$end_date = new DateTime($customer_data['end_date']);
$today = new DateTime();
$remaining_days = $today->diff($end_date)->days;
if($today > $end_date) {
    $remaining_days = 0;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - <?php echo htmlspecialchars($customer_data['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                        <i data-lucide="shield" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-900">Müşteri Paneli</h1>
                        <p class="text-xs text-gray-500">Hoş geldiniz, <?php echo ?></p>
                    </div>
                </div>
                <a href="customer_logout.php" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span>Çıkış Yap</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i data-lucide="calendar" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <?php echo getStatusBadge($customer_data['status']); ?>
                </div>
                <p class="text-sm text-gray-500 mb-1">Lisans <p class="text-2xl font-bold text-gray-900"><?php echo $customer_data['status'] === 'active' ? 'Aktif' : 'Pasif'; ?></p>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6 text-green-600"></i>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">Kalan Süre</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $remaining_days; ?> gün</p>
            </div>

            <div class="bg-white rounded-xl p-6 border <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i data-lucide="key" class="w-6 h-6 text-purple-600"></i>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">Lisans Tipi</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($customer_data['license_type']); ?></p>
            </div>
        </div>

        <!-- License Information -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <i data-lucide="file-text" class="w-5 h-5"></i>
                Lisans Bilgileri
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Lisans Anahtarı</label>
                    <p class="text-lg font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($customer_data['license_key']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Müşteri <p class="text-lg font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($customer_data['customer_id']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Başlangıç Tarihi</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo date("d.m.Y", strtotime($customer_data['start_date'])); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Bitiş Tarihi</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo date("d.m.Y", strtotime($customer_data['end_date'])); ?></p>
                </div>
                <?php if($customer_data['licensed_ip']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Kayıtlı IP Adresi</label>
                    <p class="text-lg font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($customer_data['licensed_ip']); ?></p>
                </div>
                <?php endif; ?>
                <?php if($customer_data['hwid']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Donanım ID (HWID)</label>
                    <p class="text-lg font-mono text-gray-900 truncate"><?php echo htmlspecialchars(substr($customer_data['hwid'], 0, 20)) . '...'; ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5"></i>
                Müşteri Bilgileri
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Ad Soyad</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($customer_data['name']); ?></p>
                </div>
                <?php if($customer_data['company']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Şirket</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($customer_data['company']); ?></p>
                </div>
                <?php endif; ?>
                <?php if($customer_data['email']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                    <p class="text-lg text-gray-900"><?php echo htmlspecialchars($customer_data['email']); ?></p>
                </div>
                <?php endif; ?>
                <?php if($customer_data['phone']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Telefon</label>
                    <p class="text-lg text-gray-900"><?php echo htmlspecialchars($customer_data['phone']); ?></p>
                </div>
                <?php endif; ?>
                <?php if($customer_data['city'] || $customer_data['district']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Şehir / İlçe</label>
                    <p class="text-lg text-gray-900"><?php echo htmlspecialchars($customer_data['city']) . ' / ' . htmlspecialchars($customer_data['district']); ?></p>
                </div>
                <?php endif; ?>
                <?php if($customer_data['address']): ?>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-500 mb-1">Adres</label>
                    <p class="text-lg text-gray-900"><?php echo htmlspecialchars($customer_data['address']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

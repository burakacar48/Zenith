<?php
require_once "header.php";

// İstatistikleri hesapla
$total_customers_result = $mysqli->query("SELECT COUNT(id) as count FROM customers");
$total_customers = $total_customers_result->fetch_assoc()['count'];

$active_licenses_result = $mysqli->query("SELECT COUNT(id) as count FROM licenses WHERE status = 'active'");
$active_licenses = $active_licenses_result->fetch_assoc()['count'];

$expired_licenses_result = $mysqli->query("SELECT COUNT(id) as count FROM licenses WHERE status = 'expired' OR end_date < CURDATE()");
$expired_licenses = $expired_licenses_result->fetch_assoc()['count'];

$pending_licenses_result = $mysqli->query("SELECT COUNT(id) as count FROM licenses WHERE status = 'pending'");
$pending_licenses = $pending_licenses_result->fetch_assoc()['count'];

// Son 30 günde eklenen müşteriler
$recent_customers_result = $mysqli->query("SELECT COUNT(id) as count FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$recent_customers = $recent_customers_result ? $recent_customers_result->fetch_assoc()['count'] : 0;

// Bu ayın süresi dolacak lisanslar
$expiring_soon_result = $mysqli->query("SELECT COUNT(id) as count FROM licenses WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'active'");
$expiring_soon = $expiring_soon_result->fetch_assoc()['count'];

$activeTab = $_GET['tab'] ?? 'customers';

// Tab tanımlamaları
$tabs = [
    'customers' => [
        'label' => 'Müşteri Yönetimi', 
        'icon' => 'users',
        'description' => 'Tüm müşterileri görüntüle ve yönet'
    ], 
    'dealers' => [
        'label' => 'Bayi Yönetimi', 
        'icon' => 'building-2',
        'description' => 'Bayi ağınızı yönetin'
    ], 
    'add_customer' => [
        'label' => 'Yeni Müşteri', 
        'icon' => 'user-plus',
        'description' => 'Sisteme yeni müşteri ekleyin'
    ], 
    'payments' => [
        'label' => 'Ödeme Takibi', 
        'icon' => 'credit-card',
        'description' => 'Ödeme geçmişi ve raporları'
    ],
    'analytics' => [
        'label' => 'Analitik', 
        'icon' => 'bar-chart-3',
        'description' => 'Detaylı istatistikler ve raporlar'
    ]
];
?>

            <!-- Dashboard Statistics -->
            <section class="stats-grid">
                <!-- Toplam Müşteri -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="stat-label">Toplam Müşteri</div>
                    <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                    <div class="stat-change positive">
                        <i data-lucide="trending-up" class="w-3 h-3"></i>
                        <span>Son 30 gün: +<?php echo $recent_customers; ?></span>
                    </div>
                </div>

                <!-- Aktif Lisanslar -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="stat-label">Aktif Lisans</div>
                    <div class="stat-value"><?php echo number_format($active_licenses); ?></div>
                    <div class="stat-change positive">
                        <i data-lucide="shield" class="w-3 h-3"></i>
                        <span>Çalışan lisanslar</span>
                    </div>
                </div>

                <!-- Süresi Dolan -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="stat-label">Süresi Dolan</div>
                    <div class="stat-value"><?php echo number_format($expired_licenses); ?></div>
                    <div class="stat-change negative">
                        <i data-lucide="clock" class="w-3 h-3"></i>
                        <span>7 gün içinde: <?php echo $expiring_soon; ?></span>
                    </div>
                </div>

                <!-- Bekleyen Lisanslar -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                        <i data-lucide="hourglass" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="stat-label">Bekleyen Lisans</div>
                    <div class="stat-value"><?php echo number_format($pending_licenses); ?></div>
                    <div class="stat-change">
                        <i data-lucide="pending" class="w-3 h-3"></i>
                        <span>Onay bekliyor</span>
                    </div>
                </div>
            </section>

            <!-- Quick Actions Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-primary">Hızlı İşlemler</h2>
                    <div class="search-container">
                        <div class="search-icon">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                        <input 
                            type="text" 
                            id="global-search"
                            class="form-input search-input" 
                            placeholder="Müşteri ara, lisans bul..."
                            autocomplete="off"
                        >
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <nav class="nav-tabs">
                <?php foreach ($tabs as $tabId => $tab): 
                    $isActive = ($activeTab === $tabId);
                    $activeClass = $isActive ? 'active' : '';
                ?>
                    <a href="?tab=<?php echo $tabId; ?>" 
                       class="nav-tab <?php echo $activeClass; ?>"
                       title="<?php echo htmlspecialchars($tab['description']); ?>">
                        <i data-lucide="<?php echo $tab['icon']; ?>" class="w-4 h-4"></i>
                        <span><?php echo htmlspecialchars($tab['label']); ?></span>
                        <?php if ($tabId === 'customers' && $active_licenses > 0): ?>
                            <span class="badge badge-info ml-2"><?php echo $active_licenses; ?></span>
                        <?php elseif ($tabId === 'add_customer'): ?>
                            <span class="badge badge-success ml-2">Yeni</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Main Content Area -->
            <main class="content-card">
                <?php
                // Tab içeriklerini yükle
                switch ($activeTab) {
                    case 'customers':
                        echo '<div class="mb-4">';
                        echo '<h2 class="text-xl font-bold mb-2 flex items-center gap-2">';
                        echo '<i data-lucide="users" class="w-5 h-5"></i>';
                        echo 'Müşteri Yönetimi';
                        echo '</h2>';
                        echo '<p class="text-secondary text-sm">Tüm müşterilerinizi görüntüleyin, düzenleyin ve yönetin.</p>';
                        echo '</div>';
                        include 'customers.php';
                        break;
                        
                    case 'dealers':
                        echo '<div class="empty-state">';
                        echo '<div class="empty-state-icon">';
                        echo '<i data-lucide="building-2" class="w-16 h-16 mx-auto text-muted"></i>';
                        echo '</div>';
                        echo '<h3 class="empty-state-title">Bayi Yönetimi</h3>';
                        echo '<p class="empty-state-description">Bayi yönetim modülü yakında aktif edilecek. Bu bölümde bayi ağınızı yönetebileceksiniz.</p>';
                        echo '<a href="?tab=customers" class="btn btn-primary">Müşteri Yönetimine Dön</a>';
                        echo '</div>';
                        break;
                        
                    case 'add_customer':
                        echo '<div class="mb-4">';
                        echo '<h2 class="text-xl font-bold mb-2 flex items-center gap-2">';
                        echo '<i data-lucide="user-plus" class="w-5 h-5"></i>';
                        echo 'Yeni Müşteri Ekle';
                        echo '</h2>';
                        echo '<p class="text-secondary text-sm">Sisteme yeni bir müşteri ve lisans kaydı oluşturun.</p>';
                        echo '</div>';
                        include 'add_customer.php';
                        break;
                        
                    case 'edit_license':
                        echo '<div class="mb-4">';
                        echo '<h2 class="text-xl font-bold mb-2 flex items-center gap-2">';
                        echo '<i data-lucide="edit-3" class="w-5 h-5"></i>';
                        echo 'Lisans Düzenle';
                        echo '</h2>';
                        echo '<p class="text-secondary text-sm">Mevcut lisans bilgilerini güncelle ve yönet.</p>';
                        echo '</div>';
                        include 'edit_license.php';
                        break;
                        
                    case 'edit_customer':
                        echo '<div class="mb-4">';
                        echo '<h2 class="text-xl font-bold mb-2 flex items-center gap-2">';
                        echo '<i data-lucide="user-edit" class="w-5 h-5"></i>';
                        echo 'Müşteri Düzenle';
                        echo '</h2>';
                        echo '<p class="text-secondary text-sm">Müşteri bilgilerini güncelle ve yönet.</p>';
                        echo '</div>';
                        include 'edit_customer.php';
                        break;
                        
                    case 'payments':
                        echo '<div class="empty-state">';
                        echo '<div class="empty-state-icon">';
                        echo '<i data-lucide="credit-card" class="w-16 h-16 mx-auto text-muted"></i>';
                        echo '</div>';
                        echo '<h3 class="empty-state-title">Ödeme Takibi</h3>';
                        echo '<p class="empty-state-description">Ödeme takip sistemi geliştiriliyor. Yakında müşteri ödemelerini buradan takip edebileceksiniz.</p>';
                        echo '<a href="?tab=customers" class="btn btn-primary">Müşteri Yönetimine Dön</a>';
                        echo '</div>';
                        break;
                        
                    case 'analytics':
                        echo '<div class="empty-state">';
                        echo '<div class="empty-state-icon">';
                        echo '<i data-lucide="bar-chart-3" class="w-16 h-16 mx-auto text-muted"></i>';
                        echo '</div>';
                        echo '<h3 class="empty-state-title">Analitik Dashboard</h3>';
                        echo '<p class="empty-state-description">Gelişmiş analitik ve raporlama modülü hazırlanıyor. Detaylı istatistikleri yakında burada görebileceksiniz.</p>';
                        echo '<a href="?tab=customers" class="btn btn-primary">Müşteri Yönetimine Dön</a>';
                        echo '</div>';
                        break;
                        
                    default:
                        // Varsayılan olarak müşteriler sekmesini göster
                        header("Location: ?tab=customers");
                        exit;
                        break;
                }
                ?>
            </main>

<?php require_once "footer.php"; ?>

<?php
// Gelişmiş müşteri ve lisans bilgilerini çek
$sql = "SELECT 
            c.id as customer_id, 
            c.name, 
            c.email,
            c.phone,
            c.created_at as customer_created,
            l.license_type, 
            l.start_date, 
            l.end_date, 
            l.status, 
            l.id as license_id,
            l.hwid,
            DATEDIFF(l.end_date, CURDATE()) as days_remaining
        FROM customers c
        JOIN licenses l ON c.id = l.customer_id
        ORDER BY 
            CASE l.status 
                WHEN 'active' THEN 1 
                WHEN 'pending' THEN 2 
                WHEN 'expired' THEN 3 
                ELSE 4 
            END,
            c.name ASC";
$result = $mysqli->query($sql);

// Gelişmiş status badge fonksiyonu
function getAdvancedStatusBadge($status, $days_remaining = null) {
    switch ($status) {
        case 'active':
            if ($days_remaining !== null && $days_remaining <= 7 && $days_remaining > 0) {
                return '<span class="badge badge-warning">Yakında Dolacak</span>';
            } elseif ($days_remaining !== null && $days_remaining <= 0) {
                return '<span class="badge badge-error">Süresi Dolmuş</span>';
            }
            return '<span class="badge badge-success">Aktif</span>';
        case 'pending':
            return '<span class="badge badge-info">Beklemede</span>';
        case 'expired':
            return '<span class="badge badge-error">Süresi Dolmuş</span>';
        case 'cancelled':
            return '<span class="badge badge-neutral">İptal Edildi</span>';
        default:
            return '<span class="badge badge-neutral">Bilinmiyor</span>';
    }
}

// Lisans tipine göre ikon
function getLicenseTypeIcon($license_type) {
    $type = strtolower($license_type);
    if (strpos($type, 'premium') !== false) return 'crown';
    if (strpos($type, 'basic') !== false) return 'user';
    if (strpos($type, 'pro') !== false) return 'star';
    return 'package';
}

// Sayfa parametreleri
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'asc';
?>

<!-- Advanced Filter and Actions Bar -->
<div class="page-header">
    <div class="flex items-center gap-4 flex-1">
        <div class="search-container flex-1 max-w-md">
            <div class="search-icon">
                <i data-lucide="search" class="w-4 h-4"></i>
            </div>
            <input 
                type="text" 
                id="customer-search"
                class="form-input search-input" 
                placeholder="Müşteri adı, email, telefon ara..."
                value="<?php echo htmlspecialchars($search); ?>"
                autocomplete="off"
            >
        </div>
        
        <!-- Quick Filters -->
        <div class="flex items-center gap-2">
            <select id="status-filter" class="form-input" style="width: auto; min-width: 120px;">
                <option value="">Tüm Durumlar</option>
                <option value="active">Aktif</option>
                <option value="pending">Beklemede</option>
                <option value="expired">Süresi Dolmuş</option>
                <option value="cancelled">İptal Edilmiş</option>
            </select>
            
            <select id="license-type-filter" class="form-input" style="width: auto; min-width: 120px;">
                <option value="">Tüm Lisanslar</option>
                <option value="basic">Basic</option>
                <option value="premium">Premium</option>
                <option value="pro">Pro</option>
            </select>
        </div>
    </div>
    
    <div class="flex items-center gap-3">
        <button class="btn btn-secondary" onclick="exportCustomers()" title="Müşteri listesini dışa aktar">
            <i data-lucide="download" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Dışa Aktar</span>
        </button>
        
        <a href="?tab=add_customer" class="btn btn-primary">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Yeni Müşteri</span>
        </a>
    </div>
</div>

<!-- Customer Statistics -->
<div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <?php
    $stats = [
        'active' => $mysqli->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'active'")->fetch_assoc()['count'],
        'pending' => $mysqli->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'pending'")->fetch_assoc()['count'],
        'expired' => $mysqli->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'expired'")->fetch_assoc()['count'],
        'expiring_soon' => $mysqli->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'active' AND DATEDIFF(end_date, CURDATE()) <= 7 AND DATEDIFF(end_date, CURDATE()) > 0")->fetch_assoc()['count']
    ];
    ?>
    
    <div class="stat-card" style="padding: 1rem;">
        <div class="flex items-center gap-3">
            <div class="stat-icon" style="width: 32px; height: 32px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                <i data-lucide="check-circle" class="w-4 h-4 text-white"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size: 1.5rem;"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Aktif Lisans</div>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="padding: 1rem;">
        <div class="flex items-center gap-3">
            <div class="stat-icon" style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                <i data-lucide="clock" class="w-4 h-4 text-white"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size: 1.5rem;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Beklemede</div>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="padding: 1rem;">
        <div class="flex items-center gap-3">
            <div class="stat-icon" style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-white"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size: 1.5rem;"><?php echo $stats['expiring_soon']; ?></div>
                <div class="stat-label">Yakında Dolacak</div>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="padding: 1rem;">
        <div class="flex items-center gap-3">
            <div class="stat-icon" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <i data-lucide="x-circle" class="w-4 h-4 text-white"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size: 1.5rem;"><?php echo $stats['expired']; ?></div>
                <div class="stat-label">Süresi Dolmuş</div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Data Table -->
<div class="overflow-x-auto">
    <table class="data-table" id="customers-table">
        <thead>
            <tr>
                <th>
                    <div class="flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span>Müşteri Bilgileri</span>
                    </div>
                </th>
                <th>
                    <div class="flex items-center gap-2">
                        <i data-lucide="package" class="w-4 h-4"></i>
                        <span>Lisans Detayları</span>
                    </div>
                </th>
                <th>
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Tarih Bilgileri</span>
                    </div>
                </th>
                <th>
                    <div class="flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                        <span>Durum</span>
                    </div>
                </th>
                <th>
                    <div class="flex items-center gap-2">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                        <span>İşlemler</span>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="customer-row" 
                        data-customer-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>"
                        data-email="<?php echo strtolower(htmlspecialchars($row['email'] ?? '')); ?>"
                        data-status="<?php echo $row['status']; ?>"
                        data-license-type="<?php echo strtolower($row['license_type']); ?>">
                        
                        <!-- Müşteri Bilgileri -->
                        <td>
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="<?php echo getLicenseTypeIcon($row['license_type']); ?>" class="w-5 h-5 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-primary"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <?php if (!empty($row['email'])): ?>
                                        <div class="text-sm text-secondary"><?php echo htmlspecialchars($row['email']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['phone'])): ?>
                                        <div class="text-sm text-muted"><?php echo htmlspecialchars($row['phone']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs text-muted mt-1">
                                        ID: #<?php echo $row['customer_id']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Lisans Detayları -->
                        <td>
                            <div>
                                <div class="font-medium text-primary mb-1">
                                    <?php echo htmlspecialchars($row['license_type']); ?>
                                </div>
                                <?php if (!empty($row['hwid'])): ?>
                                    <div class="text-xs text-muted font-mono bg-black/20 px-2 py-1 rounded">
                                        HWID: <?php echo substr(htmlspecialchars($row['hwid']), 0, 12); ?>...
                                    </div>
                                <?php endif; ?>
                                <div class="text-xs text-muted mt-1">
                                    Lisans ID: #<?php echo $row['license_id']; ?>
                                </div>
                            </div>
                        </td>

                        <!-- Tarih Bilgileri -->
                        <td>
                            <div class="text-sm">
                                <div class="flex items-center gap-2 mb-1">
                                    <i data-lucide="play-circle" class="w-3 h-3 text-success"></i>
                                    <span class="text-secondary">Başlangıç:</span>
                                </div>
                                <div class="text-primary font-medium mb-2">
                                    <?php echo date("d.m.Y", strtotime($row['start_date'])); ?>
                                </div>
                                
                                <div class="flex items-center gap-2 mb-1">
                                    <i data-lucide="stop-circle" class="w-3 h-3 text-error"></i>
                                    <span class="text-secondary">Bitiş:</span>
                                </div>
                                <div class="text-primary font-medium">
                                    <?php echo date("d.m.Y", strtotime($row['end_date'])); ?>
                                </div>
                                
                                <?php if ($row['days_remaining'] !== null): ?>
                                    <div class="text-xs text-muted mt-2">
                                        <?php 
                                        if ($row['days_remaining'] > 0) {
                                            echo $row['days_remaining'] . ' gün kaldı';
                                        } elseif ($row['days_remaining'] == 0) {
                                            echo 'Bugün süresi doluyor';
                                        } else {
                                            echo abs($row['days_remaining']) . ' gün önce doldu';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Durum -->
                        <td>
                            <?php echo getAdvancedStatusBadge($row['status'], $row['days_remaining']); ?>
                            
                            <?php if ($row['status'] === 'active' && $row['days_remaining'] <= 7 && $row['days_remaining'] > 0): ?>
                                <div class="mt-1">
                                    <span class="badge badge-warning" style="font-size: 0.625rem;">Uyarı</span>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- İş

                        <!-- İşlemler -->
                        <td>
                            <div class="action-buttons">
                                <a href="index.php?tab=edit_license&id=<?php echo $row['license_id']; ?>" 
                                   class="action-btn action-btn-edit"
                                   title="Lisans ve müşteri bilgilerini düzenle">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>
                                
                                <button onclick="showCustomerDetails(<?php echo $row['customer_id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')"
                                        class="action-btn action-btn-edit"
                                        title="Müşteri detaylarını görüntüle">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                
                                <button onclick="renewLicense(<?php echo $row['license_id']; ?>)"
                                        class="action-btn"
                                        style="background: var(--success-bg); color: var(--success); border: 1px solid rgba(34, 197, 94, 0.2);"
                                        title="Lisansı yenile">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                </button>
                                
                                <button onclick="deleteLicense(<?php echo $row['license_id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')"
                                        class="action-btn action-btn-delete"
                                        title="Lisansı sil">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-12">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i data-lucide="users" class="w-16 h-16 mx-auto text-muted"></i>
                            </div>
                            <h3 class="empty-state-title">Henüz müşteri bulunmuyor</h3>
                            <p class="empty-state-description">
                                Sisteme ilk müşterinizi ekleyerek başlayın. Müşteri ekleme işlemi hızlı ve kolaydır.
                            </p>
                            <a href="?tab=add_customer" class="btn btn-primary mt-4">
                                <i data-lucide="user-plus" class="w-4 h-4"></i>
                                İlk Müşteri Ekle
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Advanced search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('customer-search');
    const statusFilter = document.getElementById('status-filter');
    const licenseTypeFilter = document.getElementById('license-type-filter');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusFilter_value = statusFilter.value;
        const licenseTypeFilter_value = licenseTypeFilter.value;
        const rows = document.querySelectorAll('.customer-row');
        
        rows.forEach(row => {
            const name = row.dataset.customerName || '';
            const email = row.dataset.email || '';
            const status = row.dataset.status || '';
            const licenseType = row.dataset.licenseType || '';
            
            const matchesSearch = searchTerm === '' || 
                                name.includes(searchTerm) || 
                                email.includes(searchTerm);
            const matchesStatus = statusFilter_value === '' || status === statusFilter_value;
            const matchesLicenseType = licenseTypeFilter_value === '' || licenseType.includes(licenseTypeFilter_value);
            
            if (matchesSearch && matchesStatus && matchesLicenseType) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
    if (licenseTypeFilter) licenseTypeFilter.addEventListener('change', filterTable);
});

// Customer management functions
function showCustomerDetails(customerId, customerName) {
    // Modal veya popup ile müşteri detaylarını göster
    alert(`${customerName} müşterisinin detayları gösterilecek. (ID: ${customerId})`);
}

function renewLicense(licenseId) {
    if (confirm('Bu lisansı yenilemek istediğinizden emin misiniz?')) {
        // AJAX ile lisans yenileme işlemi
        window.location.href = `renew_license.php?id=${licenseId}`;
    }
}

function deleteLicense(licenseId, customerName) {
    if (confirm(`${customerName} müşterisinin lisansını kalıcı olarak silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!`)) {
        window.location.href = `delete_license.php?id=${licenseId}`;
    }
}

function exportCustomers() {
    // Müşteri listesini CSV/Excel formatında dışa aktar
    window.location.href = 'export_customers.php';
}

// Auto-refresh functionality for status updates
setInterval(function() {
    // Her 30 saniyede bir durum güncellemelerini kontrol et
    const statusElements = document.querySelectorAll('.badge');
    statusElements.forEach(element => {
        // Subtle animation for active statuses
        if (element.classList.contains('badge-success')) {
            element.style.opacity = '0.7';
            setTimeout(() => element.style.opacity = '1', 200);
        }
    });
}, 30000);
</script>
<?php
require_once "header.php";
$total_customers_result = $mysqli->query("SELECT COUNT(id) as count FROM customers");
$total_customers = $total_customers_result->fetch_assoc()['count'];
$active_licenses_result = $mysqli->query("SELECT COUNT(id) as count FROM licenses WHERE status = 'active'");
$active_licenses = $active_licenses_result->fetch_assoc()['count'];
$expired_licenses_result = $mysqli->query("SELECT COUNT(id) as count FROM licenses WHERE status = 'expired' OR end_date < CURDATE()");
$expired_licenses = $expired_licenses_result->fetch_assoc()['count'];
$monthly_revenue = "84.5K";
$activeTab = $_GET['tab'] ?? 'customers';
?>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Toplam Müşteri</p>
        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_customers); ?></p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Aktif Lisans</p>
        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($active_licenses); ?></p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-5 h-5 text-orange-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Süresi Dolan</p>
        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($expired_licenses); ?></p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-5 h-5 text-purple-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Aylık Gelir</p>
        <p class="text-2xl font-bold text-gray-900">₺<?php echo $monthly_revenue; ?></p>
    </div>
</div>

<!-- Main Content Area -->
<?php
if ($activeTab === 'customers') { include 'customers.php'; } 
elseif ($activeTab === 'dealers') { 
    echo '<div class="bg-white rounded-xl border border-gray-200 p-8 text-center mt-6">';
    echo '<p class="text-gray-500">Bayiler bölümü yapım aşamasında.</p>';
    echo '</div>';
} 
elseif ($activeTab === 'add_customer') { include 'add_customer.php'; } 
elseif ($activeTab === 'edit_license') { include 'edit_license.php'; } 
elseif ($activeTab === 'edit_customer') { include 'edit_customer.php'; }
elseif ($activeTab === 'payments') { 
    echo '<div class="bg-white rounded-xl border border-gray-200 p-8 text-center mt-6">';
    echo '<p class="text-gray-500">Ödemeler bölümü yapım aşamasında.</p>';
    echo '</div>';
}
?>

<?php require_once "footer.php"; ?>

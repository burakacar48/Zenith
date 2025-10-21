<?php
// Bayiler listesini çek
$sql = "SELECT * FROM dealers ORDER BY id DESC";
$result = $mysqli->query($sql);

// Durum badge fonksiyonu
function getDealerStatusBadge($status) {
    switch ($status) {
        case 'active':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>';
        case 'inactive':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Pasif</span>';
        case 'suspended':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Askıda</span>';
        default:
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Bilinmiyor</span>';
    }
}

// Avatar renkleri
$avatarColors = [
    'bg-gradient-to-br from-indigo-500 to-indigo-600',
    'bg-gradient-to-br from-cyan-500 to-cyan-600',
    'bg-gradient-to-br from-emerald-500 to-emerald-600',
    'bg-gradient-to-br from-amber-500 to-amber-600',
    'bg-gradient-to-br from-rose-500 to-rose-600'
];
?>

<!-- Bayiler Header -->
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Bayiler</h3>
            <p class="text-sm text-gray-500">Bayi ağınızı yönetin ve performansları takip edin</p>
        </div>
        <a href="?tab=add_dealer" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Yeni Bayi Ekle</span>
        </a>
    </div>
</div>

<!-- Dealers List -->
<div class="bg-white rounded-xl border border-gray-200">
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-gray-900">Tüm Bayiler</h3>
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:flex-initial">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" placeholder="Bayi ara..." class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50 flex-shrink-0">
                    <i data-lucide="filter" class="w-4 h-4 text-gray-600"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="divide-y divide-gray-100">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $index = 0;
            while($dealer = $result->fetch_assoc()): 
                // Bayi müşteri sayısını çek
                $dealer_id = $dealer['id'];
                $customer_count_sql = "SELECT COUNT(*) as count FROM customers WHERE dealer_id = $dealer_id";
                $customer_count_result = $mysqli->query($customer_count_sql);
                $customer_count = $customer_count_result->fetch_assoc()['count'];
                
                // İnitials oluştur
                $initials = '';
                $nameParts = explode(' ', $dealer['dealer_name']);
                foreach($nameParts as $part) {
                    if(!empty($part)) {
                        $initials .= mb_substr($part, 0, 1, 'UTF-8');
                    }
                }
                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                $avatarColor = $avatarColors[$index % count($avatarColors)];
                $index++;
            ?>
            <!-- Dealer Card -->
            <div class="p-4 sm:p-6 hover:bg-gray-50 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl <?php echo $avatarColor; ?> flex items-center justify-center text-white font-bold flex-shrink-0">
                        <?php echo $initials; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <h4 class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($dealer['dealer_name']); ?></h4>
                                <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($dealer['contact_person'] ?? 'İletişim Yok'); ?></p>
                            </div>
                            <?php echo getDealerStatusBadge($dealer['status']); ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="hash" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="font-mono text-xs font-semibold text-blue-600"><?php echo htmlspecialchars($dealer['dealer_code']); ?></span>
                            </div>
                            <?php if(!empty($dealer['email'])): ?>
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="mail" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="text-xs"><?php echo htmlspecialchars($dealer['email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($dealer['phone'])): ?>
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="phone" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="text-xs"><?php echo htmlspecialchars($dealer['phone']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="users" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="text-xs"><?php echo $customer_count; ?> Müşteri</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="percent" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="text-xs font-medium text-green-600">%<?php echo number_format($dealer['commission_rate'], 2); ?> Komisyon</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="?tab=edit_dealer&id=<?php echo $dealer['id']; ?>" class="w-9 h-9 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </a>
                        <a href="?tab=delete_dealer&id=<?php echo $dealer['id']; ?>" onclick="return confirm('Bu bayiyi silmek istediğinizden emin misiniz?');" class="w-9 h-9 rounded-lg hover:bg-red-50 flex items-center justify-center text-red-600">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="building-2" class="w-8 h-8 text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Henüz bayi kaydı bulunmuyor</h3>
                <p class="text-gray-500 mb-6">İlk bayinizi ekleyerek başlayın</p>
                <a href="?tab=add_dealer" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Bayi Ekle</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
    <div class="p-4 sm:p-6 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-600"><?php echo $result->num_rows; ?> bayi gösteriliyor</p>
            <div class="flex items-center gap-2">
                <button class="px-3 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" disabled>
                    Önceki
                </button>
                <button class="px-3 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50" disabled>
                    Sonraki
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

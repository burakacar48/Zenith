<?php
// Ödemeleri çek (JOIN ile müşteri ve bayi bilgileri)
$sql = "SELECT p.*, c.name as customer_name, c.company, d.dealer_name, l.license_key 
        FROM payments p 
        LEFT JOIN customers c ON p.customer_id = c.id 
        LEFT JOIN dealers d ON p.dealer_id = d.id
        LEFT JOIN licenses l ON p.license_id = l.id
        ORDER BY p.payment_date DESC";
$result = $mysqli->query($sql);

// Ödeme durumu badge fonksiyonu
function getPaymentStatusBadge($status) {
    switch ($status) {
        case 'completed':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Tamamlandı</span>';
        case 'pending':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Beklemede</span>';
        case 'failed':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Başarısız</span>';
        case 'refunded':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">İade</span>';
        default:
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Bilinmiyor</span>';
    }
}

// Ödeme yöntemi badge fonksiyonu
function getPaymentMethodBadge($method) {
    switch ($method) {
        case 'cash':
            return '<span class="inline-flex items-center gap-1.5"><i data-lucide="banknote" class="w-3.5 h-3.5"></i> Nakit</span>';
        case 'bank_transfer':
            return '<span class="inline-flex items-center gap-1.5"><i data-lucide="landmark" class="w-3.5 h-3.5"></i> Havale</span>';
        case 'credit_card':
            return '<span class="inline-flex items-center gap-1.5"><i data-lucide="credit-card" class="w-3.5 h-3.5"></i> Kredi Kartı</span>';
        case 'other':
            return '<span class="inline-flex items-center gap-1.5"><i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i> Diğer</span>';
        default:
            return '<span class="inline-flex items-center gap-1.5"><i data-lucide="help-circle" class="w-3.5 h-3.5"></i> Bilinmiyor</span>';
    }
}

// Toplam istatistikler
$stats_sql = "SELECT 
    SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as total_completed,
    SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as total_pending,
    COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_count,
    COUNT(*) as total_count
    FROM payments";
$stats_result = $mysqli->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!-- Ödemeler İstatistikleri -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Toplam Gelir</p>
        <p class="text-2xl font-bold text-gray-900">₺<?php echo number_format($stats['total_completed'] ?? 0, 2); ?></p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Bekleyen</p>
        <p class="text-2xl font-bold text-gray-900">₺<?php echo number_format($stats['total_pending'] ?? 0, 2); ?></p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <i data-lucide="receipt" class="w-5 h-5 text-blue-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Tamamlanan</p>
        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['completed_count'] ?? 0); ?></p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-5 h-5 text-purple-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Toplam İşlem</p>
        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_count'] ?? 0); ?></p>
    </div>
</div>

<!-- Ödemeler Header -->
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Ödemeler</h3>
            <p class="text-sm text-gray-500">Tüm ödeme işlemlerini görüntüleyin ve yönetin</p>
        </div>
        <a href="?tab=add_payment" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition shadow-lg">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Yeni Ödeme Ekle</span>
        </a>
    </div>
</div>

<!-- Ödemeler Listesi -->
<div class="bg-white rounded-xl border border-gray-200">
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-gray-900">Tüm Ödemeler</h3>
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:flex-initial">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" placeholder="Ödeme ara..." class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 hover:bg-gray-50 flex-shrink-0">
                    <i data-lucide="filter" class="w-4 h-4 text-gray-600"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Müşteri</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tutar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Yöntem</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fatura No</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($payment = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                                <?php if($payment['company']): ?>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($payment['company']); ?></div>
                                <?php endif; ?>
                                <?php if($payment['dealer_name']): ?>
                                <div class="text-xs text-blue-600 flex items-center gap-1 mt-1">
                                    <i data-lucide="building-2" class="w-3 h-3"></i>
                                    <?php echo htmlspecialchars($payment['dealer_name']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-bold text-gray-900">₺<?php echo number_format($payment['amount'], 2); ?></div>
                            <div class="text-xs text-gray-500"><?php echo strtoupper($payment['currency']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <?php echo getPaymentMethodBadge($payment['payment_method']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo getPaymentStatusBadge($payment['payment_status']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <?php echo date("d.m.Y", strtotime($payment['payment_date'])); ?>
                            <div class="text-xs text-gray-400"><?php echo date("H:i", strtotime($payment['payment_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if($payment['invoice_number']): ?>
                            <span class="font-mono text-xs text-gray-700"><?php echo htmlspecialchars($payment['invoice_number']); ?></span>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-1">
                                <a href="?tab=view_payment&id=<?php echo $payment['id']; ?>" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600" title="Görüntüle">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="?tab=edit_payment&id=<?php echo $payment['id']; ?>" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600" title="Düzenle">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="receipt" class="w-8 h-8 text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Henüz ödeme kaydı bulunmuyor</h3>
                            <p class="text-gray-500 mb-6">İlk ödeme kaydınızı ekleyerek başlayın</p>
                            <a href="?tab=add_payment" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>Ödeme Ekle</span>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
    <div class="p-4 sm:p-6 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-600"><?php echo $result->num_rows; ?> ödeme gösteriliyor</p>
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

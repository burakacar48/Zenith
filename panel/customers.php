<?php
// Müşteri ve lisans bilgilerini birleştirerek çek
$sql = "SELECT c.id as customer_id, c.customer_id as customer_unique_id, c.name, c.company, l.license_key, l.license_type, l.start_date, l.end_date, l.status, l.id as license_id
        FROM customers c
        JOIN licenses l ON c.id = l.customer_id
        ORDER BY c.name ASC";
$result = $mysqli->query($sql);

function getStatusBadge($status) { 
    switch ($status) {
        case 'active':
        case 'pending':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>';
        case 'expired':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Süresi Doldu</span>';
        case 'cancelled':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Beklemede</span>';
        default:
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Bilinmiyor</span>';
    }
}

// Avatar renkleri
$avatarColors = [
    'bg-gradient-to-br from-blue-500 to-blue-600',
    'bg-gradient-to-br from-purple-500 to-purple-600',
    'bg-gradient-to-br from-orange-500 to-orange-600',
    'bg-gradient-to-br from-green-500 to-green-600',
    'bg-gradient-to-br from-pink-500 to-pink-600'
];
?>

<!-- Customers List -->
<div class="bg-white rounded-xl border border-gray-200 mt-6">
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-gray-900">Son Müşteriler</h3>
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:flex-initial">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" placeholder="Ara..." class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            while($row = $result->fetch_assoc()): 
                $initials = '';
                $nameParts = explode(' ', $row['name']);
                foreach($nameParts as $part) {
                    if(!empty($part)) {
                        $initials .= mb_substr($part, 0, 1, 'UTF-8');
                    }
                }
                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
                $avatarColor = $avatarColors[$index % count($avatarColors)];
                $index++;
            ?>
            <!-- Customer Card -->
            <div class="p-4 sm:p-6 hover:bg-gray-50 transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl <?php echo $avatarColor; ?> flex items-center justify-center text-white font-bold flex-shrink-0">
                        <?php echo $initials; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <h4 class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($row['name']); ?></h4>
                                <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($row['company'] ?? 'Şirket Yok'); ?></p>
                            </div>
                            <?php echo getStatusBadge($row['status']); ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                            <?php if(!empty($row['customer_unique_id'])): ?>
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="user" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="font-mono text-xs font-semibold text-blue-600">ID: <?php echo htmlspecialchars($row['customer_unique_id']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="key" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="font-mono text-xs license-blur"><?php echo htmlspecialchars($row['license_key']); ?></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <i data-lucide="calendar" class="w-4 h-4 flex-shrink-0"></i>
                                <span class="text-xs"><?php echo date("d.m.Y", strtotime($row['end_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="index.php?tab=edit_license&id=<?php echo $row['license_id']; ?>" class="w-9 h-9 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        <a href="delete_license.php?id=<?php echo $row['license_id']; ?>" onclick="return confirm('Bu lisansı ve potansiyel olarak müşteri kaydını kalıcı olarak silmek istediğinizden emin misiniz?');" class="w-9 h-9 rounded-lg hover:bg-red-50 flex items-center justify-center text-red-600">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="p-8 text-center text-gray-500">
                Henüz müşteri veya lisans kaydı bulunmuyor.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
    <div class="p-4 sm:p-6 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-600">1-<?php echo $result->num_rows; ?> / <?php echo $result->num_rows; ?> kayıt gösteriliyor</p>
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
</div>

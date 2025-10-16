<?php
// config.php dosyasını dahil et ve oturumu başlat
require_once "config.php";

// Kullanıcının giriş yapıp yapmadığını kontrol et, yapmadıysa login sayfasına yönlendir
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Kullanıcı bilgilerini al
$current_user = $_SESSION["username"] ?? 'Admin';
$current_time = date('H:i');
$current_date = date('d.m.Y');

// Aktif sayfa belirleme
$current_page = basename($_SERVER['PHP_SELF'], '.php');
if (isset($_GET['tab'])) {
    $current_page = $_GET['tab'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zenith License Panel - Profesyonel Lisans Yönetimi</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="Zenith License Panel - Gelişmiş lisans yönetim sistemi">
    <meta name="author" content="Zenith Development">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- External Resources -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="modern_admin.css">
    
    <!-- JavaScript for interactivity -->
    <script>
        // Theme and interaction utilities
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Add loading states to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (this.href && !this.href.includes('#')) {
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';
                        
                        // Reset after 3 seconds if page doesn't change
                        setTimeout(() => {
                            this.style.opacity = '';
                            this.style.pointerEvents = '';
                        }, 3000);
                    }
                });
            });
            
            // Real-time clock update
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('tr-TR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                const clockElement = document.getElementById('realtime-clock');
                if (clockElement) {
                    clockElement.textContent = timeString;
                }
            }
            
            // Update clock every second
            setInterval(updateClock, 1000);
            updateClock(); // Initial call
            
            // Search functionality
            const searchInput = document.getElementById('global-search');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const tableRows = document.querySelectorAll('.data-table tbody tr');
                    
                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</head>
<body>
    <!-- Main Admin Container -->
    <div class="admin-container">
        <div class="admin-wrapper">
            
            <!-- Header Section -->
            <header class="admin-header">
                <div class="header-content">
                    <!-- Brand Section -->
                    <div class="header-brand">
                        <div class="brand-logo">
                            <img src="images/PanelLogo.png" alt="Panel Logo" class="logo-image">
                        </div>
                        <div class="brand-info">
                            <h1>Zenith License Panel</h1>
                            <p>Gelişmiş lisans yönetim sistemi</p>
                        </div>
                    </div>
                    
                    <!-- Header Actions -->
                    <div class="flex items-center gap-4">
                        <!-- System Status -->
                        <div class="hidden md:flex items-center gap-3 px-4 py-2 bg-black/20 rounded-lg border border-white/10">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                <span class="text-sm text-gray-300">Sistem Aktif</span>
                            </div>
                            <div class="w-px h-4 bg-white/20"></div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                                <span id="realtime-clock" class="text-sm text-gray-300 font-mono"><?php echo $current_time; ?></span>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="flex items-center gap-3 px-4 py-2 bg-black/20 rounded-lg border border-white/10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                                    <i data-lucide="user" class="w-4 h-4 text-white"></i>
                                </div>
                                <div class="hidden sm:block">
                                    <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($current_user); ?></div>
                                    <div class="text-xs text-gray-400">Yönetici</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logout Button -->
                        <a href="logout.php"
                           class="btn btn-danger"
                           onclick="return confirm('Oturumu kapatmak istediğinizden emin misiniz?')"
                           title="Güvenli çıkış">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Çıkış</span>
                        </a>
                    </div>
                </div>
                
                <!-- Navigation Breadcrumb -->
                <div class="mt-4 pt-4 border-t border-white/10">
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        <span>Ana Panel</span>
                        <?php if ($current_page !== 'index' && $current_page !== 'customers'): ?>
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            <span class="text-white capitalize"><?php echo str_replace('_', ' ', $current_page); ?></span>
                        <?php endif; ?>
                        <div class="ml-auto flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span><?php echo $current_date; ?></span>
                        </div>
                    </div>
                </div>
            </header>
<?php
// config.php dosyasını dahil et ve oturumu başlat
require_once "config.php";

// Kullanıcının giriş yapıp yapmadığını kontrol et, yapmadıysa login sayfasına yönlendir
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lisans Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            transition: transform 0.3s ease;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }

        .license-blur {
            filter: blur(5px);
            transition: filter 0.3s ease;
            cursor: pointer;
            user-select: none;
        }

        .license-blur:hover {
            filter: blur(2px);
        }

        .license-blur.revealed {
            filter: blur(0);
            cursor: default;
        }

        /* Dark mode styles */
        .dark {
            color-scheme: dark;
        }

        .dark body {
            background: linear-gradient(to bottom right, #0f172a, #1e293b, #0f172a);
        }

        .dark .bg-white {
            background-color: #1e293b;
        }

        .dark .bg-gray-50 {
            background-color: #0f172a;
        }

        .dark .border-gray-200 {
            border-color: #334155;
        }

        .dark .border-gray-100 {
            border-color: #1e293b;
        }

        .dark .text-gray-900 {
            color: #f1f5f9;
        }

        .dark .text-gray-700 {
            color: #cbd5e1;
        }

        .dark .text-gray-600 {
            color: #94a3b8;
        }

        .dark .text-gray-500 {
            color: #64748b;
        }

        .dark .hover\:bg-gray-50:hover {
            background-color: #334155;
        }

        .dark .hover\:bg-gray-100:hover {
            background-color: #334155;
        }

        .theme-toggle {
            transition: transform 0.3s ease;
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body class="bg-gray-50 transition-colors duration-300">
    
    <!-- Mobile Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 top-0 h-full w-64 bg-white border-r border-gray-200 z-50 flex flex-col">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                        <i data-lucide="shield" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-900">LicenseHub</h1>
                        <p class="text-xs text-gray-500">Yönetim Paneli</p>
                    </div>
                </div>
                <button id="closeSidebar" class="md:hidden text-gray-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="?tab=customers" class="flex items-center gap-3 px-4 py-3 rounded-lg <?php echo ($_GET['tab'] ?? 'customers') == 'customers' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'; ?> font-medium">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>Dashboard</span>
            </a>
            <a href="?tab=add_customer" class="flex items-center gap-3 px-4 py-3 rounded-lg <?php echo ($_GET['tab'] ?? '') == 'add_customer' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'; ?> font-medium">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span>Müşteri Ekle</span>
            </a>
            <a href="?tab=customers" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                <i data-lucide="key" class="w-5 h-5"></i>
                <span>Lisanslar</span>
            </a>
            <a href="?tab=dealers" class="flex items-center gap-3 px-4 py-3 rounded-lg <?php echo ($_GET['tab'] ?? '') == 'dealers' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'; ?> font-medium">
                <i data-lucide="building-2" class="w-5 h-5"></i>
                <span>Bayiler</span>
            </a>
            <a href="?tab=payments" class="flex items-center gap-3 px-4 py-3 rounded-lg <?php echo ($_GET['tab'] ?? '') == 'payments' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'; ?> font-medium">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
                <span>Ödemeler</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                <span>Raporlar</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span>Ayarlar</span>
            </a>
        </nav>

        <div class="p-4 border-t border-gray-200">
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                    AD
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm truncate">Admin User</p>
                    <p class="text-xs text-gray-500 truncate">admin@panel.com</p>
                </div>
                <a href="logout.php" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="md:ml-64">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <button id="menuBtn" class="md:hidden text-gray-600">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <div class="min-w-0">
                            <h2 class="text-xl md:text-2xl font-bold text-gray-900 truncate">
                                <?php 
                                $pageTitle = 'Dashboard';
                                $activeTab = $_GET['tab'] ?? 'customers';
                                switch($activeTab) {
                                    case 'customers': $pageTitle = 'Müşteriler'; break;
                                    case 'add_customer': $pageTitle = 'Müşteri Ekle'; break;
                                    case 'edit_license': $pageTitle = 'Lisans Düzenle'; break;
                                    case 'edit_customer': $pageTitle = 'Müşteri Düzenle'; break;
                                    case 'dealers': $pageTitle = 'Bayiler'; break;
                                    case 'payments': $pageTitle = 'Ödemeler'; break;
                                }
                                echo $pageTitle;
                                ?>
                            </h2>
                            <p class="text-sm text-gray-500 hidden sm:block">Genel sistem durumu</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button id="themeToggle" class="theme-toggle hidden sm:flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 text-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">
                            <i data-lucide="sun" class="w-5 h-5 sun-icon"></i>
                            <i data-lucide="moon" class="w-5 h-5 moon-icon hidden"></i>
                        </button>
                        <button class="hidden sm:flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 text-gray-600 relative dark:hover:bg-gray-700 dark:text-gray-400">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <a href="?tab=add_customer" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-medium">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Yeni Ekle</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="p-4 sm:p-6 lg:p-8 space-y-6">

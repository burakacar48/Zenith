-- ===============================================
-- COMPLETE DATABASE SCHEMA FOR LICENSE MANAGEMENT PANEL
-- ===============================================

-- Admin kullanıcıları tablosu
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100),
  `full_name` varchar(100),
  `role` enum('super_admin','admin','viewer') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Müşteriler tablosu
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(5) UNIQUE,
  `customer_password` varchar(255),
  `name` varchar(100) NOT NULL,
  `email` varchar(100),
  `phone` varchar(20),
  `company` varchar(100),
  `address` text,
  `city` varchar(50),
  `district` varchar(50),
  `dealer_id` int(11) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_dealer_id` (`dealer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lisanslar tablosu
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `license_key` varchar(50) NOT NULL UNIQUE,
  `license_type` enum('Standard','Premium','Enterprise') DEFAULT 'Standard',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','pending','expired','cancelled') DEFAULT 'pending',
  `licensed_ip` varchar(45),
  `hwid` varchar(255),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_license_key` (`license_key`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_license_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bayiler tablosu
CREATE TABLE IF NOT EXISTS `dealers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dealer_code` varchar(10) UNIQUE NOT NULL,
  `dealer_name` varchar(100) NOT NULL,
  `contact_person` varchar(100),
  `email` varchar(100),
  `phone` varchar(20),
  `address` text,
  `city` varchar(50),
  `district` varchar(50),
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dealer_code` (`dealer_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ödemeler tablosu
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `license_id` int(11),
  `dealer_id` int(11),
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'TRY',
  `payment_method` enum('cash','bank_transfer','credit_card','other') DEFAULT 'cash',
  `payment_date` datetime NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `invoice_number` varchar(50),
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_license_id` (`license_id`),
  KEY `idx_dealer_id` (`dealer_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_payment_date` (`payment_date`),
  CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_license` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payment_dealer` FOREIGN KEY (`dealer_id`) REFERENCES `dealers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin oturum tablosu
CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistem aktivite log tablosu
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `user_type` enum('admin','customer') DEFAULT 'admin',
  `action` varchar(100) NOT NULL,
  `description` text,
  `ip_address` varchar(45),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistem ayarları tablosu
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text,
  `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bildirimler tablosu
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `user_type` enum('admin','customer') DEFAULT 'admin',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` boolean DEFAULT FALSE,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign key constraint eklemeleri

ALTER TABLE `customers` 
ADD CONSTRAINT `fk_customer_dealer` 
FOREIGN KEY (`dealer_id`) REFERENCES `dealers` (`id`) ON DELETE SET NULL;

-- Varsayılan admin kullanıcısı (username: admin, password: admin123)

INSERT INTO `admins` (`username`, `password_hash`, `email`, `full_name`, `role`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@panel.com', 'Admin User', 'super_admin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Varsayılan sistem ayarları

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('company_name', 'LicenseHub', 'string', 'Şirket Adı'),
('company_email', 'info@licensehub.com', 'string', 'Şirket Email'),
('company_phone', '+90 XXX XXX XX XX', 'string', 'Şirket Telefon'),
('default_license_duration', '365', 'number', 'Varsayılan Lisans Süresi (Gün)'),
('currency', 'TRY', 'string', 'Para Birimi'),
('tax_rate', '20', 'number', 'KDV Oranı (%)'),
('enable_notifications', '1', 'boolean', 'Bildirimleri Etkinleştir'),
('license_expiry_warning_days', '30', 'number', 'Lisans Bitim Uyarısı (Gün)')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

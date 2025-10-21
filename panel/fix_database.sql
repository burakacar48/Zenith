-- Oncelikle mevcut foreign key constraintleri kaldir (varsa)
ALTER TABLE customers DROP FOREIGN KEY IF EXISTS fk_customer_dealer;
ALTER TABLE payments DROP FOREIGN KEY IF EXISTS fk_payment_customer;
ALTER TABLE payments DROP FOREIGN KEY IF EXISTS fk_payment_license;
ALTER TABLE payments DROP FOREIGN KEY IF EXISTS fk_payment_dealer;

-- Dealers tablosunu olustur
CREATE TABLE IF NOT EXISTS dealers (
  id int(11) NOT NULL AUTO_INCREMENT,
  dealer_code varchar(10) NOT NULL,
  dealer_name varchar(100) NOT NULL,
  contact_person varchar(100),
  email varchar(100),
  phone varchar(20),
  address text,
  city varchar(50),
  district varchar(50),
  commission_rate decimal(5,2) DEFAULT 0.00,
  status enum('active','inactive','suspended') DEFAULT 'active',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY dealer_code (dealer_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments tablosunu olustur
CREATE TABLE IF NOT EXISTS payments (
  id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  license_id int(11),
  dealer_id int(11),
  amount decimal(10,2) NOT NULL,
  currency varchar(3) DEFAULT 'TRY',
  payment_method enum('cash','bank_transfer','credit_card','other') DEFAULT 'cash',
  payment_date datetime NOT NULL,
  payment_status enum('pending','completed','failed','refunded') DEFAULT 'pending',
  invoice_number varchar(50),
  notes text,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Simdi foreign key constraintleri ekle
ALTER TABLE customers ADD CONSTRAINT fk_customer_dealer FOREIGN KEY (dealer_id) REFERENCES dealers (id) ON DELETE SET NULL;

ALTER TABLE payments ADD CONSTRAINT fk_payment_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE;

ALTER TABLE payments ADD CONSTRAINT fk_payment_license FOREIGN KEY (license_id) REFERENCES licenses (id) ON DELETE SET NULL;

ALTER TABLE payments ADD CONSTRAINT fk_payment_dealer FOREIGN KEY (dealer_id) REFERENCES dealers (id) ON DELETE SET NULL;

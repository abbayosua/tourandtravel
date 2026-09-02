-- ================================================================
-- Schema Klook Features — jalankan setelah schema.sql existing
-- Semua additive (CREATE TABLE IF NOT EXISTS)
-- AMAN DIJALANKAN BERULANG
-- NOTE: MySQL 9.x — ALTER TABLE ADD COLUMN tanpa IF NOT EXISTS
--       (IF NOT EXISTS hanya MariaDB). Seeder PHP menangani ALTER.
-- ================================================================

-- 1) Hero slides untuk carousel landing page
CREATE TABLE IF NOT EXISTS hero_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  image_url VARCHAR(255) NOT NULL,
  title VARCHAR(200) DEFAULT NULL,
  subtitle VARCHAR(200) DEFAULT NULL,
  cta_text VARCHAR(50) DEFAULT NULL,
  cta_link VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Newsletter subscribers
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Attractions / Tiket tempat wisata
CREATE TABLE IF NOT EXISTS attractions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  city VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(12,2) NOT NULL,
  price_currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  cover_image VARCHAR(255) DEFAULT NULL,
  category VARCHAR(100) DEFAULT NULL,
  duration VARCHAR(50) DEFAULT NULL,
  instant_confirmation TINYINT(1) NOT NULL DEFAULT 1,
  free_cancellation TINYINT(1) NOT NULL DEFAULT 0,
  best_seller TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attraction_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attraction_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  visit_date DATE NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  total_price DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  booking_code VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Airport Transfers
CREATE TABLE IF NOT EXISTS transfers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  from_city VARCHAR(100) NOT NULL,
  to_city VARCHAR(100) NOT NULL,
  from_type ENUM('airport','city','port','hotel') NOT NULL DEFAULT 'airport',
  to_type ENUM('airport','city','port','hotel') NOT NULL DEFAULT 'city',
  price DECIMAL(12,2) NOT NULL,
  price_currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  vehicle_type VARCHAR(50) DEFAULT NULL,
  max_passengers INT NOT NULL DEFAULT 4,
  description TEXT,
  instant_confirmation TINYINT(1) NOT NULL DEFAULT 1,
  free_cancellation TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transfer_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transfer_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  pickup_date DATE NOT NULL,
  pickup_time TIME NOT NULL,
  pickup_location VARCHAR(255) NOT NULL,
  dropoff_location VARCHAR(255) DEFAULT NULL,
  flight_number VARCHAR(20) DEFAULT NULL,
  passengers INT NOT NULL DEFAULT 1,
  total_price DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  booking_code VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (transfer_id) REFERENCES transfers(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Promo Codes
CREATE TABLE IF NOT EXISTS promo_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  discount_value DECIMAL(12,2) NOT NULL,
  min_purchase DECIMAL(12,2) DEFAULT NULL,
  max_discount DECIMAL(12,2) DEFAULT NULL,
  usage_limit INT DEFAULT NULL,
  used_count INT NOT NULL DEFAULT 0,
  valid_from DATE NOT NULL,
  valid_until DATE NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Wallet / Loyalty
CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  type ENUM('earn','spend','refund','bonus') NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  reference_type VARCHAR(50) DEFAULT NULL,
  reference_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7) Referrals (ALTER via seeder PHP: mysql_tambah_kolom() helper)
-- NOTE: kolom referral_code / referred_by ditambahkan di database/seed-klook-ui.php
--       karena MySQL tidak mendukung ADD COLUMN IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS referrals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  referrer_id INT NOT NULL,
  referred_email VARCHAR(150) NOT NULL,
  referred_user_id INT DEFAULT NULL,
  status ENUM('pending','completed','rewarded') NOT NULL DEFAULT 'pending',
  reward_amount DECIMAL(12,2) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8) Collections (curated sets)
CREATE TABLE IF NOT EXISTS collections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  description TEXT,
  cover_image VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  collection_id INT NOT NULL,
  item_type ENUM('tour','attraction','hotel','transfer') NOT NULL,
  item_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9) FAQ
CREATE TABLE IF NOT EXISTS faq_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS faq_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (category_id) REFERENCES faq_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10) Trains
CREATE TABLE IF NOT EXISTS trains (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  route_from VARCHAR(100) NOT NULL,
  route_to VARCHAR(100) NOT NULL,
  departure_time TIME NOT NULL,
  arrival_time TIME NOT NULL,
  duration VARCHAR(20) DEFAULT NULL,
  price DECIMAL(12,2) NOT NULL,
  price_currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  class VARCHAR(50) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS train_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  train_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  travel_date DATE NOT NULL,
  seats INT NOT NULL DEFAULT 1,
  total_price DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  booking_code VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (train_id) REFERENCES trains(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11) Connectivity products (eSIM / SIM / WiFi)
CREATE TABLE IF NOT EXISTS connectivity_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  type ENUM('esim','sim','wifi') NOT NULL DEFAULT 'esim',
  country VARCHAR(100) NOT NULL,
  coverage VARCHAR(100) DEFAULT NULL,
  data_quota VARCHAR(50) DEFAULT NULL,
  duration_days INT NOT NULL DEFAULT 7,
  price DECIMAL(12,2) NOT NULL,
  price_currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  description TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS connectivity_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  total_price DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  booking_code VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES connectivity_products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12) Kolom tambahan untuk tabel existing (via seeder PHP: seed-klook-ui.php)
--     karena MySQL 9 tidak mendukung ADD COLUMN IF NOT EXISTS.
-- tours:  duration_days, duration_nights, location_city, instant_confirmation, free_cancellation, best_seller
-- hotels: lat, lng, amenities, instant_confirmation, free_cancellation, best_seller
-- flights: baggage_allowance, refundable
-- ferries: amenities
-- rental_cars: fuel_type, year, with_driver
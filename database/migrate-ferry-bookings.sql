-- migrate-ferry-bookings.sql — ferry_bookings table (local processing, no Easybook redirect)
-- Idempotent via CREATE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS ferry_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(20) NOT NULL,
    user_id INT DEFAULT NULL,
    company VARCHAR(100) NOT NULL,
    vessel_name VARCHAR(100) DEFAULT NULL,
    route_from VARCHAR(150) NOT NULL,
    route_to VARCHAR(150) NOT NULL,
    departure_date DATE NOT NULL,
    departure_time VARCHAR(10) NOT NULL,
    arrival_time VARCHAR(10) DEFAULT NULL,
    passengers INT NOT NULL DEFAULT 1,
    price_per_pax DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    passenger_data JSON DEFAULT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(200) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_fb_code (booking_code),
    INDEX idx_fb_user (user_id),
    INDEX idx_fb_status (status),
    INDEX idx_fb_date (departure_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

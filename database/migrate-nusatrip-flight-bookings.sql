-- ============================================================
-- migrate-nusatrip-flight-bookings.sql — simpan booking tiket pesawat NusaTrip.
-- Idempotent via CREATE TABLE IF NOT EXISTS.
-- ============================================================
CREATE TABLE IF NOT EXISTS nusatrip_flight_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    airline VARCHAR(120) DEFAULT NULL,
    flight_number VARCHAR(40) DEFAULT NULL,
    origin VARCHAR(10) DEFAULT NULL,
    destination VARCHAR(10) DEFAULT NULL,
    departure_date DATE DEFAULT NULL,
    passengers INT DEFAULT 1,
    cabin VARCHAR(40) DEFAULT NULL,
    booking_code VARCHAR(60) DEFAULT NULL,
    provider_ref VARCHAR(200) DEFAULT NULL,
    task_id VARCHAR(120) DEFAULT NULL,
    checkout_id VARCHAR(120) DEFAULT NULL,
    total_price DECIMAL(12,2) DEFAULT 0,
    va_bank VARCHAR(80) DEFAULT NULL,
    va_number VARCHAR(60) DEFAULT NULL,
    va_expires_at DATETIME DEFAULT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    raw_summary MEDIUMTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_nfb_task (task_id),
    INDEX idx_nfb_user (user_id),
    INDEX idx_nfb_code (booking_code),
    INDEX idx_nfb_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- migrate-live-bookings.sql — simpan booking live (hotel NusaTrip) + kolom VA flight
-- Idempotent via information_schema / CREATE IF NOT EXISTS.
-- ============================================================

-- 1) Booking hotel NusaTrip (guest checkout bisa, tapi hanya user login yang tersimpan).
CREATE TABLE IF NOT EXISTS nusatrip_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    hotel_id VARCHAR(40) NOT NULL,
    hotel_name VARCHAR(200) DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    checkin DATE NOT NULL,
    checkout DATE NOT NULL,
    guests INT DEFAULT 1,
    room_category VARCHAR(120) DEFAULT NULL,
    room_board VARCHAR(120) DEFAULT NULL,
    room_rate DECIMAL(12,2) DEFAULT 0,
    booking_code VARCHAR(60) DEFAULT NULL,
    provider_ref VARCHAR(120) DEFAULT NULL,
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
    UNIQUE KEY uniq_nb_task (task_id),
    INDEX idx_nb_user (user_id),
    INDEX idx_nb_code (booking_code),
    INDEX idx_nb_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) flight_bookings: kolom VA/provider + booking_code + title (agar muncul & bisa resume).
DELIMITER $$

DROP PROCEDURE IF EXISTS fb_add_live_cols $$
CREATE PROCEDURE fb_add_live_cols()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'booking_code') THEN
        ALTER TABLE flight_bookings ADD COLUMN booking_code VARCHAR(64) DEFAULT NULL AFTER offer_id;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'provider') THEN
        ALTER TABLE flight_bookings ADD COLUMN provider VARCHAR(20) NOT NULL DEFAULT 'local' AFTER schedule_id;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'title') THEN
        ALTER TABLE flight_bookings ADD COLUMN title VARCHAR(200) DEFAULT NULL AFTER provider;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'va_bank') THEN
        ALTER TABLE flight_bookings ADD COLUMN va_bank VARCHAR(80) DEFAULT NULL AFTER total_price;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'va_number') THEN
        ALTER TABLE flight_bookings ADD COLUMN va_number VARCHAR(60) DEFAULT NULL AFTER va_bank;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'payment_total') THEN
        ALTER TABLE flight_bookings ADD COLUMN payment_total DECIMAL(12,2) DEFAULT NULL AFTER va_number;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'payment_deadline') THEN
        ALTER TABLE flight_bookings ADD COLUMN payment_deadline VARCHAR(80) DEFAULT NULL AFTER payment_total;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'flight_bookings' AND column_name = 'payment_status') THEN
        ALTER TABLE flight_bookings ADD COLUMN payment_status VARCHAR(20) DEFAULT 'unpaid' AFTER payment_deadline;
    END IF;
END $$

DELIMITER ;

CALL fb_add_live_cols();
DROP PROCEDURE IF EXISTS fb_add_live_cols;

-- schedule_id harus boleh NULL: order Duffel tidak punya jadwal lokal.
ALTER TABLE flight_bookings MODIFY COLUMN schedule_id INT DEFAULT NULL;

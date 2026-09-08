-- ============================================================
-- migrate-admin-bookings-phase5.sql — fase 5: aksi status & catatan internal
-- - hotel_bookings: kolom email + admin_note
-- - flight_bookings: tabel (tour-style status + offer_id + pax)
-- Idempotent via information_schema / CREATE IF NOT EXISTS.
-- ============================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS hb_phase5_cols $$
CREATE PROCEDURE hb_phase5_cols()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'hotel_bookings' AND column_name = 'email') THEN
        ALTER TABLE hotel_bookings ADD COLUMN email VARCHAR(200) DEFAULT NULL AFTER phone;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'hotel_bookings' AND column_name = 'admin_note') THEN
        ALTER TABLE hotel_bookings ADD COLUMN admin_note TEXT DEFAULT NULL AFTER status;
    END IF;
END $$

DELIMITER ;

CALL hb_phase5_cols();
DROP PROCEDURE IF EXISTS hb_phase5_cols;

CREATE TABLE IF NOT EXISTS flight_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    offer_id VARCHAR(64) DEFAULT NULL,
    seats INT NOT NULL DEFAULT 1,
    pax JSON DEFAULT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(200) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    departure_date DATE NOT NULL,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fb_sched (schedule_id),
    INDEX idx_fb_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- migrate-payments.sql — tabel pembayaran (Midtrans Snap) — Fase 1 PRD
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_type VARCHAR(20) NOT NULL DEFAULT 'tour',      -- tour|hotel|flight|train|transfer|attraction|esim
    booking_id INT NOT NULL,                                -- id di tabel booking terkait
    booking_code VARCHAR(20) DEFAULT NULL,                  -- kode booking (bila ada)
    order_id VARCHAR(64) NOT NULL,                          -- Midtrans order_id (unik)
    gross_amount DECIMAL(12,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',          -- pending|paid|failed|expired|challenge
    payment_type VARCHAR(40) DEFAULT NULL,                  -- bank_transfer|gopay|qris|credit_card|...
    transaction_id VARCHAR(64) DEFAULT NULL,                -- Midtrans transaction_id
    raw_payload JSON DEFAULT NULL,                          -- notifikasi terakhir (debug/audit)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_order_id (order_id),
    INDEX idx_payments_booking (booking_type, booking_id),
    INDEX idx_payments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kolom is_paid helper di bookings (tour) untuk query cepat daftar "perlu bayar"
-- (idempotent via information_schema)
DELIMITER $$

DROP PROCEDURE IF EXISTS payments_add_is_paid $$
CREATE PROCEDURE payments_add_is_paid()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'bookings'
          AND column_name = 'payment_status'
    ) THEN
        ALTER TABLE bookings
            ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER status,
            ADD INDEX idx_bookings_payment (payment_status);
    END IF;
END $$

DELIMITER ;

CALL payments_add_is_paid();
DROP PROCEDURE IF EXISTS payments_add_is_paid;

-- Setting Midtrans (idempotent)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('midtrans_env', 'sandbox'),
    ('midtrans_server_key', ''),
    ('midtrans_client_key', ''),
    ('payment_enabled', '1');

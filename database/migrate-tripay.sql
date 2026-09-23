-- ============================================================
-- migrate-tripay.sql — modul Tripay + mode manual/instant — Fase Tripay
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- Kolom gateway di payments (bisa menampung midtrans MAUPUN tripay)
DELIMITER $$

DROP PROCEDURE IF EXISTS tripay_add_cols $$
CREATE PROCEDURE tripay_add_cols()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'payments'
          AND column_name = 'gateway'
    ) THEN
        ALTER TABLE payments
            ADD COLUMN gateway VARCHAR(20) NOT NULL DEFAULT 'midtrans' AFTER booking_code,
            ADD COLUMN reference VARCHAR(64) DEFAULT NULL AFTER order_id,
            ADD COLUMN pay_code VARCHAR(64) DEFAULT NULL AFTER reference,
            ADD COLUMN pay_url VARCHAR(255) DEFAULT NULL AFTER pay_code,
            ADD COLUMN checkout_url VARCHAR(255) DEFAULT NULL AFTER pay_url,
            ADD INDEX idx_payments_gateway (gateway),
            ADD INDEX idx_payments_reference (reference);
    END IF;
END $$

DELIMITER ;

CALL tripay_add_cols();
DROP PROCEDURE IF EXISTS tripay_add_cols;

-- Setting mode + gateway + kredensial Tripay (idempotent)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('payment_mode', 'manual'),
    ('payment_gateway', 'midtrans'),
    ('tripay_env', 'sandbox'),
    ('tripay_api_key', ''),
    ('tripay_private_key', ''),
    ('tripay_merchant_code', '');

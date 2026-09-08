-- ============================================================
-- migrate-points.sql — fase 11: loyalty points
-- Ledger transaksi + saldo via SUM. Rate earn = % dari total booking
-- (points_earning_rate di settings, default 1% → 1 point per 100).
-- Idempotent: CREATE IF NOT EXISTS + kolom via information_schema.
-- ============================================================

CREATE TABLE IF NOT EXISTS points_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,                        -- + earn, - redeem
    booking_type VARCHAR(20) DEFAULT NULL,      -- tour|hotel|flight|...
    booking_id INT DEFAULT NULL,
    booking_code VARCHAR(30) DEFAULT NULL,
    reason VARCHAR(100) NOT NULL DEFAULT 'earn', -- earn|redeem|adjust|refund
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pl_user (user_id, created_at),
    UNIQUE KEY uniq_pl_booking (booking_type, booking_id, reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setting rate (idempotent)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('points_earning_rate', '1');

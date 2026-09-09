-- ============================================================
-- migrate-user-tiers.sql — Fase 12: loyalty tier system
-- Adds `tier` column to users + seed tiers based on booking count.
-- ============================================================

-- Add tier column to users table (idempotent)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'tier');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN tier VARCHAR(20) NOT NULL DEFAULT \'explorer\' AFTER phone', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create index for tier-based queries (idempotent)
SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_tier');
SET @sql2 := IF(@idxExist = 0, 'CREATE INDEX idx_users_tier ON users(tier)', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;
CREATE TABLE IF NOT EXISTS user_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier_name VARCHAR(20) NOT NULL UNIQUE,
    display_name VARCHAR(50) NOT NULL,
    min_bookings INT NOT NULL DEFAULT 0,
    earning_rate DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    icon VARCHAR(50) DEFAULT NULL,
    color VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default tiers (matching admin loyalty-settings thresholds)
INSERT IGNORE INTO user_tiers (tier_name, display_name, min_bookings, earning_rate, icon, color) VALUES
('explorer', 'Explorer', 0, 1.00, 'bi-compass', '#6c757d'),
('silver', 'Silver', 2, 1.20, 'bi-star', '#adb5bd'),
('gold', 'Gold', 5, 1.50, 'bi-trophy', '#ffc107'),
('platinum', 'Platinum', 10, 2.00, 'bi-gem', '#0d6efd');

-- Backfill: assign tiers based on completed booking count
UPDATE users u
JOIN (
    SELECT user_id,
           COUNT(*) as booking_count
    FROM bookings
    WHERE status IN ('confirmed', 'paid', 'completed')
    GROUP BY user_id
) b ON u.id = b.user_id
SET u.tier = CASE
    WHEN b.booking_count >= 10 THEN 'platinum'
    WHEN b.booking_count >= 5 THEN 'gold'
    WHEN b.booking_count >= 2 THEN 'silver'
    ELSE 'explorer'
END
WHERE u.tier = 'explorer';

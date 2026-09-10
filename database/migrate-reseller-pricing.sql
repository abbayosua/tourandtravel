-- ============================================================
-- migrate-reseller-pricing.sql — Reseller-specific tour pricing
-- Idempotent: safe to run multiple times.
-- ============================================================

CREATE TABLE IF NOT EXISTS reseller_tour_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    reseller_price DECIMAL(12,2) NOT NULL,
    min_pax INT NOT NULL DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reseller_price_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
    CONSTRAINT uq_reseller_tour UNIQUE (tour_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for filtering active prices
SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reseller_tour_prices' AND INDEX_NAME = 'idx_rtp_active');
SET @sql := IF(@idxExist = 0, "CREATE INDEX idx_rtp_active ON reseller_tour_prices(active)", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

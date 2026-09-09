-- ============================================================
-- migrate-corporate-rates.sql — Corporate rates (FOLLOW-20260909-104850)
-- corporate_companies: perusahaan + discount %.
-- users.corporate_company_id: user terafiliasi perusahaan.
-- Idempotent.
-- ============================================================

CREATE TABLE IF NOT EXISTS corporate_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'corporate_company_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN corporate_company_id INT DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

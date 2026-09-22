-- ============================================================
-- migrate-oauth.sql — Fase 1: OAuth social login (Google)
-- Adds `google_id` + `avatar_url` columns to users table.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- Add google_id column (idempotent)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'google_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL DEFAULT NULL AFTER password_hash, ADD UNIQUE INDEX idx_users_google_id (google_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add avatar_url column (idempotent)
SET @exist2 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar_url');
SET @sql2 := IF(@exist2 = 0, 'ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL DEFAULT NULL AFTER google_id', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

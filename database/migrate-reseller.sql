-- ============================================================
-- migrate-reseller.sql — Reseller account system
-- Adds `role` ENUM + `reseller_balance` to users table.
-- Idempotent: safe to run multiple times.
-- ============================================================

-- 1) Add `role` column to users (ENUM: user, reseller, admin)
SET @roleExist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role');
SET @roleSql := IF(@roleExist = 0, "ALTER TABLE users ADD COLUMN role ENUM('user','reseller','admin') NOT NULL DEFAULT 'user' AFTER phone", 'SELECT 1');
PREPARE roleStmt FROM @roleSql; EXECUTE roleStmt; DEALLOCATE PREPARE roleStmt;

-- 2) Add `reseller_balance` column to users (DECIMAL 12,2)
SET @balExist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reseller_balance');
SET @balSql := IF(@balExist = 0, "ALTER TABLE users ADD COLUMN reseller_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER role", 'SELECT 1');
PREPARE balStmt FROM @balSql; EXECUTE balStmt; DEALLOCATE PREPARE balStmt;

-- 3) Add index on `role` for filtering queries
SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_role');
SET @idxSql := IF(@idxExist = 0, "CREATE INDEX idx_users_role ON users(role)", 'SELECT 1');
PREPARE idxStmt FROM @idxSql; EXECUTE idxStmt; DEALLOCATE PREPARE idxStmt;

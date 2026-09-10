-- ============================================================
-- migrate-reseller-bookings.sql — Add reseller tracking to bookings
-- Adds `booking_source` and `reseller_id` columns to bookings.
-- Idempotent: safe to run multiple times.
-- ============================================================

-- 1) Add `booking_source` ENUM column
SET @srcExist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'booking_source');
SET @srcSql := IF(@srcExist = 0, "ALTER TABLE bookings ADD COLUMN booking_source ENUM('direct','reseller') NOT NULL DEFAULT 'direct' AFTER status", 'SELECT 1');
PREPARE srcStmt FROM @srcSql; EXECUTE srcStmt; DEALLOCATE PREPARE srcStmt;

-- 2) Add `reseller_id` INT column (FK → users)
SET @ridExist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'reseller_id');
SET @ridSql := IF(@ridExist = 0, "ALTER TABLE bookings ADD COLUMN reseller_id INT DEFAULT NULL AFTER booking_source", 'SELECT 1');
PREPARE ridStmt FROM @ridSql; EXECUTE ridStmt; DEALLOCATE PREPARE ridStmt;

-- 3) Add index on `booking_source` for filtering
SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'idx_bookings_source');
SET @idxSql := IF(@idxExist = 0, "CREATE INDEX idx_bookings_source ON bookings(booking_source)", 'SELECT 1');
PREPARE idxStmt FROM @idxSql; EXECUTE idxStmt; DEALLOCATE PREPARE idxStmt;

-- 4) Add index on `reseller_id` for lookups
SET @ridxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'idx_bookings_reseller');
SET @ridxSql := IF(@ridxExist = 0, "CREATE INDEX idx_bookings_reseller ON bookings(reseller_id)", 'SELECT 1');
PREPARE ridxStmt FROM @ridxSql; EXECUTE ridxStmt; DEALLOCATE PREPARE ridxStmt;

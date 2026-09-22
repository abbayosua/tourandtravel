-- ============================================================
-- migrate-refund.sql — Fase 3: refund self-service
-- bookings.refund_status/refund_amount/refund_reason + tours.refund_policy.
-- Policy default: full (>H-7), 50% (H-3 s/d H-7), non-refundable (<H-3).
-- Kolom tours.refund_policy: 'auto' (pakai default) | 'non_refundable' | 'full_refund'.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- bookings.refund_status (idempotent)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'refund_status');
SET @sql := IF(@exist = 0, 'ALTER TABLE bookings ADD COLUMN refund_status ENUM(''none'',''requested'',''approved'',''rejected'') NOT NULL DEFAULT ''none'' AFTER status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- bookings.refund_amount (idempotent)
SET @exist2 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'refund_amount');
SET @sql2 := IF(@exist2 = 0, 'ALTER TABLE bookings ADD COLUMN refund_amount DECIMAL(12,2) NULL DEFAULT NULL AFTER refund_status', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- bookings.refund_reason (idempotent)
SET @exist3 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'refund_reason');
SET @sql3 := IF(@exist3 = 0, 'ALTER TABLE bookings ADD COLUMN refund_reason VARCHAR(500) NULL DEFAULT NULL AFTER refund_amount', 'SELECT 1');
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- tours.refund_policy (idempotent)
SET @exist4 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tours' AND COLUMN_NAME = 'refund_policy');
SET @sql4 := IF(@exist4 = 0, 'ALTER TABLE tours ADD COLUMN refund_policy ENUM(''auto'',''non_refundable'',''full_refund'') NOT NULL DEFAULT ''auto'' AFTER free_cancellation', 'SELECT 1');
PREPARE stmt4 FROM @sql4; EXECUTE stmt4; DEALLOCATE PREPARE stmt4;

-- Index untuk lookup refund pending di admin (idempotent)
SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'idx_refund_status');
SET @sql5 := IF(@idxExist = 0, 'CREATE INDEX idx_refund_status ON bookings(refund_status)', 'SELECT 1');
PREPARE stmt5 FROM @sql5; EXECUTE stmt5; DEALLOCATE PREPARE stmt5;

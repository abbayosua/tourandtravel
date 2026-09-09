-- ============================================================
-- migrate-review-hotel-id.sql — tambah kolom hotel_id ke reviews
-- Idempotent via information_schema check.
-- ============================================================

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'hotel_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE reviews ADD COLUMN hotel_id INT DEFAULT NULL AFTER tour_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND INDEX_NAME = 'idx_reviews_hotel');
SET @sql2 := IF(@idxExist = 0, 'CREATE INDEX idx_reviews_hotel ON reviews(hotel_id)', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

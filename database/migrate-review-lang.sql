-- ============================================================
-- migrate-review-lang.sql — Multi-lang UGC (FOLLOW-20260909-104850)
-- Kolom lang di reviews + backfill dari session default (id).
-- Idempotent.
-- ============================================================

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'lang');
SET @sql := IF(@exist = 0, 'ALTER TABLE reviews ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT ''id'' AFTER comment', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- tour_id nullable (hotel-only reviews)
SET @tourNull := (SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'tour_id');
SET @sqlTour := IF(@tourNull = 'NO', 'ALTER TABLE reviews MODIFY tour_id INT DEFAULT NULL', 'SELECT 1');
PREPARE stmtTour FROM @sqlTour; EXECUTE stmtTour; DEALLOCATE PREPARE stmtTour;

SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND INDEX_NAME = 'idx_reviews_lang');
SET @sqlIdx := IF(@idxExist = 0, 'CREATE INDEX idx_reviews_lang ON reviews(lang)', 'SELECT 1');
PREPARE stmtIdx FROM @sqlIdx; EXECUTE stmtIdx; DEALLOCATE PREPARE stmtIdx;

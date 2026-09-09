-- ============================================================
-- migrate-flash-sales.sql — fase 10: flash sale engine
-- Diskon per produk (tour/hotel/attraction/esim), periode, kuota.
-- Idempotent: CREATE IF NOT EXISTS + INSERT WHERE NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS flash_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type VARCHAR(20) NOT NULL DEFAULT 'tour',   -- tour|hotel|attraction|esim
    item_id INT NOT NULL,
    discount_percent TINYINT NOT NULL DEFAULT 10,    -- 5..70
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    stock_limit INT DEFAULT NULL,                    -- NULL = tanpa kuota
    sold_count INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_fs_item (item_type, item_id),
    INDEX idx_fs_active (is_active, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed demo: 2 tour aktif sekarang (id 61, 62), kadaluarsa tahun depan
INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, is_active)
SELECT 'tour', t.id, 15, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 180 DAY, 50, 1
FROM tours t
WHERE t.id IN (61, 62)
  AND NOT EXISTS (SELECT 1 FROM flash_sales fs WHERE fs.item_type = 'tour' AND fs.item_id = t.id);

-- Seed kadaluarsa (untuk sad path test): tour 63
INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, is_active)
SELECT 'tour', t.id, 30, NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 1 DAY, 10, 1
FROM tours t
WHERE t.id = 63
  AND NOT EXISTS (SELECT 1 FROM flash_sales fs WHERE fs.item_type = 'tour' AND fs.item_id = t.id);

-- Seed stok habis (untuk sad path test): tour 64
INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, sold_count, is_active)
SELECT 'tour', t.id, 25, NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 30 DAY, 5, 5, 1
FROM tours t
WHERE t.id = 64
  AND NOT EXISTS (SELECT 1 FROM flash_sales fs WHERE fs.item_type = 'tour' AND fs.item_id = t.id);

-- Seed flash sale hotel: hotel 37, 38 aktif -20%
INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, is_active)
SELECT 'hotel', h.id, 20, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 90 DAY, 30, 1
FROM hotels h
WHERE h.id IN (37, 38)
  AND NOT EXISTS (SELECT 1 FROM flash_sales fs WHERE fs.item_type = 'hotel' AND fs.item_id = h.id);

-- Seed flash sale hotel: hotel 39 kadaluarsa (sad path)
INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, is_active)
SELECT 'hotel', h.id, 15, NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 1 DAY, 20, 1
FROM hotels h
WHERE h.id = 39
  AND NOT EXISTS (SELECT 1 FROM flash_sales fs WHERE fs.item_type = 'hotel' AND fs.item_id = h.id);

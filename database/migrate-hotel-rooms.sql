-- ============================================================
-- migrate-hotel-rooms.sql — Fase 2 KEKURANGAN: tipe kamar hotel
-- Tabel hotel_rooms + seed 3 tipe kamar per hotel (20 hotel).
-- Idempotent: CREATE TABLE IF NOT EXISTS + INSERT ... WHERE NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS hotel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,                 -- nama tipe kamar (ID)
    name_en VARCHAR(150) DEFAULT NULL,          -- nama tipe kamar (EN)
    bed_type VARCHAR(50) NOT NULL DEFAULT 'double',  -- single|double|twin|king|suite
    max_guest INT NOT NULL DEFAULT 2,
    rate DECIMAL(12,2) NOT NULL,                -- harga per malam
    breakfast TINYINT(1) NOT NULL DEFAULT 0,
    refundable TINYINT(1) NOT NULL DEFAULT 1,
    stock INT NOT NULL DEFAULT 5,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hr_hotel (hotel_id, is_active),
    CONSTRAINT fk_hr_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: 3 tipe kamar per hotel mengacu price_per_night hotel.
-- Idempotent via NOT EXISTS (tanpa unique key karena nama bebas).
INSERT INTO hotel_rooms (hotel_id, name, name_en, bed_type, max_guest, rate, breakfast, refundable, stock)
SELECT h.id, r.nm, r.nm_en, r.bed, r.guest,
       ROUND(h.price_per_night * r.mult, 2), r.brkf, 1, r.stock
FROM hotels h
JOIN (
    SELECT 'Superior Double' nm, 'Superior Double' nm_en, 'double' bed, 2 guest, 0.85 mult, 1 brkf, 8 stock
    UNION ALL SELECT 'Deluxe Twin', 'Deluxe Twin', 'twin', 2, 1.00, 1, 6
    UNION ALL SELECT 'Executive Suite', 'Executive Suite', 'suite', 3, 1.60, 0, 3
) r
WHERE NOT EXISTS (
    SELECT 1 FROM hotel_rooms hr WHERE hr.hotel_id = h.id
) AND h.is_active = 1;

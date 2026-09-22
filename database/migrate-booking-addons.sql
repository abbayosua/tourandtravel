-- ============================================================
-- migrate-booking-addons.sql — Fase 4: travel insurance & add-on
-- Tabel booking_addons: baris per add-on per booking (type, amount).
-- Type saat ini: 'insurance' (premi 3% x total). Aman utk tipe lain di masa depan.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS booking_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_type VARCHAR(20) NOT NULL DEFAULT 'tour',   -- tour|hotel|flight|...
    booking_id INT NOT NULL,
    type VARCHAR(30) NOT NULL,                          -- insurance|baggage|seat|...
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    meta VARCHAR(255) NULL DEFAULT NULL,                -- info tambahan (mis. plan name)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_addon (booking_type, booking_id, type),
    INDEX idx_addon_booking (booking_type, booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

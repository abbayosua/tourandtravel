-- ============================================================
-- migrate-availability.sql — Fase 2: real-time availability engine
-- Kolom slots_booked di price_calendar + kolom booked di tour_dates
-- (available_slots = kuota; booked = terpakai; sisa = available_slots - booked).
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- price_calendar.slots_booked (idempotent)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'price_calendar' AND COLUMN_NAME = 'slots_booked');
SET @sql := IF(@exist = 0, 'ALTER TABLE price_calendar ADD COLUMN slots_booked INT NOT NULL DEFAULT 0 AFTER slots', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- tour_dates.booked (idempotent)
SET @exist2 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_dates' AND COLUMN_NAME = 'booked');
SET @sql2 := IF(@exist2 = 0, 'ALTER TABLE tour_dates ADD COLUMN booked INT NOT NULL DEFAULT 0 AFTER available_slots', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Backfill: booked = jumlah peserta booking confirmed/pending pada tour_date tsb (idempotent via reset dulu)
UPDATE tour_dates td
SET td.booked = (
    SELECT COALESCE(SUM(b.participants), 0)
    FROM bookings b
    WHERE b.tour_date_id = td.id
      AND b.status IN ('confirmed', 'pending')
);

-- Ledger idempotency deduksi slot (Fase 2)
CREATE TABLE IF NOT EXISTS availability_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_type VARCHAR(20) NOT NULL,
    booking_id INT NOT NULL,
    action ENUM('deduct','release') NOT NULL,
    slots INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ledger (booking_type, booking_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

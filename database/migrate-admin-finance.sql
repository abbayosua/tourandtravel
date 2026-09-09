-- migrate-admin-finance.sql
-- PRD: ADMINPRD.md — Admin Dashboard Operational System
-- Menambah: tabel expenses + kolom cogs + index (created_at, status) di 8 tabel booking
-- CATATAN: server DB ini tidak mendukung ADD COLUMN/INDEX IF NOT EXISTS.
-- Idempotency ditangani database/migrate-expenses.php (cek information_schema dulu).

-- ============================================================
-- 1. Tabel expenses (P&L: pengeluaran operasional)
-- ============================================================
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,          -- Marketing, Operasional, Gaji, Sewa, Utilitas, Lainnya
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    booking_type VARCHAR(20) NULL,          -- nullable: terkait booking tertentu
    booking_id INT NULL,                    -- nullable: ID booking terkait
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Kolom cogs di 8 tabel booking
-- ============================================================
ALTER TABLE bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE hotel_bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE flight_bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE attraction_bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE transfer_bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE train_bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE connectivity_bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE ferry_bookings ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0;

-- ============================================================
-- 3. Index (created_at, status) untuk query sales report & P&L
-- ============================================================
ALTER TABLE bookings ADD INDEX idx_created_status (created_at, status);
ALTER TABLE hotel_bookings ADD INDEX idx_created_status (created_at, status);
ALTER TABLE flight_bookings ADD INDEX idx_created_status (created_at, status);
ALTER TABLE attraction_bookings ADD INDEX idx_created_status (created_at, status);
ALTER TABLE transfer_bookings ADD INDEX idx_created_status (created_at, status);
ALTER TABLE train_bookings ADD INDEX idx_created_status (created_at, status);
ALTER TABLE connectivity_bookings ADD INDEX idx_created_status (created_at, status);
ALTER TABLE ferry_bookings ADD INDEX idx_created_status (created_at, status);

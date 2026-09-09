<?php
/**
 * migrate-expenses.php — runner idempotent untuk migrate-admin-finance.sql
 * PRD: ADMINPRD.md — Admin Dashboard Operational System
 * Cek information_schema sebelum CREATE/ALTER agar aman dijalankan berulang
 * (server DB ini tidak mendukung ADD COLUMN/INDEX IF NOT EXISTS).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$bookingTables = [
    'bookings', 'hotel_bookings', 'flight_bookings', 'attraction_bookings',
    'transfer_bookings', 'train_bookings', 'connectivity_bookings', 'ferry_bookings',
];

function tableExists(string $t): bool {
    $st = db()->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $st->execute([$t]);
    return (bool)$st->fetchColumn();
}

function columnExists(string $table, string $col): bool {
    $st = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $st->execute([$table, $col]);
    return (bool)$st->fetchColumn();
}

function indexExists(string $table, string $index): bool {
    $st = db()->prepare("SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $st->execute([$table, $index]);
    return (bool)$st->fetchColumn();
}

$actions = 0;

try {
    // 1. Tabel expenses
    if (!tableExists('expenses')) {
        db()->exec("CREATE TABLE expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            booking_type VARCHAR(20) NULL,
            booking_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "OK: tabel expenses dibuat\n";
        $actions++;
    } else {
        echo "SKIP: tabel expenses sudah ada\n";
    }

    // 2. Kolom cogs di 8 tabel booking
    foreach ($bookingTables as $t) {
        if (!tableExists($t)) {
            echo "SKIP: tabel $t tidak ada\n";
            continue;
        }
        if (!columnExists($t, 'cogs')) {
            db()->exec("ALTER TABLE `$t` ADD COLUMN cogs DECIMAL(12,2) NOT NULL DEFAULT 0");
            echo "OK: kolom cogs ditambah ke $t\n";
            $actions++;
        } else {
            echo "SKIP: $t.cogs sudah ada\n";
        }
    }

    // 3. Index (created_at, status) di 8 tabel booking
    foreach ($bookingTables as $t) {
        if (!tableExists($t)) continue;
        if (!indexExists($t, 'idx_created_status')) {
            db()->exec("ALTER TABLE `$t` ADD INDEX idx_created_status (created_at, status)");
            echo "OK: index idx_created_status ditambah ke $t\n";
            $actions++;
        } else {
            echo "SKIP: $t.idx_created_status sudah ada\n";
        }
    }

    echo "SELESAI: $actions aksi dijalankan (idempotent, aman diulang)\n";
    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
/**
 * migrate-currency-snapshot.php — runner idempotent.
 * Menambah kolom snapshot mata uang + FX buffer di tabel `bookings` (tour),
 * setting global `fx_buffer_pct`, dan backfill booking lama.
 * Aman dijalankan berulang (cek information_schema sebelum ALTER).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

function columnExists(string $table, string $col): bool {
    $st = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $st->execute([$table, $col]);
    return (bool)$st->fetchColumn();
}

$columns = [
    'source_amount'     => "DECIMAL(14,2) NULL COMMENT 'Nominal dalam mata uang produk'",
    'source_currency'   => "VARCHAR(5) NULL COMMENT 'IDR/SGD/USD'",
    'charged_amount'    => "DECIMAL(14,2) NULL COMMENT 'Nominal yang benar-benar ditagih (IDR)'",
    'charged_currency'  => "VARCHAR(5) NOT NULL DEFAULT 'IDR'",
    'fx_rate'           => "DECIMAL(18,8) NULL COMMENT 'kurs source->charged saat booking'",
    'rate_locked_at'    => "DATETIME NULL",
    'fx_buffer_amount'  => "DECIMAL(14,2) NOT NULL DEFAULT 0",
];

$actions = 0;

try {
    foreach ($columns as $col => $def) {
        if (!columnExists('bookings', $col)) {
            db()->exec("ALTER TABLE `bookings` ADD COLUMN `$col` $def");
            echo "OK: bookings.$col ditambah\n";
            $actions++;
        } else {
            echo "SKIP: bookings.$col sudah ada\n";
        }
    }

    // Setting global FX buffer (persen). Default 0 = nonaktif sampai admin set.
    db()->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('fx_buffer_pct', '0')")->execute();
    echo "OK: setting fx_buffer_pct dipastikan ada\n";

    // Backfill booking lama: perlakukan sebagai IDR (charged = total_price) agar
    // TIDAK mengubah tagihan historis. Booking baru mengisi snapshot sebenarnya
    // (source_currency + fx_rate) saat dibuat. Ini mencegah overcharge pada data
    // lama yang tidak konsisten (mis. booking lama di tour yang kini ber-currency SGD).
    $n = db()->exec("
        UPDATE bookings
        SET source_amount = total_price,
            source_currency = 'IDR',
            charged_amount = total_price,
            charged_currency = 'IDR',
            fx_rate = 1,
            rate_locked_at = COALESCE(rate_locked_at, created_at),
            fx_buffer_amount = COALESCE(fx_buffer_amount, 0)
        WHERE source_currency IS NULL OR charged_amount IS NULL
    ");
    echo "OK: backfill legacy (IDR) untuk $n booking\n";
    $actions += (int)$n;

    echo "SELESAI: $actions aksi dijalankan (idempotent)\n";
    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

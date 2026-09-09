<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Kolom zh per tabel (idempotent — cek information_schema sebelum ALTER)
$targets = [
    'posts'       => ['title_zh'       => "VARCHAR(255) NULL", 'excerpt_zh' => "TEXT NULL", 'body_zh' => "MEDIUMTEXT NULL"],
    'hotels'      => ['name_zh'        => "VARCHAR(255) NULL", 'description_zh' => "TEXT NULL"],
    'hotel_rooms' => ['name_zh'        => "VARCHAR(255) NULL"],
];

try {
    $db = db();
    $added = 0;
    foreach ($targets as $table => $cols) {
        $existing = $db->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($cols as $col => $def) {
            if (!in_array($col, $existing, true)) {
                $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                echo "OK: $table.$col ditambahkan\n";
                $added++;
            } else {
                echo "SKIP: $table.$col sudah ada\n";
            }
        }
    }
    echo $added ? "DONE: $added kolom ditambahkan\n" : "DONE: semua kolom sudah ada\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

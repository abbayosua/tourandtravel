<?php
// migrate-tour-bilingual.php — kolom bilingual tour + itinerary + tour_dates (idempotent).
// Pola: konten ID di kolom asli, EN/ZH di kolom {field}_{kode}. Baca via tContent() (fallback otomatis).
// Jalankan: php database/migrate-tour-bilingual.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$targets = [
    'tours' => [
        'title_zh' => 'VARCHAR(255) NULL',
        'description_zh' => 'TEXT NULL',
        'category_en' => 'VARCHAR(100) NULL',
        'category_zh' => 'VARCHAR(100) NULL',
        'location_city_en' => 'VARCHAR(100) NULL',
        'location_city_zh' => 'VARCHAR(100) NULL',
        'highlights_en' => 'TEXT NULL',
        'highlights_zh' => 'TEXT NULL',
        'includes_en' => 'TEXT NULL',
        'includes_zh' => 'TEXT NULL',
        'excludes_en' => 'TEXT NULL',
        'excludes_zh' => 'TEXT NULL',
        'flight_info_en' => 'TEXT NULL',
        'flight_info_zh' => 'TEXT NULL',
        'meeting_point_en' => 'VARCHAR(255) NULL',
        'meeting_point_zh' => 'VARCHAR(255) NULL',
        'important_notes_en' => 'TEXT NULL',
        'important_notes_zh' => 'TEXT NULL',
        'route_cities_en' => 'VARCHAR(255) NULL',
        'route_cities_zh' => 'VARCHAR(255) NULL',
    ],
    'itineraries' => [
        'title_zh' => 'VARCHAR(200) NULL',
        'description_zh' => 'TEXT NULL',
        'meals_en' => 'VARCHAR(200) NULL',
        'meals_zh' => 'VARCHAR(200) NULL',
        'accommodation_en' => 'VARCHAR(200) NULL',
        'accommodation_zh' => 'VARCHAR(200) NULL',
    ],
    'tour_dates' => [
        'note_en' => 'VARCHAR(100) NULL',
        'note_zh' => 'VARCHAR(100) NULL',
    ],
];

try {
    $db = db();
    $added = 0;
    foreach ($targets as $table => $cols) {
        $existing = $db->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'"
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
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}

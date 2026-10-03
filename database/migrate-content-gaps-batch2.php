<?php
/**
 * migrate-content-gaps-batch2.php
 *
 * Sisa gap konten per-bahasa:
 *   - itineraries 101 (tour 63): accommodation_zh kosong → tour-detail zh
 *     menampilkan "Hotel 4-5* (Shanghai)" (Inggris).
 *   - trains/rental_cars name_en kosong (nama proper noun) → isi = name agar
 *     audit konten bersih (tidak ada perubahan tampilan).
 *
 * Idempotent.
 * Jalankan: php database/migrate-content-gaps-batch2.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$count = 0;

// Itinerary accommodation_zh
$stmt = db()->prepare("UPDATE itineraries SET accommodation_zh = COALESCE(NULLIF(accommodation_zh, ''), ?) WHERE id = ?");
$stmt->execute(['4-5星酒店（上海）', 101]);
$count += $stmt->rowCount();

// trains / rental_cars name_en (proper nouns)
foreach (['trains', 'rental_cars'] as $tb) {
    $count += db()->exec("UPDATE `$tb` SET name_en = name WHERE (name_en IS NULL OR name_en = '') AND name <> ''");
}

echo "Updated $count content rows.\n";

<?php
/**
 * migrate-translations-pdf-errors.php
 *
 * Pesan error endpoint PDF (itinerary-pdf/tour-itinerary-pdf) sebelumnya
 * hardcoded (die('...')); kini via t(). Seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-pdf-errors.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'ID itinerary wajib diisi.' => 'Itinerary ID required.',
        'Itinerary tidak ditemukan.' => 'Itinerary not found.',
        'Slug wajib diisi.' => 'Slug required.',
        'Tour tidak ditemukan.' => 'Tour not found.',
    ],
    'zh' => [
        'ID itinerary wajib diisi.' => '需要行程 ID。',
        'Itinerary tidak ditemukan.' => '未找到行程。',
        'Slug wajib diisi.' => '需要 Slug。',
        'Tour tidak ditemukan.' => '未找到旅游。',
    ],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count PDF error translation rows.\n";

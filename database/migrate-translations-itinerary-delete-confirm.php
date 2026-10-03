<?php
/**
 * migrate-translations-itinerary-delete-confirm.php
 *
 * Terjemahan en/zh untuk dialog konfirmasi hapus item itinerary
 * (tour-detail.php, itinerary builder).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-itinerary-delete-confirm.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Hapus item ini dari itinerary?' => 'Remove this item from the itinerary?'],
    'zh' => ['Hapus item ini dari itinerary?' => '从行程中删除此项目？'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count itinerary-delete-confirm translation rows.\n";

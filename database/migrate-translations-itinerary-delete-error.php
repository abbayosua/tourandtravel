<?php
/**
 * migrate-translations-itinerary-delete-error.php
 *
 * Pesan error saat hapus itinerary gagal (my-itinerary.php).
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-itinerary-delete-error.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Gagal menghapus itinerary. Coba lagi.' => 'Failed to delete the itinerary. Try again.'],
    'zh' => ['Gagal menghapus itinerary. Coba lagi.' => '删除行程失败。请重试。'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count itinerary-delete-error translation rows.\n";

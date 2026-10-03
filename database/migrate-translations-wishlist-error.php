<?php
/**
 * migrate-translations-wishlist-error.php
 *
 * Terjemahan en/zh untuk feedback error tombol wishlist (footer-shared.php
 * toggleWishlist).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-wishlist-error.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Gagal menyimpan wishlist. Coba lagi.' => 'Failed to save wishlist. Try again.'],
    'zh' => ['Gagal menyimpan wishlist. Coba lagi.' => '保存收藏失败，请重试。'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count wishlist-error translation rows.\n";

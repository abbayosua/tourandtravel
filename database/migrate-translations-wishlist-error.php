<?php
/**
 * migrate-translations-wishlist-error.php
 *
 * Terjemahan en/zh untuk toast tombol wishlist (footer-shared.php
 * toggleWishlist): sukses tambah/hapus, error, dan ajakan login untuk tamu.
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-wishlist-error.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Gagal menyimpan wishlist. Coba lagi.' => 'Failed to save wishlist. Try again.',
        'Ditambahkan ke wishlist' => 'Added to wishlist',
        'Dihapus dari wishlist' => 'Removed from wishlist',
        'Klik Login untuk menambahkan wishlist' => 'Click Login to add to wishlist',
    ],
    'zh' => [
        'Gagal menyimpan wishlist. Coba lagi.' => '保存收藏失败，请重试。',
        'Ditambahkan ke wishlist' => '已加入心愿单',
        'Dihapus dari wishlist' => '已从心愿单移除',
        'Klik Login untuk menambahkan wishlist' => '点击登录以加入心愿单',
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
echo "Upserted $count wishlist-error translation rows.\n";

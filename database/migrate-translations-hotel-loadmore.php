<?php
/**
 * migrate-translations-hotel-loadmore.php
 *
 * Terjemahan en/zh untuk error state infinite scroll di hotels.php
 * (kegagalan fetch hotels-ajax.php dulu dibiarkan senyap).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-hotel-loadmore.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Gagal memuat hotel. Periksa koneksi Anda.' => 'Failed to load hotels. Check your connection.',
    ],
    'zh' => [
        'Gagal memuat hotel. Periksa koneksi Anda.' => '加载酒店失败，请检查网络连接。',
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
echo "Upserted $count hotel load-more translation rows.\n";

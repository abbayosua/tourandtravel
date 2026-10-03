<?php
/**
 * migrate-translations-tour-loadmore.php
 *
 * Menambahkan terjemahan en/zh untuk error state infinite scroll di tours.php
 * (sebelumnya kegagalan fetch dibiarkan senyap: spinner berputar tanpa pesan).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-tour-loadmore.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Coba Lagi' => 'Try Again',
        'Gagal memuat tour. Periksa koneksi Anda.' => 'Failed to load tours. Check your connection.',
        'Kota asal dan tujuan tidak boleh sama' => 'Origin and destination cannot be the same',
    ],
    'zh' => [
        'Coba Lagi' => '重试',
        'Gagal memuat tour. Periksa koneksi Anda.' => '加载旅游失败，请检查网络连接。',
        'Kota asal dan tujuan tidak boleh sama' => '出发地和目的地不能相同',
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
echo "Upserted $count tour load-more translation rows.\n";

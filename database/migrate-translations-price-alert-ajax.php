<?php
/**
 * migrate-translations-price-alert-ajax.php
 *
 * Terjemahan en/zh untuk feedback AJAX form price alert (tour-detail.php,
 * hotel-detail.php). Sebelumnya form POST biasa sehingga user diarahkan ke
 * halaman JSON mentah; kini disubmit via fetch dan menampilkan pesan ini.
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-price-alert-ajax.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Alert harga disimpan!' => 'Price alert saved!',
        'Gagal menyimpan alert. Coba lagi.' => 'Failed to save alert. Try again.',
    ],
    'zh' => [
        'Alert harga disimpan!' => '价格提醒已保存！',
        'Gagal menyimpan alert. Coba lagi.' => '保存提醒失败，请重试。',
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
echo "Upserted $count price-alert feedback translation rows.\n";

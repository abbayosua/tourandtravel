<?php
/**
 * migrate-translations-payment-failed.php
 *
 * Key 'Pembayaran gagal. Coba lagi.' dipakai di booking-success.php (polling
 * status pembayaran) tetapi belum punya terjemahan en/zh — sehingga bocor
 * bahasa Indonesia. Seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-payment-failed.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Pembayaran gagal. Coba lagi.' => 'Payment failed. Please try again.'],
    'zh' => ['Pembayaran gagal. Coba lagi.' => '支付失败。请重试。'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count payment-failed translation rows.\n";

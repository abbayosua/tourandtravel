<?php
/**
 * migrate-translations-payment-expire-confirm.php
 *
 * Konfirmasi "Tandai Kedaluwarsa" di admin/payments.php.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-payment-expire-confirm.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Tandai pembayaran ini kedaluwarsa?' => 'Mark this payment as expired?'],
    'zh' => ['Tandai pembayaran ini kedaluwarsa?' => '将此付款标记为已过期？'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count payment-expire-confirm translation rows.\n";

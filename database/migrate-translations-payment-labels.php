<?php
/**
 * migrate-translations-payment-labels.php
 *
 * Melengkapi terjemahan label pembayaran yang sebelumnya hardcoded / belum
 * punya terjemahan Mandarin di booking-success.php:
 *   - 'Virtual Account' (toggle Singapay) → en sudah ada, tambah zh
 *   - 'Biaya' (label fee channel Tripay; key sudah ada en/zh, seed ulang idempotent)
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-payment-labels.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Virtual Account' => 'Virtual Account',
    ],
    'zh' => [
        'Virtual Account' => '虚拟账户',
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
echo "Upserted $count payment label translation rows.\n";

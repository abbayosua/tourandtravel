<?php
/**
 * migrate-translations-wallet-redeem-confirm.php
 *
 * Terjemahan en/zh untuk dialog konfirmasi penukaran points di wallet.php
 * (sebelumnya menekan "Tukar" langsung menukar points tanpa konfirmasi).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-wallet-redeem-confirm.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Tukar points menjadi KlookCash? Penukaran tidak dapat dibatalkan.' => 'Convert points to KlookCash? This cannot be undone.',
    ],
    'zh' => [
        'Tukar points menjadi KlookCash? Penukaran tidak dapat dibatalkan.' => '将积分兑换为 KlookCash？此操作无法撤销。',
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
echo "Upserted $count wallet redeem confirm translation rows.\n";

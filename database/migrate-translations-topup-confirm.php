<?php
/**
 * migrate-translations-topup-confirm.php
 *
 * Konfirmasi approve/reject topup reseller (admin/reseller-topups.php).
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-topup-confirm.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Setujui topup ini dan tambahkan saldo reseller?' => 'Approve this topup and add it to the reseller balance?',
        'Tolak topup ini?' => 'Reject this topup?',
    ],
    'zh' => [
        'Setujui topup ini dan tambahkan saldo reseller?' => '批准此充值并添加到经销商余额？',
        'Tolak topup ini?' => '拒绝此充值？',
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
echo "Upserted $count topup-confirm translation rows.\n";

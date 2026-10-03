<?php
/**
 * migrate-translations-refund-messages.php
 *
 * Pesan refund (includes/refund.php) sebelumnya hardcoded Indonesia dan
 * ditampilkan di my-bookings.php via ?rmsg=. Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-refund-messages.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Booking ini sudah tidak bisa direfund (melewati batas waktu / non-refundable)' => 'This booking can no longer be refunded (deadline passed / non-refundable)',
        'Pengajuan refund diterima. Estimasi refund:' => 'Refund request received. Estimated refund:',
    ],
    'zh' => [
        'Booking ini sudah tidak bisa direfund (melewati batas waktu / non-refundable)' => '此预订已无法退款（已过期限 / 不可退款）',
        'Pengajuan refund diterima. Estimasi refund:' => '退款申请已收到。预计退款：',
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
echo "Upserted $count refund message translation rows.\n";

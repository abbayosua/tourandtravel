<?php
/**
 * migrate-translations-booking-status-confirm.php
 *
 * Konfirmasi ubah status booking (admin/bookings.php).
 *
 * Idempotent.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Ubah status booking ini? Customer akan menerima notifikasi.' => "Change this booking's status? The customer will be notified."],
    'zh' => ['Ubah status booking ini? Customer akan menerima notifikasi.' => '更改此订单状态？客户将收到通知。'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) { $stmt->execute([$key, $lang, $value]); $count++; }
}
echo "Upserted $count booking-status-confirm translation rows.\n";

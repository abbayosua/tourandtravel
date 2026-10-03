<?php
/**
 * migrate-translations-admin-labels.php
 *
 * Label admin yang sebelumnya hardcoded (bukan t()) kini via t():
 *   - admin/nav-menus.php   : URL, Tab, Menu (header tabel)
 *   - admin/hotel-api-settings.php : 'Auto' (opsi sumber hotel)
 *   - admin/bookings.php    : 'Refund' (badge; key sudah ada)
 * Seed key yang belum ada.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-admin-labels.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Auto' => 'Auto'],
    'zh' => ['Auto' => '自动'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count admin label translation rows.\n";

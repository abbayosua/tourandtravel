<?php
/**
 * migrate-translations-pagetitles.php
 *
 * $pageTitle yang sebelumnya hardcoded (tidak ikut bahasa) kini via t():
 *   - login.php             : 'Login'   (key sudah ada)
 *   - hotels.php            : 'Hotel'   (key sudah ada)
 *   - admin/rental-car-edit : 'Edit Rental Mobil' (key sudah ada)
 *   - nusatrip-book.php     : 'Booking Hotel NusaTrip' (seed baru)
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-pagetitles.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Booking Hotel NusaTrip' => 'NusaTrip Hotel Booking'],
    'zh' => ['Booking Hotel NusaTrip' => 'NusaTrip 酒店预订'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count page-title translation rows.\n";

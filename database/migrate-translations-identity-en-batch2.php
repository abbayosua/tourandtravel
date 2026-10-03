<?php
/**
 * migrate-translations-identity-en-batch2.php
 *
 * Lanjutan audit key dengan nilai `en` identity (sama dengan key Indonesia),
 * sehingga versi EN tetap menampilkan bahasa Indonesia. Key ini dirender di:
 *   - tour-detail.php : tombol "Buat Itinerary" + placeholder aktivitas itinerary
 *   - ferry-booking.php / pelni-booking.php : tombol "Konfirmasi Pesanan"
 *   - admin/promo-codes.php : label "Deskripsi:"
 * (nilai zh sudah benar)
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-identity-en-batch2.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Buat Itinerary' => 'Create Itinerary',
        'Aktivitas, mis: Kintamani Tour 08:00' => 'Activity, e.g. Kintamani Tour 08:00',
        'Konfirmasi Pesanan' => 'Confirm Order',
        'Deskripsi:' => 'Description:',
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
echo "Upserted $count identity-en (batch 2) translation rows.\n";

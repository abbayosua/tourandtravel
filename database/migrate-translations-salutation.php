<?php
/**
 * migrate-translations-salutation.php
 *
 * Dropdown sapaan (title) di nusatrip-book.php sebelumnya hardcoded
 * (MR/MRS/MS) dan nilai option = teks. Kini label via t() dengan value
 * tetap MR/MRS/MS (API NusaTrip butuh kode itu). Seed key.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-salutation.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['MR' => 'Mr', 'MRS' => 'Mrs', 'MS' => 'Ms'],
    'id' => ['MR' => 'Tn', 'MRS' => 'Ny', 'MS' => 'Nn'],
    'zh' => ['MR' => '先生', 'MRS' => '女士', 'MS' => '小姐'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count salutation translation rows.\n";

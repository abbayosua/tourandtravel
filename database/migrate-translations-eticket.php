<?php
/**
 * migrate-translations-eticket.php
 *
 * Badge hasil pencarian ferry (ferries.php) sebelumnya hardcoded: "Ferry" dan
 * "e-ticket" (tidak diterjemahkan). "Ferry" kini via t('Ferry') (sudah ada);
 * tambah key 'e-ticket'.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-eticket.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['e-ticket' => 'e-ticket'],
    'zh' => ['e-ticket' => '电子票'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count e-ticket translation rows.\n";

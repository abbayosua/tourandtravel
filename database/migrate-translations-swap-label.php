<?php
/**
 * migrate-translations-swap-label.php
 *
 * aria-label tombol tukar (transport-search) sebelumnya hardcoded "Tukar".
 * Kini via t('Tukar asal dan tujuan'); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-swap-label.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Tukar asal dan tujuan' => 'Swap origin and destination'],
    'zh' => ['Tukar asal dan tujuan' => '交换出发地和目的地'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count swap label translation rows.\n";

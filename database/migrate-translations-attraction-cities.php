<?php
/**
 * migrate-translations-attraction-cities.php
 *
 * Nama kota atraksi (attractions.city) dirender via t($city); 'Bali' dan
 * 'Yogyakarta' belum punya key sehingga bocor (Latin) di zh. Seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-attraction-cities.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Bali' => 'Bali', 'Yogyakarta' => 'Yogyakarta'],
    'zh' => ['Bali' => '巴厘岛', 'Yogyakarta' => '日惹'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count attraction city translation rows.\n";

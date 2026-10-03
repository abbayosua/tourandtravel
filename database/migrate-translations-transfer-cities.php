<?php
/**
 * migrate-translations-transfer-cities.php
 *
 * Nama kota transfer (transfers.from_city/to_city) dirender via t($city);
 * beberapa nilai belum punya key terjemahan sehingga bocor (Latin) di zh.
 * Seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-transfer-cities.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Jakarta' => 'Jakarta',
        'Kuta/Seminyak' => 'Kuta/Seminyak',
        'Malioboro' => 'Malioboro',
        'Soekarno-Hatta (CGK)' => 'Soekarno-Hatta (CGK)',
    ],
    'zh' => [
        'Jakarta' => '雅加达',
        'Kuta/Seminyak' => '库塔/水明漾',
        'Malioboro' => '马里奥波罗',
        'Soekarno-Hatta (CGK)' => '苏加诺-哈达 (CGK)',
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
echo "Upserted $count transfer city translation rows.\n";

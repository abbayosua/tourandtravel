<?php
/**
 * migrate-translations-promo-check.php
 *
 * Terjemahan en/zh untuk state loading saat memeriksa kode promo
 * (assets/js/klook.js applyPromo + tour-detail.php applyTourPromo).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-promo-check.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Memeriksa kode promo...' => 'Checking promo code...'],
    'zh' => ['Memeriksa kode promo...' => '正在检查优惠码……'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count promo-check translation rows.\n";

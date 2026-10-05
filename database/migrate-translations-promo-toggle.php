<?php
/**
 * migrate-translations-promo-toggle.php
 *
 * Terjemahan en/zh untuk key baru dari toggle "Saya punya Kode promo"
 * pada form booking paket tour (field kode promo dipindah ke bawah + disembunyikan).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-promo-toggle.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Saya punya Kode promo' => 'I have a promo code',
    ],
    'zh' => [
        'Saya punya Kode promo' => '我有优惠码',
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
echo "Upserted $count promo-toggle translation rows.\n";

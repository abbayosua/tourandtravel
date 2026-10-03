<?php
/**
 * migrate-translations-email-placeholder.php
 *
 * Placeholder email contoh di form ulasan (tour-detail/hotel-detail) sebelumnya
 * hardcoded "email@contoh.com" (Indonesia). Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-email-placeholder.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['email@contoh.com' => 'email@example.com'],
    'zh' => ['email@contoh.com' => 'email@example.com'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count email placeholder translation rows.\n";

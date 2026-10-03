<?php
/**
 * migrate-translations-navbar-labels.php
 *
 * aria-label/title navbar (currency/language/theme toggle) sebelumnya hardcoded
 * Inggris. Kini via t() ('Mata Uang'/'Bahasa' sudah ada). Seed 'Tema'.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-navbar-labels.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Tema' => 'Theme'],
    'zh' => ['Tema' => '主题'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count navbar label translation rows.\n";

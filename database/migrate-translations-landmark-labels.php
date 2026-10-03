<?php
/**
 * migrate-translations-landmark-labels.php
 *
 * aria-label landmark navigasi (navbar 'Main', bottom-nav 'Bottom Navigation')
 * sebelumnya hardcoded Inggris. Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-landmark-labels.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Menu Utama' => 'Main Menu', 'Navigasi Bawah' => 'Bottom Navigation'],
    'zh' => ['Menu Utama' => '主菜单', 'Navigasi Bawah' => '底部导航'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count landmark label translation rows.\n";

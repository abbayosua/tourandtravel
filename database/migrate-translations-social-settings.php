<?php
/**
 * migrate-translations-social-settings.php
 *
 * Label pengaturan media sosial (admin/brand-settings.php).
 *
 * Idempotent.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Media Sosial' => 'Social Media', 'Kosongkan untuk menyembunyikan.' => 'Leave blank to hide.'],
    'zh' => ['Media Sosial' => '社交媒体', 'Kosongkan untuk menyembunyikan.' => '留空则隐藏。'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) { $stmt->execute([$key, $lang, $value]); $count++; }
}
echo "Upserted $count social-settings translation rows.\n";

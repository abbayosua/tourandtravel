<?php
/**
 * migrate-translations-file-corrupt.php
 *
 * resizeImage() mengembalikan pesan 'File rusak' hardcoded (bukan t()) sehingga
 * tampil Indonesia di en/zh. Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-file-corrupt.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['File rusak' => 'Corrupt file'],
    'zh' => ['File rusak' => '文件损坏'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count file-corrupt translation rows.\n";

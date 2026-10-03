<?php
/**
 * migrate-translations-placeholders.php
 *
 * Placeholder input yang sebelumnya hardcoded (bukan t()):
 *   - nusatrip-book.php : 'Nama depan', 'Nama belakang', 'Nomor kartu'
 *   - admin/tour-edit.php : 'Sarapan, makan siang'
 * Kini via t(); seed key-nya.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-placeholders.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Nama depan' => 'First name',
        'Nama belakang' => 'Last name',
        'Nomor kartu' => 'Card number',
        'Sarapan, makan siang' => 'Breakfast, lunch',
    ],
    'zh' => [
        'Nama depan' => '名',
        'Nama belakang' => '姓',
        'Nomor kartu' => '卡号',
        'Sarapan, makan siang' => '早餐、午餐',
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
echo "Upserted $count placeholder translation rows.\n";

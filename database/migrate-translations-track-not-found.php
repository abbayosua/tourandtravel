<?php
/**
 * migrate-translations-track-not-found.php
 *
 * track.php dulu menampilkan ulang form tanpa pesan saat kode booking tidak
 * ditemukan. Kini ada error state; seed terjemahan en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-track-not-found.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Kode booking tidak ditemukan. Periksa kembali kode Anda.' => 'Booking code not found. Please check your code.'],
    'zh' => ['Kode booking tidak ditemukan. Periksa kembali kode Anda.' => '未找到预订编号。请检查您的编号。'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count track not-found translation rows.\n";

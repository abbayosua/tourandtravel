<?php
/**
 * migrate-translations-ambiguous-mulai.php
 *
 * Key 'Mulai' dipakai di dua konteks berbeda sehingga terjemahannya salah satu:
 *  - prefix harga pencarian ("Mulai Rp 1.500.000" = From)  -> butuh 'Mulai dari'
 *  - kolom/waktu mulai flash sale & tanggal mulai itinerary -> 'Mulai' = Start
 *
 * 'Mulai' en sebelumnya "From" (salah untuk konteks start; zh 开始 benar).
 * Fix: 'Mulai' en -> 'Start'; tambah key 'Mulai dari' (en From / zh 起) untuk
 * prefix harga (dipakai assets/js/script.js).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-ambiguous-mulai.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Mulai' => 'Start',
        'Mulai dari' => 'From',
    ],
    'zh' => [
        'Mulai' => '开始',
        'Mulai dari' => '起',
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
echo "Upserted $count ambiguous 'Mulai' translation rows.\n";

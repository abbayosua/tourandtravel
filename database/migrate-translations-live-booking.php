<?php
/**
 * migrate-translations-live-booking.php
 *
 * Terjemahan en/zh untuk key baru dari fitur simpan/lanjutkan booking live
 * (hotel NusaTrip, flight, PELNI, KAI) setelah embel-embel "NusaTrip" dihapus.
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-live-booking.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Harga kamar ini tidak tersedia' => 'This room rate is not available',
        'Harga belum tervalidasi. Ulangi dari halaman hotel.' => 'Price not validated yet. Restart from the hotel page.',
        'Lanjutkan Pembayaran' => 'Continue Payment',
    ],
    'zh' => [
        'Harga kamar ini tidak tersedia' => '此房价不可用',
        'Harga belum tervalidasi. Ulangi dari halaman hotel.' => '价格尚未验证。请从酒店页面重新开始。',
        'Lanjutkan Pembayaran' => '继续支付',
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
echo "Upserted $count live-booking translation rows.\n";

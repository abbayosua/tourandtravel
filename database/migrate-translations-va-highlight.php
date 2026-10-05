<?php
/**
 * migrate-translations-va-highlight.php
 *
 * Terjemahan untuk highlight nomor VA di halaman sukses KAI & PELNI
 * (train-booking.php, pelni-booking.php): label kode booking sekunder
 * + label batas bayar.
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-va-highlight.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'pakai untuk lacak / konfirmasi WA' => 'for tracking / WA confirmation',
        'Batas Bayar' => 'Payment Deadline',
        'Kode Invoice' => 'Invoice Code',
        'Pesanan Diterima' => 'Order Received',
        'Konfirmasi pembayaran kapal PELNI ' => 'Confirm PELNI ship payment ',
        'Hubungi kami via WhatsApp untuk mendapatkan nomor pembayaran.' => 'Contact us via WhatsApp to get the payment number.',
    ],
    'zh' => [
        'pakai untuk lacak / konfirmasi WA' => '用于订单追踪 / WhatsApp确认',
        'Batas Bayar' => '付款期限',
        'Kode Invoice' => '发票代码',
        'Pesanan Diterima' => '订单已收到',
        'Konfirmasi pembayaran kapal PELNI ' => '确认PELNI船票付款 ',
        'Hubungi kami via WhatsApp untuk mendapatkan nomor pembayaran.' => '请通过WhatsApp联系我们获取付款号码。',
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
echo "Upserted $count VA highlight translation rows.\n";

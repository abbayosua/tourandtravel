<?php
/**
 * migrate-translations-modify-booking.php
 *
 * Terjemahan en/zh untuk key baru pada fitur "Ubah Booking" tour
 * (edit pemesan, tanggal, peserta + paspor; lock saat pembayaran terkonfirmasi).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-modify-booking.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Baru' => 'New',
        'Data Pemesan' => 'Customer Details',
        'Ganti paspor' => 'Change passport',
        'Hapus peserta' => 'Remove participant',
        'Lihat paspor' => 'View passport',
        'Nama pemesan' => 'Customer name',
        'Nama pemesan wajib diisi' => 'Customer name is required',
        'Pembayaran sudah terkonfirmasi — booking tidak dapat diubah.' => 'Payment is confirmed — the booking can no longer be edited.',
        'Unggah paspor' => 'Upload passport',
    ],
    'zh' => [
        'Baru' => '新',
        'Data Pemesan' => '客户信息',
        'Ganti paspor' => '更换护照',
        'Hapus peserta' => '删除参与者',
        'Lihat paspor' => '查看护照',
        'Nama pemesan' => '客户姓名',
        'Nama pemesan wajib diisi' => '客户姓名必填',
        'Pembayaran sudah terkonfirmasi — booking tidak dapat diubah.' => '付款已确认——预订不可再修改。',
        'Unggah paspor' => '上传护照',
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
echo "Upserted $count modify-booking translation rows.\n";

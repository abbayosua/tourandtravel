<?php
/**
 * migrate-translations-kereta-booking.php
 *
 * Terjemahan untuk fitur booking tiket KAI langsung ke penyedia (klikmbc.biz):
 * halaman train-booking.php + pesan error di trains.php.
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-kereta-booking.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Pesan Tiket KAI' => 'Book KAI Ticket',
        'Pesan Tiket' => 'Book Ticket',
        'Selesaikan pembayaran ke Virtual Account di bawah. Tiket diproses otomatis oleh penyedia.' => 'Complete the payment to the Virtual Account below. The ticket is processed automatically by the provider.',
        'Nomor Virtual Account' => 'Virtual Account Number',
        'Total Bayar' => 'Total Payment',
        'Batas Waktu' => 'Deadline',
        'Salin Nomor VA' => 'Copy VA Number',
        'Tersalin' => 'Copied',
        'Kode booking sudah dibuat. Silakan hubungi kami via WhatsApp untuk mendapatkan nomor pembayaran.' => 'Booking code created. Please contact us via WhatsApp to get the payment number.',
        'Detail Tiket' => 'Ticket Details',
        'Rute' => 'Route',
        'Keberangkatan' => 'Departure',
        'Penumpang' => 'Passenger',
        'Konfirmasi pembayaran tiket KAI ' => 'Confirm KAI ticket payment ',
        'Cari Tiket Lain' => 'Find Another Ticket',
        'Sesi pemesanan tidak ditemukan. Silakan cari jadwal kereta terlebih dahulu.' => 'Booking session not found. Please search for a train schedule first.',
        'Cari Tiket KAI' => 'Find KAI Ticket',
        'Data Penumpang' => 'Passenger Details',
        'Jumlah Penumpang' => 'Number of Passengers',
        'Nama sesuai identitas' => 'Name as on ID',
        'Nomor identitas (KTP/Paspor)' => 'ID number (KTP/Passport)',
        'Catatan (opsional)' => 'Notes (optional)',
        'Pesan Sekarang' => 'Book Now',
        'Pembayaran dilakukan langsung ke Virtual Account penyedia tiket.' => 'Payment is made directly to the ticket provider\'s Virtual Account.',
        'Ringkasan' => 'Summary',
        'Kode' => 'Code',
        'Jam' => 'Time',
        'Harga/orang' => 'Price/person',
        'Nama penumpang %d harus diisi' => 'Passenger %d name is required',
        'Nomor identitas penumpang %d tidak valid' => 'Passenger %d ID number is invalid',
        'Sesi pemesanan kedaluwarsa. Silakan cari jadwal ulang.' => 'Booking session expired. Please search the schedule again.',
        'Gagal mencari jadwal. Silakan coba lagi.' => 'Failed to load schedules. Please try again.',
        'Jadwal tidak lagi tersedia. Silakan cari jadwal ulang.' => 'Schedule is no longer available. Please search the schedule again.',
        'Gagal memuat detail tiket. Silakan coba lagi.' => 'Failed to load ticket details. Please try again.',
        'Pemesanan gagal: ' => 'Booking failed: ',
        'Gagal memuat jadwal kereta. Coba lagi.' => 'Failed to load train schedules. Try again.',
    ],
    'zh' => [
        'Pesan Tiket KAI' => '预订KAI车票',
        'Pesan Tiket' => '预订车票',
        'Selesaikan pembayaran ke Virtual Account di bawah. Tiket diproses otomatis oleh penyedia.' => '请向下方的虚拟账户完成付款。车票将由供应商自动处理。',
        'Nomor Virtual Account' => '虚拟账户号码',
        'Total Bayar' => '付款总额',
        'Batas Waktu' => '截止时间',
        'Salin Nomor VA' => '复制虚拟账户号码',
        'Tersalin' => '已复制',
        'Kode booking sudah dibuat. Silakan hubungi kami via WhatsApp untuk mendapatkan nomor pembayaran.' => '预订代码已生成。请通过WhatsApp联系我们获取付款号码。',
        'Detail Tiket' => '车票详情',
        'Rute' => '路线',
        'Keberangkatan' => '出发',
        'Penumpang' => '乘客',
        'Konfirmasi pembayaran tiket KAI ' => '确认KAI车票付款 ',
        'Cari Tiket Lain' => '查找其他车票',
        'Sesi pemesanan tidak ditemukan. Silakan cari jadwal kereta terlebih dahulu.' => '未找到预订会话。请先搜索火车时刻。',
        'Cari Tiket KAI' => '查找KAI车票',
        'Data Penumpang' => '乘客信息',
        'Jumlah Penumpang' => '乘客人数',
        'Nama sesuai identitas' => '与证件一致的姓名',
        'Nomor identitas (KTP/Paspor)' => '证件号码（KTP/护照）',
        'Catatan (opsional)' => '备注（可选）',
        'Pesan Sekarang' => '立即预订',
        'Pembayaran dilakukan langsung ke Virtual Account penyedia tiket.' => '款项直接支付至车票供应商的虚拟账户。',
        'Ringkasan' => '摘要',
        'Kode' => '代码',
        'Jam' => '时间',
        'Harga/orang' => '每人价格',
        'Nama penumpang %d harus diisi' => '乘客 %d 的姓名必填',
        'Nomor identitas penumpang %d tidak valid' => '乘客 %d 的证件号码无效',
        'Sesi pemesanan kedaluwarsa. Silakan cari jadwal ulang.' => '预订会话已过期。请重新搜索时刻。',
        'Gagal mencari jadwal. Silakan coba lagi.' => '加载时刻失败。请重试。',
        'Jadwal tidak lagi tersedia. Silakan cari jadwal ulang.' => '时刻已不可用。请重新搜索时刻。',
        'Gagal memuat detail tiket. Silakan coba lagi.' => '加载车票详情失败。请重试。',
        'Pemesanan gagal: ' => '预订失败：',
        'Gagal memuat jadwal kereta. Coba lagi.' => '加载火车时刻失败。请重试。',
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
echo "Upserted $count kereta booking translation rows.\n";

<?php
/**
 * migrate-translations-nusatrip-flight.php
 *
 * Terjemahan en/zh untuk key baru dari fitur booking tiket pesawat NusaTrip
 * (flights.php + nusatrip-flight-book.php).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-nusatrip-flight.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Booking Tiket Pesawat' => 'Flight Ticket Booking',
        'Data Kontak' => 'Contact Details',
        'Data kontak wajib diisi.' => 'Contact details are required.',
        'Gagal membuat sesi booking. Silakan coba lagi.' => 'Failed to create booking session. Please try again.',
        'Harga belum tervalidasi. Ulangi dari halaman pencarian.' => 'Price not validated yet. Restart from the search page.',
        'Harga tiket ini tidak tersedia' => 'This ticket price is not available',
        'Modul NusaTrip nonaktif.' => 'NusaTrip module is disabled.',
        'Nama semua penumpang wajib diisi.' => 'All passenger names are required.',
        'NusaTrip tidak terjangkau' => 'NusaTrip is unreachable',
        'Sesi booking kedaluwarsa. Ulangi dari halaman pencarian.' => 'Booking session expired. Restart from the search page.',
        'Sesi penerbangan tidak valid atau kedaluwarsa. Silakan cari ulang.' => 'Flight session is invalid or expired. Please search again.',
        'Silakan cari penerbangan lain.' => 'Please search for another flight.',
    ],
    'zh' => [
        'Booking Tiket Pesawat' => '机票预订',
        'Data Kontak' => '联系人信息',
        'Data kontak wajib diisi.' => '联系人信息为必填项。',
        'Gagal membuat sesi booking. Silakan coba lagi.' => '创建预订会话失败。请重试。',
        'Harga belum tervalidasi. Ulangi dari halaman pencarian.' => '价格尚未验证。请从搜索页面重新开始。',
        'Harga tiket ini tidak tersedia' => '此机票价格不可用',
        'Modul NusaTrip nonaktif.' => 'NusaTrip 模块已停用。',
        'Nama semua penumpang wajib diisi.' => '所有乘客姓名均为必填项。',
        'NusaTrip tidak terjangkau' => '无法连接 NusaTrip',
        'Sesi booking kedaluwarsa. Ulangi dari halaman pencarian.' => '预订会话已过期。请从搜索页面重新开始。',
        'Sesi penerbangan tidak valid atau kedaluwarsa. Silakan cari ulang.' => '航班会话无效或已过期。请重新搜索。',
        'Silakan cari penerbangan lain.' => '请搜索其他航班。',
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
echo "OK: $count entri terjemahan nusatrip flight diproses.\n";

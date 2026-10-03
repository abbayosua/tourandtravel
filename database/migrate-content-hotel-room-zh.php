<?php
/**
 * migrate-content-hotel-room-zh.php
 *
 * hotel_rooms.name_zh kosong untuk semua kamar, sehingga hotel-detail
 * menampilkan nama kamar berbahasa Inggris saat bahasa = zh (hotel-detail.php
 * memilih name_zh -> name_en -> name). Isi name_zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-content-hotel-room-zh.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'Superior Double' => '高级大床房',
    'Deluxe Twin'     => '豪华双床房',
    'Executive Suite' => '行政套房',
];

$stmt = db()->prepare("UPDATE hotel_rooms SET name_zh = COALESCE(NULLIF(name_zh, ''), ?) WHERE name = ?");
$count = 0;
foreach ($dict as $name => $zh) {
    $stmt->execute([$zh, $name]);
    $count += $stmt->rowCount();
}
echo "Updated $count hotel_rooms rows.\n";

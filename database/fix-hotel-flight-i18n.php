<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
$en = [
'Jumlah kamar' => 'Number of rooms',
'Mau menginap di mana?' => 'Where do you want to stay?',
'Rekomendasi' => 'Recommended',
'Tambah tanggal' => 'Add date',
'Tamu & Kamar' => 'Guests & Rooms',
'Total tamu & kamar' => 'Total guests & rooms',
'Urut:' => 'Sort:',
'Usia 13+' => 'Age 13+',
'Usia 2-12' => 'Age 2-12',
'Hotel, vila & resor pilihan — booking instan, harga terbaik.' => 'Handpicked hotels, villas & resorts — instant booking, best rates.',
'Tiket pesawat pilihan — booking instan, harga terbaik.' => 'Handpicked flights — instant booking, best fares.',
'Pesan tiket ferry — booking instan, harga terbaik.' => 'Book ferry tickets — instant booking, best prices.',
'Tiket kereta pilihan — booking instan, harga terbaik.' => 'Best train tickets — instant booking, best prices.',
];
foreach ($en as $k => $v) saveTranslation($k, 'en', $v);
$zh = [
'Find Your' => '寻找您的',
'Stay' => '住宿',
'stays' => '住宿',
'ferries' => '渡轮',
'trains' => '火车',
'flights' => '航班',
'Jumlah kamar' => '房间数量',
'Mau menginap di mana?' => '想住在哪里？',
'Rekomendasi' => '推荐',
'Tambah tanggal' => '添加日期',
'Tamu & Kamar' => '客人和房间',
'Total tamu & kamar' => '客人房间总数',
'Urut:' => '排序：',
'Usia 13+' => '年龄13+',
'Usia 2-12' => '年龄2-12',
'Top stays' => '热门住宿',
'Top stays in' => '热门住宿',
'Loading...' => '加载中...',
'Hotel, vila & resor pilihan — booking instan, harga terbaik.' => '精选酒店、别墅与度假村 — 即时预订，最优价格。',
'Tiket pesawat pilihan — booking instan, harga terbaik.' => '精选航班 — 即时预订，最优价格。',
'Pesan tiket ferry — booking instan, harga terbaik.' => '预订船票 — 即时预订，最优价格。',
'Tiket kereta pilihan — booking instan, harga terbaik.' => '精选火车票 — 即时预订，最优价格。',
];
foreach ($zh as $k => $v) saveTranslation($k, 'zh', $v);
echo "DONE\n";

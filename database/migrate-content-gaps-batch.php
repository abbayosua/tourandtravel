<?php
/**
 * migrate-content-gaps-batch.php
 *
 * Menutup sisa gap konten (per-bahasa) untuk item AKTIF yang masih menampilkan
 * bahasa sumber saat bahasa en/zh:
 *   - attractions 4/5 : name_en + description_en (name/description dirender via tContent)
 *   - tours 63        : route_cities_en/zh (dipakai brosur PDF)
 *   - tours 131       : location_city_zh (kartu tour beranda) + route_cities_zh
 *   - itineraries 101/112 : meals_zh
 *
 * Idempotent.
 * Jalankan: php database/migrate-content-gaps-batch.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$count = 0;

// Attractions
$att = [
    4 => ['Borobudur Temple Sunrise Ticket', 'Watch the sunrise at the world’s largest Buddhist temple.'],
    5 => ['Jakarta Aquarium Entry', 'An ocean aquarium with a tunnel and animal shows.'],
];
$stmt = db()->prepare("UPDATE attractions SET name_en = ?, description_en = ? WHERE id = ?");
foreach ($att as $id => $row) { $stmt->execute([$row[0], $row[1], $id]); $count++; }

// Tours
$tours = [
    63 => ['route_cities_en' => 'SHANGHAI - HANGZHOU - WUXI - SUZHOU - WUZHEN - SHANGHAI',
           'route_cities_zh' => '上海 - 杭州 - 无锡 - 苏州 - 乌镇 - 上海'],
    131 => ['location_city_zh' => '北京',
            'route_cities_zh' => '雅加达 - 北京 - 慕田峪 - 四惠'],
];
foreach ($tours as $id => $fields) {
    $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($fields)));
    $stmt = db()->prepare("UPDATE tours SET $set WHERE id = ?");
    $stmt->execute([...array_values($fields), $id]);
    $count++;
}

// Itineraries meals_zh
$itin = [101 => '不含餐', 112 => '午餐：杭州本地菜'];
$stmt = db()->prepare("UPDATE itineraries SET meals_zh = ? WHERE id = ?");
foreach ($itin as $id => $val) { $stmt->execute([$val, $id]); $count++; }

echo "Updated $count content rows.\n";

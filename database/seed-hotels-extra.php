<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$hotels = [
    ['Batam View Beach Resort', 'Batam', 4, 850000, 'Resort tepi pantai dengan kolam renang, spa, dan pemandangan laut. Cocok untuk liburan keluarga.'],
    ['Nagoya Hill Hotel Batam', 'Batam', 4, 650000, 'Hotel strategis di pusat Nagoya, dekat pusat perbelanjaan, kamar modern & nyaman.'],
    ['Holiday Inn Batam', 'Batam', 5, 1200000, 'Hotel bintang 5 di Nagoya, fasilitas lengkap, kolam renang rooftop.'],
    ['Pacific Palace Hotel', 'Batam', 3, 450000, 'Hotel budget-friendly di pusat Batam, dekat pelabuhan dan pusat oleh-oleh.'],
    ['Best Western Premier Panbil', 'Batam', 5, 1500000, 'Resor mewah dengan lapangan golf, kolam renang olympic, dan 6 restoran.'],
    ['Hotel Borobudur Jakarta', 'Jakarta', 5, 2000000, 'Hotel bersejarah di Lapangan Banteng, kolam renang terbesar di Jakarta, taman luas.'],
    ['ARTOTEL Gelora Senayan', 'Jakarta', 4, 950000, 'Hotel desain artistik di kawasan Senayan, dekat GBK dan fX Sudirman.'],
    ['Aryaduta Menteng', 'Jakarta', 4, 1400000, 'Hotel di pusat Menteng, pemandangan kota modern, kolam renang rooftop.'],
    ['WHITEHOUSE Boutique Hotel', 'Jakarta', 3, 550000, 'Boutique hotel di kawasan SCBD, interior elegan, harga terjangkau.'],
    ['Pullman Jakarta Central Park', 'Jakarta', 5, 1800000, 'Hotel bintang 5 terintegrasi dengan Central Park Mall, akses langsung ke mal.'],
];

foreach ($hotels as $h) {
    $slug = buatSlug($h[0]);
    $s = db()->prepare("SELECT COUNT(*) FROM hotels WHERE slug = ?");
    $s->execute([$slug]);
    if ($s->fetchColumn() == 0) {
        $st = db()->prepare("INSERT INTO hotels (name, slug, city, star_rating, price_per_night, description) VALUES (?, ?, ?, ?, ?, ?)");
        $st->execute([$h[0], $slug, $h[1], $h[2], $h[3], $h[4]]);
        echo "OK: {$h[0]}\n";
    } else {
        echo "SKIP: {$h[0]}\n";
    }
}

<?php
/**
 * migrate-content-hotel-en.php
 *
 * 15 hotel aktif (id 6-20) tidak punya description_en, sehingga halaman
 * hotel-detail menampilkan deskripsi Indonesia saat bahasa = EN. name_en juga
 * dikosongkan (name sudah Inggris). Terjemahan zh sudah ada.
 *
 * Idempotent.
 * Jalankan: php database/migrate-content-hotel-en.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$en = [
    6 => 'A resort in the heart of Ubud’s forest with private villas, an infinity pool and yoga.',
    7 => 'A strategic 4-star hotel near Malioboro with full facilities.',
    8 => 'A modern hotel in Batam’s business district with a pool and gym.',
    9 => 'A budget-friendly hotel in central Bandung with colourful design and free breakfast.',
    10 => 'A business hotel in the Golden Triangle area, close to malls and offices.',
    11 => 'A beachfront resort with a pool, spa and sea views. Ideal for family holidays.',
    12 => 'A strategic hotel in central Nagoya, near shopping centres, with modern and comfortable rooms.',
    13 => 'A 5-star hotel in Nagoya with full facilities and a rooftop pool.',
    14 => 'A budget-friendly hotel in central Batam, near the port and souvenir centres.',
    15 => 'A luxury resort with a golf course, an Olympic pool and 6 restaurants.',
    16 => 'A historic hotel at Lapangan Banteng with Jakarta’s largest pool and extensive gardens.',
    17 => 'An artistic design hotel in the Senayan area, near GBK and fX Sudirman.',
    18 => 'A hotel in central Menteng with modern city views and a rooftop pool.',
    19 => 'A boutique hotel in the SCBD area with elegant interiors at an affordable price.',
    20 => 'A 5-star hotel integrated with Central Park Mall, with direct mall access.',
];

$upd = db()->prepare("UPDATE hotels SET description_en = ?, name_en = COALESCE(NULLIF(name_en, ''), name) WHERE id = ?");
$count = 0;
foreach ($en as $id => $desc) {
    $upd->execute([$desc, $id]);
    $count++;
}
echo "Updated $count hotel rows with en content.\n";

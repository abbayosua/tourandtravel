<?php
/**
 * Seed data: Hotels, Flights, Ferries, Rental Cars
 * Run: php database/seed-layanan.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== HOTELS ===\n";
$hotels = [
    ['Grand Hyatt Bali', 'Bali', 5, 2500000, 'Resor mewah di kawasan Nusa Dua dengan pemandangan pantai, kolam renang infinity, spa kelas dunia.'],
    ['The Ritz-Carlton Jakarta', 'Jakarta', 5, 3200000, 'Hotel bintang 5 di jantung ibu kota, pemandangan cakrawala kota, restoran fine dining.'],
    ['Hotel Indonesia Kempinski', 'Jakarta', 5, 2800000, 'Ikonik di Bundaran HI, kamar mewah, akses langsung ke Grand Indonesia.'],
    ['Mulia Resort Nusa Dua', 'Bali', 5, 3500000, 'Resor all-inclusive dengan pantai pribadi, lapangan golf, 7 restoran.'],
    ['Shangri-La Hotel Surabaya', 'Surabaya', 5, 1800000, 'Hotel bintang 5 di pusat kota, kolam renang rooftop, pemandangan laut.'],
    ['Four Seasons Resort Ubud', 'Bali', 5, 4500000, 'Resor di tengah hutan Ubud dengan vila pribadi, infinity pool, yoga.'],
    ['Hotel Santika Premiere Yogyakarta', 'Yogyakarta', 4, 850000, 'Hotel bintang 4 strategis dekat Malioboro, fasilitas lengkap.'],
    ['Aston Hotel Batam', 'Batam', 4, 650000, 'Hotel modern di pusat bisnis Batam, kolam renang, gym.'],
    ['Ibis Styles Bandung', 'Bandung', 3, 450000, 'Hotel budget-friendly di pusat Bandung, desain colorful, sarapan gratis.'],
    ['Hilton Garden Inn Jakarta', 'Jakarta', 4, 1200000, 'Hotel bisnis di kawasan Segitiga Emas, dekat mal dan perkantoran.'],
];
foreach ($hotels as $h) {
    $slug = buatSlug($h[0]);
    $s = db()->prepare("SELECT COUNT(*) FROM hotels WHERE slug = ?");
    $s->execute([$slug]);
    if ($s->fetchColumn() == 0) {
        $st = db()->prepare("INSERT INTO hotels (name, slug, city, star_rating, price_per_night, description, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $st->execute([$h[0], $slug, $h[1], $h[2], $h[3], $h[4]]);
        echo "  OK: {$h[0]}\n";
    } else echo "  SKIP: {$h[0]}\n";
}

echo "\n=== FLIGHTS ===\n";
$flights = [
    ['Garuda Indonesia', 'GA-412', 'Jakarta (CGK)', 'Denpasar (DPS)', '07:00', '10:00', '3 jam', 1500000, 'economy'],
    ['Garuda Indonesia', 'GA-413', 'Denpasar (DPS)', 'Jakarta (CGK)', '11:00', '14:00', '3 jam', 1500000, 'economy'],
    ['Garuda Indonesia', 'GA-422', 'Jakarta (CGK)', 'Denpasar (DPS)', '07:00', '10:00', '3 jam', 3500000, 'business'],
    ['Lion Air', 'JT-521', 'Jakarta (CGK)', 'Yogyakarta (JOG)', '08:30', '09:45', '1 jam 15 mnt', 650000, 'economy'],
    ['Lion Air', 'JT-522', 'Yogyakarta (JOG)', 'Jakarta (CGK)', '10:30', '11:45', '1 jam 15 mnt', 650000, 'economy'],
    ['Batik Air', 'ID-6210', 'Jakarta (CGK)', 'Surabaya (SUB)', '09:00', '10:30', '1 jam 30 mnt', 850000, 'economy'],
    ['AirAsia', 'QZ-7521', 'Jakarta (CGK)', 'Singapore (SIN)', '06:00', '09:00', '3 jam', 1200000, 'economy'],
    ['AirAsia', 'QZ-7522', 'Singapore (SIN)', 'Jakarta (CGK)', '10:00', '13:00', '3 jam', 1200000, 'economy'],
    ['Cathay Pacific', 'CX-786', 'Jakarta (CGK)', 'Hong Kong (HKG)', '01:00', '07:00', '6 jam', 4800000, 'economy'],
    ['Japan Airlines', 'JL-29', 'Jakarta (CGK)', 'Tokyo (NRT)', '06:00', '16:00', '10 jam', 8500000, 'economy'],
    ['Singapore Airlines', 'SQ-961', 'Jakarta (CGK)', 'Singapore (SIN)', '14:00', '17:00', '3 jam', 2200000, 'economy'],
    ['Korean Air', 'KE-628', 'Jakarta (CGK)', 'Seoul (ICN)', '23:00', '07:00', '8 jam', 5200000, 'economy'],
];
foreach ($flights as $f) {
    $fn = $f[1];
    $s = db()->prepare("SELECT COUNT(*) FROM flights WHERE flight_number = ?");
    $s->execute([$fn]);
    if ($s->fetchColumn() == 0) {
        $st = db()->prepare("INSERT INTO flights (airline, flight_number, from_city, to_city, departure_time, arrival_time, duration, price, class, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $st->execute($f);
        echo "  OK: {$f[0]}\n";
    } else echo "  SKIP: {$f[0]}\n";
}

echo "\n=== FERRIES ===\n";
$ferries = [
    ['ASDP Indonesia Ferry', 'Merak', 'Bakauheni', '06:00', '08:00', 350000, 'KMP Portlink III'],
    ['ASDP Indonesia Ferry', 'Merak', 'Bakauheni', '10:00', '12:00', 350000, 'KMP Royal'],
    ['ASDP Indonesia Ferry', 'Merak', 'Bakauheni', '14:00', '16:00', 350000, 'KMP Portlink III'],
    ['Batam Fast Ferry', 'Batam (Sekupang)', 'Singapore (HarbourFront)', '08:00', '09:00', 450000, 'SSB Sea'],
    ['Batam Fast Ferry', 'Batam (Sekupang)', 'Singapore (HarbourFront)', '14:00', '15:00', 450000, 'SSB Sea'],
    ['Indomal Express', 'Batam (Batu Ampar)', 'Johor (Malaysia)', '07:00', '08:30', 380000, 'Indomal III'],
    ['Pelni', 'Tanjung Priok', 'Tanjung Pandan', '20:00', '06:00', 280000, 'KM Kelud'],
    ['Pelni', 'Surabaya', 'Makassar', '18:00', '06:00', 350000, 'KM Tidar'],
];
foreach ($ferries as $f) {
    $s = db()->prepare("SELECT COUNT(*) FROM ferries WHERE company = ? AND route_from = ? AND route_to = ? AND departure_time = ?");
    $s->execute([$f[0], $f[1], $f[2], $f[3]]);
    if ($s->fetchColumn() == 0) {
        $st = db()->prepare("INSERT INTO ferries (company, route_from, route_to, departure_time, arrival_time, price, vessel_name, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $st->execute($f);
        echo "  OK: {$f[0]} {$f[1]}–{$f[2]}\n";
    } else echo "  SKIP: {$f[0]} {$f[1]}–{$f[2]}\n";
}

echo "\n=== RENTAL CARS ===\n";
$cars = [
    ['Toyota Avanza', 'MVP', 'Jakarta', 350000, 'automatic', 7],
    ['Toyota Innova', 'MVP', 'Jakarta', 500000, 'automatic', 7],
    ['Honda Brio', 'Hatchback', 'Jakarta', 250000, 'automatic', 5],
    ['Daihatsu Xenia', 'MVP', 'Bali', 350000, 'automatic', 7],
    ['Toyota Fortuner', 'SUV', 'Bali', 800000, 'automatic', 7],
    ['Suzuki Ertiga', 'MVP', 'Yogyakarta', 300000, 'automatic', 7],
    ['Mitsubishi Pajero', 'SUV', 'Bali', 950000, 'automatic', 7],
    ['Honda Civic', 'Sedan', 'Jakarta', 450000, 'automatic', 5],
    ['Toyota Agya', 'Hatchback', 'Bandung', 200000, 'manual', 5],
    ['Daihatsu Terios', 'SUV', 'Yogyakarta', 400000, 'automatic', 7],
];
foreach ($cars as $c) {
    $slug = buatSlug($c[0] . '-' . strtolower($c[3]));
    $s = db()->prepare("SELECT COUNT(*) FROM rental_cars WHERE slug = ?");
    $s->execute([$slug]);
    if ($s->fetchColumn() == 0) {
        $st = db()->prepare("INSERT INTO rental_cars (name, slug, car_type, city, price_per_day, transmission, passenger_capacity, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $st->execute([$c[0], $slug, $c[1], $c[2], $c[3], $c[4], $c[5]]);
        echo "  OK: {$c[0]} ({$c[3]})\n";
    } else echo "  SKIP: {$c[0]}\n";
}

echo "\nSelesai!\n";

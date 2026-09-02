<?php
/**
 * Seeder: Klook UI features + ALTER TABLE (MySQL 9 compatible)
 * Run: php database/seed-klook-ui.php
 *
 * - Adds new columns to existing tables (MySQL lacks ADD COLUMN IF NOT EXISTS)
 * - Seeds hero_slides, attractions, transfers, promo_codes
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * Add column if it does not exist (MySQL-safe)
 */
function klook_add_column($table, $column, $definition) {
    try {
        // Column names are hardcoded constants (not user input)
        $stmt = db()->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->fetch()) {
            echo "  SKIP: $table.$column (exists)\n";
            return;
        }
        db()->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "  OK: $table.$column added\n";
    } catch (Throwable $e) {
        echo "  ERROR: $table.$column -> " . $e->getMessage() . "\n";
    }
}

echo "=== ALTER TABLE (new columns) ===\n";
klook_add_column('tours', 'duration_days', 'INT DEFAULT NULL AFTER max_participants');
klook_add_column('tours', 'duration_nights', 'INT DEFAULT NULL AFTER duration_days');
klook_add_column('tours', 'location_city', 'VARCHAR(100) DEFAULT NULL AFTER category');
klook_add_column('tours', 'instant_confirmation', "TINYINT(1) NOT NULL DEFAULT 1");
klook_add_column('tours', 'free_cancellation', "TINYINT(1) NOT NULL DEFAULT 0");
klook_add_column('tours', 'best_seller', "TINYINT(1) NOT NULL DEFAULT 0");

klook_add_column('hotels', 'lat', 'DECIMAL(10,7) DEFAULT NULL');
klook_add_column('hotels', 'lng', 'DECIMAL(10,7) DEFAULT NULL');
klook_add_column('hotels', 'amenities', 'VARCHAR(500) DEFAULT NULL');
klook_add_column('hotels', 'instant_confirmation', "TINYINT(1) NOT NULL DEFAULT 1");
klook_add_column('hotels', 'free_cancellation', "TINYINT(1) NOT NULL DEFAULT 0");
klook_add_column('hotels', 'best_seller', "TINYINT(1) NOT NULL DEFAULT 0");

klook_add_column('flights', 'baggage_allowance', 'VARCHAR(50) DEFAULT NULL');
klook_add_column('flights', 'refundable', "TINYINT(1) NOT NULL DEFAULT 0");

klook_add_column('ferries', 'amenities', 'VARCHAR(500) DEFAULT NULL');

klook_add_column('rental_cars', 'fuel_type', 'VARCHAR(20) DEFAULT NULL');
klook_add_column('rental_cars', 'year', 'INT DEFAULT NULL');
klook_add_column('rental_cars', 'with_driver', "TINYINT(1) NOT NULL DEFAULT 0");

klook_add_column('users', 'referral_code', 'VARCHAR(20) DEFAULT NULL UNIQUE');
klook_add_column('users', 'referred_by', 'INT DEFAULT NULL');

echo "\n=== HERO SLIDES ===\n";
$heroSlides = [
    ['image_url' => 'https://images.unsplash.com/photo-1488085061387-422e29b40080?w=1920&q=80', 'title' => 'Your World of Joy', 'subtitle' => 'Temukan paket tour impian Anda', 'cta_text' => 'Jelajahi', 'cta_link' => 'tours.php', 'sort_order' => 1],
    ['image_url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80', 'title' => 'Liburan Impian', 'subtitle' => 'Pantai, gunung, kota — semua di sini', 'cta_text' => 'Lihat Tour', 'cta_link' => 'tours.php', 'sort_order' => 2],
    ['image_url' => 'https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=1920&q=80', 'title' => 'Petualangan Baru', 'subtitle' => 'Harga terbaik untuk setiap destinasi', 'cta_text' => 'Mulai', 'cta_link' => 'tours.php', 'sort_order' => 3],
];
foreach ($heroSlides as $s) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM hero_slides WHERE image_url = ?");
    $stmt->execute([$s['image_url']]);
    if ($stmt->fetchColumn() == 0) {
        $ins = db()->prepare("INSERT INTO hero_slides (image_url, title, subtitle, cta_text, cta_link, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$s['image_url'], $s['title'], $s['subtitle'], $s['cta_text'], $s['cta_link'], $s['sort_order']]);
        echo "  OK: hero slide {$s['sort_order']}\n";
    } else {
        echo "  SKIP: hero slide {$s['sort_order']}\n";
    }
}

echo "\n=== ATTRACTIONS ===\n";
$attractions = [
    ['name' => 'Tiket Masuk Taman Mini Indonesia Indah', 'city' => 'Jakarta', 'category' => 'Taman & Hiburan', 'price' => 25000, 'duration' => '1 hari', 'best_seller' => 1, 'desc' => 'Jelajahi budaya Indonesia di TMII dengan tiket masuk harian.'],
    ['name' => 'Monas Observation Deck Ticket', 'city' => 'Jakarta', 'category' => 'Landmark', 'price' => 15000, 'duration' => '1-2 jam', 'best_seller' => 0, 'desc' => 'Nikmati pemandangan Jakarta dari puncak Monumen Nasional.'],
    ['name' => 'Waterbom Bali Day Pass', 'city' => 'Bali', 'category' => 'Taman Air', 'price' => 350000, 'duration' => '1 hari', 'best_seller' => 1, 'desc' => 'Taman air terbaik di Asia dengan berbagai wahana seru.'],
    ['name' => 'Candi Borobudur Sunrise Ticket', 'city' => 'Yogyakarta', 'category' => 'Landmark', 'price' => 455000, 'duration' => '3 jam', 'best_seller' => 0, 'desc' => 'Saksikan matahari terbit di candi Buddha terbesar di dunia.'],
    ['name' => 'Jakarta Aquarium Entry', 'city' => 'Jakarta', 'category' => 'Aquarium', 'price' => 120000, 'duration' => '2-3 jam', 'best_seller' => 0, 'desc' => 'Akuarium laut dengan terowongan dan pertunjukan satwa.'],
];
foreach ($attractions as $a) {
    $slug = buatSlug($a['name']);
    $stmt = db()->prepare("SELECT COUNT(*) FROM attractions WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() == 0) {
        $ins = db()->prepare("INSERT INTO attractions (name, slug, city, category, description, price, duration, best_seller) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$a['name'], $slug, $a['city'], $a['category'], $a['desc'], $a['price'], $a['duration'], $a['best_seller']]);
        echo "  OK: {$a['name']}\n";
    } else {
        echo "  SKIP: {$a['name']}\n";
    }
}

echo "\n=== TRANSFERS ===\n";
$transfers = [
    ['name' => 'Bandara Soekarno-Hatta ke Kota Jakarta', 'from_city' => 'Soekarno-Hatta (CGK)', 'to_city' => 'Jakarta Pusat', 'from_type' => 'airport', 'to_type' => 'city', 'price' => 150000, 'vehicle_type' => 'Sedan', 'max_passengers' => 3, 'desc' => 'Transfer privat dari bandara ke pusat kota Jakarta.'],
    ['name' => 'Bandara Ngurah Rai ke Kuta & Seminyak', 'from_city' => 'Ngurah Rai (DPS)', 'to_city' => 'Kuta/Seminyak', 'from_type' => 'airport', 'to_type' => 'hotel', 'price' => 120000, 'vehicle_type' => 'MVP', 'max_passengers' => 6, 'desc' => 'Jemput bandara Bali menuju area Kuta atau Seminyak.'],
    ['name' => 'Bandara YIA ke Malioboro', 'from_city' => 'YIA (Yogyakarta)', 'to_city' => 'Malioboro', 'from_type' => 'airport', 'to_type' => 'hotel', 'price' => 180000, 'vehicle_type' => 'Sedan', 'max_passengers' => 3, 'desc' => 'Transfer dari Bandara Yogyakarta ke area Malioboro.'],
    ['name' => 'Pelabuhan Merak ke Bandara Soekarno-Hatta', 'from_city' => 'Pelabuhan Merak', 'to_city' => 'Soekarno-Hatta (CGK)', 'from_type' => 'port', 'to_type' => 'airport', 'price' => 250000, 'vehicle_type' => 'MVP', 'max_passengers' => 6, 'desc' => 'Antar jemput dari Pelabuhan Merak menuju bandara.'],
    ['name' => 'Bandara Juanda ke Pusat Kota Surabaya', 'from_city' => 'Juanda (SUB)', 'to_city' => 'Surabaya Pusat', 'from_type' => 'airport', 'to_type' => 'city', 'price' => 100000, 'vehicle_type' => 'Sedan', 'max_passengers' => 3, 'desc' => 'Transfer dari Bandara Juanda ke pusat kota Surabaya.'],
];
foreach ($transfers as $t) {
    $slug = buatSlug($t['name']);
    $stmt = db()->prepare("SELECT COUNT(*) FROM transfers WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() == 0) {
        $ins = db()->prepare("INSERT INTO transfers (name, slug, from_city, to_city, from_type, to_type, price, vehicle_type, max_passengers, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$t['name'], $slug, $t['from_city'], $t['to_city'], $t['from_type'], $t['to_type'], $t['price'], $t['vehicle_type'], $t['max_passengers'], $t['desc']]);
        echo "  OK: {$t['name']}\n";
    } else {
        echo "  SKIP: {$t['name']}\n";
    }
}

echo "\n=== PROMO CODES ===\n";
$promos = [
    ['code' => 'HEMAT10', 'desc' => 'Diskon 10% untuk semua paket tour', 'discount_type' => 'percentage', 'discount_value' => 10, 'min_purchase' => 500000, 'max_discount' => 500000, 'valid_from' => date('Y-m-d'), 'valid_until' => date('Y-m-d', strtotime('+1 year'))],
    ['code' => 'FLAT50', 'desc' => 'Potongan Rp50.000 untuk booking hotel', 'discount_type' => 'fixed', 'discount_value' => 50000, 'min_purchase' => 200000, 'max_discount' => null, 'valid_from' => date('Y-m-d'), 'valid_until' => date('Y-m-d', strtotime('+6 months'))],
    ['code' => 'WELCOME', 'desc' => 'Diskon 15% untuk member baru', 'discount_type' => 'percentage', 'discount_value' => 15, 'min_purchase' => 100000, 'max_discount' => 200000, 'valid_from' => date('Y-m-d'), 'valid_until' => date('Y-m-d', strtotime('+3 months'))],
];
foreach ($promos as $p) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM promo_codes WHERE code = ?");
    $stmt->execute([$p['code']]);
    if ($stmt->fetchColumn() == 0) {
        $ins = db()->prepare("INSERT INTO promo_codes (code, description, discount_type, discount_value, min_purchase, max_discount, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$p['code'], $p['desc'], $p['discount_type'], $p['discount_value'], $p['min_purchase'], $p['max_discount'], $p['valid_from'], $p['valid_until']]);
        echo "  OK: {$p['code']}\n";
    } else {
        echo "  SKIP: {$p['code']}\n";
    }
}

echo "\n=== TRAINS ===\n";
$trains = [
    ['name' => 'Argo Bromo Anggrek', 'route_from' => 'Gambir', 'route_to' => 'Surabaya Pasar Turi', 'departure_time' => '08:00:00', 'arrival_time' => '15:30:00', 'duration' => '7j 30m', 'price' => 550000, 'class' => 'Eksekutif'],
    ['name' => 'Argo Parahyangan', 'route_from' => 'Gambir', 'route_to' => 'Bandung', 'departure_time' => '06:10:00', 'arrival_time' => '09:15:00', 'duration' => '3j 05m', 'price' => 250000, 'class' => 'Eksekutif'],
    ['name' => 'Taksaka', 'route_from' => 'Gambir', 'route_to' => 'Yogyakarta', 'departure_time' => '20:15:00', 'arrival_time' => '01:20:00', 'duration' => '5j 05m', 'price' => 380000, 'class' => 'Eksekutif'],
    ['name' => 'Sancaka', 'route_from' => 'Yogyakarta', 'route_to' => 'Surabaya Gubeng', 'departure_time' => '09:00:00', 'arrival_time' => '13:30:00', 'duration' => '4j 30m', 'price' => 280000, 'class' => 'Bisnis'],
    ['name' => 'Argo Wilis', 'route_from' => 'Bandung', 'route_to' => 'Surabaya Gubeng', 'departure_time' => '07:20:00', 'arrival_time' => '16:45:00', 'duration' => '9j 25m', 'price' => 320000, 'class' => 'Eksekutif'],
];
foreach ($trains as $tr) {
    $slug = buatSlug($tr['name']);
    $stmt = db()->prepare("SELECT COUNT(*) FROM trains WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() == 0) {
        $ins = db()->prepare("INSERT INTO trains (name, slug, route_from, route_to, departure_time, arrival_time, duration, price, class) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$tr['name'], $slug, $tr['route_from'], $tr['route_to'], $tr['departure_time'], $tr['arrival_time'], $tr['duration'], $tr['price'], $tr['class']]);
        echo "  OK: {$tr['name']}\n";
    } else {
        echo "  SKIP: {$tr['name']}\n";
    }
}

echo "\n=== CONNECTIVITY PRODUCTS (eSIM/SIM/WiFi) ===\n";
$connProducts = [
    ['name' => 'eSIM Indonesia 5GB', 'type' => 'esim', 'country' => 'Indonesia', 'coverage' => 'Nasional', 'data_quota' => '5GB', 'duration_days' => 7, 'price' => 75000, 'desc' => 'eSIM instan untuk Indonesia dengan kuota 5GB selama 7 hari.'],
    ['name' => 'eSIM Bali 10GB', 'type' => 'esim', 'country' => 'Indonesia', 'coverage' => 'Bali', 'data_quota' => '10GB', 'duration_days' => 14, 'price' => 125000, 'desc' => 'eSIM kuota besar khusus area Bali, aktif 14 hari.'],
    ['name' => 'SIM Card Traveloka 5GB', 'type' => 'sim', 'country' => 'Indonesia', 'coverage' => 'Nasional', 'data_quota' => '5GB', 'duration_days' => 10, 'price' => 50000, 'desc' => 'SIM fisik dengan kuota 5GB, pengiriman ke seluruh Indonesia.'],
    ['name' => 'Pocket WiFi 4G Unlimited', 'type' => 'wifi', 'country' => 'Indonesia', 'coverage' => 'Nasional', 'data_quota' => 'Unlimited', 'duration_days' => 30, 'price' => 200000, 'desc' => 'Pocket WiFi unlimited untuk seluruh Indonesia.'],
    ['name' => 'eSIM Asia Tenggara 20GB', 'type' => 'esim', 'country' => 'Asia Tenggara', 'coverage' => 'Regional', 'data_quota' => '20GB', 'duration_days' => 30, 'price' => 350000, 'desc' => 'eSIM regional untuk 10 negara Asia Tenggara.'],
];
foreach ($connProducts as $cp) {
    $slug = buatSlug($cp['name']);
    $stmt = db()->prepare("SELECT COUNT(*) FROM connectivity_products WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() == 0) {
        $ins = db()->prepare("INSERT INTO connectivity_products (name, slug, type, country, coverage, data_quota, duration_days, price, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$cp['name'], $slug, $cp['type'], $cp['country'], $cp['coverage'], $cp['data_quota'], $cp['duration_days'], $cp['price'], $cp['desc']]);
        echo "  OK: {$cp['name']}\n";
    } else {
        echo "  SKIP: {$cp['name']}\n";
    }
}

echo "\n=== FAQ ===\n";
$faqCategories = [
    ['name' => 'Umum', 'sort_order' => 1],
    ['name' => 'Pembayaran', 'sort_order' => 2],
    ['name' => 'Pembatalan & Refund', 'sort_order' => 3],
    ['name' => 'KlookCash', 'sort_order' => 4],
];
$catIds = [];
foreach ($faqCategories as $fc) {
    $stmt = db()->prepare("SELECT id FROM faq_categories WHERE name = ?");
    $stmt->execute([$fc['name']]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        $ins = db()->prepare("INSERT INTO faq_categories (name, sort_order) VALUES (?, ?)");
        $ins->execute([$fc['name'], $fc['sort_order']]);
        $id = (int)db()->lastInsertId();
        echo "  OK: kategori {$fc['name']}\n";
    } else {
        echo "  SKIP: kategori {$fc['name']}\n";
    }
    $catIds[$fc['name']] = (int)$id;
}
$faqItems = [
    ['cat' => 'Umum', 'q' => 'Bagaimana cara memesan tour?', 'a' => 'Pilih tour yang diinginkan, tentukan tanggal dan jumlah peserta, lalu isi form pemesanan. Setelah pembayaran, Anda akan menerima konfirmasi via email dan WhatsApp.'],
    ['cat' => 'Umum', 'q' => 'Apakah perlu akun untuk booking?', 'a' => 'Tidak wajib. Anda dapat melakukan pemesanan sebagai tamu, namun kami sarankan membuat akun agar dapat melacak booking dan mendapatkan KlookCash.'],
    ['cat' => 'Pembayaran', 'q' => 'Metode pembayaran apa saja yang tersedia?', 'a' => 'Kami menerima transfer bank, virtual account, QRIS, dan pembayaran langsung di lokasi untuk beberapa produk.'],
    ['cat' => 'Pembayaran', 'q' => 'Apakah bisa membayar di tempat?', 'a' => 'Untuk sebagian produk dengan label "Bayar di Lokasi", Anda dapat membayar langsung di venue.'],
    ['cat' => 'Pembatalan & Refund', 'q' => 'Bagaimana kebijakan pembatalan?', 'a' => 'Produk dengan label "Batal Gratis" dapat dibatalkan tanpa biaya sebelum H-1. Produk lain mengikuti kebijakan penyedia.'],
    ['cat' => 'Pembatalan & Refund', 'q' => 'Berapa lama proses refund?', 'a' => 'Refund diproses dalam 3-7 hari kerja setelah pembatalan disetujui, dikembalikan ke metode pembayaran awal.'],
    ['cat' => 'KlookCash', 'q' => 'Apa itu KlookCash?', 'a' => 'KlookCash adalah saldo reward yang Anda dapatkan sebesar 5% dari setiap booking. Dapat digunakan sebagai pengurang pembayaran transaksi berikutnya.'],
    ['cat' => 'KlookCash', 'q' => 'Bagaimana cara menggunakan KlookCash?', 'a' => 'Centang opsi "Gunakan KlookCash" pada form pemesanan dan saldo Anda otomatis mengurangi total pembayaran.'],
];
foreach ($faqItems as $fi) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM faq_items WHERE question = ?");
    $stmt->execute([$fi['q']]);
    if ($stmt->fetchColumn() == 0) {
        $ins = db()->prepare("INSERT INTO faq_items (category_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
        $ins->execute([$catIds[$fi['cat']], $fi['q'], $fi['a'], 0]);
        echo "  OK: {$fi['q']}\n";
    } else {
        echo "  SKIP: {$fi['q']}\n";
    }
}

echo "\n=== UPDATE kolom baru di data existing ===\n";
// Set best_seller untuk tour populer (rating tinggi)
$stmt = db()->prepare("UPDATE tours SET best_seller = 1 WHERE rating >= 4.8 AND best_seller = 0 LIMIT 3");
$stmt->execute();
echo "  Tours best_seller: " . $stmt->rowCount() . " updated\n";
// Set location_city dari description / kategori sebagai fallback
$stmt = db()->prepare("UPDATE tours SET location_city = 'Bali' WHERE title LIKE '%Bali%' AND location_city IS NULL");
$stmt->execute();
$stmt = db()->prepare("UPDATE tours SET location_city = 'Yogyakarta' WHERE title LIKE '%Yogyakarta%' AND location_city IS NULL");
$stmt->execute();
$stmt = db()->prepare("UPDATE tours SET location_city = 'Jakarta' WHERE title LIKE '%Jakarta%' AND location_city IS NULL");
$stmt->execute();
$stmt = db()->prepare("UPDATE tours SET location_city = 'Lombok' WHERE title LIKE '%Lombok%' AND location_city IS NULL");
$stmt->execute();
echo "  Tours location_city updated\n";

echo "\nSelesai!\n";
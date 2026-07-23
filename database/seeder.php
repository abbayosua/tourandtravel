<?php
/**
 * Seed data: Import tour packages from user's list
 * Run: php database/seeder.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$tours = [
    // CHINA
    ['title' => 'Magical Chongqing 8D7N', 'category' => 'China', 'price' => 12500000, 'desc' => 'Jelajahi kota pegunungan Chongqing yang unik dengan arsitektur vertikalnya, makanan pedas khas, dan pemandangan sungai Yangtze yang menakjubkan. Termasuk akomodasi hotel bintang 4 dan transportasi.', 'days' => 8],
    ['title' => 'Chongqing 3D Depth 8D7N', 'category' => 'China', 'price' => 11800000, 'desc' => 'Eksplorasi mendalam kota Chongqing dengan kunjungan ke situs bersejarah, kuil kuno, dan mencicipi kuliner hotpot autentik.', 'days' => 8],
    ['title' => 'Guizhou Discovery 8D7N', 'category' => 'China', 'price' => 13200000, 'desc' => 'Temukan keindahan provinsi Guizhou dengan air terjun Huangguoshu yang megah, desa etnis Miao, dan pemandangan karst yang menakjubkan.', 'days' => 8],
    ['title' => 'Guizhou Colorful 8D7N', 'category' => 'China', 'price' => 12800000, 'desc' => 'Nikmati pesona Guizhou yang penuh warna, budaya etnis minoritas yang kaya, dan lanskap pegunungan yang hijau.', 'days' => 8],
    ['title' => 'Guizhou–Chongqing 8D7N', 'category' => 'China', 'price' => 13500000, 'desc' => 'Kombinasi sempurna dua destinasi: Guizhou dengan air terjun dan desa etnis, lalu Chongqing dengan kota vertikalnya yang futuristik.', 'days' => 8],
    ['title' => 'Zhangjiajie 8D7N', 'category' => 'China', 'price' => 14500000, 'desc' => 'Saksikan keajaiban alam Zhangjiajie yang menginspirasi film Avatar. Pilar-pilar batu pasir raksasa, jembatan kaca, dan pemandangan surreal.', 'days' => 8],
    ['title' => 'Fairy Zhangjiajie Xiangxi 8D7N', 'category' => 'China', 'price' => 15000000, 'desc' => 'Jelajahi Zhangjiajie dan Xiangxi, negeri dongeng dengan pegunungan karst, desa tua, dan budaya Tujia yang autentik.', 'days' => 8],
    ['title' => 'Beijing 8D7N', 'category' => 'China', 'price' => 13800000, 'desc' => 'Kunjungi ibu kota China: Tembok Besar, Kota Terlarang, Lapangan Tiananmen, dan Kuil Surga. Pengalaman budaya dan sejarah yang tak terlupakan.', 'days' => 8],
    ['title' => 'Beijing + Universal Studios 8D7N', 'category' => 'China', 'price' => 15500000, 'desc' => 'Kombinasi wisata sejarah Beijing dan keseruan Universal Studios Beijing. Nikmati atraksi dunia di taman hiburan terbesar se-Asia.', 'days' => 8],
    ['title' => 'Shanghai + Disney 8D7N', 'category' => 'China', 'price' => 15800000, 'desc' => 'Nikmati gemerlap Shanghai dan keajaiban Disneyland Shanghai. Dari Bund hingga Disney Castle, liburan impian untuk keluarga.', 'days' => 8],
    ['title' => 'Shanghai–Hangzhou 8D7N', 'category' => 'China', 'price' => 14200000, 'desc' => 'Jelajahi Shanghai modern dan Hangzhou yang romantis dengan Danau Barat yang terkenal. Perpaduan sempurna kota dan alam.', 'days' => 8],
    ['title' => 'Shanghai–Suzhou–Hangzhou 8D7N', 'category' => 'China', 'price' => 14800000, 'desc' => 'Segitiga emas China Timur: Shanghai metropolitan, Suzhou dengan taman klasiknya, dan Hangzhou dengan Danau Baratnya.', 'days' => 8],
    ['title' => 'Chengdu–Jiuzhaigou–Chongqing 8D7N', 'category' => 'China', 'price' => 15200000, 'desc' => 'Dari Chengdu bersama panda raksasa, ke Jiuzhaigou dengan danau warna-warni, lalu Chongqing yang spektakuler.', 'days' => 8],
    ['title' => 'Chengdu–Pengzhou–Chongqing 8D7N', 'category' => 'China', 'price' => 14600000, 'desc' => 'Jelajahi Chengdu, kota kelahiran panda, Pengzhou dengan budaya lokalnya, dan diakhiri dengan pesona Chongqing.', 'days' => 8],
    ['title' => 'Yunnan 8D7N', 'category' => 'China', 'price' => 14000000, 'desc' => 'Provinsi Yunnan dengan keindahan alamnya: Kota Kunming yang abadi semi, danau Erhai, dan pegunungan salju.', 'days' => 8],
    ['title' => 'Yunnan–Lijiang–Shangri-La 8D7N', 'category' => 'China', 'price' => 15600000, 'desc' => 'Dari Yunnan ke Shangri-La legendaris. Kunjungi Lijiang dengan kota kunonya dan Shangri-La di dataran tinggi Tibet.', 'days' => 8],
    ['title' => 'Kunming–Dali–Lijiang 8D7N', 'category' => 'China', 'price' => 14400000, 'desc' => 'Perjalanan klasik Yunnan: Kunming si kota semi abadi, Dali dengan danau Erhai, dan Lijiang yang bersejarah.', 'days' => 8],
    ['title' => 'North Xinjiang 8D7N', 'category' => 'China', 'price' => 16500000, 'desc' => 'Jelajahi Xinjiang Utara dengan pemandangan padang rumput, danau biru, dan pegunungan Tianshan yang megah.', 'days' => 8],
    ['title' => 'North Xinjiang + Wusun Ancient Trail 8D7N', 'category' => 'China', 'price' => 17800000, 'desc' => 'Petualangan di Xinjiang Utara dengan trekking di Wusun Ancient Trail, menyusuri jalur kuno dengan pemandangan spektakuler.', 'days' => 8],
    ['title' => 'North Xinjiang + Yining 8D7N', 'category' => 'China', 'price' => 17200000, 'desc' => 'Xinjiang Utara ditambah Yining, kota cantik di lembah Ili dengan kebun apel dan budaya Kazakh.', 'days' => 8],
    ['title' => 'North Xinjiang Tour 10D9N', 'category' => 'China', 'price' => 19800000, 'desc' => 'Tur Xinjiang Utara yang lebih lengkap dengan waktu lebih panjang untuk mengeksplorasi setiap sudut keindahannya.', 'days' => 10],
    ['title' => 'Silk Road Urumqi–Turpan–Dunhuang 10D9N', 'category' => 'China', 'price' => 21500000, 'desc' => 'Jalur Sutra klasik: Urumqi, Turpan dengan anggurnya, dan Dunhuang dengan goa Mogao yang bersejarah.', 'days' => 10],
    ['title' => 'Beijing–Chengde–Chongqing 10D9N', 'category' => 'China', 'price' => 19800000, 'desc' => 'Kombinasi Beijing, Chengde dengan istana musim panasnya, dan Chongqing yang dramatis.', 'days' => 10],
    ['title' => 'Hangzhou–Shanghai–Wuzhen–Xitang 10D9N', 'category' => 'China', 'price' => 18800000, 'desc' => 'Jelajahi kota-kota air klasik China: Hangzhou, Shanghai modern, Wuzhen dan Xitang yang memesona.', 'days' => 10],
    ['title' => 'Zhangjiajie–Chongqing 10D9N', 'category' => 'China', 'price' => 19500000, 'desc' => 'Dua destinasi ikonik China: keajaiban alam Zhangjiajie dan futuristic city Chongqing.', 'days' => 10],
    ['title' => 'Chongqing–Chengdu 10D9N', 'category' => 'China', 'price' => 18500000, 'desc' => 'Tur lengkap dua kota besar China Barat: Chongqing dan Chengdu, rumah panda raksasa.', 'days' => 10],
    ['title' => 'Northern Xinjiang 11D10N', 'category' => 'China', 'price' => 22500000, 'desc' => 'Petualangan Northern Xinjiang paling komprehensif. Dari Urumqi hingga Kanas Lake, nikmati setiap momen.', 'days' => 11],
    ['title' => 'China Liburan Idul Fitri', 'category' => 'China', 'price' => 16000000, 'desc' => 'Paket spesial liburan Idul Fitri ke China. Nikmati suasana China dengan berbagai destinasi pilihan.', 'days' => 8],

    // JEPANG
    ['title' => 'Osaka–Kyoto 7D5N', 'category' => 'Jepang', 'price' => 16800000, 'desc' => 'Jelajahi Osaka yang gemerlap dan Kyoto yang tradisional. Dari Universal Studios hingga Kuil Kinkakuji.', 'days' => 7],
    ['title' => 'Tokyo Japan Tour 7D6N', 'category' => 'Jepang', 'price' => 19500000, 'desc' => 'Tur lengkap Tokyo: Shibuya, Shinjuku, Asakusa, dan Disneyland Tokyo. Nikmati pengalaman kota futuristik Jepang.', 'days' => 7],

    // KOREA SELATAN
    ['title' => 'Best of Korea 5D', 'category' => 'Korea Selatan', 'price' => 9800000, 'desc' => 'Paket 5 hari terbaik Korea: Seoul, Gyeongbokgung, Myeongdong, dan N Seoul Tower.', 'days' => 5],
    ['title' => 'Korea Tour 7D6N', 'category' => 'Korea Selatan', 'price' => 13500000, 'desc' => 'Tur lengkap Korea 7 hari: Seoul, Busan, dan Jeju Island. Nikmati K-culture dan kuliner Korea.', 'days' => 7],
    ['title' => 'Winter Korea 7D8N', 'category' => 'Korea Selatan', 'price' => 14800000, 'desc' => 'Nikmati musim dingin di Korea: ski di resort terbaik, pemandangan salju, dan Festival Ice Fishing.', 'days' => 7],
    ['title' => 'Best of Korea 8D7N', 'category' => 'Korea Selatan', 'price' => 15800000, 'desc' => 'Korea selengkapnya 8 hari: Seoul, Busan, Jeju, dan Nami Island. Pengalaman K-culture yang mendalam.', 'days' => 8],
    ['title' => 'Cherry Blossom Busan–Yeosu', 'category' => 'Korea Selatan', 'price' => 12500000, 'desc' => 'Saksikan keindahan bunga sakura di Busan dan Yeosu. Musim semi yang romantis di Korea Selatan.', 'days' => 6],
    ['title' => 'Ski Korea', 'category' => 'Korea Selatan', 'price' => 14200000, 'desc' => 'Paket ski musim dingin di resort terbaik Korea. Lengkap dengan perlengkapan ski dan instruktur profesional.', 'days' => 6],
    ['title' => 'Korea + Nami Island', 'category' => 'Korea Selatan', 'price' => 11800000, 'desc' => 'Tur Korea dengan kunjungan ke Nami Island yang terkenal. Seoul, Petite France, dan Nami Island.', 'days' => 6],

    // VIETNAM
    ['title' => 'Hanoi–Sapa–Halong 5D4N', 'category' => 'Vietnam', 'price' => 6500000, 'desc' => 'Jelajahi tiga destinasi ikonik Vietnam: Hanoi yang bersejarah, Sapa dengan teraseringnya, dan Halong Bay.', 'days' => 5],
    ['title' => 'Hanoi–Sapa–Halong 6D5N', 'category' => 'Vietnam', 'price' => 7800000, 'desc' => 'Tur Vietnam lebih panjang: Hanoi, Sapa, dan Halong Bay dengan overnight cruise.', 'days' => 6],
    ['title' => 'Hanoi–Sapa–Halong 7D6N', 'category' => 'Vietnam', 'price' => 8900000, 'desc' => 'Eksplorasi Vietnam yang lebih lengkap dengan waktu lebih banyak di setiap destinasi.', 'days' => 7],
    ['title' => 'Northern Vietnam 8D7N', 'category' => 'Vietnam', 'price' => 10500000, 'desc' => 'Tur Vietnam Utara terlengkap: Hanoi, Sapa, Halong, Ninh Binh, dan Mai Chau.', 'days' => 8],
    ['title' => 'Da Lat + Central Vietnam', 'category' => 'Vietnam', 'price' => 8200000, 'desc' => 'Jelajahi Da Lat kota bunga, Hoi An kuno, dan Hue - ibu kota kekaisaran Vietnam.', 'days' => 7],
    ['title' => 'Hanoi–Sapa–Halong + Ninh Binh', 'category' => 'Vietnam', 'price' => 9200000, 'desc' => 'Tur lengkap Vietnam Utara termasuk Ninh Binh - Halong Bay di daratan dengan pemandangan karst yang spektakuler.', 'days' => 8],

    // TAIWAN
    ['title' => 'Taiwan 8D7N', 'category' => 'Taiwan', 'price' => 14800000, 'desc' => 'Jelajahi Taiwan: Taipei 101, Jiufen, Taroko Gorge, dan pasar malam Taiwan yang legendaris.', 'days' => 8],

    // KAZAKHSTAN
    ['title' => 'Four Countries Kazakhstan 8D7N', 'category' => 'Kazakhstan', 'price' => 18500000, 'desc' => 'Jelajahi Asia Tengah: Kazakhstan, Kyrgyzstan, Uzbekistan, dan Tajikistan. Pesona Jalur Sutra yang autentik.', 'days' => 8],

    // KANADA
    ['title' => 'Victoria Canada 8D7N', 'category' => 'Kanada', 'price' => 32500000, 'desc' => 'Nikmati keindahan Victoria, British Columbia. Taman Butchart yang megah, paus orca, dan pemandangan pantai Pasifik.', 'days' => 8],

    // INDONESIA
    ['title' => 'BCS Explore Batam 4D3N', 'category' => 'Indonesia', 'price' => 2800000, 'desc' => 'Liburan singkat ke Batam: wisata belanja, golf, kuliner seafood, dan resort tepi pantai.', 'days' => 4],

    // MALAYSIA
    ['title' => 'Genting Highlands 3 Hari 2 Malam', 'category' => 'Malaysia', 'price' => 3500000, 'desc' => 'Liburan ke Genting Highlands: taman hiburan outdoor & indoor, kasino, dan udara sejuk pegunungan.', 'days' => 3],

    // CRUISE
    ['title' => 'Royal Caribbean Spectrum of the Seas', 'category' => 'Cruise', 'price' => 12500000, 'desc' => 'Nikmati kemewahan Royal Caribbean Spectrum of the Seas dari Singapore. Fasilitas bintang 5, entertainment kelas dunia.', 'days' => 5],

    // PROMO / SPECIAL
    ['title' => 'Travel Fair', 'category' => 'Promo', 'price' => 0, 'desc' => 'Dapatkan promo spesial di Travel Fair kami. Tiket pesawat, hotel, dan paket tour dengan harga terbaik.', 'days' => 0],
    ['title' => 'Special Offer Universal Studios Singapore', 'category' => 'Promo', 'price' => 1500000, 'desc' => 'Promo spesial tiket Universal Studios Singapore. Nikmati atraksi dunia di taman hiburan terbaik Asia.', 'days' => 1],
    ['title' => 'Special Offer Genting', 'category' => 'Promo', 'price' => 1200000, 'desc' => 'Promo spesial tiket Genting Highlands. Taman hiburan, resort, dan pemandangan pegunungan yang sejuk.', 'days' => 2],
    ['title' => 'Jadwal Ferry Batam–Singapore', 'category' => 'Informasi', 'price' => 0, 'desc' => 'Informasi jadwal ferry Batam–Singapore. Tersedia jadwal keberangkatan setiap hari dengan harga bersaing.', 'days' => 0],
];

// Insert tours
echo "Menambahkan " . count($tours) . " tour baru...\n";

$count = 0;
foreach ($tours as $t) {
    // Parse durasi dari title
    $slug = buatSlug($t['title']);

    // Cek apakah sudah ada
    $stmt = db()->prepare("SELECT COUNT(*) FROM tours WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        echo "  SKIP (already exists): {$t['title']}\n";
        continue;
    }

    // Default deskripsi
    $description = $t['desc'] ?? "Paket wisata {$t['title']}. Nikmati pengalaman liburan tak terlupakan bersama TourAndTravel.";

    // Insert tour
    $stmt = db()->prepare("INSERT INTO tours (title, slug, category, description, price, max_participants, is_active) VALUES (?, ?, ?, ?, ?, 25, 1)");
    $stmt->execute([$t['title'], $slug, $t['category'], $description, $t['price']]);
    $tourId = db()->lastInsertId();

    // Generate departure dates (tiap bulan untuk 6 bulan ke depan)
    $days = $t['days'];
    if ($days > 0) {
        $startMonth = [8, 9, 10, 11, 12, 1]; // Agustus - Januari
        $startYear = 2026;
        foreach ($startMonth as $i => $m) {
            $y = $startYear + ($m < 8 && $i > 0 ? 1 : 0);
            $departure = sprintf("%04d-%02d-15", $y, $m);
            $return = date('Y-m-d', strtotime($departure . " + " . ($days - 1) . " days"));
            $slots = rand(10, 25);

            $stmt = db()->prepare("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tourId, $departure, $return, $slots]);

            // Only 2 dates per tour to keep it manageable
            if ($i >= 2) break;
        }
    }

    // Generate itinerary
    if ($days > 1) {
        for ($d = 1; $d <= $days; $d++) {
            if ($d == 1) {
                $itTitle = "Kedatangan & Check-in";
                $itDesc = "Tiba di bandara, check-in hotel, free time untuk mengeksplorasi sekitar.";
                $itMeals = "Makan malam";
            } elseif ($d == $days) {
                $itTitle = "Check-out & Transfer";
                $itDesc = "Sarapan, check-out hotel, free time hingga transfer ke bandara.";
                $itMeals = "Sarapan";
            } else {
                $itTitle = "Hari $d: Eksplorasi & Wisata";
                $itDesc = "Sarapan di hotel, lanjut tur wisata ke tempat-tempat menarik. Kembali ke hotel untuk istirahat.";
                $itMeals = "Sarapan, makan siang, makan malam";
            }

            $stmt = db()->prepare("INSERT INTO itineraries (tour_id, day_number, title, description, meals, accommodation) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tourId, $d, $itTitle, $itDesc, $itMeals, 'Hotel bintang 4']);
        }
    }

    $count++;
    echo "  OK: {$t['title']} (category: {$t['category']}, price: " . formatRupiah($t['price']) . ")\n";
}

echo "\nSelesai! $count tour baru berhasil ditambahkan.\n";

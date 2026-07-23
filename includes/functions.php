<?php
/**
 * Format angka ke Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function tglIndonesia($date) {
    $hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $t = strtotime($date);
    $namaHari = $hari[date('l', $t)];
    $tanggal = date('j', $t);
    $namaBulan = $bulan[(int)date('m', $t)];
    $tahun = date('Y', $t);

    return "$namaHari, $tanggal $namaBulan $tahun";
}

/**
 * Buat slug dari string
 */
function buatSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Upload gambar
 */
function uploadGambar($file, $targetDir) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file harus JPG/PNG/WebP'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 2MB'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $destPath = $targetDir . '/' . $filename;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'message' => 'Gagal menyimpan file'];
}

/**
 * Escape HTML
 */
function e($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Ambil data tours aktif dengan filter lanjutan + sort + pagination
 */
function getTours($category = null, $search = null, $priceRange = null, $duration = null, $rating = null, $sort = null, $page = 1, $perPage = 12) {
    $sql = "SELECT * FROM tours WHERE is_active = 1";
    $countSql = "SELECT COUNT(*) FROM tours WHERE is_active = 1";
    $params = [];
    $countParams = [];

    if ($category) {
        $sql .= " AND category = ?";
        $countSql .= " AND category = ?";
        $params[] = $category;
        $countParams[] = $category;
    }

    if ($search) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $countSql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $countParams[] = "%$search%";
        $countParams[] = "%$search%";
    }

    if ($priceRange) {
        $rangeSql = match($priceRange) {
            '1' => " AND price < 5000000",
            '2' => " AND price BETWEEN 5000000 AND 10000000",
            '3' => " AND price BETWEEN 10000000 AND 20000000",
            '4' => " AND price > 20000000",
            default => ''
        };
        $sql .= $rangeSql;
        $countSql .= $rangeSql;
    }

    if ($duration) {
        $durSql = match($duration) {
            '1' => " AND (title REGEXP '[3-5][Dd][0-9]?[Nn]?' OR title REGEXP '3[[:space:]]*Hari')",
            '2' => " AND (title REGEXP '[6-8][Dd][0-9]?[Nn]?' OR title REGEXP '[6-8][[:space:]]*Hari')",
            '3' => " AND (title REGEXP '1[0-9][Dd]' OR title REGEXP '1[0-9][[:space:]]*Hari' OR title REGEXP '11[Dd]')",
            default => ''
        };
        $sql .= $durSql;
        $countSql .= $durSql;
    }

    if ($rating) {
        $sql .= " AND rating >= ?";
        $countSql .= " AND rating >= ?";
        $params[] = (float)$rating;
        $countParams[] = (float)$rating;
    }

    // Sort
    $sql .= match($sort) {
        'price_asc' => " ORDER BY price ASC",
        'price_desc' => " ORDER BY price DESC",
        'rating' => " ORDER BY rating DESC, total_reviews DESC",
        'popular' => " ORDER BY total_reviews DESC, rating DESC",
        default => " ORDER BY created_at DESC"
    };

    // Pagination
    $page = max(1, (int)$page);
    $offset = ($page - 1) * $perPage;
    $sql .= " LIMIT $perPage OFFSET $offset";

    // Hitung total
    $countStmt = db()->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetchColumn();

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $tours = $stmt->fetchAll();

    return ['tours' => $tours, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => max(1, (int)ceil($total / $perPage))];
}

/**
 * Cek user login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser() {
    if (!isLoggedIn()) return null;
    $stmt = db()->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Wishlist
 */
function isWishlisted($userId, $tourId) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ? AND tour_id = ?");
    $stmt->execute([$userId, $tourId]);
    return $stmt->fetchColumn() > 0;
}

function getUserWishlists($userId) {
    $stmt = db()->prepare("SELECT t.* FROM wishlists w JOIN tours t ON w.tour_id = t.id WHERE w.user_id = ? AND t.is_active = 1 ORDER BY w.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getWishlistIds($userId) {
    $stmt = db()->prepare("SELECT tour_id FROM wishlists WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Ambil detail tour by slug
 */
function getTourBySlug($slug) {
    $stmt = db()->prepare("SELECT * FROM tours WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

/**
 * Ambil detail tour by id
 */
function getTourById($id) {
    $stmt = db()->prepare("SELECT * FROM tours WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Ambil tanggal keberangkatan tour
 */
function getTourDates($tourId) {
    $stmt = db()->prepare("SELECT * FROM tour_dates WHERE tour_id = ? AND is_active = 1 AND departure_date >= CURDATE() ORDER BY departure_date ASC");
    $stmt->execute([$tourId]);
    return $stmt->fetchAll();
}

/**
 * Ambil itinerary tour
 */
function getItineraries($tourId) {
    $stmt = db()->prepare("SELECT * FROM itineraries WHERE tour_id = ? ORDER BY day_number ASC");
    $stmt->execute([$tourId]);
    return $stmt->fetchAll();
}

/**
 * Ambil semua kategori
 */
function getCategories() {
    $stmt = db()->query("SELECT DISTINCT category FROM tours WHERE is_active = 1 ORDER BY category ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Ambil URL gambar tour, prioritas: upload > wiki > loremflickr > picsum
 */
function getTourImage($tour, $size = 'medium') {
    // 1. Uploaded image
    if ($tour['cover_image']) {
        return BASE_URL . '/uploads/' . $tour['cover_image'];
    }

    // 2. Wikimedia Commons (via DB — path relatif: wiki/hash.webp)
    if (!empty($tour['wiki_image'])) {
        return BASE_URL . '/uploads/' . $tour['wiki_image'];
    }

    $dimensions = [
        'small'  => '320/240',
        'medium' => '640/480',
        'large'  => '1200/800',
    ];
    $dim = $dimensions[$size] ?? '640/480';

    // 3. Loremflickr fallback
    $pool = ['travel', 'beach', 'mountain', 'ocean', 'city', 'nature', 'landscape', 'sea'];
    $idx = abs(crc32($tour['id'] ?? $tour['title'])) % count($pool);
    $keyword = $pool[$idx];

    return "https://loremflickr.com/{$dim}/{$keyword}?lock=" . $idx;
}

/**
 * Ambil URL fallback gambar (picsum) jika loremflickr gagal load
 */
function getTourImageFallback($tour, $size = 'medium') {
    $dimensions = [
        'small'  => '320/240',
        'medium' => '640/480',
        'large'  => '1200/800',
    ];
    $dim = $dimensions[$size] ?? '640/480';
    $seed = strtolower(str_replace([' '], '-', $tour['title']));
    return "https://picsum.photos/seed/{$seed}/{$dim}";
}

/**
 * Ambil gambar destinasi kota dari cache Wikimedia
 */
function getDestinasiImage($city) {
    $cacheFile = __DIR__ . '/../cache/wiki-cities.json';
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
        if (isset($cache[$city]) && $cache[$city]) {
            return BASE_URL . '/uploads/' . $cache[$city];
        }
    }
    return "https://picsum.photos/seed/" . strtolower(str_replace(' ', '-', $city)) . "/400/300";
}

/**
 * Hitung sisa slot
 */
function getSisaSlot($tourDateId) {
    $stmt = db()->prepare("
        SELECT td.available_slots - COALESCE(SUM(b.participants), 0) as sisa
        FROM tour_dates td
        LEFT JOIN bookings b ON b.tour_date_id = td.id AND b.status IN ('pending', 'confirmed')
        WHERE td.id = ?
        GROUP BY td.id
    ");
    $stmt->execute([$tourDateId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['sisa'] : 0;
}

/**
 * Render bintang rating HTML
 */
function renderStars($rating) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5;
    $html = '<span class="text-warning small">';
    for ($i = 0; $i < 5; $i++) {
        if ($i < $full) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($i == $full && $half) {
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }
    $html .= '</span>';
    return $html;
}

/**
 * Hitung diskon persen
 */
function getDiskonPersen($tour) {
    if (!empty($tour['original_price']) && $tour['original_price'] > $tour['price']) {
        return round((($tour['original_price'] - $tour['price']) / $tour['original_price']) * 100);
    }
    return 0;
}

/**
 * Ekstrak kata kunci untuk galeri dari judul tour
 */
function getGalleryKeywords($tour) {
    $base = $tour['title'];
    $base = preg_replace('/\d+[dD]\d+[nN]?/i', '', $base);
    $base = str_ireplace(['Tour', 'Package', 'Paket', 'Travel', '–', '-'], ' ', $base);
    $base = trim(preg_replace('/\s+/', ' ', $base));

    $keywords = [$base];

    // Tambah variasi kata kunci
    $words = array_filter(explode(' ', $base));
    if (count($words) >= 2) {
        $keywords[] = $words[0] . ' landmark';
        $keywords[] = $words[0] . ' culture';
        if (isset($words[1])) {
            $keywords[] = $words[0] . ' ' . $words[1] . ' view';
        }
    }

    return array_unique($keywords);
}

/**
 * Fasilitas default untuk tour
 */
function getTourFacilities() {
    return [
        ['icon' => 'bi-building', 'label' => 'Hotel Bintang 4'],
        ['icon' => 'bi-bus-front', 'label' => 'Transport AC'],
        ['icon' => 'bi-cup-hot', 'label' => 'Makan Sesuai Itinerary'],
        ['icon' => 'bi-person-badge', 'label' => 'Tour Guide Profesional'],
        ['icon' => 'bi-shield-check', 'label' => 'Asuransi Perjalanan'],
        ['icon' => 'bi-camera', 'label' => 'Dokumentasi'],
    ];
}

/**
 * Ambil tour berdasarkan keyword (destinasi kota)
 */
function getToursByCity($keyword) {
    $stmt = db()->prepare("SELECT * FROM tours WHERE is_active = 1 AND (title LIKE ? OR description LIKE ?) ORDER BY price ASC");
    $like = "%$keyword%";
    $stmt->execute([$like, $like]);
    return $stmt->fetchAll();
}

/**
 * Data destinasi kota terstruktur per kategori
 */
function getCityDestinations() {
    return [
        'China' => [
            ['city' => 'Beijing', 'img' => 'beijing'],
            ['city' => 'Shanghai', 'img' => 'shanghai'],
            ['city' => 'Zhangjiajie', 'img' => 'zhangjiajie'],
            ['city' => 'Chengdu', 'img' => 'chengdu'],
            ['city' => 'Chongqing', 'img' => 'chongqing'],
            ['city' => 'Guizhou', 'img' => 'guizhou'],
            ['city' => 'Yunnan', 'img' => 'yunnan'],
            ['city' => 'Xinjiang', 'img' => 'xinjiang'],
        ],
        'Jepang' => [
            ['city' => 'Tokyo', 'img' => 'tokyo'],
            ['city' => 'Osaka', 'img' => 'osaka'],
            ['city' => 'Kyoto', 'img' => 'kyoto'],
        ],
        'Korea Selatan' => [
            ['city' => 'Seoul', 'img' => 'seoul'],
            ['city' => 'Busan', 'img' => 'busan'],
        ],
        'Vietnam' => [
            ['city' => 'Hanoi', 'img' => 'hanoi'],
            ['city' => 'Da Lat', 'img' => 'dalat'],
            ['city' => 'Sapa', 'img' => 'sapa'],
            ['city' => 'Halong', 'img' => 'halong-bay'],
        ],
    ];
}

/**
 * Hitung jumlah tour di suatu kota
 */
function countToursByCity($city) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM tours WHERE is_active = 1 AND title LIKE ?");
    $stmt->execute(["%$city%"]);
    return $stmt->fetchColumn();
}
?>

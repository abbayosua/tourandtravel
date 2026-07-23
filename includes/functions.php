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
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Ambil data tours aktif
 */
function getTours($category = null, $search = null) {
    $sql = "SELECT * FROM tours WHERE is_active = 1";
    $params = [];

    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    if ($search) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
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
 * Ambil URL gambar tour, fallback ke picsum jika loremflickr gagal
 */
function getTourImage($tour, $size = 'medium') {
    if ($tour['cover_image']) {
        return BASE_URL . '/uploads/' . $tour['cover_image'];
    }

    $dimensions = [
        'small'  => '320/240',
        'medium' => '640/480',
        'large'  => '1200/800',
    ];
    $dim = $dimensions[$size] ?? '640/480';

    // Pool keyword yang pasti work di loremflickr
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

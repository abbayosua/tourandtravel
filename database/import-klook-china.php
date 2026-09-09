<?php
/**
 * import-klook-china.php — Import 50 tour dari data Klook scraping ke database.
 * Jalankan: php database/import-klook-china.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$jsonFile = '/Users/user/www/klookscraper/klook_china.json';
if (!is_file($jsonFile)) {
    echo "ERROR: $jsonFile not found\n";
    exit(1);
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data || !is_array($data)) {
    echo "ERROR: Invalid JSON\n";
    exit(1);
}

echo "Total scraped items: " . count($data) . "\n";

// ============================================================
// Mapping city_id → kota
// ============================================================
$cityMap = [
    57  => 'Beijing',
    58  => 'Shanghai',
    59  => 'Guangzhou',
    60  => 'Shenzhen',
    61  => 'Chengdu',
    62  => 'Xi\'an',
    161 => 'Hangzhou',
    179 => 'Zhangjiajie',
    182 => 'Chongqing',
    187 => 'Guilin',
];

// ============================================================
// Pilih 50 item: 5 per kota dari 10 kota
// ============================================================
$selected = [];
$perCity = 5;
$targetCities = array_keys($cityMap);

foreach ($targetCities as $cid) {
    $candidates = array_filter($data, fn($item) => (int)$item['city_id'] === $cid);
    // Prioritas: rating tinggi, review banyak
    usort($candidates, function ($a, $b) {
        $ra = (float)str_replace(',', '', $a['rating'] ?? '0');
        $rb = (float)str_replace(',', '', $b['rating'] ?? '0');
        return $rb <=> $ra;
    });
    $selected = array_merge($selected, array_slice($candidates, 0, $perCity));
}

echo "Selected: " . count($selected) . " tours\n";

// ============================================================
// Generate slug
// ============================================================
function makeSlug(string $title): string {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return substr($slug, 0, 180);
}

// ============================================================
// Parse price: "Rp 307,913" → 307913.00
// ============================================================
function parsePrice(string $priceStr): float {
    $clean = preg_replace('/[^0-9]/', '', $priceStr);
    return (float)$clean;
}

// ============================================================
// Extract description from tags
// ============================================================
function makeDescription(array $item): string {
    $desc = $item['title'] ?? '';
    $tags = $item['tags'] ?? '';
    if ($tags) {
        $desc .= "\n\nHighlights:\n";
        foreach (explode('; ', $tags) as $tag) {
            $desc .= "- " . trim($tag) . "\n";
        }
    }
    $booked = $item['booked'] ?? '';
    if ($booked) {
        $desc .= "\nPopularity: " . $booked;
    }
    return $desc;
}

// ============================================================
// Generate itinerary dari title + tags
// ============================================================
function generateItinerary(array $item): array {
    $title = $item['title'] ?? '';
    $tags = $item['tags'] ?? '';
    $city = $item['_city'] ?? 'Unknown';

    $days = [];
    $tagsArr = array_filter(explode('; ', $tags));

    // Day 1: Title utama + highlights
    $day1Desc = "Hari pertama di $city.\n\n";
    $day1Desc .= "Activity: " . $title . "\n\n";
    if (!empty($tagsArr)) {
        $day1Desc .= "Highlights:\n";
        foreach (array_slice($tagsArr, 0, 4) as $t) {
            $day1Desc .= "- $t\n";
        }
    }
    $days[] = [
        'day_number' => 1,
        'title' => "Day 1 — $city",
        'description' => $day1Desc,
        'meals' => null,
        'accommodation' => null,
    ];

    // Day 2: Suggested explore
    $days[] = [
        'day_number' => 2,
        'title' => "Day 2 — Explore $city",
        "description" => "Free day to explore $city on your own. Visit local markets, try street food, and experience the local culture.\n\nRecommended:\n- Walk around the city center\n- Try local cuisine\n- Visit nearby attractions",
        'meals' => 'Breakfast',
        'accommodation' => 'Hotel',
    ];

    return $days;
}

// ============================================================
// Insert ke database
// ============================================================
$db = db();
$imported = 0;
$skipped = 0;

foreach ($selected as $item) {
    $cityId = (int)$item['city_id'];
    $city = $cityMap[$cityId] ?? 'China';
    $item['_city'] = $city;

    $title = $item['title'] ?? '';
    $slug = makeSlug($title);

    // Cek duplikat
    $exists = $db->prepare("SELECT COUNT(*) FROM tours WHERE slug = ?");
    $exists->execute([$slug]);
    if ($exists->fetchColumn() > 0) {
        echo "SKIP (duplikat): $title\n";
        $skipped++;
        continue;
    }

    $price = parsePrice($item['sale_price'] ?? '0');
    $originalPrice = parsePrice($item['underline_price'] ?? '0');
    if ($originalPrice <= 0) $originalPrice = null;
    $rating = (float)($item['rating'] ?? '4.5');
    $reviewCount = (int)str_replace(',', '', str_replace('+', '', str_replace(' kali dipesan', '', $item['booked'] ?? '0')));
    $coverImage = $item['image_url'] ?? null;

    // Insert tour
    $stmt = $db->prepare("INSERT INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active) VALUES (?, ?, ?, ?, ?, 'IDR', ?, 20, ?, ?, ?, 1)");
    $stmt->execute([
        $title,
        $slug,
        $city,
        makeDescription($item),
        $price,
        $originalPrice,
        $rating,
        min($reviewCount, 99999),
        $coverImage,
    ]);
    $tourId = (int)$db->lastInsertId();

    // Insert itinerary days
    $itineraries = generateItinerary($item);
    foreach ($itineraries as $it) {
        $db->prepare("INSERT INTO itineraries (tour_id, day_number, title, description, meals, accommodation) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$tourId, $it['day_number'], $it['title'], $it['description'], $it['meals'], $it['accommodation']]);
    }

    // Insert tour images (gallery)
    $gallery = $item['gallery_images'] ?? [];
    if (!empty($gallery)) {
        foreach (array_slice($gallery, 0, 5) as $idx => $img) {
            $db->prepare("INSERT INTO tour_images (tour_id, image_path, caption, sort_order) VALUES (?, ?, ?, ?)")
                ->execute([$tourId, $img, $title, $idx]);
        }
    } elseif ($coverImage) {
        $db->prepare("INSERT INTO tour_images (tour_id, image_path, caption, sort_order) VALUES (?, ?, ?, 0)")
            ->execute([$tourId, $coverImage, $title]);
    }

    // Insert tour_date (1 month from now, random slots)
    $departureDate = date('Y-m-d', strtotime('+30 days'));
    $returnDate = date('Y-m-d', strtotime('+37 days'));
    $slots = rand(5, 20);
    $db->prepare("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active) VALUES (?, ?, ?, ?, 1)")
        ->execute([$tourId, $departureDate, $returnDate, $slots]);

    echo "OK [$tourId] $title ($city) — Rp " . number_format($price) . "\n";
    $imported++;
}

echo "\n=== SELESAI ===\n";
echo "Imported: $imported\n";
echo "Skipped: $skipped\n";
echo "Total tours now: " . $db->query("SELECT COUNT(*) FROM tours")->fetchColumn() . "\n";

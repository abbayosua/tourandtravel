<?php
/**
 * CLI Helper: Ambil gambar tour dari Wikimedia Commons dan simpan ke database.
 * 
 * Penggunaan:
 *   php database/fetch-wiki-images.php
 *   php database/fetch-wiki-images.php --all       (tour + destinasi, default)
 *   php database/fetch-wiki-images.php --tours     (hanya tour)
 *   php database/fetch-wiki-images.php --cities    (hanya destinasi kota)
 * 
 * Gambar disimpan di kolom `wiki_image` di tabel tours.
 * Untuk destinasi kota, disimpan di cache/wiki-cities.json
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

define('CACHE_CITIES', __DIR__ . '/../cache/wiki-cities.json');

/**
 * Fetch satu gambar dari Wikimedia Commons
 */
function fetchWikiImage($keyword) {
    $clean = preg_replace('/\d+[dD]\d+[nN]?/i', '', $keyword);
    $clean = str_ireplace(['Tour', 'Package', 'Paket', 'Travel', 'Fair', 'Special', 'Offer', '+', '–', '-', '/'], ' ', $clean);
    $clean = preg_replace('/\s+/', ' ', trim($clean));
    $words = array_filter(explode(' ', $clean));
    $search = implode(' ', array_slice($words, 0, 3));

    if (!trim($search)) $search = $keyword;

    echo "  Mencari: \"$search\"... ";

    $params = http_build_query([
        'action' => 'query',
        'generator' => 'search',
        'gsrsearch' => $search,
        'gsrnamespace' => 6,
        'prop' => 'imageinfo',
        'iiprop' => 'url',
        'gsrlimit' => 5,
        'format' => 'json',
    ]);

    $url = "https://commons.wikimedia.org/w/api.php?{$params}";
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'TourAndTravel-Bot/1.0']]);
    $response = @file_get_contents($url, false, $ctx);

    if (!$response) {
        echo "GAGAL (network)\n";
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data['query']['pages'])) {
        echo "TIDAK ADA\n";
        return null;
    }

    foreach ($data['query']['pages'] as $page) {
        if (isset($page['imageinfo'][0]['url'])) {
            $imgUrl = $page['imageinfo'][0]['url'];
            $ext = strtolower(pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                echo "OK\n";
                return $imgUrl;
            }
        }
    }

    echo "TIDAK ADA\n";
    return null;
}

// ===== MAIN =====

$mode = $argv[1] ?? '--all';
$count = 0;
$total = 0;

// Buat cache dir
if (!is_dir(dirname(CACHE_CITIES))) mkdir(dirname(CACHE_CITIES), 0755, true);

echo "\n========================================\n";
echo "  TourAndTravel — Fetch Wiki Images\n";
echo "========================================\n\n";

// 1. Tours
if (in_array($mode, ['--all', '--tours'])) {
    echo "--- TOURS ---\n";
    $tours = db()->query("SELECT id, title, wiki_image FROM tours WHERE is_active = 1 ORDER BY id")->fetchAll();
    $total = count($tours);

    foreach ($tours as $i => $t) {
        if ($t['wiki_image']) {
            echo "  [$i/$total] {$t['title']} → sudah ada\n";
            continue;
        }

        $img = fetchWikiImage($t['title']);
        if ($img) {
            $stmt = db()->prepare("UPDATE tours SET wiki_image = ? WHERE id = ?");
            $stmt->execute([$img, $t['id']]);
            $count++;
        }

        if ($i < $total - 1) usleep(500000); // 500ms delay biar gak kena rate limit
    }
    echo "\n";
}

// 2. Destinasi Kota (disimpan di JSON cache)
if (in_array($mode, ['--all', '--cities'])) {
    echo "--- DESTINASI KOTA ---\n";

    require_once __DIR__ . '/../includes/functions.php';

    $cityCache = [];
    if (file_exists(CACHE_CITIES)) {
        $cityCache = json_decode(file_get_contents(CACHE_CITIES), true) ?: [];
    }

    $dests = getCityDestinations();
    $flat = [];
    foreach ($dests as $cat => $list) {
        foreach ($list as $d) {
            $flat[] = $d['city'];
        }
    }

    $total = count($flat);
    foreach ($flat as $i => $city) {
        if (isset($cityCache[$city]) && $cityCache[$city]) {
            echo "  [$i/$total] $city → sudah ada\n";
            continue;
        }

        $img = fetchWikiImage($city . ' travel');
        if ($img) {
            $cityCache[$city] = $img;
            file_put_contents(CACHE_CITIES, json_encode($cityCache));
            $count++;
        }

        if ($i < $total - 1) usleep(500000);
    }
    echo "\n";
}

echo "========================================\n";
echo "  Selesai! $count gambar baru ditambahkan.\n";
echo "========================================\n\n";

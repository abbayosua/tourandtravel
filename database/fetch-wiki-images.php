<?php
/**
 * CLI Helper: Download gambar dari Wikimedia Commons → kompres WebP → simpan lokal.
 * 
 * Penggunaan:
 *   php database/fetch-wiki-images.php           # tour + kota
 *   php database/fetch-wiki-images.php --tours    # hanya tour
 *   php database/fetch-wiki-images.php --cities   # hanya kota
 * 
 * Gambar disimpan di uploads/wiki/ sebagai WebP.
 * Kolom wiki_image berisi path: wiki/hash.webp
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

define('WIKI_DIR', __DIR__ . '/../uploads/wiki');
define('CACHE_CITIES', __DIR__ . '/../cache/wiki-cities.json');

function fetchWikiUrl($keyword) {
    $clean = preg_replace('/\d+[dD]\d+[nN]?/i', '', $keyword);
    $clean = str_ireplace(['Tour', 'Package', 'Paket', 'Travel', 'Fair', 'Special', 'Offer', '+', '–', '-', '/'], ' ', $clean);
    $clean = preg_replace('/\s+/', ' ', trim($clean));
    $words = array_filter(explode(' ', $clean));
    $search = implode(' ', array_slice($words, 0, 2));
    if (!trim($search)) $search = $keyword;

    echo "  Cari: \"$search\"... ";

    $params = http_build_query([
        'action' => 'query',
        'generator' => 'search',
        'gsrsearch' => $search,
        'gsrnamespace' => 6,
        'prop' => 'imageinfo',
        'iiprop' => 'url|thumburl',
        'iiurlwidth' => 800,
        'gsrlimit' => 5,
        'format' => 'json',
    ]);

    $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'TourAndTravel-Bot/1.0']]);
    $res = @file_get_contents("https://commons.wikimedia.org/w/api.php?{$params}", false, $ctx);
    if (!$res) { echo "NETWORK ERR\n"; return null; }

    $data = json_decode($res, true);
    if (!isset($data['query']['pages'])) { echo "TIDAK ADA\n"; return null; }

    foreach ($data['query']['pages'] as $page) {
        if (isset($page['imageinfo'][0]['thumburl'])) {
            $ext = strtolower(pathinfo(parse_url($page['imageinfo'][0]['thumburl'], PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                echo "OK\n";
                return $page['imageinfo'][0]['thumburl'];
            }
        }
    }
    echo "TIDAK ADA\n";
    return null;
}

function downloadAndCompress($sourceUrl) {
    if (!is_dir(WIKI_DIR)) mkdir(WIKI_DIR, 0755, true);

    $ctx = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => 'TourAndTravel-Bot/1.0']]);
    $raw = @file_get_contents($sourceUrl, false, $ctx);
    if (!$raw) return null;

    $info = @getimagesizefromstring($raw);
    if (!$info) return null;

    $gd = match ($info[2]) {
        IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP => @imagecreatefromstring($raw),
        default => null,
    };
    if (!$gd) return null;

    // Resize max 1200px
    $w = imagesx($gd);
    $h = imagesy($gd);
    if ($w > 1200) {
        $r = 1200 / $w;
        $nw = 1200; $nh = (int)($h * $r);
        $rz = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($rz, $gd, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($gd);
        $gd = $rz;
    }

    $hash = md5($sourceUrl);
    $filename = "{$hash}.webp";
    $dest = WIKI_DIR . "/{$filename}";

    imagewebp($gd, $dest, 65);
    imagedestroy($gd);

    $kb = round(filesize($dest) / 1024);
    echo "           ↓ wiki/{$filename} ({$kb}KB)\n";
    return "wiki/{$filename}";
}

// === MAIN ===
$mode = $argv[1] ?? '--all';
$count = 0;

if (!is_dir(dirname(CACHE_CITIES))) mkdir(dirname(CACHE_CITIES), 0755, true);

echo "\n========================================\n";
echo "  TourAndTravel — Fetch & Compress Images\n";
echo "========================================\n\n";

if (in_array($mode, ['--all', '--tours'])) {
    echo "--- TOURS ---\n";
    $tours = db()->query("SELECT id, title, wiki_image FROM tours WHERE is_active = 1 ORDER BY id")->fetchAll();
    foreach ($tours as $i => $t) {
        $total = count($tours);
        echo "[$i/$total] {$t['title']}\n";

        if ($t['wiki_image'] && file_exists(__DIR__ . '/../uploads/' . $t['wiki_image'])) {
            echo "       → sudah ada\n";
            continue;
        }

        $url = fetchWikiUrl($t['title']);
        if ($url) {
            $path = downloadAndCompress($url);
            if ($path) {
                $s = db()->prepare("UPDATE tours SET wiki_image = ? WHERE id = ?");
                $s->execute([$path, $t['id']]);
                $count++;
            }
        }
        if ($i < count($tours) - 1) usleep(800000);
    }
    echo "\n";
}

if (in_array($mode, ['--all', '--cities'])) {
    echo "--- DESTINASI KOTA ---\n";
    require_once __DIR__ . '/../includes/functions.php';

    $cc = file_exists(CACHE_CITIES) ? (json_decode(file_get_contents(CACHE_CITIES), true) ?: []) : [];
    $flat = [];
    foreach (getCityDestinations() as $cat => $list)
        foreach ($list as $d) $flat[] = $d['city'];

    foreach ($flat as $i => $city) {
        $total = count($flat);
        echo "[$i/$total] $city\n";

        if (isset($cc[$city]) && $cc[$city] && file_exists(__DIR__ . '/../uploads/' . $cc[$city])) {
            echo "       → sudah ada\n";
            continue;
        }

        $url = fetchWikiUrl($city);
        if ($url) {
            $path = downloadAndCompress($url);
            if ($path) {
                $cc[$city] = $path;
                file_put_contents(CACHE_CITIES, json_encode($cc));
                $count++;
            }
        }
        if ($i < count($flat) - 1) usleep(800000);
    }
    echo "\n";
}

echo "========================================\n";
echo "  Selesai! $count gambar baru.\n";
echo "========================================\n\n";

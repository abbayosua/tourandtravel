<?php
/**
 * download-tour-images.php — Download gambar tour dari Klook CDN ke uploads/tours/.
 * Update cover_image di DB ke path lokal.
 * Jalankan: php database/download-tour-images.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$uploadDir = __DIR__ . '/../uploads/tours/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Ambil semua tour yang cover_image masih URL external
$stmt = db()->query("SELECT id, slug, cover_image FROM tours WHERE cover_image LIKE 'http%'");
$tours = $stmt->fetchAll();

echo "Tours with external images: " . count($tours) . "\n";

$downloaded = 0;
$failed = 0;

foreach ($tours as $t) {
    $url = $t['cover_image'];
    $slug = $t['slug'];
    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
    $filename = substr($slug, 0, 80) . '.' . $ext;
    $localPath = $uploadDir . $filename;

    if (is_file($localPath) && filesize($localPath) > 1000) {
        // Sudah ada, update DB
        db()->prepare("UPDATE tours SET cover_image = ? WHERE id = ?")->execute(['tours/' . $filename, $t['id']]);
        echo "EXISTS [$t[id]] $filename\n";
        $downloaded++;
        continue;
    }

    // Download
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0']]);
    $data = @file_get_contents($url, false, $ctx);

    if ($data && strlen($data) > 1000) {
        file_put_contents($localPath, $data);
        db()->prepare("UPDATE tours SET cover_image = ? WHERE id = ?")->execute(['tours/' . $filename, $t['id']]);
        echo "OK [$t[id]] $filename (" . strlen($data) . " bytes)\n";
        $downloaded++;
    } else {
        echo "FAIL [$t[id]] $url\n";
        $failed++;
    }

    // Throttle: 100ms antar download
    usleep(100000);
}

echo "\n=== SELESAI ===\n";
echo "Downloaded: $downloaded\n";
echo "Failed: $failed\n";

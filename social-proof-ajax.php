<?php
/**
 * social-proof-ajax.php — data social proof (booking ≤60 menit terakhir)
 * Privacy-safe: hanya nama ter-mask + kota/kota tujuan + waktu relatif. Tanpa email/telepon/user_id.
 * Cache: file cache 60 detik (mengurangi beban DB).
 * GET ?limit=8 (max 12)
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

function spMaskName(string $name): string {
    $name = trim($name);
    if ($name === '') return 'Tamu';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0], 0, 1) . str_repeat('*', max(1, mb_strlen($parts[0]) - 1));
    if (count($parts) > 1) {
        $last = mb_substr($parts[count($parts) - 1], 0, 1) . str_repeat('*', max(1, mb_strlen($parts[count($parts) - 1]) - 1));
        return $first . ' ' . $last;
    }
    return $first;
}

function spCollect(): array {
    $out = [];

    $rows = db()->query("SELECT name, created_at FROM bookings WHERE created_at >= NOW() - INTERVAL 60 MINUTE ORDER BY created_at DESC LIMIT 12")->fetchAll();
    foreach ($rows as $r) {
        $out[] = ['type' => 'tour', 'name' => spMaskName((string)$r['name']), 'when' => (string)$r['created_at']];
    }

    $rows = db()->query("SELECT name, created_at FROM hotel_bookings WHERE created_at >= NOW() - INTERVAL 60 MINUTE ORDER BY created_at DESC LIMIT 12")->fetchAll();
    foreach ($rows as $r) {
        $out[] = ['type' => 'hotel', 'name' => spMaskName((string)$r['name']), 'when' => (string)$r['created_at']];
    }

    usort($out, fn($a, $b) => strcmp($b['when'], $a['when']));
    return array_slice($out, 0, 12);
}

$limit = max(1, min(12, (int)($_GET['limit'] ?? 8)));

$cacheFile = __DIR__ . '/scripts/out/social-proof-cache.json';
$items = null;
if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
    $decoded = json_decode((string)file_get_contents($cacheFile), true);
    if (is_array($decoded)) $items = $decoded;
}
if ($items === null) {
    try {
        $items = spCollect();
    } catch (Throwable $e) {
        $items = [];
    }
    @file_put_contents($cacheFile, json_encode($items), LOCK_EX);
}

echo json_encode([
    'success' => true,
    'count' => count($items),
    'items' => array_slice($items, 0, $limit),
]);

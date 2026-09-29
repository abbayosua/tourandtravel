<?php
/**
 * AJAX autosuggest kota hotel (NusaTrip location search + fallback DB lokal).
 * GET ?q=jak → JSON [{label, location_id}].
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/hotelapi.php';

header('Content-Type: application/json; charset=utf-8');
$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) { echo json_encode([]); exit; }

$out = [];
try {
    $r = hotelApiNusaAuto($q);
    foreach (array_slice($r['results'] ?? [], 0, 8) as $x) {
        $full = (string)($x['label'] ?? '');
        if ($full === '') continue;
        $short = trim((string)explode(',', $full)[0]);
        if ($short === '') $short = $full;
        $out[] = ['label' => $short, 'full_label' => $full, 'location_id' => $x['location_id'] ?? null];
    }
} catch (Throwable $e) {}

if (!$out) {
    try {
        $st = db()->prepare("SELECT DISTINCT city FROM hotels WHERE is_active = 1 AND city LIKE ? ORDER BY city ASC LIMIT 8");
        $st->execute(['%' . $q . '%']);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $c) $out[] = ['label' => (string)$c, 'location_id' => null];
    } catch (Throwable $e) {}
}

echo json_encode($out);

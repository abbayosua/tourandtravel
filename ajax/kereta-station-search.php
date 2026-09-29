<?php
/**
 * AJAX: Kereta station search (hardcoded stations)
 * Returns JSON array with label, label_code for train stations
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../kereta.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$q = strtolower($q);
$results = [];

foreach ($KERETA_STATION_MAP as $code => $label) {
    if (strpos(strtolower($label), $q) !== false || strpos(strtolower($code), $q) !== false) {
        $results[] = [
            'label' => $label,
            'label_code' => $code,
        ];
    }
    if (count($results) >= 8) break;
}

echo json_encode($results);

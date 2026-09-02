<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/easybook.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

$places = easybookSearchPlace($query);

// Flatten: each place + its sub-places (terminals)
$results = [];
foreach ($places as $p) {
    $pid = $p['pid'];
    $pn = $p['pn'];
    $spid = $p['spid'];
    $spn = $p['spn'];

    if ($spid == 0 && $pn) {
        // Main city
        $results[] = [
            'label' => $pn . ' (' . ($p['ctn'] ?? '') . ')',
            'pid' => $pid,
            'spid' => 0,
            'name' => $pn,
            'country' => $p['ctn'] ?? '',
        ];
    } elseif ($spid && $spn) {
        // Terminal
        $results[] = [
            'label' => $spn . ', ' . $pn,
            'pid' => $pid,
            'spid' => $spid,
            'name' => $spn,
            'city' => $pn,
        ];
    }
}

echo json_encode(array_slice($results, 0, 8));

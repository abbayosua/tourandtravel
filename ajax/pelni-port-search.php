<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pelni.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? $_GET['term'] ?? '');
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = pelniSearchPort($query);
echo json_encode($results);

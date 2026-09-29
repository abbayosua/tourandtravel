<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../pelni.php';

header('Content-Type: application/json');

$query = trim($_GET['term'] ?? '');
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = pelniSearchPort($query);
echo json_encode($results);

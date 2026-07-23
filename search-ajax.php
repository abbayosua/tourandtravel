<?php
/**
 * AJAX search autocomplete
 * Returns JSON of matching tour titles & categories
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$like = "%$q%";

// Cari tour
$stmt = db()->prepare("SELECT title as label, slug, 'tour' as type, price FROM tours WHERE is_active = 1 AND title LIKE ? LIMIT 8");
$stmt->execute([$like]);
$tours = $stmt->fetchAll();

// Cari kategori
$stmt = db()->prepare("SELECT DISTINCT category as label, NULL as slug, 'category' as type, NULL as price FROM tours WHERE is_active = 1 AND category LIKE ? LIMIT 4");
$stmt->execute([$like]);
$categories = $stmt->fetchAll();

$result = array_merge($tours, $categories);

echo json_encode($result);

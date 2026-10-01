<?php
/**
 * AJAX search autocomplete
 * Returns JSON of matching tour titles & categories (dilokalkan sesuai bahasa aktif)
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

// Cari tour (cocokkan judul semua bahasa, tampilkan judul sesuai bahasa aktif)
$stmt = db()->prepare("SELECT title, title_en, title_zh, slug, price FROM tours WHERE is_active = 1 AND (title LIKE ? OR title_en LIKE ? OR title_zh LIKE ?) LIMIT 8");
$stmt->execute([$like, $like, $like]);
$tours = [];
foreach ($stmt->fetchAll() as $r) {
    $tours[] = ['label' => tContent($r, 'title'), 'slug' => $r['slug'], 'type' => 'tour', 'price' => $r['price']];
}

// Cari kategori
$stmt = db()->prepare("SELECT DISTINCT category, category_en, category_zh FROM tours WHERE is_active = 1 AND (category LIKE ? OR category_en LIKE ? OR category_zh LIKE ?) LIMIT 4");
$stmt->execute([$like, $like, $like]);
$categories = [];
foreach ($stmt->fetchAll() as $r) {
    $categories[] = ['label' => tContent($r, 'category'), 'slug' => null, 'type' => 'category', 'price' => null];
}

echo json_encode(array_merge($tours, $categories));

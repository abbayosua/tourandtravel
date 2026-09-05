<?php
/**
 * sitemap.php — XML sitemap dinamis (tours/hotels/attractions/transfers/trains/esim/blog + statis).
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [];
$add = function ($loc, $priority = '0.8', $changefreq = 'weekly') use (&$urls) {
    $urls[] = ['loc' => $loc, 'priority' => $priority, 'changefreq' => $changefreq];
};

$add(BASE_URL . '/', '1.0', 'daily');
foreach (['tours.php','hotels.php','flights.php','ferries.php','trains.php','transfers.php','attractions.php','esim.php','rental-cars.php','faq.php','collection.php','blog.php'] as $p) {
    $add(BASE_URL . '/' . $p, '0.9');
}

$addS = function ($table, $page, $prio = '0.7') use (&$add) {
    try {
        $rows = db()->query("SELECT slug FROM `$table` WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $slug) $add(BASE_URL . '/' . $page . '?slug=' . urlencode($slug), $prio);
    } catch (Throwable $e) {}
};
$addS('tours', 'tour-detail.php', '0.8');
$addS('hotels', 'hotel-detail.php');
$addS('attractions', 'attraction-detail.php');
$addS('transfers', 'transfer-detail.php');
$addS('trains', 'train-detail.php');
$addS('rental_cars', 'rental-car-detail.php');
$addS('connectivity_products', 'esim-detail.php');
try {
    $blogRows = db()->query("SELECT slug FROM posts WHERE status = 'published'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($blogRows as $slug) $add(BASE_URL . '/blog-detail.php?slug=' . urlencode($slug), '0.6');
} catch (Throwable $e) {}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
    <url>
        <loc><?= htmlspecialchars($u['loc']) ?></loc>
        <priority><?= $u['priority'] ?></priority>
        <changefreq><?= $u['changefreq'] ?></changefreq>
    </url>
<?php endforeach; ?>
</urlset>

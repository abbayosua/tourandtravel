<?php
/**
 * tours-ajax.php — AJAX pagination for tours listing.
 * Returns HTML fragment of tour cards for infinite scroll.
 *
 * GET page=2&category=&search=&... → HTML fragment
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/components/tour-card.php';

header('Content-Type: text/html; charset=utf-8');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$priceRange = $_GET['harga'] ?? null;
$duration = $_GET['durasi'] ?? null;
$rating = $_GET['rating'] ?? null;
$sort = $_GET['sort'] ?? null;
$minPrice = $_GET['min_price'] ?? null;
$maxPrice = $_GET['max_price'] ?? null;
$departure = $_GET['departure'] ?? null;

$result = getTours($category, $search, $priceRange, $duration, $rating, $sort, $page, $limit, $minPrice, $maxPrice, $departure);
$tours = $result['tours'];

$wishlistIds = [];
if (isLoggedIn()) {
    $wishlistIds = getWishlistIds($_SESSION['user_id']);
}

ob_start();
if (count($tours) > 0):
?>
<div class="row g-3" data-page="<?= $page ?>">
    <?php foreach ($tours as $tour): ?>
        <?php renderTourCard($tour, $wishlistIds); ?>
    <?php endforeach; ?>
</div>
<?php
else:
?>
<div class="text-center py-4 text-muted" data-empty="true">
    <i class="bi bi-search fs-1"></i>
    <p class="mt-2"><?= t('Semua tour sudah dimuat.') ?></p>
</div>
<?php
endif;

echo ob_get_clean();

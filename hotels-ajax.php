<?php
/**
 * hotels-ajax.php — AJAX pagination for hotels listing.
 * Returns HTML fragment of hotel cards for infinite scroll.
 *
 * GET page=2&city=&checkin=&... → HTML fragment
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$city = $_GET['city'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = (int)($_GET['guests'] ?? 2);
$stars = $_GET['stars'] ?? '';
$sort = $_GET['sort'] ?? 'price';
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$amenitiesRaw = $_GET['amenity'] ?? [];
$amenities = array_values(array_filter(is_array($amenitiesRaw) ? array_map('trim', $amenitiesRaw) : explode(',', (string)$amenitiesRaw), fn($v) => $v !== ''));
$validAmenities = ['WiFi', 'Kolam', 'Parkir', 'Sarapan', 'Gym', 'Spa', 'Restoran'];
$amenities = array_values(array_intersect($validAmenities, $amenities));

$sql = "SELECT * FROM hotels WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city LIKE ?"; $params[] = "%$city%"; }
if ($stars) { $sql .= " AND star_rating = ?"; $params[] = (int)$stars; }
if ($minPrice !== '') { $sql .= " AND price_per_night >= ?"; $params[] = (float)$minPrice; }
if ($maxPrice !== '') { $sql .= " AND price_per_night <= ?"; $params[] = (float)$maxPrice; }
if (count($amenities)) {
    foreach ($amenities as $am) {
        $sql .= " AND amenities LIKE ?";
        $params[] = "%$am%";
    }
}
$sql .= match($sort) {
    'price' => " ORDER BY price_per_night ASC",
    'price_desc' => " ORDER BY price_per_night DESC",
    'stars' => " ORDER BY star_rating DESC, price_per_night ASC",
    default => " ORDER BY price_per_night ASC"
};
$sql .= " LIMIT $limit OFFSET $offset";

$hotelStmt = db()->prepare($sql);
$hotelStmt->execute($params);
$hotels = $hotelStmt->fetchAll();

$hotelWishlistIds = isLoggedIn() ? (getUserWishlistItems($_SESSION['user_id'])['hotel'] ?? []) : [];

ob_start();
if (count($hotels) > 0):
foreach ($hotels as $h):
    $flashH = getFlashSalePrice((float)$h['price_per_night'], 'hotel', (int)$h['id']);
    $flashSaleH = $flashH['flash'];
    $displayPriceH = $flashH['price'];
    $linkParams = 'slug=' . e($h['slug']) . '&checkin=' . urlencode($checkin ?: date('Y-m-d')) . '&checkout=' . urlencode($checkout ?: date('Y-m-d', strtotime('+2 days'))) . '&guests=' . $guests;
?>
<div class="col-12" data-page="<?= $page ?>">
    <div class="card border-0 shadow-sm mb-3 overflow-hidden klook-hover-card position-relative">
        <button class="btn btn-sm position-absolute top-0 end-0 m-1 like-btn wishlist-btn klook-wishlist-btn text-white bg-dark bg-opacity-25" style="z-index:5;"
            onclick="toggleWishlist(this, <?= (int)$h['id'] ?>, 'hotel')" title="<?= t('Simpan ke wishlist') ?>">
            <i class="bi bi-heart<?= in_array((int)$h['id'], $hotelWishlistIds) ? '-fill text-danger' : '' ?>"></i>
        </button>
        <div class="row g-0">
            <div class="col-md-3 col-4" style="min-height: 160px;">
                <img src="https://placehold.co/640x480?text=<?= urlencode($h['name']) ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($h['name']) ?>" loading="lazy">
            </div>
            <div class="col-md-9 col-8">
                <div class="card-body p-3 d-flex flex-column h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-semibold mb-1"><?= e(tContent($h, 'name')) ?></h6>
                            <div class="small text-warning mb-1"><?= str_repeat('★', $h['star_rating']) ?></div>
                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($h['city']) ?></small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-primary fs-5" data-testid="card-price"><?= formatRupiah($displayPriceH) ?></span>
                            <?php if ($flashSaleH): ?>
                                <small class="text-decoration-line-through text-muted d-block" style="font-size: 11px;"><?= formatRupiah($h['price_per_night']) ?></small>
                                <span class="badge bg-danger" style="font-size: 10px;">-<?= (int)$flashSaleH['discount_percent'] ?>%</span>
                                <?php if ($flashSaleH['stock_limit'] !== null): ?><small class="d-block text-danger" style="font-size: 11px;" data-testid="card-flash-stock"><?= t('Sisa') ?> <?= max(0, (int)$flashSaleH['stock_limit'] - (int)$flashSaleH['sold_count']) ?> <?= t('slot') ?></small><?php endif; ?>
                                <small class="d-block text-muted flash-countdown" data-deadline="<?= e(date('c', strtotime($flashSaleH['ends_at']))) ?>" data-testid="card-countdown"></small>
                            <?php endif; ?>
                            <small class="d-block text-muted" style="font-size: 11px;"><?= t('/malam termasuk pajak') ?></small>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <a href="hotel-detail.php?<?= $linkParams ?>" class="btn btn-primary rounded-pill px-4 py-1" style="font-size: 13px;"><?= t('Pesan') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
endforeach;
else:
?>
<div class="text-center py-4 text-muted" data-empty="true">
    <i class="bi bi-building fs-1"></i>
    <p class="mt-2"><?= t('Semua hotel sudah dimuat.') ?></p>
</div>
<?php
endif;

echo ob_get_clean();

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/hotelapi.php';
require_once 'includes/components/live-hotel-card.php';

$pageTitle = t('Hotel');
$city = $_GET['city'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = (int)($_GET['guests'] ?? 2);
$starsRaw = $_GET['stars'] ?? '';
$stars = in_array($starsRaw, ['1', '2', '3', '4', '5'], true) ? $starsRaw : '';
$sort = $_GET['sort'] ?? 'price';
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$amenitiesRaw = $_GET['amenity'] ?? [];
$amenities = array_values(array_filter(is_array($amenitiesRaw) ? array_map('trim', $amenitiesRaw) : explode(',', (string)$amenitiesRaw), fn($v) => $v !== ''));
$validAmenities = ['WiFi', 'Kolam', 'Parkir', 'Sarapan', 'Gym', 'Spa', 'Restoran'];
$amenities = array_values(array_intersect($validAmenities, $amenities));
$freeCancel = (int)($_GET['free_cancel'] ?? 0);
$instantConf = (int)($_GET['instant'] ?? 0);
$bestSeller = (int)($_GET['best'] ?? 0);

$cities = db()->query("SELECT DISTINCT city FROM hotels WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

$whereSql = '';
$params = [];
if ($city) { $whereSql .= " AND city LIKE ?"; $params[] = "%$city%"; }
if ($stars) { $whereSql .= " AND star_rating = ?"; $params[] = (int)$stars; }
if ($minPrice !== '') { $whereSql .= " AND price_per_night >= ?"; $params[] = (float)$minPrice; }
if ($maxPrice !== '') { $whereSql .= " AND price_per_night <= ?"; $params[] = (float)$maxPrice; }
if (count($amenities)) {
    foreach ($amenities as $am) {
        $whereSql .= " AND amenities LIKE ?";
        $params[] = "%$am%";
    }
}
if ($freeCancel) { $whereSql .= " AND free_cancellation = 1"; }
if ($instantConf) { $whereSql .= " AND instant_confirmation = 1"; }
if ($bestSeller) { $whereSql .= " AND best_seller = 1"; }
$sql = "SELECT * FROM hotels WHERE is_active = 1" . $whereSql . match($sort) {
    'price' => " ORDER BY price_per_night ASC",
    'price_desc' => " ORDER BY price_per_night DESC",
    'stars' => " ORDER BY star_rating DESC, price_per_night ASC",
    default => " ORDER BY price_per_night ASC"
};

$perPage = 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$totalHotels = db()->prepare("SELECT COUNT(*) FROM hotels WHERE is_active = 1" . $whereSql);
$totalHotels->execute($params);
$totalHotels = (int)$totalHotels->fetchColumn();
$lastPage = max(1, (int)ceil($totalHotels / $perPage));
$sql .= " LIMIT $perPage OFFSET " . (($currentPage - 1) * $perPage);

$hotels = db()->prepare($sql);
$hotels->execute($params);
$hotels = $hotels->fetchAll();

// Live hotel API (Booking.com/OYO/NusaTrip) — primary saat kota dicari.
$usingLive = false;
$liveHotels = [];
$liveSource = null;
$liveError = null;
$liveEnabled = function_exists('hotelApiEnabled') && hotelApiEnabled();
if ($city !== '' && $liveEnabled) {
    // Fetch SEMUA hotel kota sekali; filter (bintang/harga) & sort dilakukan di klien
    // (JS #hotelFilterForm) agar ganti filter tidak memanggil endpoint lagi.
    $live = hotelApiSearch($city, [
        'checkin' => $checkin,
        'checkout' => $checkout,
        'guests' => $guests,
    ]);
    if (!empty($live['hotels'])) {
        $liveHotels = $live['hotels'];
        $usingLive = true;
        $liveSource = $live['source'] ?? 'live';
    } else {
        $liveError = $live['error'] ?? null;
    }
}
$displayHotels = $usingLive ? $liveHotels : $hotels;

$hotelWishlistIds = isLoggedIn() ? (getUserWishlistItems($_SESSION['user_id'])['hotel'] ?? []) : [];
$hotelSearched = ($city !== '') || ($stars !== '') || ($minPrice !== '') || ($maxPrice !== '') || count($amenities) || $freeCancel || $instantConf || $bestSeller || ($checkin !== '') || ($checkout !== '') || ($guests !== 2) || ((int)($_GET['page'] ?? 1) > 1) || (($sort ?? 'price') !== 'price');
require_once 'includes/components/breadcrumb.php';
require_once 'includes/components/hero-loader.php';
$heroSlides = getHeroSlides('hotel');
require_once 'includes/header-shared.php';
require __DIR__ . '/includes/homepage/hotel-hero.php';
?>
<?php if ($hotelSearched): ?>
<section class="py-4 bg-light">
    <div class="container">

        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET" id="hotelFilterForm" class="row g-2 align-items-end" onsubmit="event.preventDefault(); onHotelFilterChange();">
                                <input type="hidden" name="city" value="<?= e($city) ?>">
                                <input type="hidden" name="checkin" value="<?= e($checkin ?: date('Y-m-d')) ?>">
                                <input type="hidden" name="checkout" value="<?= e($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>">
                                <input type="hidden" name="guests" value="<?= $guests ?>">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Bintang') ?></label>
                                    <div class="d-flex flex-wrap gap-2" data-testid="stars-filter">
                                        <?php for ($s=5; $s>=3; $s--): ?>
                                        <input type="radio" class="btn-check" name="stars" id="star<?= $s ?>" value="<?= $s ?>" <?= $stars == $s ? 'checked' : '' ?> onchange="onHotelFilterChange()">
                                        <label class="btn btn-sm btn-outline-warning rounded-pill" for="star<?= $s ?>"><?= str_repeat('★', $s) ?></label>
                                        <?php endfor; ?>
                                        <input type="radio" class="btn-check" name="stars" id="starAll" value="" <?= $stars === '' ? 'checked' : '' ?> onchange="onHotelFilterChange()">
                                        <label class="btn btn-sm btn-outline-secondary rounded-pill" for="starAll"><?= t('Semua') ?></label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Harga per Malam') ?></label>
                                    <div class="mb-1 d-flex justify-content-between small fw-semibold">
                                        <span id="hPriceMinLabel">Rp <?= number_format((float)($minPrice ?: 0), 0, ',', '.') ?></span>
                                        <span class="text-muted fw-normal">–</span>
                                        <span id="hPriceMaxLabel">Rp <?= number_format((float)($maxPrice ?: 5000000), 0, ',', '.') ?></span>
                                    </div>
                                    <div class="h-dual-range">
                                        <div class="h-dual-track"><div class="h-dual-fill" id="hPriceFill"></div></div>
                                        <input type="range" class="form-range h-dual-input" id="hPriceMinRange" min="0" max="5000000" step="250000" value="<?= (int)($minPrice ?: 0) ?>" data-testid="hotel-price-min-range" aria-label="<?= t('Harga minimum') ?>">
                                        <input type="range" class="form-range h-dual-input" id="hPriceMaxRange" min="0" max="5000000" step="250000" value="<?= (int)($maxPrice ?: 5000000) ?>" data-testid="hotel-price-max-range" aria-label="<?= t('Harga maksimum') ?>">
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mt-1">
                                        <span><i class="bi bi-dash-circle me-1"></i><?= t('Min') ?></span>
                                        <span><?= t('Maks') ?><i class="bi bi-plus-circle ms-1"></i></span>
                                    </div>
                                    <input type="hidden" name="min_price" id="hPriceMinInput" value="<?= e($minPrice) ?>">
                                    <input type="hidden" name="max_price" id="hPriceMaxInput" value="<?= e($maxPrice) ?>">
                                </div>
                                <?php if (!$usingLive): ?>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Fasilitas') ?></label>
                                    <div class="row g-1" data-testid="amenities-filter">
                                        <?php foreach (['Kolam', 'Parkir', 'WiFi', 'Sarapan', 'Gym', 'Spa', 'Restoran'] as $am): ?>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="amenity[]" value="<?= $am ?>" id="am<?= md5($am) ?>" <?= in_array($am, $amenities, true) ? 'checked' : '' ?> onchange="onHotelFilterChange()">
                                                <label class="form-check-label small" for="am<?= md5($am) ?>"><?= t($am) ?></label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="best" value="1" id="fltBest" <?= $bestSeller ? 'checked' : '' ?> onchange="onHotelFilterChange()">
                                        <label class="form-check-label small" for="fltBest"><?= t('Best Seller') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="free_cancel" value="1" id="fltCancel" <?= $freeCancel ? 'checked' : '' ?> onchange="onHotelFilterChange()">
                                        <label class="form-check-label small" for="fltCancel"><?= t('Batal Gratis') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="instant" value="1" id="fltInstant" <?= $instantConf ? 'checked' : '' ?> onchange="onHotelFilterChange()">
                                        <label class="form-check-label small" for="fltInstant"><?= t('Konfirmasi Instan') ?></label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Urutkan') ?></label>
                                    <select name="sort" class="form-select form-select-sm" onchange="onHotelFilterChange()">
                                        <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>><?= t('Harga Termurah') ?></option>
                                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= t('Harga Termahal') ?></option>
                                        <option value="stars" <?= $sort === 'stars' ? 'selected' : '' ?>><?= t('Bintang Tertinggi') ?></option>
                                    </select>
                                </div>
                                <div class="col-12 d-grid mt-3">
                                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search me-1"></i><?= t('Cari') ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results: Agoda list view -->
            <div class="col-lg-9">
                <!-- Skeleton Loading (shown initially, hidden after content loads) -->
                <div id="hotelSkeleton">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="card border-0 shadow-sm mb-3 overflow-hidden">
                        <div class="row g-0">
                            <div class="col-md-3 col-4">
                                <div class="skeleton skeleton-img" style="min-height: 160px;"></div>
                            </div>
                            <div class="col-md-9 col-8">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="skeleton skeleton-text" style="width: 60%; height: 16px;"></div>
                                            <div class="skeleton skeleton-text" style="width: 30%; height: 12px;"></div>
                                            <div class="skeleton skeleton-text" style="width: 40%; height: 12px;"></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="skeleton skeleton-text" style="width: 80px; height: 20px; margin-left: auto;"></div>
                                            <div class="skeleton skeleton-text" style="width: 60px; height: 12px; margin-left: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 mt-3">
                                        <div class="skeleton skeleton-text" style="width: 60px; height: 18px;"></div>
                                        <div class="skeleton skeleton-text" style="width: 50px; height: 18px;"></div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="skeleton skeleton-btn"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Actual Content (hidden initially, shown after load) -->
                <div id="hotelContent" style="display: none;">
                <?php if (count($displayHotels) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><span id="hotelResultCount"><?= count($displayHotels) ?></span> <?= t('hotel ditemukan') ?></small>
                    <div class="d-flex gap-1">
                        <?php if ($usingLive): ?>
                        <button type="button" data-hotel-sort="price" onclick="sortLiveHotels('price')" class="btn btn-sm <?= $sort === 'price' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termurah') ?></button>
                        <button type="button" data-hotel-sort="price_desc" onclick="sortLiveHotels('price_desc')" class="btn btn-sm <?= $sort === 'price_desc' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termahal') ?></button>
                        <button type="button" data-hotel-sort="stars" onclick="sortLiveHotels('stars')" class="btn btn-sm <?= $sort === 'stars' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Bintang Tertinggi') ?></button>
                        <?php else: ?>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price']))) ?>" class="btn btn-sm <?= $sort === 'price' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termurah') ?></a>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price_desc']))) ?>" class="btn btn-sm <?= $sort === 'price_desc' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termahal') ?></a>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'stars']))) ?>" class="btn btn-sm <?= $sort === 'stars' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Bintang Tertinggi') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($usingLive): ?>
                    <?php foreach ($liveHotels as $lh) renderLiveHotelCard($lh, $city, $checkin, $checkout, $guests); ?>
                <?php else: ?>
                <?php foreach ($hotels as $h):
                    $amenities = array_filter(array_map('trim', explode(',', $h['amenities'] ?? '')));
                    $linkParams = 'slug=' . e($h['slug']) . '&checkin=' . urlencode($checkin ?: date('Y-m-d')) . '&checkout=' . urlencode($checkout ?: date('Y-m-d', strtotime('+2 days'))) . '&guests=' . $guests;
                    $flashH = getFlashSalePrice((float)$h['price_per_night'], 'hotel', (int)$h['id']);
                    $flashSaleH = $flashH['flash'];
                    $displayPriceH = $flashH['price'];
                ?>
                <div class="card border-0 shadow-sm mb-3 overflow-hidden klook-hover-card position-relative">
                    <button class="btn btn-sm position-absolute top-0 end-0 m-1 like-btn wishlist-btn klook-wishlist-btn text-white bg-dark bg-opacity-25" style="z-index:5;"
                        onclick="return toggleWishlist(this, <?= (int)$h['id'] ?>, 'hotel', event)" title="<?= t('Simpan ke wishlist') ?>">
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
                                        <span class="fw-bold text-primary fs-5" data-testid="card-price"><?= formatCurrencySpan($displayPriceH, 'IDR') ?></span>
                                        <?php if ($flashSaleH): ?>
                                            <small class="text-decoration-line-through text-muted d-block" style="font-size: 11px;"><?= formatCurrencySpan($h['price_per_night'], 'IDR') ?></small>
                                            <span class="badge bg-danger" style="font-size: 10px;">-<?= (int)$flashSaleH['discount_percent'] ?>%</span>
                                            <?php if ($flashSaleH['stock_limit'] !== null): ?><small class="d-block text-danger" style="font-size: 11px;" data-testid="card-flash-stock"><?= t('Sisa') ?> <?= max(0, (int)$flashSaleH['stock_limit'] - (int)$flashSaleH['sold_count']) ?> <?= t('slot') ?></small><?php endif; ?>
                                            <small class="d-block text-muted flash-countdown" data-deadline="<?= e(date('c', strtotime($flashSaleH['ends_at']))) ?>" data-testid="card-countdown"></small>
                                        <?php endif; ?>
                                        <small class="d-block text-muted" style="font-size: 11px;"><?= t('/malam termasuk pajak') ?></small>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <?php if (!empty($h['best_seller'])): ?><span class="badge bg-danger" style="font-size: 10px;"><?= t('Best Seller') ?></span><?php endif; ?>
                                    <?php if (!empty($h['instant_confirmation'])): ?><span class="badge bg-success" style="font-size: 10px;"><i class="bi bi-lightning-charge-fill me-1"></i><?= t('Instan') ?></span><?php endif; ?>
                                    <?php if (!empty($h['free_cancellation'])): ?><span class="badge bg-info text-white" style="font-size: 10px;"><i class="bi bi-shield-check me-1"></i><?= t('Batal Gratis') ?></span><?php endif; ?>
                                </div>
                                <?php if (count($amenities) > 0): ?>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <?php foreach (array_slice($amenities, 0, 5) as $am): ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 10px;"><?= e(trim($am)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="mt-auto pt-2">
                                    <a href="hotel-detail.php?<?= $linkParams ?>" class="btn btn-primary rounded-pill px-4 py-1" style="font-size: 13px;"><?= t('Pesan') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada hotel ditemukan.') ?></p>
                    <a href="hotels.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a>
                </div>
                <?php endif; ?>
                </div>
                <?php if (!$usingLive && $lastPage > $currentPage): ?>
                <div class="load-more-trigger text-center py-4" data-page="<?= $currentPage ?>" data-last-page="<?= $lastPage ?>" data-testid="hotel-load-more">
                    <div class="load-more-spinner spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?= t('Loading...') ?></span>
                    </div>
                    <div class="load-more-error d-none" data-load-error="true">
                        <i class="bi bi-wifi-off fs-3 text-muted"></i>
                        <p class="mt-2 mb-2 text-muted small"><?= t('Gagal memuat hotel. Periksa koneksi Anda.') ?></p>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-load-retry><?= t('Coba Lagi') ?></button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Peta harga -->
<?php if ($hotelSearched): ?>
<section class="pb-4 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-2"><i class="bi bi-geo-alt me-1"></i><?= t('Peta harga hotel') ?></h6>
                <?php
                require_once 'includes/components/map-leaflet.php';
                $mapPoints = [];
                foreach ($displayHotels as $h) {
                    if (empty($h['lat']) || empty($h['lng'])) continue;
                    if ($usingLive) {
                        $mapPoints[] = ['lat' => (float)$h['lat'], 'lng' => (float)$h['lng'], 'label' => (string)($h['name'] ?? ''), 'price' => (string)($h['price_formatted'] ?? ''), 'link' => 'hotel-detail.php?' . http_build_query(['live' => 1, 'src' => $h['source'] ?? '', 'city' => $city, 'id' => $h['external_id'] ?? ''])];
                    } else {
                        $mapPoints[] = ['lat' => (float)$h['lat'], 'lng' => (float)$h['lng'], 'label' => tContent($h, 'name'), 'price' => formatRupiah($h['price_per_night']), 'link' => 'hotel-detail.php?slug=' . urlencode($h['slug'])];
                    }
                }
                renderMap('hotelsMap', $mapPoints, -2.5, 118.0, 5);
                ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php require_once 'includes/footer-shared.php'; ?>
<style>
.h-dual-range { position: relative; height: 28px; }
.h-dual-track { position: absolute; top: 50%; left: 0; right: 0; height: 6px; transform: translateY(-50%); background: #dee2e6; border-radius: 999px; }
.h-dual-fill { position: absolute; top: 0; bottom: 0; background: #0d6efd; border-radius: 999px; }
.h-dual-input { position: absolute !important; top: 0; left: 0; width: 100%; height: 28px; margin: 0; background: transparent; pointer-events: none; -webkit-appearance: none; appearance: none; }
.h-dual-input::-webkit-slider-runnable-track { background: transparent; height: 28px; }
.h-dual-input::-webkit-slider-thumb { pointer-events: auto; -webkit-appearance: none; width: 18px; height: 18px; margin-top: 5px; border-radius: 50%; background: #fff; border: 2px solid #0d6efd; box-shadow: 0 1px 3px rgba(0,0,0,.25); cursor: grab; }
.h-dual-input::-moz-range-track { background: transparent; }
.h-dual-input::-moz-range-thumb { pointer-events: auto; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 2px solid #0d6efd; cursor: grab; }
.h-dual-input:focus { box-shadow: none; }
</style>
<script>
// Show skeleton initially, then reveal content
document.addEventListener('DOMContentLoaded', function() {
    var skeleton = document.getElementById('hotelSkeleton');
    var content = document.getElementById('hotelContent');
    if (skeleton && content) {
        // Small delay to show skeleton effect
        setTimeout(function() {
            skeleton.style.display = 'none';
            content.style.display = 'block';
        }, 300);
    }
});
</script>
<script>
(function() {
    var minR = document.getElementById('hPriceMinRange'), maxR = document.getElementById('hPriceMaxRange');
    var minI = document.getElementById('hPriceMinInput'), maxI = document.getElementById('hPriceMaxInput');
    var minL = document.getElementById('hPriceMinLabel'), maxL = document.getElementById('hPriceMaxLabel');
    var fill = document.getElementById('hPriceFill');
    if (!minR || !maxR) return;
    var ABS_MIN = parseInt(minR.min, 10) || 0;
    var ABS_MAX = parseInt(minR.max, 10) || 5000000;
    var GAP = parseInt(minR.step, 10) || 250000;
    var loc = (window.I18N && window.I18N.locale) || 'id-ID';
    var fmt = function(n) { return 'Rp ' + Number(n).toLocaleString(loc); };
    function sync(from) {
        var lo = parseInt(minR.value, 10), hi = parseInt(maxR.value, 10);
        // Jepit agar tidak saling lewat (sisakan 1 step gap)
        if (lo > hi - GAP) {
            if (from === 'min') { lo = hi - GAP; if (lo < ABS_MIN) lo = ABS_MIN; minR.value = lo; }
            else { hi = lo + GAP; if (hi > ABS_MAX) hi = ABS_MAX; maxR.value = hi; }
        }
        minI.value = lo <= ABS_MIN ? '' : lo;
        maxI.value = hi >= ABS_MAX ? '' : hi;
        minL.textContent = fmt(lo); maxL.textContent = fmt(hi);
        if (fill) {
            var pLo = ((lo - ABS_MIN) / (ABS_MAX - ABS_MIN)) * 100;
            var pHi = ((hi - ABS_MIN) / (ABS_MAX - ABS_MIN)) * 100;
            fill.style.left = pLo + '%'; fill.style.width = Math.max(0, pHi - pLo) + '%';
        }
        // Pastikan thumb min selalu di atas saat overlap di ujung kiri
        minR.style.zIndex = (lo > ABS_MAX - (ABS_MAX - ABS_MIN) / 2) ? '4' : '5';
        maxR.style.zIndex = '3';
    }
    minR.addEventListener('input', function() { sync('min'); if (window.HOTEL_LIVE) applyHotelFilter(); });
    maxR.addEventListener('input', function() { sync('max'); if (window.HOTEL_LIVE) applyHotelFilter(); });
    minR.addEventListener('change', onHotelFilterChange);
    maxR.addEventListener('change', onHotelFilterChange);
    sync();
})();
</script>
<script>
// Infinite Scroll with IntersectionObserver (port dari tours.php)
document.addEventListener('DOMContentLoaded', function() {
    var loadMoreTrigger = document.querySelector('.load-more-trigger');
    if (loadMoreTrigger) {
        var currentPage = parseInt(loadMoreTrigger.dataset.page);
        var lastPage = parseInt(loadMoreTrigger.dataset.lastPage);
        var spinner = loadMoreTrigger.querySelector('.load-more-spinner');
        var errorBox = loadMoreTrigger.querySelector('.load-more-error');
        var retryBtn = loadMoreTrigger.querySelector('[data-load-retry]');
        var loading = false;
        var failed = false;

        function loadNextPage() {
            if (loading || failed || currentPage >= lastPage) return;
            loading = true;
            if (spinner) spinner.classList.remove('d-none');
            if (errorBox) errorBox.classList.add('d-none');
            currentPage++;

            var params = new URLSearchParams(window.location.search);
            params.set('page', currentPage);
            var ajaxUrl = 'hotels-ajax.php?' + params.toString();

            fetch(ajaxUrl)
                .then(function(response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.text();
                })
                .then(function(html) {
                    var temp = document.createElement('div');
                    temp.innerHTML = html;
                    if (temp.querySelector('[data-empty]')) {
                        loadMoreTrigger.remove();
                        loading = false;
                        return;
                    }
                    var grid = document.getElementById('hotelContent');
                    if (grid) {
                        grid.insertAdjacentHTML('beforeend', temp.innerHTML);
                    }
                    loadMoreTrigger.dataset.page = currentPage;
                    if (currentPage >= lastPage) {
                        loadMoreTrigger.remove();
                    }
                    loading = false;
                })
                .catch(function() {
                    currentPage--;
                    loading = false;
                    failed = true;
                    if (spinner) spinner.classList.add('d-none');
                    if (errorBox) errorBox.classList.remove('d-none');
                });
        }

        if (retryBtn) {
            retryBtn.addEventListener('click', function() {
                failed = false;
                loadNextPage();
            });
        }

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) loadNextPage();
            });
        }, { rootMargin: '200px' });

        observer.observe(loadMoreTrigger);
    }
});
</script>
<script>
// ===== Filter & sort sisi klien untuk hasil live NusaTrip/OYO =====
// Daftar hotel di-fetch SEKALI saat kota dicari, lalu filter (bintang/harga) & sort
// di browser tanpa memanggil endpoint lagi.
var HOTEL_LIVE = <?= $usingLive ? 'true' : 'false' ?>;
window.HOTEL_LIVE = HOTEL_LIVE;

function hotelFilterState() {
    var form = document.getElementById('hotelFilterForm');
    if (!form) return null;
    var starEl = form.querySelector('input[name="stars"]:checked');
    var minEl = form.querySelector('input[name="min_price"]');
    var maxEl = form.querySelector('input[name="max_price"]');
    return {
        stars: starEl && starEl.value !== '' ? parseInt(starEl.value, 10) : 0,
        min: minEl && minEl.value !== '' ? parseFloat(minEl.value) : 0,
        max: maxEl && maxEl.value !== '' ? parseFloat(maxEl.value) : Infinity
    };
}

function hotelMatches(el, f) {
    if (f.stars > 0 && (parseInt(el.dataset.hotelStar, 10) || 0) !== f.stars) return false;
    var price = parseFloat(el.dataset.hotelPrice || '0') || 0;
    if (price > 0 && (price < f.min || price > f.max)) return false;
    return true;
}

function applyHotelFilter() {
    var f = hotelFilterState();
    if (!f) return;
    var visible = 0;
    document.querySelectorAll('#hotelContent .js-live-hotel').forEach(function (el) {
        var ok = hotelMatches(el, f);
        el.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    var cnt = document.getElementById('hotelResultCount');
    if (cnt) cnt.textContent = visible;
}

function onHotelFilterChange() {
    var form = document.getElementById('hotelFilterForm');
    if (HOTEL_LIVE) {
        applyHotelFilter();
        var sel = form ? form.querySelector('select[name="sort"]') : null;
        sortLiveHotels(sel ? sel.value : 'price');
    } else if (form) {
        form.submit();
    }
}

function sortLiveHotels(mode) {
    var content = document.getElementById('hotelContent');
    if (!content) return;
    Array.prototype.slice.call(content.querySelectorAll('.js-live-hotel')).sort(function (a, b) {
        var pa = parseFloat(a.dataset.hotelPrice || '0') || 0, pb = parseFloat(b.dataset.hotelPrice || '0') || 0;
        var sa = parseInt(a.dataset.hotelStar, 10) || 0, sb = parseInt(b.dataset.hotelStar, 10) || 0;
        if (mode === 'price_desc') return pb - pa;
        if (mode === 'stars') return (sb - sa) || (pa - pb);
        return pa - pb;
    }).forEach(function (el) { content.appendChild(el); });
    document.querySelectorAll('[data-hotel-sort]').forEach(function (b) {
        var active = b.dataset.hotelSort === mode;
        b.classList.toggle('btn-primary', active);
        b.classList.toggle('btn-outline-secondary', !active);
    });
    var form = document.getElementById('hotelFilterForm');
    var sel = form ? form.querySelector('select[name="sort"]') : null;
    if (sel && sel.value !== mode) sel.value = mode;
}

document.addEventListener('DOMContentLoaded', function () {
    if (!HOTEL_LIVE) return;
    applyHotelFilter();
    var form = document.getElementById('hotelFilterForm');
    var sel = form ? form.querySelector('select[name="sort"]') : null;
    sortLiveHotels(sel ? sel.value : 'price');
});
</script>
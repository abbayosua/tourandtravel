<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Hotel';
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

$hotelWishlistIds = isLoggedIn() ? (getUserWishlistItems($_SESSION['user_id'])['hotel'] ?? []) : [];
require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4 bg-light">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Hotel'), 'url' => null]]); ?>

        <!-- Agoda-style search bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <form method="GET" class="row g-2 g-md-3 align-items-end">
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Kota') ?></div>
                            <input type="text" name="city" class="form-control" placeholder="<?= t('Cari kota...') ?>" value="<?= e($city) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Check-in') ?></div>
                            <input type="date" name="checkin" class="form-control" value="<?= e($checkin ?: date('Y-m-d')) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Check-out') ?></div>
                            <input type="date" name="checkout" class="form-control" value="<?= e($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Tamu') ?></div>
                            <select name="guests" class="form-select">
                                <?php for ($g=1; $g<=10; $g++): ?>
                                <option value="<?= $g ?>" <?= $guests === $g ? 'selected' : '' ?>><?= $g ?> <?= t('Tamu') ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-auto d-grid">
                        <button class="btn btn-primary traveloka-search-btn px-4" type="submit"><i class="bi bi-search me-1"></i><?= t('Cari') ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET" class="row g-2 align-items-end">
                                <input type="hidden" name="city" value="<?= e($city) ?>">
                                <input type="hidden" name="checkin" value="<?= e($checkin ?: date('Y-m-d')) ?>">
                                <input type="hidden" name="checkout" value="<?= e($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>">
                                <input type="hidden" name="guests" value="<?= $guests ?>">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Bintang') ?></label>
                                    <div class="d-flex flex-wrap gap-2" data-testid="stars-filter">
                                        <?php for ($s=5; $s>=3; $s--): ?>
                                        <input type="radio" class="btn-check" name="stars" id="star<?= $s ?>" value="<?= $s ?>" <?= $stars == $s ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="btn btn-sm btn-outline-warning rounded-pill" for="star<?= $s ?>"><?= str_repeat('★', $s) ?></label>
                                        <?php endfor; ?>
                                        <input type="radio" class="btn-check" name="stars" id="starAll" value="" <?= $stars === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="btn btn-sm btn-outline-secondary rounded-pill" for="starAll"><?= t('Semua') ?></label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Harga per Malam') ?></label>
                                    <div class="mb-1 d-flex justify-content-between small text-muted">
                                        <span id="hPriceMinLabel">Rp <?= number_format((float)($minPrice ?: 0), 0, ',', '.') ?></span>
                                        <span id="hPriceMaxLabel">Rp <?= number_format((float)($maxPrice ?: 5000000), 0, ',', '.') ?></span>
                                    </div>
                                    <input type="range" class="form-range" id="hPriceMinRange" min="0" max="5000000" step="250000" value="<?= (int)($minPrice ?: 0) ?>" data-testid="hotel-price-min-range">
                                    <input type="range" class="form-range" id="hPriceMaxRange" min="0" max="5000000" step="250000" value="<?= (int)($maxPrice ?: 5000000) ?>" data-testid="hotel-price-max-range">
                                    <input type="hidden" name="min_price" id="hPriceMinInput" value="<?= e($minPrice) ?>">
                                    <input type="hidden" name="max_price" id="hPriceMaxInput" value="<?= e($maxPrice) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Fasilitas') ?></label>
                                    <div class="row g-1" data-testid="amenities-filter">
                                        <?php foreach (['Kolam', 'Parkir', 'WiFi', 'Sarapan', 'Gym', 'Spa', 'Restoran'] as $am): ?>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="amenity[]" value="<?= $am ?>" id="am<?= md5($am) ?>" <?= in_array($am, $amenities, true) ? 'checked' : '' ?> onchange="this.form.submit()">
                                                <label class="form-check-label small" for="am<?= md5($am) ?>"><?= t($am) ?></label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="best" value="1" id="fltBest" <?= $bestSeller ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="fltBest"><?= t('Best Seller') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="free_cancel" value="1" id="fltCancel" <?= $freeCancel ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="fltCancel"><?= t('Batal Gratis') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="instant" value="1" id="fltInstant" <?= $instantConf ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="fltInstant"><?= t('Konfirmasi Instan') ?></label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Urutkan') ?></label>
                                    <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
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
                <?php if (count($hotels) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($hotels) ?> <?= t('hotel ditemukan') ?></small>
                    <div class="d-flex gap-1">
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price']))) ?>" class="btn btn-sm <?= $sort === 'price' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termurah') ?></a>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price_desc']))) ?>" class="btn btn-sm <?= $sort === 'price_desc' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termahal') ?></a>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'stars']))) ?>" class="btn btn-sm <?= $sort === 'stars' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Bintang Tertinggi') ?></a>
                    </div>
                </div>
                <?php foreach ($hotels as $h):
                    $amenities = array_filter(array_map('trim', explode(',', $h['amenities'] ?? '')));
                    $linkParams = 'slug=' . e($h['slug']) . '&checkin=' . urlencode($checkin ?: date('Y-m-d')) . '&checkout=' . urlencode($checkout ?: date('Y-m-d', strtotime('+2 days'))) . '&guests=' . $guests;
                    $flashH = getFlashSalePrice((float)$h['price_per_night'], 'hotel', (int)$h['id']);
                    $flashSaleH = $flashH['flash'];
                    $displayPriceH = $flashH['price'];
                ?>
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
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada hotel ditemukan.') ?></p>
                    <a href="hotels.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a>
                </div>
                <?php endif; ?>
                </div>
                <?php if ($lastPage > $currentPage): ?>
                <div class="load-more-trigger text-center py-4" data-page="<?= $currentPage ?>" data-last-page="<?= $lastPage ?>" data-testid="hotel-load-more">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Peta harga -->
<section class="pb-4 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-semibold mb-2"><i class="bi bi-geo-alt me-1"></i><?= t('Peta harga hotel') ?></h6>
                <?php
                require_once 'includes/components/map-leaflet.php';
                $mapPoints = [];
                foreach ($hotels as $h) {
                    if ($h['lat'] === null || $h['lng'] === null) continue;
                    $mapPoints[] = ['lat' => (float)$h['lat'], 'lng' => (float)$h['lng'], 'label' => tContent($h, 'name'), 'price' => formatRupiah($h['price_per_night']), 'link' => 'hotel-detail.php?slug=' . urlencode($h['slug'])];
                }
                renderMap('hotelsMap', $mapPoints, -2.5, 118.0, 5);
                ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
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
    if (!minR || !maxR) return;
    var fmt = function(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); };
    function sync() {
        var lo = parseInt(minR.value), hi = parseInt(maxR.value);
        if (lo > hi) { var t = lo; lo = hi; hi = t; }
        minI.value = lo <= 0 ? '' : lo;
        maxI.value = hi >= 5000000 ? '' : hi;
        minL.textContent = fmt(lo); maxL.textContent = fmt(hi);
    }
    minR.addEventListener('input', sync); maxR.addEventListener('input', sync);
    minR.addEventListener('change', function() { minR.closest('form').submit(); });
    maxR.addEventListener('change', function() { maxR.closest('form').submit(); });
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
        var loading = false;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && !loading && currentPage < lastPage) {
                    loading = true;
                    currentPage++;

                    var params = new URLSearchParams(window.location.search);
                    params.set('page', currentPage);
                    var ajaxUrl = 'hotels-ajax.php?' + params.toString();

                    fetch(ajaxUrl)
                        .then(function(response) { return response.text(); })
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
                            loading = false;
                        });
                }
            });
        }, { rootMargin: '200px' });

        observer.observe(loadMoreTrigger);
    }
});
</script>
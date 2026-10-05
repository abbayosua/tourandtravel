<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Paket Tour');
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$priceRange = $_GET['harga'] ?? null;
$duration = $_GET['durasi'] ?? null;
$rating = $_GET['rating'] ?? null;
$sort = $_GET['sort'] ?? null;
$minPrice = $_GET['min_price'] ?? null;
$maxPrice = $_GET['max_price'] ?? null;
$departure = $_GET['departure'] ?? null;
$page = (int)($_GET['page'] ?? 1);

$result = getTours($category, $search, $priceRange, $duration, $rating, $sort, $page, 12, $minPrice, $maxPrice, $departure);
$tours = $result['tours'];
$total = $result['total'];
$lastPage = $result['lastPage'];
$currentPage = $result['page'];

$categories = getCategories();

$durasiOptions = ['1' => t('3-5 Hari'), '2' => t('6-8 Hari'), '3' => t('9+ Hari')];
$hargaOptions = ['1' => t('< Rp 5 Juta'), '2' => t('Rp 5-10 Juta'), '3' => t('Rp 10-20 Juta'), '4' => t('> Rp 20 Juta')];
$ratingOptions = ['4.5' => '★ 4.5+', '4' => '★ 4.0+'];
$sortOptions = ['termurah' => t('Termurah'), 'termahal' => t('Termahal'), 'rating' => t('Rating Tertinggi'), 'popular' => t('Terpopuler')];
$departureOptions = ['today' => t('Hari ini'), 'tomorrow' => t('Besok'), 'week' => t('7 hari ke depan'), 'month' => t('30 hari ke depan')];
$priceFloor = 0;
$priceCeil = 25000000; // IDR
$hasAdvanced = ($minPrice !== null && $minPrice !== '') || ($maxPrice !== null && $maxPrice !== '') || ($departure !== null && $departure !== '');

$wishlistIds = [];
if (isLoggedIn()) {
    $wishlistIds = getWishlistIds($_SESSION['user_id']);
}

// Klook-style components
require_once 'includes/components/tour-card.php';
require_once 'includes/components/pagination.php';
require_once 'includes/components/breadcrumb.php';
require_once 'includes/components/page-hero.php';

require_once 'includes/header-shared.php';
renderPageHero(t('Paket Tour'), $total . ' ' . t('tour ditemukan'), [['label' => t('Paket Tour'), 'url' => 'tours.php']]);
?>
<section class="py-4">
    <div class="container">

        <div class="d-flex justify-content-end align-items-center mb-3">
            <a href="tours.php" class="btn btn-sm btn-outline-secondary rounded-pill <?= !$category && !$search && !$priceRange && !$duration && !$rating && !$sort ? 'd-none' : '' ?>">
                <i class="bi bi-x-circle me-1"></i><?= t('Reset') ?>
            </a>
        </div>

        <!-- Active Filter Chips -->
        <?php
        $activeFilters = [];
        if ($category) $activeFilters[] = ['key' => 'category', 'label' => t($category), 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['category' => 1]))];
        if ($search) $activeFilters[] = ['key' => 'search', 'label' => t('Pencarian') . ': ' . $search, 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['search' => 1]))];
        if ($priceRange && isset($hargaOptions[$priceRange])) $activeFilters[] = ['key' => 'harga', 'label' => $hargaOptions[$priceRange], 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['harga' => 1]))];
        if ($duration && isset($durasiOptions[$duration])) $activeFilters[] = ['key' => 'durasi', 'label' => $durasiOptions[$duration], 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['durasi' => 1]))];
        if ($rating && isset($ratingOptions[$rating])) $activeFilters[] = ['key' => 'rating', 'label' => $ratingOptions[$rating], 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['rating' => 1]))];
        if ($departure && isset($departureOptions[$departure])) $activeFilters[] = ['key' => 'departure', 'label' => $departureOptions[$departure], 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['departure' => 1]))];
        if ($minPrice !== null && $minPrice !== '') $activeFilters[] = ['key' => 'min_price', 'label' => t('Min') . ' Rp ' . number_format((float)$minPrice, 0, ',', '.'), 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['min_price' => 1]))];
        if ($maxPrice !== null && $maxPrice !== '') $activeFilters[] = ['key' => 'max_price', 'label' => t('Maks') . ' Rp ' . number_format((float)$maxPrice, 0, ',', '.'), 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['max_price' => 1]))];
        if ($sort && isset($sortOptions[$sort])) $activeFilters[] = ['key' => 'sort', 'label' => $sortOptions[$sort], 'removeUrl' => 'tours.php?' . http_build_query(array_diff_key($_GET, ['sort' => 1]))];
        ?>
        <?php if (!empty($activeFilters)): ?>
        <div class="d-flex flex-wrap gap-2 mb-3" data-testid="active-filters">
            <?php foreach ($activeFilters as $filter): ?>
            <a href="<?= e($filter['removeUrl']) ?>" class="btn btn-sm btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1">
                <?= e($filter['label']) ?>
                <i class="bi bi-x-circle"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Sidebar Filter (desktop sticky / mobile collapse) -->
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <!-- Toggle mobile -->
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <?php if ($hasAdvanced): ?>
                        <a href="tours.php" class="btn btn-sm btn-outline-secondary rounded-pill d-lg-none"><?= t('Reset') ?></a>
                        <?php endif; ?>
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET">
                                <h6 class="fw-semibold mb-2"><?= t('Kategori') ?></h6>
                                <select name="category" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Kategori') ?></option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e(t($cat)) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Rentang Harga (IDR)') ?></h6>
                                <div class="mb-1 d-flex justify-content-between small fw-semibold">
                                    <span id="priceMinLabel">Rp <?= number_format((float)($minPrice ?: $priceFloor), 0, ',', '.') ?></span>
                                    <span class="text-muted fw-normal">–</span>
                                    <span id="priceMaxLabel">Rp <?= number_format((float)($maxPrice ?: $priceCeil), 0, ',', '.') ?></span>
                                </div>
                                <div class="t-dual-range">
                                    <div class="t-dual-track"><div class="t-dual-fill" id="priceFill"></div></div>
                                    <input type="range" class="form-range t-dual-input" id="priceMinRange" min="<?= $priceFloor ?>" max="<?= $priceCeil ?>" step="500000" value="<?= (int)($minPrice ?: $priceFloor) ?>" data-testid="price-min-range" aria-label="<?= t('Harga minimum') ?>">
                                    <input type="range" class="form-range t-dual-input" id="priceMaxRange" min="<?= $priceFloor ?>" max="<?= $priceCeil ?>" step="500000" value="<?= (int)($maxPrice ?: $priceCeil) ?>" data-testid="price-max-range" aria-label="<?= t('Harga maksimum') ?>">
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1 mb-2">
                                    <span><i class="bi bi-dash-circle me-1"></i><?= t('Min') ?></span>
                                    <span><?= t('Maks') ?><i class="bi bi-plus-circle ms-1"></i></span>
                                </div>
                                <input type="hidden" name="min_price" id="priceMinInput" value="<?= e((string)($minPrice ?? '')) ?>">
                                <input type="hidden" name="max_price" id="priceMaxInput" value="<?= e((string)($maxPrice ?? '')) ?>">
                                <button type="submit" class="btn btn-sm btn-primary w-100 mb-3" data-testid="apply-price"><?= t('Terapkan') ?></button>

                                <h6 class="fw-semibold mb-2"><?= t('Waktu Keberangkatan') ?></h6>
                                <select name="departure" class="form-select form-select-sm mb-3" onchange="this.form.submit()" data-testid="departure-select">
                                    <option value=""><?= t('Kapan saja') ?></option>
                                    <?php foreach ($departureOptions as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $departure === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Durasi') ?></h6>
                                <select name="durasi" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Durasi') ?></option>
                                    <?php foreach ($durasiOptions as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $duration === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Harga') ?></h6>
                                <select name="harga" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Harga') ?></option>
                                    <?php foreach ($hargaOptions as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $priceRange === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Rating') ?></h6>
                                <select name="rating" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Rating') ?></option>
                                    <?php foreach ($ratingOptions as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $rating === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Urutkan') ?></h6>
                                <select name="sort" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Urutkan') ?></option>
                                    <?php foreach ($sortOptions as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Search -->
                                <h6 class="fw-semibold mb-2"><?= t('Pencarian') ?></h6>
                                <div class="search-wrapper input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" placeholder="<?= t('Cari...') ?>" id="catalogSearch" autocomplete="off" value="<?= e($search ?? '') ?>">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                                    <div class="search-dropdown" id="catalogSearchDropdown"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tour Grid -->
            <div class="col-lg-9">
                <!-- Skeleton Loading (shown initially, hidden after content loads) -->
                <div id="tourSkeleton" class="row g-3">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="skeleton skeleton-card">
                            <div class="skeleton skeleton-img mb-3"></div>
                            <div class="skeleton skeleton-text"></div>
                            <div class="skeleton skeleton-text" style="width: 70%;"></div>
                            <div class="d-flex justify-content-between mt-3">
                                <div class="skeleton skeleton-text" style="width: 40%;"></div>
                                <div class="skeleton skeleton-btn"></div>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Actual Content (hidden initially, shown after load) -->
                <div id="tourContent" style="display: none;">
                <?php if (count($tours) > 0): ?>
                <div class="row g-3" id="tourGrid">
                    <?php foreach ($tours as $tour): ?>
                        <?php renderTourCard($tour, $wishlistIds); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Load More Trigger (sentinel for infinite scroll) -->
                <?php if ($lastPage > $currentPage): ?>
                <div class="load-more-trigger text-center py-4" data-page="<?= $currentPage ?>" data-last-page="<?= $lastPage ?>">
                    <div class="load-more-spinner spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?= t('Loading...') ?></span>
                    </div>
                    <div class="load-more-error d-none" data-load-error="true">
                        <i class="bi bi-wifi-off fs-3 text-muted"></i>
                        <p class="mt-2 mb-2 text-muted small"><?= t('Gagal memuat tour. Periksa koneksi Anda.') ?></p>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-load-retry><?= t('Coba Lagi') ?></button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Pagination (fallback for no-JS) -->
                <?php if ($lastPage > 1): ?>
                    <?php $baseUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge(array_diff_key($_GET, ['page' => 1]), ['page' => '__PAGE__'])); ?>
                    <?php renderPagination($currentPage, $lastPage, $baseUrl); ?>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada tour ditemukan') ?></p>
                    <a href="tours.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset Filter') ?></a>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-shared.php'; ?>
<style>
.t-dual-range { position: relative; height: 28px; }
.t-dual-track { position: absolute; top: 50%; left: 0; right: 0; height: 6px; transform: translateY(-50%); background: #dee2e6; border-radius: 999px; }
.t-dual-fill { position: absolute; top: 0; bottom: 0; background: #0d6efd; border-radius: 999px; }
.t-dual-input { position: absolute !important; top: 0; left: 0; width: 100%; height: 28px; margin: 0; background: transparent; pointer-events: none; -webkit-appearance: none; appearance: none; }
.t-dual-input::-webkit-slider-runnable-track { background: transparent; height: 28px; }
.t-dual-input::-webkit-slider-thumb { pointer-events: auto; -webkit-appearance: none; width: 18px; height: 18px; margin-top: 5px; border-radius: 50%; background: #fff; border: 2px solid #0d6efd; box-shadow: 0 1px 3px rgba(0,0,0,.25); cursor: grab; }
.t-dual-input::-moz-range-track { background: transparent; }
.t-dual-input::-moz-range-thumb { pointer-events: auto; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 2px solid #0d6efd; cursor: grab; }
.t-dual-input:focus { box-shadow: none; }
</style>
<script>
// Show skeleton initially, then reveal content
document.addEventListener('DOMContentLoaded', function() {
    var skeleton = document.getElementById('tourSkeleton');
    var content = document.getElementById('tourContent');
    if (skeleton && content) {
        // Small delay to show skeleton effect
        setTimeout(function() {
            skeleton.style.display = 'none';
            content.style.display = 'block';
        }, 300);
    }

    // Infinite Scroll with IntersectionObserver
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

            // Build AJAX URL
            var params = new URLSearchParams(window.location.search);
            params.set('page', currentPage);
            var ajaxUrl = 'tours-ajax.php?' + params.toString();

            fetch(ajaxUrl)
                .then(function(response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.text();
                })
                .then(function(html) {
                    var temp = document.createElement('div');
                    temp.innerHTML = html;
                    var newCards = temp.querySelector('.row.g-3');
                    if (newCards) {
                        var grid = document.getElementById('tourGrid');
                        grid.insertAdjacentHTML('beforeend', newCards.innerHTML);
                    }

                    // Update trigger
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
(function() {
    var minR = document.getElementById('priceMinRange'), maxR = document.getElementById('priceMaxRange');
    var minI = document.getElementById('priceMinInput'), maxI = document.getElementById('priceMaxInput');
    var minL = document.getElementById('priceMinLabel'), maxL = document.getElementById('priceMaxLabel');
    var fill = document.getElementById('priceFill');
    if (!minR || !maxR) return;
    var ABS_MIN = parseInt(minR.min, 10) || <?= (int)$priceFloor ?>;
    var ABS_MAX = parseInt(minR.max, 10) || <?= (int)$priceCeil ?>;
    var GAP = parseInt(minR.step, 10) || 500000;
    var loc = (window.I18N && window.I18N.locale) || 'id-ID';
    var fmt = function(n) { return 'Rp ' + Number(n).toLocaleString(loc); };
    function sync(from) {
        var lo = parseInt(minR.value, 10), hi = parseInt(maxR.value, 10);
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
        minR.style.zIndex = (lo > ABS_MAX - (ABS_MAX - ABS_MIN) / 2) ? '4' : '5';
        maxR.style.zIndex = '3';
    }
    minR.addEventListener('input', function() { sync('min'); });
    maxR.addEventListener('input', function() { sync('max'); });
    sync();
})();
</script>

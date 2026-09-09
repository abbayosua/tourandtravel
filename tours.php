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

require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Paket Tour'), 'url' => null]]); ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0"><?= t('Paket Tour') ?></h4>
                <small class="text-muted"><?= $total ?> <?= t('tour ditemukan') ?></small>
            </div>
            <a href="tours.php" class="btn btn-sm btn-outline-secondary rounded-pill <?= !$category && !$search && !$priceRange && !$duration && !$rating && !$sort ? 'd-none' : '' ?>">
                <i class="bi bi-x-circle me-1"></i><?= t('Reset') ?>
            </a>
        </div>

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
                                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Rentang Harga (IDR)') ?></h6>
                                <div class="mb-1 d-flex justify-content-between small text-muted">
                                    <span id="priceMinLabel">Rp <?= number_format((float)($minPrice ?: $priceFloor), 0, ',', '.') ?></span>
                                    <span id="priceMaxLabel">Rp <?= number_format((float)($maxPrice ?: $priceCeil), 0, ',', '.') ?></span>
                                </div>
                                <input type="range" class="form-range" id="priceMinRange" min="<?= $priceFloor ?>" max="<?= $priceCeil ?>" step="500000" value="<?= (int)($minPrice ?: $priceFloor) ?>" data-testid="price-min-range">
                                <input type="range" class="form-range" id="priceMaxRange" min="<?= $priceFloor ?>" max="<?= $priceCeil ?>" step="500000" value="<?= (int)($maxPrice ?: $priceCeil) ?>" data-testid="price-max-range">
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
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
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
<?php require_once 'includes/footer-klook.php'; ?>
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
        var loading = false;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && !loading && currentPage < lastPage) {
                    loading = true;
                    currentPage++;
                    
                    // Build AJAX URL
                    var params = new URLSearchParams(window.location.search);
                    params.set('page', currentPage);
                    var ajaxUrl = 'tours-ajax.php?' + params.toString();
                    
                    fetch(ajaxUrl)
                        .then(function(response) { return response.text(); })
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
                            loading = false;
                        });
                }
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
    if (!minR || !maxR) return;
    var fmt = function(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); };
    function sync() {
        var lo = parseInt(minR.value), hi = parseInt(maxR.value);
        if (lo > hi) { var t = lo; lo = hi; hi = t; }
        minI.value = lo <= <?= $priceFloor ?> ? '' : lo;
        maxI.value = hi >= <?= $priceCeil ?> ? '' : hi;
        minL.textContent = fmt(lo); maxL.textContent = fmt(hi);
    }
    minR.addEventListener('input', sync); maxR.addEventListener('input', sync);
    sync();
})();
</script>

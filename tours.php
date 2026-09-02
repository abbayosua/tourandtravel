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
$page = (int)($_GET['page'] ?? 1);

$result = getTours($category, $search, $priceRange, $duration, $rating, $sort, $page, 12);
$tours = $result['tours'];
$total = $result['total'];
$lastPage = $result['lastPage'];
$currentPage = $result['page'];

$categories = getCategories();

$durasiOptions = ['1' => '3-5 Hari', '2' => '6-8 Hari', '3' => '9+ Hari'];
$hargaOptions = ['1' => '< Rp 5 Juta', '2' => 'Rp 5-10 Juta', '3' => 'Rp 10-20 Juta', '4' => '> Rp 20 Juta'];
$ratingOptions = ['4.5' => '★ 4.5+', '4' => '★ 4.0+'];
$sortOptions = ['termurah' => 'Termurah', 'termahal' => 'Termahal', 'rating' => 'Rating Tertinggi', 'popular' => 'Terpopuler'];

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
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET">
                                <h6 class="fw-semibold mb-2"><?= t('Kategori') ?></h6>
                                <select name="category" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Kategori') ?></option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
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
                <?php if (count($tours) > 0): ?>
                <div class="row g-3">
                    <?php foreach ($tours as $tour): ?>
                        <?php renderTourCard($tour, $wishlistIds); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
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
</section>
<?php require_once 'includes/footer-klook.php'; ?>

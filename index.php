<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Beranda');

$toursResult = getTours();
$tours = $toursResult['tours'];
$featuredTours = array_slice($tours, 0, 8);
$categories = getCategories();

// Ambil promo tours
$promoTours = db()->query("SELECT * FROM tours WHERE category = 'Promo' AND is_active = 1")->fetchAll();
if (!count($promoTours)) {
    $promoTours = db()->query("SELECT * FROM tours WHERE is_active = 1 AND price > 0 ORDER BY price ASC LIMIT 3")->fetchAll();
}

$wishlistIds = [];
if (isLoggedIn()) {
    $wishlistIds = getWishlistIds($_SESSION['user_id']);
}

// Komponen Klook-style
require_once 'includes/components/tour-card.php';
require_once 'includes/components/hero-search.php';
require_once 'includes/components/category-grid.php';
require_once 'includes/components/dest-card.php';
require_once 'includes/components/breadcrumb.php';
require_once 'includes/components/badge.php';
require_once 'includes/components/price.php';
require_once 'includes/components/rating-stars.php';

// Hero slides (fallback array; nanti bisa dari tabel hero_slides)
$heroSlides = [
    ['image' => 'https://images.unsplash.com/photo-1488085061387-422e29b40080?w=1920&q=80', 'alt' => 'Bali Beach'],
    ['image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80', 'alt' => 'Beach Paradise'],
    ['image' => 'https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=1920&q=80', 'alt' => 'Travel Destination'],
];
$heroHeadline = t('Your World of Joy');
$heroSub = t('Temukan paket tour impian Anda dari ratusan destinasi');

// Collections (Best Sellers dll) dari tabel collections
$collections = db()->query("SELECT * FROM collections WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3")->fetchAll();
$collectionTours = [];
foreach ($collections as $coll) {
    $items = db()->prepare("SELECT item_type, item_id FROM collection_items WHERE collection_id = ? ORDER BY sort_order ASC LIMIT 8");
    $items->execute([$coll['id']]);
    $ids = [];
    foreach ($items->fetchAll() as $it) {
        if ($it['item_type'] === 'tour') $ids[] = (int)$it['item_id'];
    }
    if (count($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = db()->prepare("SELECT * FROM tours WHERE id IN ($in) AND is_active = 1");
        $st->execute($ids);
        $collectionTours[$coll['id']] = $st->fetchAll();
    } else {
        // Fallback: best seller tours
        $collectionTours[$coll['id']] = db()->query("SELECT * FROM tours WHERE is_active = 1 AND best_seller = 1 LIMIT 4")->fetchAll();
    }
}

require_once 'includes/header-klook.php';
?>
<?php if (!isset($_GET['legacy'])): ?>
<section class="hero-klook klook-hero d-flex align-items-center position-relative overflow-hidden" style="min-height: 65vh;">
    <div id="heroCarousel" class="carousel slide carousel-fade w-100 h-100 position-absolute" data-bs-ride="carousel" data-bs-interval="5000" style="inset: 0;">
        <div class="carousel-inner h-100">
            <?php foreach ($heroSlides as $i => $slide): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?> h-100">
                <img src="<?= $slide['image'] ?>" class="d-block w-100 h-100" style="object-fit: cover;" alt="<?= e($slide['alt']) ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="hero-overlay"></div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <div class="container position-relative" style="z-index: 2;">
        <div class="row">
            <div class="col-lg-7 text-white">
                <h1 class="display-4 fw-bold mb-2 lh-1"><?= $heroHeadline ?></h1>
                <p class="lead mb-4 text-white-50"><?= $heroSub ?></p>
                <?php renderHeroSearch($categories); ?>
            </div>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="bg-white border-bottom">
    <div class="container py-3">
        <div class="row text-center g-2">
            <div class="col-3"><div class="fw-bold text-primary fs-5">150+</div><small class="text-muted"><?= t('Paket Tour') ?></small></div>
            <div class="col-3"><div class="fw-bold text-primary fs-5">5.000+</div><small class="text-muted"><?= t('Pelanggan') ?></small></div>
            <div class="col-3"><div class="fw-bold text-primary fs-5">12+</div><small class="text-muted"><?= t('Destinasi') ?></small></div>
            <div class="col-3"><div class="fw-bold text-primary fs-5">7</div><small class="text-muted"><?= t('Tahun') ?></small></div>
        </div>
    </div>
</section>

<!-- Category Grid (horizontal scroll) -->
<?php
$catIcons = ['Domestik' => '🇮🇩', 'Internasional' => '🌍', 'China' => '🇨🇳', 'Jepang' => '🇯🇵', 'Korea Selatan' => '🇰🇷', 'Vietnam' => '🇻🇳', 'Taiwan' => '🇹🇼', 'Kanada' => '🇨🇦'];
$catCounts = [];
foreach ($categories as $cat) {
    $c = db()->prepare("SELECT COUNT(*) FROM tours WHERE category = ? AND is_active = 1");
    $c->execute([$cat]);
    $catCounts[$cat] = (int)$c->fetchColumn();
}
renderCategoryGrid($categories, $catIcons, $catCounts);
?>

<!-- Flash Deals -->
<?php if (count($promoTours) > 0): ?>
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> <?= t('Flash Deals') ?></h5>
            <a href="tours.php?category=Promo" class="btn btn-sm btn-outline-danger rounded-pill px-3"><?= t('Lihat Semua') ?></a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($promoTours, 0, 3) as $promo): ?>
            <div class="col-md-4">
                <a href="tour-detail.php?slug=<?= e($promo['slug']) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm overflow-hidden promo-card h-100">
                        <div class="row g-0 h-100">
                            <div class="col-4">
                                <img src="<?= getTourImage($promo, 'small') ?>" class="h-100 w-100" style="object-fit: cover;" alt="">
                            </div>
                            <div class="col-8">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <span class="badge bg-danger small"><?= t('HOT') ?></span>
                                        <small class="text-muted"><?= t('Promo') ?></small>
                                    </div>
                                    <h6 class="fw-semibold small mb-1 text-dark"><?= e(t($promo['title'], null, $promo['content_language'] ?? 'id')) ?></h6>
                                    <?php if ($promo['price'] > 0): ?>
                                        <span class="fw-bold text-primary small"><?= formatCurrencySpan($promo['price'], $promo['price_currency'] ?? 'IDR') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-info"><?= t('Hubungi Kami') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Destinasi Populer -->
<section class="py-4">
    <div class="container">
        <h5 class="fw-bold mb-3"><?= t('Destinasi Populer') ?></h5>
        <?php $cityDests = getCityDestinations(); ?>
        <div class="row g-2">
            <?php foreach ($cityDests as $category => $cities): ?>
                <?php foreach ($cities as $dest): ?>
                    <?php renderDestCard(['city' => $dest['city'], 'count' => countToursByCity($dest['city'])]); ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php if (count($cityDests) > 0): ?>
        <div class="mt-3 kategori-scroll d-flex gap-1 overflow-auto pb-1">
            <?php foreach ($cityDests as $category => $cities): ?>
                <a href="tours.php?category=<?= urlencode($category) ?>" class="btn btn-sm btn-outline-primary rounded-pill flex-shrink-0"><?= e($category) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Rekomendasi Paket Tour -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1"><?= t('Rekomendasi Paket Tour') ?></h5>
                <p class="text-muted mb-0 small"><?= t('Pilihan terbaik untuk liburan Anda') ?></p>
            </div>
            <a href="tours.php" class="btn btn-outline-primary rounded-pill px-4"><?= t('Lihat Semua') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($featuredTours as $tour): ?>
                <?php renderTourCard($tour, $wishlistIds); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($collectionTours)): ?>
<?php foreach ($collectionTours as $collId => $collTours): $coll = array_filter($collections, fn($c) => $c['id'] === $collId); $coll = reset($coll); if (!$coll) continue; ?>
<!-- Collection: <?= e($coll['name']) ?> -->
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1"><?= e(t($coll['name'])) ?></h5>
                <?php if ($coll['description']): ?><p class="text-muted mb-0 small"><?= e(t($coll['description'])) ?></p><?php endif; ?>
            </div>
            <a href="collection.php?slug=<?= e($coll['slug']) ?>" class="btn btn-outline-primary rounded-pill px-4"><?= t('Lihat Semua') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($collTours, 0, 4) as $tour): ?>
                <?php renderTourCard($tour, $wishlistIds); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer-klook.php'; ?>
<?php else: ?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero -->
<section class="hero-klook d-flex align-items-center position-relative overflow-hidden">
    <div class="hero-bg"></div>
    <video id="heroVideo" class="hero-video" muted loop playsinline preload="none"></video>
    <div class="hero-overlay"></div>
    <div class="container position-relative z-1">
        <div class="row">
            <div class="col-lg-7 text-white">
                <h1 class="display-4 fw-bold mb-2 lh-1"><?= t('Your World of Joy') ?></h1>
                <p class="lead mb-4 text-white-50"><?= t('Temukan paket tour impian Anda dari ratusan destinasi') ?></p>
                <div class="bg-white rounded-4 p-2 shadow-lg" style="max-width: 600px;">
                    <div class="search-wrapper">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-0 shadow-none" placeholder="<?= t('Cari destinasi atau aktivitas...') ?>" id="heroSearch" autocomplete="off" onkeypress="if(event.key==='Enter') window.location='tours.php?search='+encodeURIComponent(this.value)">
                            <button class="btn btn-primary px-4 rounded-3 m-1" onclick="window.location='tours.php?search='+encodeURIComponent(document.getElementById('heroSearch').value)"><?= t('Cari') ?></button>
                        </div>
                        <div class="search-dropdown" id="heroSearchDropdown"></div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="tours.php" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold"><i class="bi bi-grid-fill me-1"></i><?= t('Semua') ?></a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="tours.php?category=<?= e($cat) ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><?= e($cat) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="bg-white border-bottom">
    <div class="container py-3">
        <div class="row text-center g-2">
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">150+</div>
                <small class="text-muted"><?= t('Paket Tour') ?></small>
            </div>
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">5.000+</div>
                <small class="text-muted"><?= t('Pelanggan') ?></small>
            </div>
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">12+</div>
                <small class="text-muted"><?= t('Destinasi') ?></small>
            </div>
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">7</div>
                <small class="text-muted"><?= t('Tahun') ?></small>
            </div>
        </div>
    </div>
</section>

<!-- Category Cards – ala Klook -->
<section class="py-4">
    <div class="container">
        <h5 class="fw-bold mb-3"><?= t('Kategori Wisata') ?></h5>
        <div class="row g-2">
            <?php
            $catIcons = [
                'Domestik' => '🇮🇩',
                'Internasional' => '🌍',
                'China' => '🇨🇳',
                'Jepang' => '🇯🇵',
                'Korea Selatan' => '🇰🇷',
                'Vietnam' => '🇻🇳',
                'Taiwan' => '🇹🇼',
                'Kanada' => '🇨🇦',
            ];
            $displayCats = ['Domestik', 'China', 'Jepang', 'Korea Selatan', 'Vietnam', 'Internasional'];
            foreach ($displayCats as $cat):
                $flag = $catIcons[$cat] ?? '🌏';
                $count = db()->prepare("SELECT COUNT(*) FROM tours WHERE category = ? AND is_active = 1");
                $count->execute([$cat]);
                $total = $count->fetchColumn();
            ?>
            <div class="col-4 col-md-2">
                <a href="tours.php?category=<?= e($cat) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm text-center py-3 cat-card">
                        <div class="fs-2 mb-1"><?= $flag ?></div>
                        <h6 class="fw-semibold small mb-0 text-dark"><?= e($cat) ?></h6>
                        <small class="text-muted"><?= $total ?> paket</small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promo / Flash Deals Banner -->
<?php if (count($promoTours) > 0): ?>
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> <?= t('Flash Deals') ?></h5>
            <a href="tours.php?category=Promo" class="btn btn-sm btn-outline-danger rounded-pill px-3"><?= t('Lihat Semua') ?></a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($promoTours, 0, 3) as $promo): ?>
            <div class="col-md-4">
                <a href="tour-detail.php?slug=<?= e($promo['slug']) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm overflow-hidden promo-card">
                        <div class="row g-0">
                            <div class="col-4">
                                <img src="<?= getTourImage($promo, 'small') ?>" class="h-100 w-100" style="object-fit: cover;" alt="">
                            </div>
                            <div class="col-8">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <span class="badge bg-danger small"><?= t('HOT') ?></span>
                                        <small class="text-muted"><?= t('Promo') ?></small>
                                    </div>
                                    <h6 class="fw-semibold small mb-1 text-dark"><?= e(t($promo['title'], null, $promo['content_language'] ?? 'id')) ?></h6>
                                    <?php if ($promo['price'] > 0): ?>
                                        <span class="fw-bold text-primary small"><?= formatCurrencySpan($promo['price'], $promo['price_currency'] ?? 'IDR') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-info"><?= t('Hubungi Kami') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Destinasi Kota – ala Klook -->
<section class="py-4">
    <div class="container">
        <h5 class="fw-bold mb-3"><?= t('Destinasi Populer') ?></h5>
        <?php $cityDests = getCityDestinations(); ?>
        <div class="row g-2">
            <?php foreach ($cityDests as $category => $cities):
                $catSlug = urlencode($category);
                $first = true;
            ?>
                <?php foreach ($cities as $dest):
                    $tourCount = countToursByCity($dest['city']);
                ?>
                <div class="col-4 col-lg-2">
                    <a href="destinasi.php?city=<?= urlencode($dest['city']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm overflow-hidden dest-card">
                            <div class="dest-img" style="background-image: url('<?= getDestinasiImage($dest['city']) ?>');">
                                <div class="dest-overlay d-flex align-items-end p-2">
                                    <div>
                                        <span class="fw-semibold text-white small d-block"><?= e($dest['city']) ?></span>
                                        <small class="text-white-50" style="font-size: 10px;"><?= $tourCount ?> <?= t('paket') ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php if (count($cityDests) > 0): ?>
        <div class="mt-3 kategori-scroll d-flex gap-1 overflow-auto pb-1">
            <?php foreach ($cityDests as $category => $cities): ?>
                <a href="tours.php?category=<?= urlencode($category) ?>" class="btn btn-sm btn-outline-primary rounded-pill flex-shrink-0"><?= e($category) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Featured Tours – ala Klook card grid -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1"><?= t('Rekomendasi Paket Tour') ?></h5>
                <p class="text-muted mb-0 small"><?= t('Pilihan terbaik untuk liburan Anda') ?></p>
            </div>
            <a href="tours.php" class="btn btn-outline-primary rounded-pill px-4"><?= t('Lihat Semua') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($featuredTours as $tour): ?>
            <div class="col-6 col-lg-3">
                <div class="card tour-card-klook border-0 shadow-sm h-100">
                    <div class="position-relative overflow-hidden rounded-top" style="height: 180px;">
                        <img src="<?= getTourImage($tour, 'medium') ?>" onerror="this.src='<?= getTourImageFallback($tour, 'medium') ?>'" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($tour['title']) ?>">
                        <?php $diskon = getDiskonPersen($tour); if ($diskon > 0): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2 shadow-sm">-<?= $diskon ?>%</span>
                        <?php endif; ?>
                        <button class="btn btn-sm position-absolute top-0 end-0 m-1 like-btn wishlist-btn <?= in_array($tour['id'], $wishlistIds) ? 'text-danger' : 'text-white' ?>" 
                            data-tour-id="<?= $tour['id'] ?>" 
                            onclick="toggleWishlist(this, <?= $tour['id'] ?>)">
                            <i class="bi bi-heart<?= in_array($tour['id'], $wishlistIds) ? '-fill' : '' ?>"></i>
                        </button>
                        <span class="badge bg-white text-dark position-absolute top-0 start-0 m-2 shadow-sm" style="margin-top: 38px !important;"><?= e($tour['category']) ?></span>
                        <span class="badge bg-white text-dark position-absolute top-0 end-0 m-2 shadow-sm"><?= e($tour['category']) ?></span>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-semibold mb-1 text-truncate"><?= e(t($tour['title'], null, $tour['content_language'] ?? 'id')) ?></h6>
                        <div class="d-flex align-items-center gap-2 small mb-1">
                            <?= renderStars($tour['rating']) ?>
                            <span class="text-muted">(<?= $tour['total_reviews'] ?>)</span>
                        </div>
                        <div class="d-flex align-items-center text-muted small mb-2">
                            <i class="bi bi-clock me-1"></i>
                            <?php
                                $stmt = db()->prepare("SELECT MIN(departure_date) as next FROM tour_dates WHERE tour_id = ? AND departure_date >= CURDATE() AND is_active = 1");
                                $stmt->execute([$tour['id']]);
                                $nextDate = $stmt->fetch();
                            ?>
                            <?php if ($nextDate && $nextDate['next']): ?>
                                <?= tglIndonesia($nextDate['next']) ?>
                            <?php else: ?>
                                Segera
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary"><?= formatRupiah($tour['price']) ?></span>
                                <small class="text-muted">/org</small>
                                <?php if ($diskon > 0): ?>
                                    <br><small class="text-decoration-line-through text-muted"><?= formatRupiah($tour['original_price']) ?></small>
                                <?php endif; ?>
                            </div>
                            <a href="tour-detail.php?slug=<?= e($tour['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">Pesan</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Social Proof – ala Klook -->
<section class="py-5">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm py-4 h-100">
                    <div class="display-5 text-warning mb-2">★ 4.8</div>
                    <div class="mb-1">
                        <span class="text-warning"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
                    </div>
                    <h6 class="fw-semibold mb-0"><?= t('Rating Pelanggan') ?></h6>
                    <small class="text-muted"><?= t('Dari 2.000+ ulasan') ?></small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm py-4 h-100">
                    <div class="display-5 text-primary mb-2"><i class="bi bi-people-fill"></i></div>
                    <div class="fs-3 fw-bold text-primary">5.000+</div>
                    <h6 class="fw-semibold mb-0"><?= t('Pelanggan Puas') ?></h6>
                    <small class="text-muted"><?= t('Tersebar di 12+ destinasi') ?></small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm py-4 h-100">
                    <div class="display-5 text-success mb-2"><i class="bi bi-hand-thumbs-up-fill"></i></div>
                    <div class="fs-3 fw-bold text-success">99%</div>
                    <h6 class="fw-semibold mb-0"><?= t('Kepuasan') ?></h6>
                    <small class="text-muted"><?= t('Pelanggan merekomendasikan kami') ?></small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <h5 class="fw-bold"><?= t('Apa Kata Mereka?') ?></h5>
            <p class="text-muted small"><?= t('Pengalaman pelanggan yang sudah traveling bersama kami') ?></p>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://i.pravatar.cc/80?img=1" class="rounded-circle me-3" width="48" height="48" alt="">
                            <div>
                                <h6 class="fw-semibold mb-0">Sari Dewi</h6>
                                <small class="text-muted">Bali Paradise 5D4N</small>
                            </div>
                        </div>
                        <div class="text-warning small mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0 small text-muted">"Liburan ke Bali bareng TourAndTravel puas banget! Hotelnya enak, guide-nya ramah, itinerary-nya lengkap. Recommended!"</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://i.pravatar.cc/80?img=12" class="rounded-circle me-3" width="48" height="48" alt="">
                            <div>
                                <h6 class="fw-semibold mb-0">Bambang S.</h6>
                                <small class="text-muted">Beijing 8D7N</small>
                            </div>
                        </div>
                        <div class="text-warning small mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0 small text-muted">"Pertama kali ke China, awalnya khawatir tapi ternyata lancar semua. Guide lokalnya speak Indonesian,很棒!"</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://i.pravatar.cc/80?img=5" class="rounded-circle me-3" width="48" height="48" alt="">
                            <div>
                                <h6 class="fw-semibold mb-0">Rina A.</h6>
                                <small class="text-muted">Korea Tour 7D6N</small>
                            </div>
                        </div>
                        <div class="text-warning small mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0 small text-muted">"Dari Seoul sampai Busan semua kece! Makin seru sama temen-temen satu grup. Next mau ke Jepang bareng sini lagi!"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blog & Info Cards – ala Klook -->
<section class="py-5">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-4">
                            <div style="height: 100%; min-height: 120px; background: url('https://placehold.co/400x200?text=Blog') center/cover no-repeat;"></div>
                        </div>
                        <div class="col-8">
                            <div class="card-body p-3">
                                <span class="badge bg-primary mb-2"><?= t('Blog') ?></span>
                                <h6 class="fw-semibold small"><?= t('Cek blog TourAndTravel') ?></h6>
                                <p class="small text-muted mb-2"><?= t('Ikuti tren travel, itinerary ideas, dan tips traveling terbaru.') ?></p>
                                <a href="tours.php" class="small fw-semibold text-primary text-decoration-none"><?= t('Baca selengkapnya') ?> <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-4">
                            <div style="height: 100%; min-height: 120px; background: url('https://placehold.co/400x200?text=Reward') center/cover no-repeat;"></div>
                        </div>
                        <div class="col-8">
                            <div class="card-body p-3">
                                <span class="badge bg-success mb-2"><?= t('Reward') ?></span>
                                <h6 class="fw-semibold small"><?= t('Dapatkan TourCash') ?></h6>
                                <p class="small text-muted mb-2"><?= t('Setiap booking dapat poin reward. Tukarkan untuk diskon tour berikutnya!') ?></p>
                                <a href="tours.php" class="small fw-semibold text-success text-decoration-none"><?= t('Pelajari') ?> <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-4">
                            <div style="height: 100%; min-height: 120px; background: url('https://placehold.co/400x200?text=Referral') center/cover no-repeat;"></div>
                        </div>
                        <div class="col-8">
                            <div class="card-body p-3">
                                <span class="badge bg-warning text-dark mb-2"><?= t('Referral') ?></span>
                                <h6 class="fw-semibold small"><?= t('Ajak Teman, Dapat Diskon') ?></h6>
                                <p class="small text-muted mb-2"><?= t('Ajak teman daftar & booking, kamu dan teman dapat diskon Rp100.000!') ?></p>
                                <a href="tours.php" class="small fw-semibold text-warning text-decoration-none"><?= t('Bagikan') ?> <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kenapa Pilih Kami – ala Klook trust -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h5 class="fw-bold"><?= t('Kenapa Pilih') ?> <?= SITE_NAME ?>?</h5>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-tags-fill"></i></div>
                    <h6 class="fw-semibold"><?= t('Harga Transparan') ?></h6>
                    <small class="text-muted"><?= t('Tidak ada biaya tersembunyi') ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-shield-check"></i></div>
                    <h6 class="fw-semibold"><?= t('Terpercaya') ?></h6>
                    <small class="text-muted">7 <?= t('tahun melayani pelanggan') ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-headset"></i></div>
                    <h6 class="fw-semibold">CS 24/7</h6>
                    <small class="text-muted"><?= t('Siap bantu kapan saja') ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-wallet2"></i></div>
                    <h6 class="fw-semibold"><?= t('Mudah Booking') ?></h6>
                    <small class="text-muted"><?= t('Proses cepat & praktis') ?></small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Banner -->
<section class="py-5" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
    <div class="container text-center text-white">
        <h4 class="fw-bold mb-2"><?= t('Siap Liburan?') ?></h4>
        <p class="mb-4 opacity-90"><?= t('Dapatkan promo spesial untuk pendaftaran hari ini') ?></p>
        <a href="tours.php" class="btn btn-light btn-lg rounded-pill px-5 fw-semibold"><?= t('Mulai Sekarang') ?> <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
<?php endif; ?>

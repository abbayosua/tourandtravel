<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Beranda');

// Fokus website (admin settings): tour | hotel | flight
$siteFocus = getSetting('site_focus', 'tour');
if (!in_array($siteFocus, ['tour', 'hotel', 'flight'], true)) $siteFocus = 'tour';

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

// Hero slides: DB per fokus (site_focus ATAU 'all', aktif, urut) — loader via komponen
require_once 'includes/components/hero-loader.php';
$heroSlides = getHeroSlides($siteFocus);
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
<?php if ($siteFocus === 'tour'): ?>
<?php require __DIR__ . '/includes/homepage/tour-hero.php'; ?>
<?php require __DIR__ . '/includes/homepage/tour-stats.php'; ?>
<?php require __DIR__ . '/includes/homepage/tour-categories.php'; ?>
<?php require __DIR__ . '/includes/homepage/tour-flash-deals.php'; ?>
<?php require __DIR__ . '/includes/homepage/tour-destinations.php'; ?>
<?php require __DIR__ . '/includes/homepage/tour-featured.php'; ?>
<?php require __DIR__ . '/includes/homepage/tour-collections.php'; ?>
<?php require_once 'includes/footer-klook.php'; ?>
<?php elseif ($siteFocus === 'hotel'): ?>
<?php require __DIR__ . '/includes/homepage/hotel-hero.php'; ?>
<?php require __DIR__ . '/includes/homepage/hotel-deals.php'; ?>
<?php require __DIR__ . '/includes/homepage/hotel-cities.php'; ?>
<?php require __DIR__ . '/includes/homepage/trust.php'; ?>
<?php require __DIR__ . '/includes/homepage/testimonials.php'; ?>
<?php require __DIR__ . '/includes/homepage/cross-sell.php'; ?>
<?php require_once 'includes/footer-klook.php'; ?>
<?php else: ?>
<?php require __DIR__ . '/includes/homepage/flight-hero.php'; ?>
<?php require __DIR__ . '/includes/homepage/flight-promo.php'; ?>
<?php require __DIR__ . '/includes/homepage/trust.php'; ?>
<?php require __DIR__ . '/includes/homepage/testimonials.php'; ?>
<?php require __DIR__ . '/includes/homepage/cross-sell.php'; ?>
<?php require_once 'includes/footer-klook.php'; ?>
<?php endif; ?>

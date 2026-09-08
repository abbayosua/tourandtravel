<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

$userId = $_SESSION['user_id'];
$wishlistItems = getUserWishlistItems($userId);

// Muat detail per tipe
$tours = [];
if (!empty($wishlistItems['tour'])) {
    $in = implode(',', array_map('intval', $wishlistItems['tour']));
    $tours = db()->query("SELECT * FROM tours WHERE id IN ($in) AND is_active = 1")->fetchAll();
    // urutkan sesuai wishlist
    $tours = array_values(array_filter($wishlistItems['tour'], fn($id) => false) ?: $tours);
    usort($tours, function ($a, $b) use ($wishlistItems) {
        return array_search((int)$a['id'], $wishlistItems['tour']) <=> array_search((int)$b['id'], $wishlistItems['tour']);
    });
}
$wishlistIds = array_map(fn($t) => (int)$t['id'], $tours);

$hotels = [];
if (!empty($wishlistItems['hotel'])) {
    $in = implode(',', array_map('intval', $wishlistItems['hotel']));
    $rows = db()->query("SELECT * FROM hotels WHERE id IN ($in) AND is_active = 1")->fetchAll();
    usort($rows, function ($a, $b) use ($wishlistItems) {
        return array_search((int)$a['id'], $wishlistItems['hotel']) <=> array_search((int)$b['id'], $wishlistItems['hotel']);
    });
    $hotels = $rows;
}

$attractions = [];
if (!empty($wishlistItems['attraction'])) {
    $in = implode(',', array_map('intval', $wishlistItems['attraction']));
    $rows = db()->query("SELECT * FROM attractions WHERE id IN ($in) AND is_active = 1")->fetchAll();
    usort($rows, function ($a, $b) use ($wishlistItems) {
        return array_search((int)$a['id'], $wishlistItems['attraction']) <=> array_search((int)$b['id'], $wishlistItems['attraction']);
    });
    $attractions = $rows;
}

$esims = [];
if (!empty($wishlistItems['esim'])) {
    $in = implode(',', array_map('intval', $wishlistItems['esim']));
    $rows = db()->query("SELECT * FROM connectivity_products WHERE id IN ($in) AND is_active = 1")->fetchAll();
    usort($rows, function ($a, $b) use ($wishlistItems) {
        return array_search((int)$a['id'], $wishlistItems['esim']) <=> array_search((int)$b['id'], $wishlistItems['esim']);
    });
    $esims = $rows;
}

$totalItems = count($tours) + count($hotels) + count($attractions) + count($esims);

$pageTitle = t('Wishlist Saya');
require_once 'includes/components/tour-card.php';
require_once 'includes/components/item-card.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-heart-fill text-danger me-2"></i><?= t('Wishlist Saya') ?></h4>

        <?php if ($totalItems > 0): ?>
            <?php if (count($tours) > 0): ?>
            <h6 class="fw-semibold text-muted mb-2"><?= t('Tour') ?> (<?= count($tours) ?>)</h6>
            <div class="row g-3 mb-4">
                <?php foreach ($tours as $tour): ?>
                    <?php renderTourCard($tour, $wishlistIds); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (count($hotels) > 0): ?>
            <h6 class="fw-semibold text-muted mb-2"><?= t('Hotel') ?> (<?= count($hotels) ?>)</h6>
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4" data-testid="wishlist-hotels">
                <?php foreach ($hotels as $h): ?>
                    <?php renderItemCard($h, 'hotel', $wishlistItems['hotel'], [
                        'price' => formatRupiah($h['price_per_night']) . ' <small class="fw-normal text-muted">/' . t('malam') . '</small>',
                    ]); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (count($attractions) > 0): ?>
            <h6 class="fw-semibold text-muted mb-2"><?= t('Atraksi') ?> (<?= count($attractions) ?>)</h6>
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4" data-testid="wishlist-attractions">
                <?php foreach ($attractions as $a): ?>
                    <?php renderItemCard($a, 'attraction', $wishlistItems['attraction'], [
                        'price' => formatCurrencySpan($a['price'], $a['price_currency'] ?? 'IDR'),
                    ]); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (count($esims) > 0): ?>
            <h6 class="fw-semibold text-muted mb-2">eSIM (<?= count($esims) ?>)</h6>
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4" data-testid="wishlist-esims">
                <?php foreach ($esims as $ep): ?>
                    <?php renderItemCard($ep, 'esim', $wishlistItems['esim'], [
                        'title' => $ep['name'],
                        'image' => 'https://placehold.co/400x200?text=' . urlencode($ep['type']),
                        'price' => formatRupiah($ep['price']),
                    ]); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
        <div class="text-center py-5 klook-empty-state">
            <div class="display-1 text-muted mb-3"><i class="bi bi-heart"></i></div>
            <h5 class="fw-bold text-dark mb-2"><?= t('Belum ada tour yang disimpan') ?></h5>
            <p class="text-muted small mb-4"><?= t('Simpan tour favorit Anda dengan menekan ikon hati') ?> <i class="bi bi-heart-fill text-danger"></i> <?= t('di halaman katalog.') ?></p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-compass me-1"></i><?= t('Jelajahi Tour') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

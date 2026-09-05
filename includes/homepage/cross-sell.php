<?php
/**
 * Cross-sell ringkas — baris kartu kecil vertikal lain sesuai fokus aktif.
 * hotel → tampilkan tour + attraction | flight → tampilkan hotel + tour | tour → hotel + attraction
 */
require_once 'includes/components/hotel-card.php';

$crossFocus = $siteFocus ?? 'tour';
$maxSmall = 4;

// Tentukan vertikal pendukung + heading
if ($crossFocus === 'hotel') {
    $verticals = ['tour' => t('Jelajahi Juga: Paket Tour'), 'attraction' => t('Aktivitas Menarik')];
} elseif ($crossFocus === 'flight') {
    $verticals = ['hotel' => t('Lengkapi Perjalanan: Hotel'), 'tour' => t('Paket Tour Populer')];
} else {
    $verticals = ['hotel' => t('Butuh Menginap?'), 'attraction' => t('Aktivitas Seru')];
}
?>
<?php foreach ($verticals as $vKind => $vTitle): ?>
<?php if ($vKind === 'tour'):
    $crossTours = db()->query("SELECT * FROM tours WHERE is_active = 1 AND best_seller = 1 ORDER BY rating DESC LIMIT {$maxSmall}")->fetchAll();
    if (!count($crossTours)) $crossTours = db()->query("SELECT * FROM tours WHERE is_active = 1 ORDER BY rating DESC LIMIT {$maxSmall}")->fetchAll();
    if (!count($crossTours)) continue; ?>
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><?= $vTitle ?></h5>
            <a href="tours.php" class="btn btn-sm btn-outline-primary rounded-pill px-3"><?= t('Lihat Semua') ?></a>
        </div>
        <div class="row g-3">
            <?php foreach ($crossTours as $ct): ?>
                <?php renderTourCard($ct, $wishlistIds, ['show_description' => false]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php elseif ($vKind === 'hotel'):
    $crossHotels = db()->query("SELECT * FROM hotels WHERE is_active = 1 ORDER BY price_per_night ASC LIMIT {$maxSmall}")->fetchAll();
    if (!count($crossHotels)) continue; ?>
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><?= $vTitle ?></h5>
            <a href="hotels.php" class="btn btn-sm btn-outline-primary rounded-pill px-3"><?= t('Lihat Semua') ?></a>
        </div>
        <div class="row g-3">
            <?php foreach ($crossHotels as $ch): ?>
                <?php renderHotelCard($ch, 0); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php else:
    $crossAttractions = db()->query("SELECT * FROM attractions WHERE is_active = 1 ORDER BY id DESC LIMIT {$maxSmall}")->fetchAll();
    if (!count($crossAttractions)) continue; ?>
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><?= $vTitle ?></h5>
            <a href="attractions.php" class="btn btn-sm btn-outline-primary rounded-pill px-3"><?= t('Lihat Semua') ?></a>
        </div>
        <div class="row g-3">
            <?php foreach ($crossAttractions as $ca): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="attraction-detail.php?slug=<?= e($ca['slug']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 klook-hover-card overflow-hidden">
                            <img src="<?= e($ca['cover_image'] ?: 'https://placehold.co/400x300?text=' . urlencode($ca['name'])) ?>" class="w-100" style="height: 130px; object-fit: cover;" alt="<?= e($ca['name']) ?>">
                            <div class="card-body p-2">
                                <h6 class="fw-semibold small mb-1 text-dark"><?= e(tContent($ca, 'name')) ?></h6>
                                <span class="fw-bold text-primary small"><?= formatCurrencySpan($ca['price']) ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php endforeach; ?>

<!-- Flash Deals -->
<?php if (count($promoTours) > 0): ?>
<section class="py-4 voyage-glass-sec">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> <?= t('Flash Deals') ?></h5>
            <a href="tours.php?category=Promo" class="btn btn-sm btn-outline-danger rounded-pill px-3"><?= t('Lihat Semua') ?></a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($promoTours, 0, 3) as $promo): ?>
            <div class="col-md-4">
                <a href="tour-detail.php?slug=<?= e($promo['slug']) ?>" class="text-decoration-none">
                    <div class="card overflow-hidden promo-card voyage-flash-card h-100">
                        <div class="voyage-flash-media">
                            <img src="<?= getTourImage($promo, 'medium') ?>" onerror="this.src='https://placehold.co/640x360?text=Promo'" alt="<?= e($promo['title']) ?>" loading="lazy">
                            <span class="voyage-flash-shade"></span>
                            <span class="badge bg-danger voyage-flash-hot"><?= t('HOT') ?></span>
                        </div>
                        <div class="card-body py-2 px-3">
                            <small class="text-muted"><?= t('Promo') ?></small>
                            <h6 class="fw-semibold small mb-1 text-dark"><?= e(t($promo['title'], null, $promo['content_language'] ?? 'id')) ?></h6>
                            <?php if ($promo['price'] > 0): ?>
                                <span class="fw-bold text-primary small"><?= formatCurrencySpan($promo['price'], $promo['price_currency'] ?? 'IDR') ?></span>
                            <?php else: ?>
                                <span class="badge bg-info"><?= t('Hubungi Kami') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

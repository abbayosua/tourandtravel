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
                                <img src="<?= getTourImage($promo, 'small') ?>" onerror="this.src='https://placehold.co/320x240?text=Promo'" class="h-100 w-100" style="object-fit: cover;" alt="">
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


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

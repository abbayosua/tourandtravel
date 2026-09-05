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
<?php endforeach; ?>
<?php endif; ?>

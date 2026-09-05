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


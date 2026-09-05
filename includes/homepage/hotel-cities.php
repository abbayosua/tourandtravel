<?php
/**
 * Preset Hotel — Kota populer + harga mulai (GROUP BY city, MIN price).
 */
$hotelCityStats = db()->query("SELECT city, MIN(price_per_night) AS min_price, COUNT(*) AS total FROM hotels WHERE is_active = 1 GROUP BY city ORDER BY total DESC, city ASC LIMIT 6")->fetchAll();
?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1"><?= t('Kota Populer') ?></h5>
                <p class="text-muted small mb-0"><?= t('Harga mulai dari — per malam') ?></p>
            </div>
            <a href="hotels.php" class="btn btn-outline-primary rounded-pill px-4"><?= t('Lihat Semua') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <?php if (count($hotelCityStats) > 0): ?>
            <div class="row g-3">
                <?php foreach ($hotelCityStats as $cs): ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="hotels.php?city=<?= urlencode($cs['city']) ?>" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 klook-hover-card text-center py-4">
                                <div class="fs-3 text-primary mb-2"><i class="bi bi-geo-alt-fill"></i></div>
                                <div class="fw-semibold small px-2"><?= e($cs['city']) ?></div>
                                <small class="text-muted mt-1">
                                    <?= formatRupiah($cs['min_price']) ?><br>
                                    <span style="font-size: 10px;"><?= $cs['total'] ?> <?= t('hotel') ?></span>
                                </small>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted"><?= t('Belum ada kota dengan data hotel.') ?></p>
        <?php endif; ?>
    </div>
</section>

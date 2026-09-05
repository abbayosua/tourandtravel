<?php
/**
 * Preset Hotel — Deal hotel terbaik (grid kartu, harga termurah dulu).
 * Data: hotels aktif; filter opsional ?city dari hero search (langsung ke hotels.php
 * untuk hasil penuh — di homepage tampilkan 6 terbaik).
 */
require_once 'includes/components/hotel-card.php';
$hotelDeals = db()->query("SELECT * FROM hotels WHERE is_active = 1 ORDER BY price_per_night ASC, id ASC LIMIT 6")->fetchAll();
?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1"><?= t('Deal Hotel Terbaik') ?></h5>
                <p class="text-muted small mb-0"><?= t('Harga termurah untuk menginap di kota favoritmu') ?></p>
            </div>
            <a href="hotels.php" class="btn btn-outline-primary rounded-pill px-4"><?= t('Lihat Semua') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <?php if (count($hotelDeals) > 0): ?>
            <div class="row g-3">
                <?php foreach ($hotelDeals as $hd): ?>
                    <?php renderHotelCard($hd); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-building fs-1 text-muted"></i>
                <p class="mt-2 text-muted"><?= t('Belum ada hotel tersedia saat ini.') ?></p>
                <a href="tours.php" class="btn btn-primary rounded-pill px-4"><?= t('Jelajahi Paket Tour') ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

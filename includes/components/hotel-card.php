<?php
/**
 * renderHotelCard — kartu hotel gaya Agoda untuk homepage preset Hotel.
 * Konsisten design-tokens (var --primary, radius, shadow) + pola kartu Klook existing.
 *
 * @param array $hotel Row hotels (id, name, slug, city, star_rating, price_per_night,
 *                     description, name_en, description_en, best_seller,
 *                     instant_confirmation, free_cancellation)
 * @param int   $maxDescription Panjang deskripsi yang dipotong (0 = tanpa deskripsi)
 */
function renderHotelCard($hotel, $maxDescription = 90) {
    $name = tContent($hotel, 'name');
    $desc = trim((string)tContent($hotel, 'description'));
    $link = 'hotel-detail.php?slug=' . e($hotel['slug']);
    $img = 'https://placehold.co/640x480?text=' . urlencode($name);
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100 klook-hover-card overflow-hidden">
            <div class="position-relative overflow-hidden" style="height: 180px;">
                <img src="<?= $img ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($name) ?>" loading="lazy">
                <?php if (!empty($hotel['best_seller'])): ?>
                    <span class="badge bg-danger position-absolute top-0 start-0 m-2 shadow-sm"><?= t('Best Seller') ?></span>
                <?php endif; ?>
                <?php if (!empty($hotel['instant_confirmation'])): ?>
                    <span class="badge bg-success position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px;">
                        <i class="bi bi-lightning-charge-fill me-1"></i><?= t('Instan') ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($hotel['free_cancellation'])): ?>
                    <span class="badge bg-info text-white position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px; margin-top: 28px !important;">
                        <i class="bi bi-shield-check me-1"></i><?= t('Batal Gratis') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-3 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="fw-semibold mb-1"><?= e($name) ?></h6>
                    <span class="text-warning small" title="<?= (int)$hotel['star_rating'] ?> <?= t('Bintang') ?>"><?= str_repeat('★', (int)$hotel['star_rating']) ?></span>
                </div>
                <small class="text-muted mb-2"><i class="bi bi-geo-alt me-1"></i><?= e($hotel['city']) ?></small>
                <?php if ($maxDescription > 0 && $desc !== ''): ?>
                    <p class="text-muted small mb-2"><?= e(mb_strlen($desc) > $maxDescription ? mb_substr($desc, 0, $maxDescription) . '…' : $desc) ?></p>
                <?php endif; ?>
                <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                    <div>
                        <span class="fw-bold text-primary"><?= formatRupiah($hotel['price_per_night']) ?></span>
                        <small class="text-muted">/<?= t('malam') ?></small>
                    </div>
                    <a href="<?= $link ?>" class="btn btn-sm btn-primary rounded-pill px-3"><?= t('Lihat') ?></a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

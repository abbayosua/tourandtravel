<?php
/**
 * Preset Hotel — Hero Agoda-style: search bar hotel dominan di atas foto latar.
 * Submit GET ke hotels.php (city, checkin, checkout, guests) — param sama dengan hotels.php.
 */
$hotelHeroSlides = array_slice($heroSlides, 0, 1);
$hotelHeroImg = $hotelHeroSlides[0]['image'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&q=80';
$hotelCheckin = date('Y-m-d');
$hotelCheckout = date('Y-m-d', strtotime('+2 days'));
$hotelCities = db()->query("SELECT DISTINCT city FROM hotels WHERE is_active = 1 ORDER BY city ASC LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);
?>
<section class="position-relative overflow-hidden" style="min-height: 70vh;">
    <img src="<?= e($hotelHeroImg) ?>" class="position-absolute w-100 h-100" style="object-fit: cover; inset: 0;" alt="">
    <div class="position-absolute w-100 h-100" style="inset: 0; background: linear-gradient(180deg, rgba(13,110,253,.55) 0%, rgba(13,110,253,.35) 55%, rgba(0,0,0,.45) 100%);"></div>

    <div class="container position-relative py-5" style="z-index: 2;">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8 text-white">
                <h1 class="display-5 fw-bold mb-2">
                    <?= !empty($hotelHeroSlides[0]['title']) ? e(t($hotelHeroSlides[0]['title'])) : t('Menginap Nyaman, Harga Terbaik') ?>
                </h1>
                <p class="lead mb-4 text-white-50">
                    <?= !empty($hotelHeroSlides[0]['subtitle']) ? e(t($hotelHeroSlides[0]['subtitle'])) : t('Dari budget sampai bintang 5 — bandingkan dan pesan sekarang.') ?>
                </p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="bg-white rounded-4 shadow-lg p-3 p-md-4">
                    <form method="GET" action="hotels.php" class="row g-2 g-md-3 align-items-end">
                        <div class="col-md">
                            <label class="form-label small fw-semibold text-muted"><?= t('Kota / Hotel') ?></label>
                            <input type="text" name="city" class="form-control" placeholder="<?= t('Cari kota atau nama hotel...') ?>" list="hotelCityList" value="<?= e($_GET['city'] ?? '') ?>">
                            <datalist id="hotelCityList">
                                <?php foreach ($hotelCities as $hc): ?><option value="<?= e($hc) ?>"></option><?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small fw-semibold text-muted"><?= t('Check-in') ?></label>
                            <input type="date" name="checkin" class="form-control" value="<?= e($hotelCheckin) ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small fw-semibold text-muted"><?= t('Check-out') ?></label>
                            <input type="date" name="checkout" class="form-control" value="<?= e($hotelCheckout) ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label small fw-semibold text-muted"><?= t('Tamu') ?></label>
                            <select name="guests" class="form-select">
                                <?php for ($g = 1; $g <= 6; $g++): ?>
                                    <option value="<?= $g ?>" <?= $g === 2 ? 'selected' : '' ?>><?= $g ?> <?= t('Tamu') ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-auto col-6">
                            <button type="submit" class="btn btn-primary w-100 px-4 py-2 fw-semibold">
                                <i class="bi bi-search me-1"></i><?= t('Cari Hotel') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            <a href="hotels.php" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold"><i class="bi bi-buildings me-1"></i><?= t('Semua Hotel') ?></a>
            <?php foreach (array_slice($hotelCities, 0, 5) as $hc): ?>
                <a href="hotels.php?city=<?= urlencode($hc) ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><?= e($hc) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

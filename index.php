<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Beranda';

$tours = getTours();
$featuredTours = array_slice($tours, 0, 8);
$categories = getCategories();

require_once 'includes/header.php';
?>

<!-- Hero – ala Klook -->
<section class="hero-klook d-flex align-items-center position-relative overflow-hidden">
    <div class="hero-bg"></div>
    <div class="container position-relative z-1">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center text-white">
                <h1 class="display-4 fw-bold mb-2">Jelajahi Petualanganmu</h1>
                <p class="lead mb-4 opacity-90">Temukan paket tour terbaik untuk liburan impian Anda</p>
                <div class="bg-white rounded-4 p-2 shadow-lg mx-auto" style="max-width: 560px;">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-0 shadow-none" placeholder="Cari destinasi atau paket tour..." id="heroSearch" onkeypress="if(event.key==='Enter') window.location='tours.php?search='+encodeURIComponent(this.value)">
                        <button class="btn btn-primary px-4 rounded-3 m-1" onclick="window.location='tours.php?search='+encodeURIComponent(document.getElementById('heroSearch').value)">Cari</button>
                    </div>
                </div>
                <!-- Category Pills -->
                <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                    <a href="tours.php" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold"><i class="bi bi-grid-fill me-1"></i>Semua</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="tours.php?category=<?= e($cat) ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><?= e($cat) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="bg-white border-bottom">
    <div class="container py-3">
        <div class="row text-center g-2">
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">150+</div>
                <small class="text-muted">Paket Tour</small>
            </div>
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">5.000+</div>
                <small class="text-muted">Pelanggan</small>
            </div>
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">12+</div>
                <small class="text-muted">Destinasi</small>
            </div>
            <div class="col-3">
                <div class="fw-bold text-primary fs-5">7</div>
                <small class="text-muted">Tahun</small>
            </div>
        </div>
    </div>
</section>

<!-- Kategori Pills (scrollable) -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex align-items-center gap-2 overflow-auto pb-2 kategori-scroll">
            <a href="tours.php" class="btn btn-primary rounded-pill fw-semibold px-4 flex-shrink-0">
                <i class="bi bi-stars me-1"></i>Semua
            </a>
            <?php foreach ($categories as $cat): ?>
                <?php
                    $icon = match($cat) {
                        'Domestik' => 'bi-flag',
                        'Internasional' => 'bi-globe2',
                        default => 'bi-compass'
                    };
                ?>
                <a href="tours.php?category=<?= e($cat) ?>" class="btn btn-outline-secondary rounded-pill px-4 flex-shrink-0">
                    <i class="bi <?= $icon ?> me-1"></i><?= e($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Tours – ala Klook card grid -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Rekomendasi Paket Tour</h4>
                <p class="text-muted mb-0 small">Pilihan terbaik untuk liburan Anda</p>
            </div>
            <a href="tours.php" class="btn btn-outline-primary rounded-pill px-4">Lihat Semua <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($featuredTours as $tour): ?>
            <div class="col-6 col-lg-3">
                <div class="card tour-card-klook border-0 shadow-sm h-100">
                    <div class="position-relative overflow-hidden rounded-top" style="height: 180px;">
                        <img src="<?= getTourImage($tour, 'medium') ?>" onerror="this.src='<?= getTourImageFallback($tour, 'medium') ?>'" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($tour['title']) ?>">
                        <span class="badge bg-white text-dark position-absolute top-0 start-0 m-2 shadow-sm"><?= e($tour['category']) ?></span>
                        <button class="btn btn-sm position-absolute top-0 end-0 m-1 text-white like-btn"><i class="bi bi-heart"></i></button>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-semibold mb-1 text-truncate"><?= e($tour['title']) ?></h6>
                        <div class="d-flex align-items-center text-muted small mb-2">
                            <i class="bi bi-clock me-1"></i>
                            <?php
                                $stmt = db()->prepare("SELECT MIN(departure_date) as next FROM tour_dates WHERE tour_id = ? AND departure_date >= CURDATE() AND is_active = 1");
                                $stmt->execute([$tour['id']]);
                                $nextDate = $stmt->fetch();
                            ?>
                            <?php if ($nextDate && $nextDate['next']): ?>
                                <?= tglIndonesia($nextDate['next']) ?>
                            <?php else: ?>
                                Segera
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary"><?= formatRupiah($tour['price']) ?></span>
                                <small class="text-muted">/org</small>
                            </div>
                            <a href="tour-detail.php?slug=<?= e($tour['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">Pesan</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Kenapa Pilih Kami – ala Klook trust -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <h4 class="fw-bold">Kenapa Pilih <?= SITE_NAME ?>?</h4>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-tags-fill"></i></div>
                    <h6 class="fw-semibold">Harga Transparan</h6>
                    <small class="text-muted">Tidak ada biaya tersembunyi</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-shield-check"></i></div>
                    <h6 class="fw-semibold">Terpercaya</h6>
                    <small class="text-muted">7 tahun melayani pelanggan</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-headset"></i></div>
                    <h6 class="fw-semibold">CS 24/7</h6>
                    <small class="text-muted">Siap bantu kapan saja</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-wallet2"></i></div>
                    <h6 class="fw-semibold">Mudah Booking</h6>
                    <small class="text-muted">Proses cepat & praktis</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Banner -->
<section class="py-5" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
    <div class="container text-center text-white">
        <h4 class="fw-bold mb-2">Siap Liburan?</h4>
        <p class="mb-4 opacity-90">Dapatkan promo spesial untuk pendaftaran hari ini</p>
        <a href="tours.php" class="btn btn-light btn-lg rounded-pill px-5 fw-semibold">Mulai Sekarang <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

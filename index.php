<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Beranda';

$tours = getTours();
$featuredTours = array_slice($tours, 0, 8);
$categories = getCategories();

// Ambil promo tours
$promoTours = db()->query("SELECT * FROM tours WHERE category = 'Promo' AND is_active = 1")->fetchAll();
if (!count($promoTours)) {
    // fallback ke tour dengan harga termurah
    $promoTours = db()->query("SELECT * FROM tours WHERE is_active = 1 AND price > 0 ORDER BY price ASC LIMIT 3")->fetchAll();
}

// Destinasi populer untuk section
$destinasi = [
    ['name' => 'Bali', 'category' => 'Domestik', 'img' => 'bali'],
    ['name' => 'China', 'category' => 'China', 'img' => 'china'],
    ['name' => 'Jepang', 'category' => 'Jepang', 'img' => 'japan'],
    ['name' => 'Korea', 'category' => 'Korea Selatan', 'img' => 'korea'],
    ['name' => 'Vietnam', 'category' => 'Vietnam', 'img' => 'vietnam'],
    ['name' => 'Singapore', 'category' => 'Internasional', 'img' => 'singapore'],
];

require_once 'includes/header.php';
?>

<!-- Hero – ala Klook -->
<section class="hero-klook d-flex align-items-center position-relative overflow-hidden">
    <div class="hero-bg"></div>
    <video id="heroVideo" class="hero-video" muted loop playsinline preload="none"></video>
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

<!-- Category Cards – ala Klook -->
<section class="py-4">
    <div class="container">
        <h5 class="fw-bold mb-3">Kategori Wisata</h5>
        <div class="row g-2">
            <?php
            $catIcons = [
                'Domestik' => ['bi-flag-fill', '#0d6efd'],
                'Internasional' => ['bi-globe2', '#6610f2'],
                'China' => ['bi-building', '#dc3545'],
                'Jepang' => ['bi-sun-fill', '#fd7e14'],
                'Korea Selatan' => ['bi-music-note-beamed', '#e83e8c'],
                'Vietnam' => ['bi-tree-fill', '#198754'],
                'Taiwan' => ['bi-geo-alt-fill', '#20c997'],
                'Kanada' => ['bi-snow2', '#0dcaf0'],
            ];
            $displayCats = ['Domestik', 'China', 'Jepang', 'Korea Selatan', 'Vietnam', 'Internasional'];
            foreach ($displayCats as $cat):
                $icon = $catIcons[$cat] ?? ['bi-compass', '#6f42c1'];
                $count = db()->prepare("SELECT COUNT(*) FROM tours WHERE category = ? AND is_active = 1");
                $count->execute([$cat]);
                $total = $count->fetchColumn();
            ?>
            <div class="col-4 col-md-2">
                <a href="tours.php?category=<?= e($cat) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm text-center py-3 cat-card">
                        <div class="fs-2 mb-1" style="color: <?= $icon[1] ?>;"><i class="bi <?= $icon[0] ?>"></i></div>
                        <h6 class="fw-semibold small mb-0 text-dark"><?= e($cat) ?></h6>
                        <small class="text-muted"><?= $total ?> paket</small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promo / Flash Deals Banner -->
<?php if (count($promoTours) > 0): ?>
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Flash Deals</h5>
            <a href="tours.php?category=Promo" class="btn btn-sm btn-outline-danger rounded-pill px-3">Lihat Semua</a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($promoTours, 0, 3) as $promo): ?>
            <div class="col-md-4">
                <a href="tour-detail.php?slug=<?= e($promo['slug']) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm overflow-hidden promo-card">
                        <div class="row g-0">
                            <div class="col-4">
                                <img src="<?= getTourImage($promo, 'small') ?>" class="h-100 w-100" style="object-fit: cover;" alt="">
                            </div>
                            <div class="col-8">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <span class="badge bg-danger small">HOT</span>
                                        <small class="text-muted">Promo</small>
                                    </div>
                                    <h6 class="fw-semibold small mb-1 text-dark"><?= e($promo['title']) ?></h6>
                                    <?php if ($promo['price'] > 0): ?>
                                        <span class="fw-bold text-primary small"><?= formatRupiah($promo['price']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Hubungi Kami</span>
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

<!-- Popular Destinations – ala Klook -->
<section class="py-4">
    <div class="container">
        <h5 class="fw-bold mb-3">Destinasi Populer</h5>
        <div class="row g-2">
            <?php foreach ($destinasi as $dest): ?>
            <div class="col-4 col-md-2">
                <a href="tours.php?category=<?= e($dest['category']) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm overflow-hidden dest-card">
                        <div class="dest-img" style="background-image: url('https://picsum.photos/seed/<?= $dest['img'] ?>/400/300');">
                            <div class="dest-overlay d-flex align-items-end p-2">
                                <span class="fw-semibold text-white small"><?= e($dest['name']) ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Tours – ala Klook card grid -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Rekomendasi Paket Tour</h5>
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

<!-- Social Proof – ala Klook -->
<section class="py-5">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm py-4 h-100">
                    <div class="display-5 text-warning mb-2">★ 4.8</div>
                    <div class="mb-1">
                        <span class="text-warning"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
                    </div>
                    <h6 class="fw-semibold mb-0">Rating Pelanggan</h6>
                    <small class="text-muted">Dari 2.000+ ulasan</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm py-4 h-100">
                    <div class="display-5 text-primary mb-2"><i class="bi bi-people-fill"></i></div>
                    <div class="fs-3 fw-bold text-primary">5.000+</div>
                    <h6 class="fw-semibold mb-0">Pelanggan Puas</h6>
                    <small class="text-muted">Tersebar di 12+ destinasi</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm py-4 h-100">
                    <div class="display-5 text-success mb-2"><i class="bi bi-hand-thumbs-up-fill"></i></div>
                    <div class="fs-3 fw-bold text-success">99%</div>
                    <h6 class="fw-semibold mb-0">Kepuasan</h6>
                    <small class="text-muted">Pelanggan merekomendasikan kami</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <h5 class="fw-bold">Apa Kata Mereka?</h5>
            <p class="text-muted small">Pengalaman pelanggan yang sudah traveling bersama kami</p>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://i.pravatar.cc/80?img=1" class="rounded-circle me-3" width="48" height="48" alt="">
                            <div>
                                <h6 class="fw-semibold mb-0">Sari Dewi</h6>
                                <small class="text-muted">Bali Paradise 5D4N</small>
                            </div>
                        </div>
                        <div class="text-warning small mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0 small text-muted">"Liburan ke Bali bareng TourAndTravel puas banget! Hotelnya enak, guide-nya ramah, itinerary-nya lengkap. Recommended!"</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://i.pravatar.cc/80?img=12" class="rounded-circle me-3" width="48" height="48" alt="">
                            <div>
                                <h6 class="fw-semibold mb-0">Bambang S.</h6>
                                <small class="text-muted">Beijing 8D7N</small>
                            </div>
                        </div>
                        <div class="text-warning small mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0 small text-muted">"Pertama kali ke China, awalnya khawatir tapi ternyata lancar semua. Guide lokalnya speak Indonesian,很棒!"</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://i.pravatar.cc/80?img=5" class="rounded-circle me-3" width="48" height="48" alt="">
                            <div>
                                <h6 class="fw-semibold mb-0">Rina A.</h6>
                                <small class="text-muted">Korea Tour 7D6N</small>
                            </div>
                        </div>
                        <div class="text-warning small mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-0 small text-muted">"Dari Seoul sampai Busan semua kece! Makin seru sama temen-temen satu grup. Next mau ke Jepang bareng sini lagi!"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kenapa Pilih Kami – ala Klook trust -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h5 class="fw-bold">Kenapa Pilih <?= SITE_NAME ?>?</h5>
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

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Beranda';

// Ambil featured tours (4 tour terbaru)
$tours = getTours();
$featuredTours = array_slice($tours, 0, 4);
$categories = getCategories();

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section d-flex align-items-center">
    <div class="container text-center text-white">
        <h1 class="display-4 fw-bold mb-3">Jelajahi Dunia Bersama Kami</h1>
        <p class="lead mb-4">Nikmati pengalaman traveling tak terlupakan dengan paket wisata terbaik</p>
        <a href="tours.php" class="btn btn-light btn-lg px-5 fw-semibold">Lihat Paket Tour</a>
    </div>
</section>

<!-- Statistik -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <div class="fs-1 fw-bold">150+</div>
                <div>Paket Tour</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fs-1 fw-bold">5.000+</div>
                <div>Pelanggan Puas</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fs-1 fw-bold">12+</div>
                <div>Negara Tujuan</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fs-1 fw-bold">7</div>
                <div>Tahun Pengalaman</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Tours -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Paket Tour Populer</h2>
            <p class="text-muted">Temukan destinasi impian Anda</p>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredTours as $tour): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card tour-card h-100 shadow-sm border-0">
                    <div class="position-relative overflow-hidden" style="height: 200px;">
                        <img src="<?= getTourImage($tour, 'medium') ?>" class="card-img-top h-100 w-100" style="object-fit: cover;" alt="<?= e($tour['title']) ?>">
                        <span class="badge bg-primary position-absolute top-0 end-0 m-2"><?= e($tour['category']) ?></span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-semibold"><?= e($tour['title']) ?></h5>
                        <p class="card-text small text-muted flex-grow-1"><?= substr(e($tour['description']), 0, 100) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="fw-bold text-primary fs-5"><?= formatRupiah($tour['price']) ?></span>
                            <a href="tour-detail.php?slug=<?= e($tour['slug']) ?>" class="btn btn-outline-primary btn-sm">Detail</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="tours.php" class="btn btn-primary btn-lg px-4">Lihat Semua Paket</a>
        </div>
    </div>
</section>

<!-- Kategori -->
<section class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Kategori Wisata</h2>
            <p class="text-muted">Pilih jenis perjalanan yang Anda inginkan</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-3">
                <a href="tours.php?category=<?= e($cat) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm text-center py-4 category-card">
                        <div class="card-body">
                            <i class="bi bi-compass-fill fs-1 text-primary mb-2 d-block"></i>
                            <h6 class="fw-semibold text-dark"><?= e($cat) ?></h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Kenapa Pilih Kami -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Kenapa Pilih Kami?</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="display-6 text-primary mb-3"><i class="bi bi-shield-check"></i></div>
                    <h5>Terpercaya</h5>
                    <p class="text-muted">Resmi terdaftar dan berpengalaman lebih dari 7 tahun melayani ribuan pelanggan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="display-6 text-primary mb-3"><i class="bi bi-wallet2"></i></div>
                    <h5>Harga Terbaik</h5>
                    <p class="text-muted">Dapatkan harga terbaik dengan berbagai pilihan paket yang sesuai budget Anda.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="display-6 text-primary mb-3"><i class="bi bi-headset"></i></div>
                    <h5>Support 24/7</h5>
                    <p class="text-muted">Tim customer service siap membantu Anda kapan saja selama perjalanan.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Banner -->
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h3 class="fw-bold mb-3">Siapkan Petualangan Anda!</h3>
        <p class="lead mb-4">Dapatkan promo spesial untuk pendaftaran early bird</p>
        <a href="tours.php" class="btn btn-light btn-lg px-5 fw-semibold">Mulai Sekarang</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

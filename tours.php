<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Paket Tour';
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$priceRange = $_GET['harga'] ?? null;
$duration = $_GET['durasi'] ?? null;
$rating = $_GET['rating'] ?? null;

$tours = getTours($category, $search, $priceRange, $duration, $rating);
$categories = getCategories();

// Hitung range durasi dari data
$durasiOptions = [
    '1' => '3-5 Hari',
    '2' => '6-8 Hari',
    '3' => '9+ Hari',
];
$hargaOptions = [
    '1' => '< Rp 5 Juta',
    '2' => 'Rp 5-10 Juta',
    '3' => 'Rp 10-20 Juta',
    '4' => '> Rp 20 Juta',
];
$ratingOptions = [
    '4.5' => '★ 4.5+',
    '4' => '★ 4.0+',
];

require_once 'includes/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0">Paket Tour</h4>
                <small class="text-muted"><?= count($tours) ?> tour ditemukan</small>
            </div>
            <a href="tours.php" class="btn btn-sm btn-outline-secondary rounded-pill <?= !$category && !$search && !$priceRange && !$duration && !$rating ? 'd-none' : '' ?>">
                <i class="bi bi-x-circle me-1"></i>Reset Filter
            </a>
        </div>

        <!-- Filter Bar -->
        <form method="GET" class="row g-2 mb-4">
            <div class="col-6 col-md-3">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="durasi" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Durasi</option>
                    <?php foreach ($durasiOptions as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $duration === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="harga" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Harga</option>
                    <?php foreach ($hargaOptions as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $priceRange === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Rating</option>
                    <?php foreach ($ratingOptions as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $rating === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Cari tour..." value="<?= e($search ?? '') ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </form>

        <!-- Tour Grid -->
        <?php if (count($tours) > 0): ?>
        <div class="row g-3">
            <?php foreach ($tours as $tour): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card tour-card-klook border-0 shadow-sm h-100">
                    <div class="position-relative overflow-hidden rounded-top" style="height: 200px;">
                        <img src="<?= getTourImage($tour, 'medium') ?>" onerror="this.src='<?= getTourImageFallback($tour, 'medium') ?>'" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($tour['title']) ?>">
                        <?php $diskon = getDiskonPersen($tour); if ($diskon > 0): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2 shadow-sm">-<?= $diskon ?>%</span>
                        <?php endif; ?>
                        <span class="badge bg-white text-dark position-absolute top-0 end-0 m-2 shadow-sm"><?= e($tour['category']) ?></span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column">
                        <h6 class="fw-semibold mb-1"><?= e($tour['title']) ?></h6>
                        <div class="d-flex align-items-center gap-2 small mb-1">
                            <?= renderStars($tour['rating']) ?>
                            <span class="text-muted">(<?= $tour['total_reviews'] ?>)</span>
                        </div>
                        <p class="small text-muted flex-grow-1 mb-2"><?= substr(e($tour['description']), 0, 100) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <div>
                                <span class="fw-bold text-primary"><?= formatRupiah($tour['price']) ?></span>
                                <?php if ($diskon > 0): ?>
                                    <small class="text-decoration-line-through text-muted ms-1"><?= formatRupiah($tour['original_price']) ?></small>
                                <?php endif; ?>
                                <small class="d-block text-muted">/orang</small>
                            </div>
                            <a href="tour-detail.php?slug=<?= e($tour['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">Detail</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-search fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Tidak ada tour ditemukan</p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4">Reset Filter</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

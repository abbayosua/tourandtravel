<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Paket Tour';
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$tours = getTours($category, $search);
$categories = getCategories();

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">Paket Tour</h2>
                <p class="text-muted">Temukan destinasi impian Anda</p>
            </div>
        </div>

        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-md-8 mb-2 mb-md-0">
                <div class="d-flex flex-wrap gap-2">
                    <a href="tours.php" class="btn btn-sm <?= !$category ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="tours.php?category=<?= e($cat) ?>" class="btn btn-sm <?= $category === $cat ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($cat) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Cari tour..." value="<?= e($search ?? '') ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Tour Grid -->
        <?php if (count($tours) > 0): ?>
        <div class="row g-4">
            <?php foreach ($tours as $tour): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card tour-card h-100 shadow-sm border-0">
                    <div class="position-relative overflow-hidden" style="height: 220px;">
                        <img src="<?= getTourImage($tour, 'medium') ?>" class="card-img-top h-100 w-100" style="object-fit: cover;" alt="<?= e($tour['title']) ?>">
                        <span class="badge bg-primary position-absolute top-0 end-0 m-2"><?= e($tour['category']) ?></span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-semibold"><?= e($tour['title']) ?></h5>
                        <p class="card-text small text-muted flex-grow-1"><?= substr(e($tour['description']), 0, 120) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <div>
                                <span class="fw-bold text-primary fs-5"><?= formatRupiah($tour['price']) ?></span>
                                <span class="d-block small text-muted">/orang</span>
                            </div>
                            <a href="tour-detail.php?slug=<?= e($tour['slug']) ?>" class="btn btn-primary">Lihat Detail</a>
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
            <a href="tours.php" class="btn btn-primary">Reset Filter</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

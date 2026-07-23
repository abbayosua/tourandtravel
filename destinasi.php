<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$city = $_GET['city'] ?? '';
$cityClean = trim($city);

if (!$cityClean) {
    header('Location: tours.php');
    exit;
}

$pageTitle = "Paket Tour ke $cityClean";
$tours = getToursByCity($cityClean);
$tourCount = count($tours);

require_once 'includes/header.php';
?>

<section class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="tours.php">Paket Tour</a></li>
                <li class="breadcrumb-item active"><?= e($cityClean) ?></li>
            </ol>
        </nav>

        <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width: 60px; height: 60px; border-radius: 12px; background: url('https://picsum.photos/seed/<?= urlencode(strtolower($cityClean)) ?>/120/120') center/cover no-repeat;" class="shadow-sm flex-shrink-0"></div>
            <div>
                <h4 class="fw-bold mb-1">Paket Tour ke <?= e($cityClean) ?></h4>
                <p class="text-muted mb-0 small"><?= $tourCount ?> paket tour tersedia</p>
            </div>
        </div>

        <?php if ($tourCount > 0): ?>
        <div class="row g-3">
            <?php foreach ($tours as $tour): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card tour-card-klook border-0 shadow-sm h-100">
                    <div class="position-relative overflow-hidden rounded-top" style="height: 200px;">
                        <img src="<?= getTourImage($tour, 'medium') ?>" onerror="this.src='<?= getTourImageFallback($tour, 'medium') ?>'" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($tour['title']) ?>">
                        <span class="badge bg-white text-dark position-absolute top-0 start-0 m-2 shadow-sm"><?= e($tour['category']) ?></span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column">
                        <h6 class="fw-semibold mb-1"><?= e($tour['title']) ?></h6>
                        <p class="small text-muted flex-grow-1 mb-2"><?= substr(e($tour['description']), 0, 100) ?>...</p>
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
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-geo-alt fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Belum ada paket tour ke <strong><?= e($cityClean) ?></strong> saat ini.</p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4">Lihat Semua Tour</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

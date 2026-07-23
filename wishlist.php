<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

$userId = $_SESSION['user_id'];
$wishlists = getUserWishlists($userId);

$pageTitle = 'Wishlist Saya';
require_once 'includes/header.php';
?>

<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-heart-fill text-danger me-2"></i>Wishlist Saya</h4>

        <?php if (count($wishlists) > 0): ?>
        <div class="row g-3">
            <?php foreach ($wishlists as $tour): ?>
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
            <i class="bi bi-heart fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Belum ada tour yang disimpan.</p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4">Jelajahi Tour</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

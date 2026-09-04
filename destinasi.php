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

$pageTitle = str_replace(':city', $cityClean, t('Paket Tour ke :city'));
$tours = getToursByCity($cityClean);
$tourCount = count($tours);

$wishlistIds = [];
if (isLoggedIn()) {
    $wishlistIds = getWishlistIds($_SESSION['user_id']);
}

require_once 'includes/components/tour-card.php';
require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>

<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('Beranda'), 'url' => 'index.php'],
            ['label' => t('Paket Tour'), 'url' => 'tours.php'],
            ['label' => $cityClean, 'url' => null],
        ]); ?>

        <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width: 60px; height: 60px; border-radius: 12px; background: url('<?= getDestinasiImage($cityClean) ?>') center/cover no-repeat;" class="shadow-sm flex-shrink-0"></div>
            <div>
                <h4 class="fw-bold mb-1"><?= e(str_replace(':city', $cityClean, t('Paket Tour ke :city'))) ?></h4>
                <p class="text-muted mb-0 small"><?= $tourCount ?> <?= t('paket tour tersedia') ?></p>
            </div>
        </div>

        <?php if ($tourCount > 0): ?>
        <div class="row g-3">
            <?php foreach ($tours as $tour): ?>
                <?php renderTourCard($tour, $wishlistIds); ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-geo-alt fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= str_replace(':city', '<strong>' . e($cityClean) . '</strong>', t('Belum ada paket tour ke :city saat ini.')) ?></p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4"><?= t('Lihat Semua Tour') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM collections WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$collection = $stmt->fetch();

if (!$collection) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = t('Collection Tidak Ditemukan');
    require_once 'includes/header-klook.php';
    echo '<div class="container py-5 text-center"><h3>' . t('Collection tidak ditemukan') . '</h3><a href="index.php" class="btn btn-primary mt-3">' . t('Kembali ke Beranda') . '</a></div>';
    require_once 'includes/footer-klook.php';
    exit;
}

$pageTitle = $collection['name'];

// Ambil item collection (tours dulu, fallback attraction)
$items = db()->prepare("SELECT item_type, item_id FROM collection_items WHERE collection_id = ? ORDER BY sort_order ASC");
$items->execute([$collection['id']]);
$tourIds = [];
foreach ($items->fetchAll() as $it) {
    if ($it['item_type'] === 'tour') $tourIds[] = (int)$it['item_id'];
}

$tours = [];
if (count($tourIds)) {
    $in = implode(',', array_fill(0, count($tourIds), '?'));
    $st = db()->prepare("SELECT * FROM tours WHERE id IN ($in) AND is_active = 1");
    $st->execute($tourIds);
    $tours = $st->fetchAll();
}

// Fallback: best seller jika collection kosong
if (empty($tours)) {
    $tours = db()->query("SELECT * FROM tours WHERE is_active = 1 AND best_seller = 1 LIMIT 8")->fetchAll();
}

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
            ['label' => $collection['name'], 'url' => null],
        ]); ?>

        <!-- Header Collection -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div style="height: 180px; background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);" class="d-flex align-items-center">
                <div class="container py-4">
                    <h3 class="fw-bold text-white mb-1"><?= e(t($collection['name'])) ?></h3>
                    <?php if ($collection['description']): ?>
                        <p class="text-white-50 mb-0"><?= e(t($collection['description'])) ?></p>
                    <?php endif; ?>
                    <small class="text-white-50"><?= count($tours) ?> <?= t('tour') ?></small>
                </div>
            </div>
        </div>

        <?php if (count($tours) > 0): ?>
        <div class="row g-3">
            <?php foreach ($tours as $tour): ?>
                <?php renderTourCard($tour, $wishlistIds); ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-collection fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Belum ada item di collection ini.') ?></p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4"><?= t('Jelajahi Tour') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Tiket Tempat Wisata';
$city = $_GET['city'] ?? '';
$category = $_GET['category'] ?? '';

$cities = db()->query("SELECT DISTINCT city FROM attractions WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$categories = db()->query("SELECT DISTINCT category FROM attractions WHERE is_active = 1 AND category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM attractions WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city = ?"; $params[] = $city; }
if ($category) { $sql .= " AND category = ?"; $params[] = $category; }
$sql .= " ORDER BY best_seller DESC, price ASC";
$attractions = db()->prepare($sql);
$attractions->execute($params);
$attractions = $attractions->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Tiket Tempat Wisata'), 'url' => null]]); ?>

        <div class="row">
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET">
                                <h6 class="fw-semibold mb-2"><?= t('Kota') ?></h6>
                                <select name="city" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Kota') ?></option>
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?= e($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Kategori') ?></h6>
                                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Kategori') ?></option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <?php if (count($attractions) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($attractions) ?> <?= t('tiket ditemukan') ?></small>
                </div>
                <div class="row g-3">
                    <?php foreach ($attractions as $a): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <div class="position-relative overflow-hidden rounded-top" style="height: 180px;">
                                <img src="<?= e($a['cover_image'] ?: 'https://placehold.co/640x480?text=' . urlencode($a['name'])) ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($a['name']) ?>">
                                <?php if (!empty($a['best_seller'])): ?>
                                    <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm">Best Seller</span>
                                <?php endif; ?>
                                <?php if (!empty($a['instant_confirmation'])): ?>
                                    <span class="badge bg-success position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px;"><i class="bi bi-lightning-charge-fill me-1"></i>Instan</span>
                                <?php endif; ?>
                                <?php if (!empty($a['free_cancellation'])): ?>
                                    <span class="badge bg-info text-white position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px; margin-top: 28px !important;"><i class="bi bi-shield-check me-1"></i>Batal Gratis</span>
                                <?php endif; ?>
                                <?php if ($a['category']): ?>
                                    <span class="badge bg-white text-dark position-absolute top-0 start-0 m-2 shadow-sm" style="margin-top: 38px;"><?= e($a['category']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e($a['name']) ?></h6>
                                <p class="small text-muted flex-grow-1 mb-2"><?= e($a['city']) ?> · <?= e($a['duration'] ?? '') ?></p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                    <div>
                                        <span class="fw-bold text-primary"><?= formatCurrencySpan($a['price'], $a['price_currency'] ?? 'IDR') ?></span>
                                        <small class="d-block text-muted">/ <?= t('orang') ?></small>
                                    </div>
                                    <a href="attraction-detail.php?slug=<?= e($a['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3"><?= t('Pesan') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-ticket fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada tiket ditemukan.') ?></p>
                    <a href="attractions.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset Filter') ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
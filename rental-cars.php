<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Rental Mobil');
$city = $_GET['city'] ?? '';
$type = $_GET['type'] ?? '';
$cities = db()->query("SELECT DISTINCT city FROM rental_cars WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$types = db()->query("SELECT DISTINCT car_type FROM rental_cars WHERE is_active = 1 ORDER BY car_type")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM rental_cars WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city = ?"; $params[] = $city; }
if ($type) { $sql .= " AND car_type = ?"; $params[] = $type; }
$sql .= " ORDER BY price_per_day ASC";
$cars = db()->prepare($sql);
$cars->execute($params);
$cars = $cars->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Rental Mobil'), 'url' => null]]); ?>

        <div class="row">
            <!-- Sidebar Filter -->
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

                                <h6 class="fw-semibold mb-2"><?= t('Tipe Mobil') ?></h6>
                                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Tipe') ?></option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9">
                <div class="row g-3">
                    <?php foreach ($cars as $car): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <div class="position-relative overflow-hidden rounded-top" style="height: 180px;">
                                <img src="https://placehold.co/640x480?text=<?= urlencode($car['name']) ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= e(tContent($car, 'name')) ?>">
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm"><?= e($car['car_type']) ?></span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e(tContent($car, 'name')) ?></h6>
                                <div class="d-flex gap-2 small text-muted mb-2">
                                    <span><i class="bi bi-geo-alt me-1"></i><?= e($car['city']) ?></span>
                                    <span><i class="bi bi-people me-1"></i><?= $car['passenger_capacity'] ?> <?= t('kursi') ?></span>
                                    <span><i class="bi bi-gear me-1"></i><?= ucfirst($car['transmission']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                    <span class="fw-bold text-primary"><?= formatCurrencySpan($car['price_per_day']) ?><small class="fw-normal text-muted">/ <?= t('hari') ?></small></span>
                                    <a href="rental-car-detail.php?slug=<?= e($car['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3"><?= t('Sewa') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($cars)): ?><div class="col-12 text-center py-5 text-muted"><?= t('Tidak ada mobil.') ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
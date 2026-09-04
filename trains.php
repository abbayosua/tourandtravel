<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Kereta Api');
$routeFrom = $_GET['from'] ?? '';
$routeTo = $_GET['to'] ?? '';
$class = $_GET['class'] ?? '';
$sort = $_GET['sort'] ?? 'price';

$fromCities = db()->query("SELECT DISTINCT route_from FROM trains WHERE is_active = 1 ORDER BY route_from")->fetchAll(PDO::FETCH_COLUMN);
$toCities = db()->query("SELECT DISTINCT route_to FROM trains WHERE is_active = 1 ORDER BY route_to")->fetchAll(PDO::FETCH_COLUMN);
$classes = db()->query("SELECT DISTINCT class FROM trains WHERE is_active = 1 AND class IS NOT NULL ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM trains WHERE is_active = 1";
$params = [];
if ($routeFrom) { $sql .= " AND route_from LIKE ?"; $params[] = "%$routeFrom%"; }
if ($routeTo) { $sql .= " AND route_to LIKE ?"; $params[] = "%$routeTo%"; }
if ($class) { $sql .= " AND class = ?"; $params[] = $class; }
$sortCol = match($sort) { 'price' => 'price ASC', 'price_desc' => 'price DESC', 'name' => 'name ASC', 'duration' => 'duration ASC', default => 'price ASC' };
$sql .= " ORDER BY $sortCol";
$trains = db()->prepare($sql);
$trains->execute($params);
$trains = $trains->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Kereta Api'), 'url' => null]]); ?>

        <div class="row">
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET">
                                <h6 class="fw-semibold mb-2"><?= t('Dari') ?></h6>
                                <select name="from" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Kota') ?></option>
                                    <?php foreach ($fromCities as $c): ?>
                                        <option value="<?= e($c) ?>" <?= $routeFrom === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Ke') ?></h6>
                                <select name="to" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Kota') ?></option>
                                    <?php foreach ($toCities as $c): ?>
                                        <option value="<?= e($c) ?>" <?= $routeTo === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Kelas') ?></h6>
                                <select name="class" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Kelas') ?></option>
                                    <?php foreach ($classes as $v): ?>
                                        <option value="<?= e($v) ?>" <?= $class === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Urutkan') ?></h6>
                                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>><?= t('Harga Terendah') ?></option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= t('Harga Tertinggi') ?></option>
                                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>><?= t('Nama') ?></option>
                                    <option value="duration" <?= $sort === 'duration' ? 'selected' : '' ?>><?= t('Durasi') ?></option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <?php if (count($trains) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($trains) ?> <?= t('kereta ditemukan') ?></small>
                </div>
                <div class="row g-3">
                    <?php foreach ($trains as $tr): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <div class="position-relative overflow-hidden rounded-top" style="height: 160px;">
                                <img src="https://placehold.co/640x400?text=Train" class="w-100 h-100" style="object-fit: cover;" alt="<?= e(tContent($tr, 'name')) ?>">
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm"><?= e($tr['class']) ?></span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e(tContent($tr, 'name')) ?></h6>
                                <p class="small text-muted flex-grow-1 mb-2">
                                    <i class="bi bi-geo-alt me-1"></i><?= e($tr['route_from']) ?> → <?= e($tr['route_to']) ?>
                                    <br><i class="bi bi-clock me-1"></i><?= substr($tr['departure_time'], 0, 5) ?> - <?= substr($tr['arrival_time'], 0, 5) ?> · <?= e($tr['duration']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                    <div>
                                        <span class="fw-bold text-primary"><?= formatCurrencySpan($tr['price'], $tr['price_currency'] ?? 'IDR') ?></span>
                                        <small class="d-block text-muted">/ <?= t('orang') ?></small>
                                    </div>
                                    <a href="train-detail.php?slug=<?= e($tr['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3"><?= t('Pesan') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-train-front fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada kereta ditemukan.') ?></p>
                    <a href="trains.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset Filter') ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
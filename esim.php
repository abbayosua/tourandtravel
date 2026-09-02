<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('eSIM & Connectivity');
$type = $_GET['type'] ?? '';
$country = $_GET['country'] ?? '';

$types = db()->query("SELECT DISTINCT type FROM connectivity_products WHERE is_active = 1 ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
$countries = db()->query("SELECT DISTINCT country FROM connectivity_products WHERE is_active = 1 ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM connectivity_products WHERE is_active = 1";
$params = [];
if ($type) { $sql .= " AND type = ?"; $params[] = $type; }
if ($country) { $sql .= " AND country = ?"; $params[] = $country; }
$sql .= " ORDER BY duration_days ASC, price ASC";
$products = db()->prepare($sql);
$products->execute($params);
$products = $products->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('eSIM & Connectivity'), 'url' => null]]); ?>

        <div class="row">
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET">
                                <h6 class="fw-semibold mb-2"><?= t('Tipe') ?></h6>
                                <select name="type" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Tipe') ?></option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst(e($t)) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Negara') ?></h6>
                                <select name="country" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Negara') ?></option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <?php if (count($products) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($products) ?> <?= t('produk ditemukan') ?></small>
                </div>
                <div class="row g-3">
                    <?php foreach ($products as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <div class="position-relative overflow-hidden rounded-top" style="height: 160px;">
                                <img src="https://placehold.co/640x400?text=<?= urlencode($p['type'])?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($p['name']) ?>">
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm"><?= strtoupper(e($p['type'])) ?></span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e($p['name']) ?></h6>
                                <p class="small text-muted flex-grow-1 mb-2">
                                    <i class="bi bi-globe me-1"></i><?= e($p['country']) ?> · <?= e($p['coverage'] ?? $p['country']) ?>
                                    <br><i class="bi bi-wifi me-1"></i><?= e($p['data_quota']) ?> · <?= $p['duration_days'] ?> <?= t('hari') ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                    <div>
                                        <span class="fw-bold text-primary"><?= formatCurrencySpan($p['price'], $p['price_currency'] ?? 'IDR') ?></span>
                                    </div>
                                    <a href="esim-detail.php?slug=<?= e($p['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3"><?= t('Pesan') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-wifi-off fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada produk ditemukan.') ?></p>
                    <a href="esim.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset Filter') ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
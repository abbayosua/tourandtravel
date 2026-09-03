<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Transfer Bandara');
$fromCity = $_GET['from'] ?? '';
$toCity = $_GET['to'] ?? '';
$vehicleType = $_GET['vehicle'] ?? '';

$fromCities = db()->query("SELECT DISTINCT from_city FROM transfers WHERE is_active = 1 ORDER BY from_city")->fetchAll(PDO::FETCH_COLUMN);
$toCities = db()->query("SELECT DISTINCT to_city FROM transfers WHERE is_active = 1 ORDER BY to_city")->fetchAll(PDO::FETCH_COLUMN);
$vehicleTypes = db()->query("SELECT DISTINCT vehicle_type FROM transfers WHERE is_active = 1 AND vehicle_type IS NOT NULL ORDER BY vehicle_type")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM transfers WHERE is_active = 1";
$params = [];
if ($fromCity) { $sql .= " AND from_city LIKE ?"; $params[] = "%$fromCity%"; }
if ($toCity) { $sql .= " AND to_city LIKE ?"; $params[] = "%$toCity%"; }
if ($vehicleType) { $sql .= " AND vehicle_type = ?"; $params[] = $vehicleType; }
$sql .= " ORDER BY price ASC";
$transfers = db()->prepare($sql);
$transfers->execute($params);
$transfers = $transfers->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Transfer Bandara'), 'url' => null]]); ?>

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
                                    <option value=""><?= t('Semua Lokasi') ?></option>
                                    <?php foreach ($fromCities as $c): ?>
                                        <option value="<?= e($c) ?>" <?= $fromCity === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Ke') ?></h6>
                                <select name="to" class="form-select form-select-sm mb-3" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Tujuan') ?></option>
                                    <?php foreach ($toCities as $c): ?>
                                        <option value="<?= e($c) ?>" <?= $toCity === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <h6 class="fw-semibold mb-2"><?= t('Tipe Kendaraan') ?></h6>
                                <select name="vehicle" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value=""><?= t('Semua Tipe') ?></option>
                                    <?php foreach ($vehicleTypes as $v): ?>
                                        <option value="<?= e($v) ?>" <?= $vehicleType === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <?php if (count($transfers) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($transfers) ?> <?= t('transfer ditemukan') ?></small>
                </div>
                <div class="row g-3">
                    <?php foreach ($transfers as $t): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <div class="position-relative overflow-hidden rounded-top" style="height: 160px;">
                                <img src="https://placehold.co/640x400?text=Transfer" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($t['name']) ?>">
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm"><?= e($t['vehicle_type'] ?? 'Transfer') ?></span>
                                <?php if (!empty($t['instant_confirmation'])): ?>
                                    <span class="badge bg-success position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px;"><i class="bi bi-lightning-charge-fill me-1"></i><?= t('Instan') ?></span>
                                <?php endif; ?>
                                <?php if (!empty($t['free_cancellation'])): ?>
                                    <span class="badge bg-info text-white position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px; margin-top: 28px !important;"><i class="bi bi-shield-check me-1"></i><?= t('Batal Gratis') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e($t['name']) ?></h6>
                                <p class="small text-muted flex-grow-1 mb-2"><i class="bi bi-arrow-left-right me-1"></i><?= e($t['from_city']) ?> → <?= e($t['to_city']) ?> · <?= $t['max_passengers'] ?> pax</p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                    <div>
                                        <span class="fw-bold text-primary"><?= formatCurrencySpan($t['price'], $t['price_currency'] ?? 'IDR') ?></span>
                                        <small class="d-block text-muted">/ <?= t('kendaraan') ?></small>
                                    </div>
                                    <a href="transfer-detail.php?slug=<?= e($t['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3"><?= t('Pesan') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-car-front fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada transfer ditemukan.') ?></p>
                    <a href="transfers.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset Filter') ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
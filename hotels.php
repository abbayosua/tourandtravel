<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Hotel';
$city = $_GET['city'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = (int)($_GET['guests'] ?? 2);
$stars = $_GET['stars'] ?? '';
$sort = $_GET['sort'] ?? 'price';

$cities = db()->query("SELECT DISTINCT city FROM hotels WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM hotels WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city LIKE ?"; $params[] = "%$city%"; }
if ($stars) { $sql .= " AND star_rating = ?"; $params[] = (int)$stars; }
$sql .= match($sort) {
    'price' => " ORDER BY price_per_night ASC",
    'price_desc' => " ORDER BY price_per_night DESC",
    'stars' => " ORDER BY star_rating DESC, price_per_night ASC",
    default => " ORDER BY price_per_night ASC"
};
$hotels = db()->prepare($sql);
$hotels->execute($params);
$hotels = $hotels->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4 bg-light">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Hotel'), 'url' => null]]); ?>

        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <div class="collapse d-lg-block" id="filterCollapse">
                            <form method="GET" class="row g-2 align-items-end">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Kota') ?></label>
                                    <input type="text" name="city" class="form-control form-control-sm" placeholder="Cari kota..." value="<?= e($city) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">Check-in</label>
                                    <input type="date" name="checkin" class="form-control form-control-sm" value="<?= e($checkin ?: date('Y-m-d')) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">Check-out</label>
                                    <input type="date" name="checkout" class="form-control form-control-sm" value="<?= e($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Tamu') ?></label>
                                    <select name="guests" class="form-select form-select-sm">
                                        <?php for ($g=1; $g<=6; $g++): ?>
                                        <option value="<?= $g ?>" <?= $guests === $g ? 'selected' : '' ?>><?= $g ?> Tamu</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Bintang') ?></label>
                                    <select name="stars" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value=""><?= t('Semua Bintang') ?></option>
                                        <?php for ($s=5; $s>=3; $s--): ?>
                                        <option value="<?= $s ?>" <?= $stars == $s ? 'selected' : '' ?>><?= str_repeat('★', $s) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Urutkan') ?></label>
                                    <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>><?= t('Harga Termurah') ?></option>
                                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= t('Harga Termahal') ?></option>
                                        <option value="stars" <?= $sort === 'stars' ? 'selected' : '' ?>><?= t('Bintang Tertinggi') ?></option>
                                    </select>
                                </div>
                                <div class="col-12 d-grid mt-3">
                                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search me-1"></i><?= t('Cari') ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9">
                <?php if (count($hotels) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($hotels) ?> <?= t('hotel ditemukan') ?></small>
                </div>
                <div class="row g-3">
                    <?php foreach ($hotels as $h): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <div class="position-relative overflow-hidden rounded-top" style="height: 180px;">
                                <img src="https://placehold.co/640x480?text=<?= urlencode($h['name']) ?>" class="w-100 h-100" style="object-fit: cover;" alt="">
                                <span class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark shadow-sm"><?= str_repeat('★', $h['star_rating']) ?></span>
                                <?php if (!empty($h['instant_confirmation'])): ?>
                                <span class="badge bg-success position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px;"><i class="bi bi-lightning-charge-fill me-1"></i>Instan</span>
                                <?php endif; ?>
                                <?php if (!empty($h['free_cancellation'])): ?>
                                <span class="badge bg-info text-white position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px; margin-top: 28px !important;"><i class="bi bi-shield-check me-1"></i>Batal Gratis</span>
                                <?php endif; ?>
                                <span class="position-absolute bottom-0 end-0 m-2 badge bg-white text-dark shadow-sm"><i class="bi bi-geo-alt me-1"></i><?= e($h['city']) ?></span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e($h['name']) ?></h6>
                                <p class="small text-muted flex-grow-1 mb-2"><?= substr(e($h['description']), 0, 80) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div>
                                        <span class="fw-bold text-primary fs-5"><?= formatRupiah($h['price_per_night']) ?></span>
                                        <small class="text-muted">/malam</small>
                                    </div>
                                    <a href="hotel-detail.php?slug=<?= e($h['slug']) ?>&checkin=<?= urlencode($checkin ?: date('Y-m-d')) ?>&checkout=<?= urlencode($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>&guests=<?= $guests ?>" class="btn btn-sm btn-primary rounded-pill px-3">Pesan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Tidak ada hotel ditemukan.') ?></p>
                    <a href="hotels.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
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
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$amenityFilter = trim($_GET['amenity'] ?? '');
$freeCancel = (int)($_GET['free_cancel'] ?? 0);
$instantConf = (int)($_GET['instant'] ?? 0);
$bestSeller = (int)($_GET['best'] ?? 0);

$cities = db()->query("SELECT DISTINCT city FROM hotels WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM hotels WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city LIKE ?"; $params[] = "%$city%"; }
if ($stars) { $sql .= " AND star_rating = ?"; $params[] = (int)$stars; }
if ($minPrice !== '') { $sql .= " AND price_per_night >= ?"; $params[] = (float)$minPrice; }
if ($maxPrice !== '') { $sql .= " AND price_per_night <= ?"; $params[] = (float)$maxPrice; }
if ($amenityFilter) { $sql .= " AND amenities LIKE ?"; $params[] = "%$amenityFilter%"; }
if ($freeCancel) { $sql .= " AND free_cancellation = 1"; }
if ($instantConf) { $sql .= " AND instant_confirmation = 1"; }
if ($bestSeller) { $sql .= " AND best_seller = 1"; }
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

        <!-- Agoda-style search bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <form method="GET" class="row g-2 g-md-3 align-items-end">
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Kota') ?></div>
                            <input type="text" name="city" class="form-control" placeholder="<?= t('Cari kota...') ?>" value="<?= e($city) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Check-in') ?></div>
                            <input type="date" name="checkin" class="form-control" value="<?= e($checkin ?: date('Y-m-d')) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Check-out') ?></div>
                            <input type="date" name="checkout" class="form-control" value="<?= e($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Tamu') ?></div>
                            <select name="guests" class="form-select">
                                <?php for ($g=1; $g<=10; $g++): ?>
                                <option value="<?= $g ?>" <?= $guests === $g ? 'selected' : '' ?>><?= $g ?> <?= t('Tamu') ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-auto d-grid">
                        <button class="btn btn-primary traveloka-search-btn px-4" type="submit"><i class="bi bi-search me-1"></i><?= t('Cari') ?></button>
                    </div>
                </form>
            </div>
        </div>

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
                                <input type="hidden" name="city" value="<?= e($city) ?>">
                                <input type="hidden" name="checkin" value="<?= e($checkin ?: date('Y-m-d')) ?>">
                                <input type="hidden" name="checkout" value="<?= e($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>">
                                <input type="hidden" name="guests" value="<?= $guests ?>">
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
                                    <label class="form-label small fw-semibold text-muted"><?= t('Harga per Malam') ?></label>
                                    <div class="d-flex gap-2">
                                        <input type="number" name="min_price" class="form-control form-control-sm" placeholder="<?= t('Min') ?>" value="<?= e($minPrice) ?>" min="0">
                                        <input type="number" name="max_price" class="form-control form-control-sm" placeholder="<?= t('Max') ?>" value="<?= e($maxPrice) ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-muted"><?= t('Fasilitas') ?></label>
                                    <input type="text" name="amenity" class="form-control form-control-sm" placeholder="<?= t('WiFi, Parkir, Kolam...') ?>" value="<?= e($amenityFilter) ?>">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="best" value="1" id="fltBest" <?= $bestSeller ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="fltBest"><?= t('Best Seller') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="free_cancel" value="1" id="fltCancel" <?= $freeCancel ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="fltCancel"><?= t('Batal Gratis') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="instant" value="1" id="fltInstant" <?= $instantConf ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="fltInstant"><?= t('Konfirmasi Instan') ?></label>
                                    </div>
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

            <!-- Results: Agoda list view -->
            <div class="col-lg-9">
                <?php if (count($hotels) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($hotels) ?> <?= t('hotel ditemukan') ?></small>
                    <div class="d-flex gap-1">
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price']))) ?>" class="btn btn-sm <?= $sort === 'price' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termurah') ?></a>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price_desc']))) ?>" class="btn btn-sm <?= $sort === 'price_desc' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Harga Termahal') ?></a>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'stars']))) ?>" class="btn btn-sm <?= $sort === 'stars' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Bintang Tertinggi') ?></a>
                    </div>
                </div>
                <?php foreach ($hotels as $h): 
                    $amenities = array_filter(array_map('trim', explode(',', $h['amenities'] ?? '')));
                    $linkParams = 'slug=' . e($h['slug']) . '&checkin=' . urlencode($checkin ?: date('Y-m-d')) . '&checkout=' . urlencode($checkout ?: date('Y-m-d', strtotime('+2 days'))) . '&guests=' . $guests;
                ?>
                <div class="card border-0 shadow-sm mb-3 overflow-hidden klook-hover-card">
                    <div class="row g-0">
                        <div class="col-md-3 col-4" style="min-height: 160px;">
                            <img src="https://placehold.co/640x480?text=<?= urlencode($h['name']) ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($h['name']) ?>" loading="lazy">
                        </div>
                        <div class="col-md-9 col-8">
                            <div class="card-body p-3 d-flex flex-column h-100">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-semibold mb-1"><?= e(tContent($h, 'name')) ?></h6>
                                        <div class="small text-warning mb-1"><?= str_repeat('★', $h['star_rating']) ?></div>
                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($h['city']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-primary fs-5"><?= formatRupiah($h['price_per_night']) ?></span>
                                        <small class="d-block text-muted" style="font-size: 11px;"><?= t('/malam termasuk pajak') ?></small>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <?php if (!empty($h['best_seller'])): ?><span class="badge bg-danger" style="font-size: 10px;"><?= t('Best Seller') ?></span><?php endif; ?>
                                    <?php if (!empty($h['instant_confirmation'])): ?><span class="badge bg-success" style="font-size: 10px;"><i class="bi bi-lightning-charge-fill me-1"></i><?= t('Instan') ?></span><?php endif; ?>
                                    <?php if (!empty($h['free_cancellation'])): ?><span class="badge bg-info text-white" style="font-size: 10px;"><i class="bi bi-shield-check me-1"></i><?= t('Batal Gratis') ?></span><?php endif; ?>
                                </div>
                                <?php if (count($amenities) > 0): ?>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <?php foreach (array_slice($amenities, 0, 5) as $am): ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 10px;"><?= e(trim($am)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="mt-auto pt-2">
                                    <a href="hotel-detail.php?<?= $linkParams ?>" class="btn btn-primary rounded-pill px-4 py-1" style="font-size: 13px;"><?= t('Pesan') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/kereta.php';

$pageTitle = t('Kereta Api');
$routeFrom = $_GET['from'] ?? '';
$routeTo = $_GET['to'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$search = isset($_GET['search']);
$class = $_GET['class'] ?? '';
$sort = $_GET['sort'] ?? 'price';

$fromCities = db()->query("SELECT DISTINCT route_from FROM trains WHERE is_active = 1 ORDER BY route_from")->fetchAll(PDO::FETCH_COLUMN);
$toCities = db()->query("SELECT DISTINCT route_to FROM trains WHERE is_active = 1 ORDER BY route_to")->fetchAll(PDO::FETCH_COLUMN);
$classes = db()->query("SELECT DISTINCT class FROM trains WHERE is_active = 1 AND class IS NOT NULL ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);

$trains = [];
$keretaError = null;

if ($search && $routeFrom && $routeTo) {
    $fromCode = $KERETA_STATION_CODE_MAP[$routeFrom] ?? '';
    $toCode = $KERETA_STATION_CODE_MAP[$routeTo] ?? '';

    if ($fromCode && $toCode) {
        $trips = keretaSearchTrips($fromCode, $toCode, $date, 1, 0, 0);
        if (!empty($trips)) {
            foreach ($trips as $t) {
                $trains[] = [
                    'id' => 'kereta_' . md5($t['train_code']),
                    'name' => $t['train_name'],
                    'slug' => '',
                    'class' => $t['train_class'],
                    'route_from' => $t['train_from'],
                    'route_to' => $t['train_to'],
                    'departure_time' => $t['departure_time'],
                    'arrival_time' => $t['arrival_time'],
                    'duration' => '',
                    'price' => $t['train_price'],
                    'price_currency' => 'IDR',
                    'is_kereta_api' => true,
                    'train_code' => $t['train_code'],
                ];
            }
        } else {
            $keretaError = 'Tidak ada jadwal kereta ditemukan untuk rute/tanggal ini.';
        }
    } else {
        $keretaError = 'Stasiun tidak ditemukan. Coba: Jakarta Kota, Bandung, Yogyakarta.';
    }
}

if (empty($trains)) {
    $sql = "SELECT * FROM trains WHERE is_active = 1";
    $params = [];
    if ($routeFrom) { $sql .= " AND route_from LIKE ?"; $params[] = "%$routeFrom%"; }
    if ($routeTo) { $sql .= " AND route_to LIKE ?"; $params[] = "%$routeTo%"; }
    if ($class) { $sql .= " AND class = ?"; $params[] = $class; }
    $sortCol = match($sort) { 'price' => 'price ASC', 'price_desc' => 'price DESC', 'name' => 'name ASC', 'duration' => 'duration ASC', default => 'price ASC' };
    $sql .= " ORDER BY $sortCol";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $trains = $stmt->fetchAll();
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/components/hero-loader.php';
$heroSlides = getHeroSlides('train');
require_once 'includes/header-shared.php';
?>
<?php if (!$search): ?>
<?php
$tsMode = 'train';
$tsAction = 'trains.php';
$tsFormId = 'trainSearchForm';
$tsAutocomplete = 'city-search-ajax.php';
$tsSearchClass = 'city-search';
$tsShowTrip = false;
$tsShowClass = true;
$tsShowMulti = false;
$tsShowCal = false;
$tsTitleB = 'Kereta';
$tsSub = t('Pesan tiket kereta api — booking instan, harga terbaik.');
$tsProof = t('trains');
$tsChips = [
    ['l' => 'Jakarta → Bandung', 'u' => 'trains.php?from=' . urlencode('Jakarta Kota, Jakarta') . '&to=' . urlencode('Bandung, Bandung') . '&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1'],
    ['l' => 'Jakarta → Yogyakarta', 'u' => 'trains.php?from=' . urlencode('Jakarta Kota, Jakarta') . '&to=' . urlencode('Yogyakarta, Yogyakarta') . '&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1'],
    ['l' => 'Jakarta → Surabaya', 'u' => 'trains.php?from=' . urlencode('Jakarta Kota, Jakarta') . '&to=' . urlencode('Surabaya Gubeng, Surabaya') . '&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1'],
];
$tsPhFrom = t('Stasiun asal');
$tsPhTo = t('Stasiun tujuan');
require __DIR__ . '/includes/homepage/transport-search.php';
?>
<?php endif; ?>

<?php if ($search): ?>
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
                <?php if ($keretaError): ?>
                <div class="alert alert-warning py-2 small mb-3"><?= e($keretaError) ?></div>
                <?php endif; ?>
                <div class="row g-3">
                    <?php foreach ($trains as $tr): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <?php if (!empty($tr['is_kereta_api'])): ?>
                            <div class="position-relative overflow-hidden rounded-top d-flex align-items-center justify-content-center bg-light" style="height: 160px;">
                                <i class="bi bi-train-front fs-1 text-primary"></i>
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm"><?= e($tr['class']) ?></span>
                            </div>
                            <?php else: ?>
                            <div class="position-relative overflow-hidden rounded-top" style="height: 160px;">
                                <img src="https://placehold.co/640x400?text=Train" class="w-100 h-100" style="object-fit: cover;" alt="<?= e(tContent($tr, 'name')) ?>">
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm"><?= e($tr['class']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e($tr['name'] ?? $tr['train_name']) ?></h6>
                                <p class="small text-muted flex-grow-1 mb-2">
                                    <i class="bi bi-geo-alt me-1"></i><?= e($tr['route_from']) ?> → <?= e($tr['route_to']) ?>
                                    <br><i class="bi bi-clock me-1"></i><?= e(substr($tr['departure_time'], 0, 5)) ?> - <?= e(substr($tr['arrival_time'], 0, 5)) ?><?php if (!empty($tr['duration'])): ?> · <?= e($tr['duration']) ?><?php endif; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                                    <div>
                                        <span class="fw-bold text-primary"><?= formatCurrencySpan($tr['price'], $tr['price_currency'] ?? 'IDR') ?></span>
                                        <small class="d-block text-muted">/ <?= t('orang') ?></small>
                                    </div>
                                    <?php if (!empty($tr['is_kereta_api'])): ?>
                                    <button class="btn btn-sm btn-primary rounded-pill px-3" disabled><?= t('Segera') ?></button>
                                    <?php else: ?>
                                    <a href="train-detail.php?slug=<?= e($tr['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3"><?= t('Pesan') ?></a>
                                    <?php endif; ?>
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
<?php endif; ?>
<?php require_once 'includes/footer-shared.php'; ?>
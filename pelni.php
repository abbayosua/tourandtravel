<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/pelni.php';
require_once 'includes/tripay.php';

$pageTitle = t('PELNI');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$search = isset($_GET['search']);
$adults = max(1, min(9, (int)($_GET['adults'] ?? 1)));
$children = max(0, min(9, (int)($_GET['children'] ?? 0)));
$passengers = $adults + $children;

$fromCode = '';
$toCode = '';

if ($from && !$fromCode) {
    $ports = pelniSearchPort($from);
    if (!empty($ports)) {
        foreach ($ports as $p) {
            if (stripos($p['label'] ?? '', $from) !== false) {
                $fromCode = $p['label_code'] ?? '';
                break;
            }
        }
        if (!$fromCode && !empty($ports[0])) {
            $fromCode = $ports[0]['label_code'] ?? '';
        }
    }
}

if ($to && !$toCode) {
    $ports = pelniSearchPort($to);
    if (!empty($ports)) {
        foreach ($ports as $p) {
            if (stripos($p['label'] ?? '', $to) !== false) {
                $toCode = $p['label_code'] ?? '';
                break;
            }
        }
        if (!$toCode && !empty($ports[0])) {
            $toCode = $ports[0]['label_code'] ?? '';
        }
    }
}

$trips = [];
$pelniError = null;

if ($search && $fromCode && $toCode) {
    $trips = pelniSearchTrips($fromCode, $toCode, $date, $adults, $children, $adults, $children);
    if (empty($trips)) {
        $pelniError = 'Tidak ada jadwal kapal ditemukan untuk rute/tanggal ini.';
    }
} elseif ($search && (!$fromCode || !$toCode)) {
    $pelniError = 'Pelabuhan asal/tujuan tidak ditemukan. Coba: Batam, Jakarta.';
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/components/hero-loader.php';
$heroSlides = getHeroSlides('pelni');
require_once 'includes/header-shared.php';
?>
<?php if (!$search): ?>
<?php
$tsMode = 'pelni';
$tsAction = 'pelni.php';
$tsFormId = 'pelniSearchForm';
$tsAutocomplete = 'ajax/pelni-port-search.php';
$tsSearchClass = 'pelni-search';
$tsShowTrip = false;
$tsShowClass = false;
$tsShowMulti = false;
$tsShowCal = false;
$tsTitleB = 'PELNI';
$tsSub = t('Pesan tiket kapal PELNI — Batam, Jakarta, dan rute lainnya.');
$tsProof = t('ships');
$tsChips = [
    ['l' => 'Batam → Jakarta', 'u' => 'pelni.php?from=Pulau+Batam%2C+Kota+Batam&to=Tanjung+Priok%2C+Jakarta+Utara&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1'],
    ['l' => 'Jakarta → Batam', 'u' => 'pelni.php?from=Tanjung+Priok%2C+Jakarta+Utara&to=Pulau+Batam%2C+Kota+Batam&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1'],
];
$tsPhFrom = t('Pelabuhan asal');
$tsPhTo = t('Pelabuhan tujuan');
require __DIR__ . '/includes/homepage/transport-search.php';
?>
<?php endif; ?>

<?php if ($search): ?>
<div class="voyage-spreader"></div>
<section class="py-4 bg-light" style="min-height:60vh;">
    <div class="container">
        <?php if (!empty($trips)): ?>
        <?php $minPrice = min(array_column($trips, 'ship_price')); ?>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div><h5 class="fw-bold mb-0"><?= count($trips) ?> <?= t('Jadwal Kapal PELNI') ?></h5><small class="text-muted"><?= formatDate($date) ?> · <?= e($from) ?> → <?= e($to) ?> · <?= $passengers ?> <?= t('pax') ?></small></div>
        </div>
        <div class="row g-3">
            <?php foreach ($trips as $t):
                $isCheapest = (float)$t['ship_price'] === (float)$minPrice;
                $logo = pelniLogo();
                $bookUrl = 'pelni-booking.php?ship_name=' . urlencode($t['ship_name']) . '&ship_code=' . urlencode($t['ship_code']) . '&from=' . urlencode($t['ship_from'] ?: $from) . '&to=' . urlencode($t['ship_to'] ?: $to) . '&date=' . e($t['ship_date'] ?? $date) . '&time=' . urlencode($t['departure_time']) . '&price=' . (float)$t['ship_price'] . '&passengers=' . $passengers . '&ship_class=' . urlencode($t['ship_class'] ?? '') . '&ship_number=' . urlencode($t['ship_number'] ?? '') . '&arrival_time=' . urlencode($t['arrival_time'] ?? '');
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm flight-card">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-2 d-flex align-items-center gap-2">
                                <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="PELNI" style="width:44px;height:44px;object-fit:contain" class="bg-white rounded-2 border" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width:44px;height:44px;<?= $logo ? 'display:none;' : '' ?>"><?= e(substr($t['ship_name'] ?? 'P', 0, 2)) ?></div><?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width:44px;height:44px;"><i class="bi bi-ship"></i></div><?php endif; ?>
                                <div><div class="fw-semibold small"><?= e($t['ship_name']) ?></div><small class="text-muted" style="font-size:11px;"><?= e($t['ship_class'] ?? '-') ?></small></div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= e($t['departure_time'] ?? '-') ?></div><small class="text-muted"><?= e($t['ship_from'] ?: $from) ?></small></div>
                                    <div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-primary position-relative"><i class="bi bi-ship text-primary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-success d-block mt-1" style="font-size:11px"><?= t('Langsung') ?></small></div>
                                    <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= e($t['arrival_time'] ?? '-') ?></div><small class="text-muted"><?= e($t['ship_to'] ?: $to) ?></small></div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center"><?php if ($isCheapest): ?><span class="badge bg-success rounded-pill"><?= t('Hemat') ?></span><?php else: ?><span class="badge bg-light text-dark border rounded-pill">PELNI</span><?php endif; ?><small class="d-block text-muted mt-1" style="font-size:11px"><i class="bi bi-ticket-perforated me-1"></i>e-ticket</small></div>
                            <div class="col-md-2 text-center"><div class="fs-6 fw-bold text-primary"><?= formatRupiah($t['ship_price']) ?></div><small class="text-muted">/ <?= t('orang') ?></small></div>
                            <div class="col-md-2 text-md-end"><a href="<?= $bookUrl ?>" class="btn btn-primary rounded-pill px-4 fw-semibold w-100" data-testid="btn-book-pelni"><?= t('Pesan') ?></a></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="alert alert-info py-2 mt-3 small" style="border-left: 3px solid var(--primary);">
            <i class="bi bi-info-circle me-1"></i><?= t('Sesampainya di pelabuhan, tunjukkan e-ticket ke petugas.') ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-ship fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= $search ? t('Tidak ada jadwal kapal untuk rute/tanggal ini.') : t('Masukkan pelabuhan asal dan tujuan untuk mencari jadwal kapal.') ?></p>
            <p class="small text-muted"><?= t('Coba: Batam → Jakarta, atau ubah tanggal.') ?></p>
            <a href="pelni.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php require_once 'includes/footer-shared.php'; ?>

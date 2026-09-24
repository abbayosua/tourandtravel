<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/easybook.php';

$pageTitle = t('Ferry');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$search = isset($_GET['search']);
$passengers = max(1, min(9, (int)($_GET['passengers'] ?? 1)));

// Default route: Batam → Singapore
if (!$from && !$to && !$search) {
    $from = 'Batam';
    $to = 'Singapore';
}

// Place IDs for Easybook
$fromPlaceId = 0;
$toPlaceId = 0;
$fromSubPlace = 0;
$toSubPlace = 0;

$fromPlaceId = (int)($_GET['from_pid'] ?? 0);
$fromSubPlace = (int)($_GET['from_spid'] ?? 0);
$toPlaceId = (int)($_GET['to_pid'] ?? 0);
$toSubPlace = (int)($_GET['to_spid'] ?? 0);

if ($from && !$fromPlaceId) {
    $places = easybookSearchPlace($from);
    if (!empty($places)) {
        foreach ($places as $p) {
            if ($p['spid'] == 0 && $p['pn']) {
                $fromPlaceId = $p['pid'];
                break;
            }
        }
        if (!$fromPlaceId && !empty($places[0])) {
            $fromPlaceId = $places[0]['pid'];
        }
    }
}

if ($to && !$toPlaceId) {
    $places = easybookSearchPlace($to);
    if (!empty($places)) {
        foreach ($places as $p) {
            if ($p['spid'] == 0 && $p['pn']) {
                $toPlaceId = $p['pid'];
                break;
            }
        }
        if (!$toPlaceId && !empty($places[0])) {
            $toPlaceId = $places[0]['pid'];
        }
    }
}

$ferries = [];
$easybookError = null;

if ($search && $fromPlaceId && $toPlaceId) {
    $ferries = easybookSearchTrips($fromPlaceId, $toPlaceId, $date, $fromSubPlace, $toSubPlace);
    if (empty($ferries)) {
        $easybookError = 'Tidak ada jadwal ferry ditemukan untuk rute/tanggal ini.';
    }
} elseif ($search && (!$fromPlaceId || !$toPlaceId)) {
    $easybookError = 'Kota asal/tujuan tidak ditemukan. Coba: Batam, Singapore, Johor.';
}

// Also get local DB ferries as fallback
if (empty($ferries)) {
    $sql = "SELECT * FROM ferries WHERE is_active = 1";
    $params = [];
    if ($from && $to) {
        $sql .= " AND (route_from LIKE ? OR route_to LIKE ?)";
        $params[] = "%$from%";
        $params[] = "%$to%";
    }
    $sql .= " ORDER BY price ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $dbFerries = $stmt->fetchAll();
    
    // Convert DB format to match Easybook format
    foreach ($dbFerries as $f) {
        $ferries[] = [
            'company' => $f['company'],
            'vessel_name' => $f['vessel_name'] ?? '',
            'departure_time' => date('H:i', strtotime($f['departure_time'])),
            'arrival_time' => $f['arrival_time'] ? date('H:i', strtotime($f['arrival_time'])) : '-',
            'available_seats' => 0,
            'price' => $f['price'],
            'from_terminal' => $f['route_from'],
            'to_terminal' => $f['route_to'],
            'date' => date('Y-m-d'),
        ];
    }
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/components/hero-loader.php';
$heroSlides = getHeroSlides('ferry');
require_once 'includes/header-shared.php';
?>
<?php if (!$search): ?>
<?php $tsMode = 'ferry'; require __DIR__ . '/includes/homepage/transport-search.php'; ?>
<?php endif; ?>
        

<?php if ($search): ?>
<div class="voyage-spreader"></div>
<section class="py-4 bg-light" style="min-height:60vh;">
    <div class="container">
        <?php if (!empty($ferries)): ?>
        <?php $minPrice = min(array_column($ferries, 'price')); ?>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div><h5 class="fw-bold mb-0"><?= count($ferries) ?> <?= t('Jadwal Ferry') ?></h5><small class="text-muted"><?= formatDate($date) ?> · <?= e($from) ?> → <?= e($to) ?> · <?= $passengers ?> <?= t('pax') ?></small></div>
        </div>
        <div class="row g-3">
            <?php foreach ($ferries as $f):
                $isCheapest = (float)$f['price'] === (float)$minPrice;
                $logo = easybookCompanyLogo($f['company']);
                $bookUrl = 'ferry-booking.php?company=' . urlencode($f['company']) . '&from=' . urlencode($f['from_terminal'] ?: $from) . '&to=' . urlencode($f['to_terminal'] ?: $to) . '&date=' . e($f['date'] ?? $date) . '&time=' . urlencode($f['departure_time']) . '&price=' . (float)$f['price'] . '&passengers=' . $passengers . '&vessel=' . urlencode($f['vessel_name'] ?? '') . '&from_terminal=' . urlencode($f['from_terminal'] ?? '') . '&to_terminal=' . urlencode($f['to_terminal'] ?? '') . '&arrival_time=' . urlencode($f['arrival_time'] ?? '');
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm flight-card">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-2 d-flex align-items-center gap-2">
                                <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="<?= e($f['company']) ?>" style="width:44px;height:44px;object-fit:contain" class="bg-white rounded-2 border" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width:44px;height:44px;<?= $logo ? 'display:none;' : '' ?>"><?= e(substr($f['company'] ?? 'F', 0, 2)) ?></div><?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width:44px;height:44px;"><i class="bi bi-water"></i></div><?php endif; ?>
                                <div><div class="fw-semibold small"><?= e($f['company']) ?></div><small class="text-muted" style="font-size:11px;"><?= e($f['vessel_name'] ?? '-') ?></small></div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= e($f['departure_time']) ?></div><small class="text-muted"><?= e($f['from_terminal'] ?: $from) ?></small></div>
                                    <div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-primary position-relative"><i class="bi bi-water text-primary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-success d-block mt-1" style="font-size:11px"><?= t('Langsung') ?></small></div>
                                    <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= e($f['arrival_time'] ?? '-') ?></div><small class="text-muted"><?= e($f['to_terminal'] ?: $to) ?></small></div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center"><?php if ($isCheapest): ?><span class="badge bg-success rounded-pill"><?= t('Hemat') ?></span><?php else: ?><span class="badge bg-light text-dark border rounded-pill">Ferry</span><?php endif; ?><small class="d-block text-muted mt-1" style="font-size:11px"><i class="bi bi-ticket-perforated me-1"></i>e-ticket</small></div>
                            <div class="col-md-2 text-center"><div class="fs-6 fw-bold text-primary"><?= formatRupiah($f['price']) ?></div><small class="text-muted">/ <?= t('orang') ?></small></div>
                            <div class="col-md-2 text-md-end"><a href="<?= $bookUrl ?>" class="btn btn-primary rounded-pill px-4 fw-semibold w-100" data-testid="btn-book-ferry"><?= t('Pesan') ?></a></div>
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
            <i class="bi bi-water fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= $search ? t('Tidak ada jadwal ferry untuk rute/tanggal ini.') : t('Masukkan kota asal dan tujuan untuk mencari jadwal ferry.') ?></p>
            <p class="small text-muted"><?= t('Coba: Batam → Singapore, atau ubah tanggal.') ?></p>
            <a href="ferries.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php require_once 'includes/footer-shared.php'; ?>

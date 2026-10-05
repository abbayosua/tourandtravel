<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/nusatrip.php';
require_once '../includes/live-source.php';

cekLogin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $enabled = isset($_POST['flight_live_enabled']) ? '1' : '0';
    $nusa = isset($_POST['nusatrip_module_enabled']) ? '1' : '0';
    $duffel = isset($_POST['flight_duffel_enabled']) ? '1' : '0';
    $fl = isset($_POST['flight_flightlist_enabled']) ? '1' : '0';
    $order = strtolower(trim((string)($_POST['flight_source_order'] ?? '')));
    $orderList = array_values(array_filter(array_map('trim', explode(',', $order))));

    if ($order !== '' && (empty($orderList) || array_diff($orderList, ['nusatrip', 'duffel', 'flightlist', 'lokal']))) {
        $error = t('Urutan sumber hanya boleh: nusatrip, duffel, flightlist, lokal (pisah koma)');
    } else {
        setSetting('flight_live_enabled', $enabled);
        setSetting('nusatrip_module_enabled', $nusa);
        setSetting('flight_duffel_enabled', $duffel);
        setSetting('flight_flightlist_enabled', $fl);
        setSetting('flight_source_order', $order !== '' ? implode(',', $orderList) : '');
        $message = t('Pengaturan Flight API tersimpan');
    }
}

$liveEnabled = flightApiEnabled();
$nusaModule = nusaModuleEnabled();
$duffelOn = duffelModuleEnabled();
$flOn = flightlistModuleEnabled();
$liveOrder = (string)getSetting('flight_source_order', '');
$effOrder = flightLiveOrder();

// Uji live (GET ?test=1&from=CGK&to=DPS&date=...)
$test = null;
if (isset($_GET['test'])) {
    $tFrom = trim((string)($_GET['from'] ?? 'CGK'));
    $tTo = trim((string)($_GET['to'] ?? 'DPS'));
    $tDate = trim((string)($_GET['date'] ?? date('Y-m-d', strtotime('+7 days'))));
    $found = ['source' => '', 'count' => 0, 'error' => null];
    foreach ($effOrder as $src) {
        if ($src === 'nusatrip') {
            $r = nusaFlightSearch(nusaParseIata($tFrom) ?: $tFrom, nusaParseIata($tTo) ?: $tTo, $tDate, 1);
            $n = count($r['data']['outbounds'] ?? []);
            if (($r['http'] ?? 0) === 200 && $n > 0) { $found = ['source' => 'nusatrip', 'count' => $n, 'error' => null]; break; }
            $found['error'] = 'nusatrip: ' . (($r['data']['messages'][0]['message'] ?? '') ?: ('http ' . ($r['http'] ?? '?')));
        } elseif ($src === 'duffel') {
            require_once '../includes/duffel.php';
            $r = duffelSearchOffers($tFrom, $tTo, $tDate, 'economy', 1);
            $n = count($r['offers'] ?? []);
            if (!isset($r['error']) && $n > 0) { $found = ['source' => 'duffel', 'count' => $n, 'error' => null]; break; }
            $found['error'] = 'duffel: ' . ($r['error'] ?? 'kosong');
        } elseif ($src === 'flightlist') {
            require_once '../includes/flightlist.php';
            $r = flightlistSearchOffers($tFrom, $tTo, $tDate, 'economy', 1);
            $n = count($r['offers'] ?? []);
            if (!isset($r['error']) && $n > 0) { $found = ['source' => 'flightlist', 'count' => $n, 'error' => null]; break; }
            $found['error'] = 'flightlist: ' . ($r['error'] ?? 'kosong');
        }
    }
    $test = ['from' => $tFrom, 'to' => $tTo, 'date' => $tDate] + $found;
}

$pageTitle = t('Flight API');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><i class="bi bi-airplane-gear me-2"></i><?= t('Live Flight API') ?></h4>
<?php if ($message): ?><div class="alert alert-success py-2"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <form method="POST" data-submit-once>
                <input type="hidden" name="save_settings" value="1">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="flight_live_enabled" id="flightLiveEnabled" value="1" <?= $liveEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="flightLiveEnabled"><?= t('Aktifkan live flight API (mati = jadwal lokal saja)') ?></label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="nusatrip_module_enabled" id="nusaModuleEnabled" value="1" <?= $nusaModule ? 'checked' : '' ?>>
                    <label class="form-check-label" for="nusaModuleEnabled"><?= t('Aktifkan modul NusaTrip (search + booking + VA)') ?></label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="flight_duffel_enabled" id="duffelEnabled" value="1" <?= $duffelOn ? 'checked' : '' ?>>
                    <label class="form-check-label" for="duffelEnabled"><?= t('Aktifkan Duffel (butuh API key funded)') ?></label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="flight_flightlist_enabled" id="flEnabled" value="1" <?= $flOn ? 'checked' : '' ?>>
                    <label class="form-check-label" for="flEnabled"><?= t('Aktifkan FlightList (gratis, lanjut Kiwi)') ?></label>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Urutan prioritas') ?></label>
                    <input name="flight_source_order" class="form-control" value="<?= e($liveOrder) ?>" placeholder="nusatrip,lokal" data-testid="flight-source-order">
                    <div class="form-text"><?= t('Coba berurutan hingga ada hasil. Checkout ikut sumber: NusaTrip native, Duffel order API, FlightList lanjut Kiwi, lokal form sendiri. Kosong = nusatrip,lokal.') ?></div>
                </div>
                <button type="submit" class="btn btn-primary" data-testid="flight-api-save"><?= t('Simpan') ?></button>
                <a href="dashboard.php" class="btn btn-outline-secondary"><?= t('Batal') ?></a>
            </form>
        </div></div>

        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold mb-2"><?= t('Uji pencarian live') ?></h6>
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="test" value="1">
                <div class="col-md-3">
                    <label class="form-label small"><?= t('Dari') ?></label>
                    <input name="from" class="form-control form-control-sm" value="<?= e($_GET['from'] ?? 'CGK') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?= t('Ke') ?></label>
                    <input name="to" class="form-control form-control-sm" value="<?= e($_GET['to'] ?? 'DPS') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Tanggal') ?></label>
                    <input name="date" type="date" class="form-control form-control-sm" value="<?= e($_GET['date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
                </div>
                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100" data-testid="flight-api-test"><?= t('Uji') ?></button></div>
            </form>
            <?php if ($test): ?>
                <div class="mt-3">
                    <?php if ($test['count'] > 0): ?>
                        <span class="badge bg-success" data-testid="flight-api-test-ok"><?= t('OK') ?>: <?= (int)$test['count'] ?> · <?= e($test['source']) ?></span>
                    <?php else: ?>
                        <span class="badge bg-danger" data-testid="flight-api-test-fail"><?= t('Kosong/gagal') ?><?= $test['error'] ? ' — ' . e($test['error']) : '' ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold"><?= t('Status') ?></h6>
            <?php if ($liveEnabled): ?>
                <span class="badge bg-success" data-testid="flight-live-status-on"><?= t('Aktif') ?></span>
            <?php else: ?>
                <span class="badge bg-secondary" data-testid="flight-live-status-off"><?= t('Nonaktif') ?></span>
            <?php endif; ?>
            <p class="small text-muted mt-2 mb-1"><?= t('Urutan efektif') ?>: <b><?= e(implode(' → ', $effOrder)) ?></b></p>
            <p class="small text-muted mb-0"><?= t('Modul NusaTrip:') ?> <?= $nusaModule ? '<span class="text-success">' . t('aktif') . '</span>' : '<span class="text-danger">' . t('nonaktif') . '</span>' ?></p>
            <p class="small text-muted mb-0"><?= t('Modul Duffel:') ?> <?= $duffelOn ? '<span class="text-success">' . t('aktif') . '</span>' : '<span class="text-danger">' . t('nonaktif') . '</span>' ?></p>
            <p class="small text-muted mb-0"><?= t('Modul FlightList:') ?> <?= $flOn ? '<span class="text-success">' . t('aktif') . '</span>' : '<span class="text-danger">' . t('nonaktif') . '</span>' ?></p>
        </div></div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>

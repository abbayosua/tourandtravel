<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/hotelapi.php';

cekLogin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $enabled = isset($_POST['hotel_live_enabled']) ? '1' : '0';
    $module = isset($_POST['nusatrip_module_enabled']) ? '1' : '0';
    $oyoModule = isset($_POST['oyo_module_enabled']) ? '1' : '0';
    $source = (string)($_POST['hotel_live_source'] ?? 'nusatrip');
    $rkey = trim((string)($_POST['nusatrip_rkey'] ?? ''));

    if (!in_array($source, ['auto', 'nusatrip', 'oyo'], true)) {
        $error = t('Sumber live tidak valid');
    } elseif ($rkey !== '' && !preg_match('/^[a-f0-9]{32,160}$/i', $rkey)) {
        $error = t('rkey NusaTrip harus hex 32–160 karakter');
    } else {
        setSetting('hotel_live_enabled', $enabled);
        setSetting('nusatrip_module_enabled', $module);
        setSetting('oyo_module_enabled', $oyoModule);
        setSetting('hotel_live_source', $source);
        setSetting('nusatrip_rkey', $rkey);
        hotelCacheClear();
        $message = t('Pengaturan Hotel API tersimpan');
    }
}

$liveEnabled = getSetting('hotel_live_enabled', '1') === '1';
$nusaModule = getSetting('nusatrip_module_enabled', '1') === '1';
$oyoModuleOn = getSetting('oyo_module_enabled', '1') === '1';
$liveSource = (string)getSetting('hotel_live_source', 'nusatrip');
$rkey = (string)getSetting('nusatrip_rkey', '');

// Uji live (GET ?test=1&city=...)
$test = null;
if (isset($_GET['test'])) {
    $testCity = trim((string)($_GET['city'] ?? 'Batam'));
    hotelCacheClear('nusatrip');
    $src = $_GET['src'] ?? ($liveSource === 'auto' ? null : $liveSource);
    $res = hotelApiSearch($testCity, ['source' => $src, 'limit' => 5]);
    $test = ['city' => $testCity, 'source' => $res['source'] ?? '?', 'count' => $res['count'] ?? 0, 'error' => $res['error'] ?? null, 'hotels' => $res['hotels'] ?? []];
}

$pageTitle = t('Hotel API');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><i class="bi bi-building-gear me-2"></i><?= t('Live Hotel API') ?></h4>
<?php if ($message): ?><div class="alert alert-success py-2"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <form method="POST">
                <input type="hidden" name="save_settings" value="1">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="hotel_live_enabled" id="hotelLiveEnabled" value="1" <?= $liveEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="hotelLiveEnabled"><?= t('Aktifkan live hotel API (Booking.com/OYO/NusaTrip)') ?></label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="nusatrip_module_enabled" id="nusaModuleEnabled" value="1" <?= $nusaModule ? 'checked' : '' ?>>
                    <label class="form-check-label" for="nusaModuleEnabled"><?= t('Aktifkan modul NusaTrip (search + booking + VA)') ?></label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="oyo_module_enabled" id="oyoModuleEnabled" value="1" <?= $oyoModuleOn ? 'checked' : '' ?>>
                    <label class="form-check-label" for="oyoModuleEnabled"><?= t('Aktifkan modul OYO (fallback listing per kota)') ?></label>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Sumber utama') ?></label>
                    <select name="hotel_live_source" class="form-select" data-testid="hotel-live-source">
                        <option value="nusatrip" <?= $liveSource === 'nusatrip' ? 'selected' : '' ?>>NusaTrip (utamakan)</option>
                        <option value="oyo" <?= $liveSource === 'oyo' ? 'selected' : '' ?>>OYO</option>
                        <option value="auto" <?= $liveSource === 'auto' ? 'selected' : '' ?>>Auto (NusaTrip native, fallback OYO)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('NusaTrip rkey (lama/opsional)') ?></label>
                    <textarea name="nusatrip_rkey" class="form-control" rows="3" placeholder="opsional, legacy scraping" data-testid="nusatrip-rkey"><?= e($rkey) ?></textarea>
                    <div class="form-text">
                        <?= t('Native API aktif tanpa rkey. rkey lama hanya untuk fallback scraping bila diperlukan.') ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" data-testid="hotel-api-save"><?= t('Simpan') ?></button>
                <a href="dashboard.php" class="btn btn-outline-secondary"><?= t('Batal') ?></a>
            </form>
        </div></div>

        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold mb-2"><?= t('Uji pencarian live') ?></h6>
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="test" value="1">
                <div class="col-md-5">
                    <label class="form-label small"><?= t('Kota') ?></label>
                    <input name="city" class="form-control form-control-sm" value="<?= e($_GET['city'] ?? 'Batam') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Sumber') ?></label>
                    <select name="src" class="form-select form-select-sm">
                        <option value="nusatrip" <?= ($_GET['src'] ?? '') === 'nusatrip' ? 'selected' : '' ?>>NusaTrip</option>
                        <option value="oyo" <?= ($_GET['src'] ?? '') === 'oyo' ? 'selected' : '' ?>>OYO</option>
                        <option value="auto" <?= ($_GET['src'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>Auto</option>
                    </select>
                </div>
                <div class="col-md-3"><button class="btn btn-sm btn-outline-primary w-100" data-testid="hotel-api-test"><?= t('Uji') ?></button></div>
            </form>
            <?php if ($test): ?>
                <div class="mt-3">
                    <?php if ($test['count'] > 0): ?>
                        <span class="badge bg-success" data-testid="hotel-api-test-ok"><?= t('OK') ?>: <?= (int)$test['count'] ?> <?= t('hotel') ?> · <?= e($test['source']) ?></span>
                    <?php else: ?>
                        <span class="badge bg-danger" data-testid="hotel-api-test-fail"><?= t('Kosong/gagal') ?> · <?= e($test['source']) ?><?= $test['error'] ? ' — ' . e($test['error']) : '' ?></span>
                    <?php endif; ?>
                    <ul class="small mt-2 mb-0">
                        <?php foreach ($test['hotels'] as $h): ?>
                            <li><?= e($h['name'] ?? '') ?> — <?= e($h['price_formatted'] ?? '-') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold"><?= t('Status') ?></h6>
            <?php if ($liveEnabled): ?>
                <span class="badge bg-success" data-testid="hotel-live-status-on"><?= t('Aktif') ?></span>
            <?php else: ?>
                <span class="badge bg-secondary" data-testid="hotel-live-status-off"><?= t('Nonaktif') ?></span>
            <?php endif; ?>
            <p class="small text-muted mt-2 mb-1"><?= t('Sumber') ?>: <b><?= e($liveSource) ?></b></p>
            <p class="small text-muted mb-0">Modul NusaTrip: <?= $nusaModule ? '<span class="text-success">aktif</span>' : '<span class="text-danger">nonaktif</span>' ?></p>
            <p class="small text-muted mb-0">Modul OYO: <?= $oyoModuleOn ? '<span class="text-success">aktif</span>' : '<span class="text-danger">nonaktif</span>' ?></p>
            <p class="small text-muted mb-0"><?= t('rkey NusaTrip') ?>: <?= $rkey !== '' ? '<span class="text-success">' . t('terisi') . '</span>' : '<span class="text-danger">' . t('kosong') . '</span>' ?></p>
        </div></div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>

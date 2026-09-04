<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Pengaturan Mata Uang');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_currency'])) {
        $currency = $_POST['default_currency'] ?? 'IDR';
        setDefaultCurrency($currency);
        $message = 'Mata uang default berhasil disimpan: ' . $currency;
    }

    if (isset($_POST['refresh_rates'])) {
        $rates = fetchFrankfurterRates();
        if ($rates) {
            $message = 'Kurs berhasil diperbarui: IDR=' . number_format($rates['IDR'], 0) . ', SGD=' . number_format($rates['SGD'], 4) . ', USD=' . number_format($rates['USD'], 4);
        } else {
            $error = t('Gagal mengambil kurs dari Frankfurter API');
        }
    }
}

$defaultCurrency = getDefaultCurrency();
$currentRates = getExchangeRates();
$currencies = getSupportedCurrencies();
?>

<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<h4 class="fw-bold mb-3"><i class="bi bi-currency-exchange me-2"></i><?= t('Pengaturan Mata Uang') ?></h4>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible py-2"><?= $message ?>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= $error ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><i class="bi bi-gear me-2"></i><?= t('Mata Uang Default') ?></h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= t('Mata uang yang ditampilkan ke pengunjung') ?></label>
                        <select name="default_currency" class="form-select">
                            <?php foreach ($currencies as $code => $info): ?>
                                <option value="<?= $code ?>" <?= $code === $defaultCurrency ? 'selected' : '' ?>>
                                    <?= $info['symbol'] ?> - <?= $info['name'] ?> (<?= $code ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="save_currency" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> <?= t('Simpan') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2"></i><?= t('Kurs Terkini (EUR Base)') ?></h6>
                <form method="POST" class="d-inline">
                    <button type="submit" name="refresh_rates" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-clockwise"></i> <?= t('Refresh') ?>
                    </button>
                </form>
            </div>
            <div class="card-body">
                <?php if ($currentRates): ?>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th><?= t('Pasangan') ?></th><th class="text-end"><?= t('Rate') ?></th></tr>
                        </thead>
                        <tbody>
                            <tr><td><?= t('EUR → IDR') ?></td><td class="text-end"><?= number_format($currentRates['IDR'], 2) ?></td></tr>
                            <tr><td><?= t('EUR → SGD') ?></td><td class="text-end"><?= number_format($currentRates['SGD'], 4) ?></td></tr>
                            <tr><td><?= t('EUR → USD') ?></td><td class="text-end"><?= number_format($currentRates['USD'], 4) ?></td></tr>
                        </tbody>
                    </table>
                    <p class="text-muted small mt-2 mb-0"><?= t('Sumber') ?>: <a href="https://frankfurter.app" target="_blank"><?= t('Frankfurter API') ?></a><?= t('—') ?><?= t('update setiap 24 jam') ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0"><?= t('Belum ada data kurs. Klik "Refresh" untuk mengambil data terbaru.') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold small"><?= t('Contoh Konversi dari IDR 10.000.000') ?></h6>
                <?php
                $sampleIDR = 10000000;
                foreach ($currencies as $code => $info):
                    $converted = convertCurrency($sampleIDR, 'IDR', $code);
                    $fmt = number_format($converted, $info['decimals'], ',', '.');
                ?>
                    <div class="d-flex justify-content-between small">
                        <span><?= $code ?></span>
                        <span class="fw-semibold"><?= $info['symbol'] ?> <?= $fmt ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

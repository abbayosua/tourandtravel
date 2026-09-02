<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = 'Pengaturan Loyalty';
$message = '';
$error = '';

// Default tiers
$defaults = [
    'loyalty_explorer_threshold' => 0,
    'loyalty_silver_threshold' => 2,
    'loyalty_gold_threshold' => 5,
    'loyalty_joyplus_threshold' => 10,
    'loyalty_earning_rate' => 5, // persen KlookCash dari total booking
    'loyalty_silver_rate' => 6,
    'loyalty_gold_rate' => 7,
    'loyalty_joyplus_rate' => 10,
];

// Load saved settings
$saved = [];
try {
    $stmt = db()->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'loyalty_%'");
    foreach ($stmt->fetchAll() as $row) $saved[$row['setting_key']] = $row['setting_value'];
} catch (Throwable $e) {}

$values = array_merge($defaults, $saved);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_loyalty'])) {
    $fields = array_keys($defaults);
    $stmt = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($fields as $f) {
        $val = (float)($_POST[$f] ?? $defaults[$f]);
        $stmt->execute([$f, $val]);
        $values[$f] = $val;
    }
    $message = 'Pengaturan loyalty berhasil disimpan';
}
?>
<?php require_once __DIR__ . '/includes/admin-header.php'; ?>

<h4 class="fw-bold mb-3"><i class="bi bi-gem me-2 text-warning"></i>Pengaturan Loyalty & KlookCash</h4>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible py-2"><?= $message ?>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2"></i>Tier Threshold (Jumlah Booking)</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Explorer — minimal booking</label>
                    <input type="number" name="loyalty_explorer_threshold" class="form-control" value="<?= (float)$values['loyalty_explorer_threshold'] ?>" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Silver — minimal booking</label>
                    <input type="number" name="loyalty_silver_threshold" class="form-control" value="<?= (float)$values['loyalty_silver_threshold'] ?>" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Gold — minimal booking</label>
                    <input type="number" name="loyalty_gold_threshold" class="form-control" value="<?= (float)$values['loyalty_gold_threshold'] ?>" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Joy+ — minimal booking</label>
                    <input type="number" name="loyalty_joyplus_threshold" class="form-control" value="<?= (float)$values['loyalty_joyplus_threshold'] ?>" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><i class="bi bi-percent me-2"></i>Earning Rate KlookCash</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Persentase dari total booking yang menjadi KlookCash reward.</p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Explorer (%)</label>
                    <input type="number" name="loyalty_earning_rate" class="form-control" value="<?= (float)$values['loyalty_earning_rate'] ?>" min="0" step="0.5">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Silver (%)</label>
                    <input type="number" name="loyalty_silver_rate" class="form-control" value="<?= (float)$values['loyalty_silver_rate'] ?>" min="0" step="0.5">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Gold (%)</label>
                    <input type="number" name="loyalty_gold_rate" class="form-control" value="<?= (float)$values['loyalty_gold_rate'] ?>" min="0" step="0.5">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Joy+ (%)</label>
                    <input type="number" name="loyalty_joyplus_rate" class="form-control" value="<?= (float)$values['loyalty_joyplus_rate'] ?>" min="0" step="0.5">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" name="save_loyalty" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Pengaturan</button>
    <a href="dashboard.php" class="btn btn-outline-secondary">Kembali</a>
</div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
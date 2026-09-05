<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Pembayaran');

$error = '';

// Simpan setting Midtrans
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $env = ($_POST['midtrans_env'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox';
    setSetting('midtrans_env', $env);
    setSetting('midtrans_server_key', trim($_POST['midtrans_server_key'] ?? ''));
    setSetting('midtrans_client_key', trim($_POST['midtrans_client_key'] ?? ''));
    setSetting('payment_enabled', isset($_POST['payment_enabled']) ? '1' : '0');
    header('Location: payments.php?msg=updated');
    exit;
}

// Tandai expired manual
if (isset($_GET['expire'])) {
    db()->prepare("UPDATE payments SET status='expired' WHERE id=? AND status='pending'")->execute([(int)$_GET['expire']]);
    header('Location: payments.php?msg=updated');
    exit;
}

$filterStatus = $_GET['status'] ?? '';
$validStatus = ['pending', 'paid', 'failed', 'expired', 'challenge'];
$sql = "SELECT * FROM payments";
$params = [];
if (in_array($filterStatus, $validStatus, true)) {
    $sql .= " WHERE status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY id DESC LIMIT 200";
$rows = db()->prepare($sql);
$rows->execute($params);
$rows = $rows->fetchAll();

$stats = db()->query("SELECT status, COUNT(*) n, COALESCE(SUM(gross_amount),0) total FROM payments GROUP BY status")->fetchAll();
$statMap = [];
foreach ($stats as $s) $statMap[$s['status']] = $s;

require_once __DIR__ . '/includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><?= t('Pembayaran') ?></h4>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success py-2 small"><?= t('Berhasil diperbarui') ?></div>
<?php endif; ?>

<!-- Statistik -->
<div class="row g-2 mb-4">
    <?php
    $badgeMap = ['paid' => 'success', 'pending' => 'warning text-dark', 'failed' => 'danger', 'expired' => 'secondary', 'challenge' => 'info'];
    foreach (['paid', 'pending', 'challenge', 'failed', 'expired'] as $st):
        $n = (int)($statMap[$st]['n'] ?? 0);
        $total = (float)($statMap[$st]['total'] ?? 0);
    ?>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm text-center py-3 h-100">
            <div><span class="badge bg-<?= $badgeMap[$st] ?>"><?= ucfirst(t($st)) ?></span></div>
            <div class="fs-4 fw-bold mt-1"><?= $n ?></div>
            <small class="text-muted"><?= formatRupiah($total) ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Settings -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><?= t('Pengaturan Midtrans') ?></h6>
        <form method="POST" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold"><?= t('Environment') ?></label>
                <select name="midtrans_env" class="form-select">
                    <option value="sandbox" <?= getSetting('midtrans_env') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                    <option value="production" <?= getSetting('midtrans_env') === 'production' ? 'selected' : '' ?>>Production</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold"><?= t('Server Key') ?></label>
                <input type="text" name="midtrans_server_key" class="form-control" value="<?= e(getSetting('midtrans_server_key')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold"><?= t('Client Key') ?></label>
                <input type="text" name="midtrans_client_key" class="form-control" value="<?= e(getSetting('midtrans_client_key')) ?>">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="payment_enabled" id="payEnabled" <?= getSetting('payment_enabled') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="payEnabled"><?= t('Aktif') ?></label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" name="save_settings" class="btn btn-primary"><?= t('Simpan') ?></button>
                <small class="text-muted ms-2">Webhook URL: <code><?= e(BASE_URL . '/webhook-midtrans.php') ?></code></small>
            </div>
        </form>
    </div>
</div>

<!-- Daftar pembayaran -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0"><?= t('Daftar Pembayaran') ?></h6>
        <div class="d-flex gap-1">
            <a href="payments.php" class="btn btn-sm <?= $filterStatus === '' ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= t('Semua') ?></a>
            <?php foreach ($validStatus as $vs): ?>
                <a href="payments.php?status=<?= $vs ?>" class="btn btn-sm <?= $filterStatus === $vs ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= ucfirst(t($vs)) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= t('Order ID') ?></th>
                        <th><?= t('Tipe') ?></th>
                        <th><?= t('Jumlah') ?></th>
                        <th><?= t('Metode') ?></th>
                        <th><?= t('Status') ?></th>
                        <th><?= t('Tanggal') ?></th>
                        <th class="text-end"><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!count($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><?= t('Belum ada pembayaran.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code class="small"><?= e($r['order_id']) ?></code></td>
                        <td><span class="badge bg-secondary"><?= e($r['booking_type']) ?> #<?= (int)$r['booking_id'] ?></span></td>
                        <td class="fw-semibold"><?= formatRupiah($r['gross_amount']) ?></td>
                        <td><small><?= e($r['payment_type'] ?? '-') ?></small></td>
                        <td><span class="badge bg-<?= $badgeMap[$r['status']] ?? 'secondary' ?>"><?= ucfirst(t($r['status'])) ?></span></td>
                        <td><small class="text-muted"><?= formatDate($r['created_at']) ?></small></td>
                        <td class="text-end">
                            <?php if ($r['status'] === 'pending'): ?>
                                <a href="payments.php?expire=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= t('Tandai Kedaluwarsa') ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

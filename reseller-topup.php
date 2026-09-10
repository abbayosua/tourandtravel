<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=reseller-topup.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (!isReseller($userId)) {
    header('Location: index.php');
    exit;
}

$topupMsg = '';
$topupErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';
    $validMethods = ['bank_transfer', 'qris', 'ewallet'];
    if (!in_array($paymentMethod, $validMethods, true)) $paymentMethod = 'bank_transfer';

    if ($amount < 50000) {
        $topupErr = t('Minimal topup Rp 50.000');
    } else {
        $proofPath = null;
        if (!empty($_FILES['proof']['tmp_name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $topupErr = t('File harus format JPG/PNG/WebP');
            } else {
                if (!is_dir('uploads/topup-proof')) mkdir('uploads/topup-proof', 0755, true);
                $proofName = 'proof_' . $userId . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['proof']['tmp_name'], 'uploads/topup-proof/' . $proofName);
                $proofPath = 'uploads/topup-proof/' . $proofName;
            }
        }

        if (!$topupErr) {
            $stmt = db()->prepare("INSERT INTO reseller_topups (user_id, amount, payment_method, proof_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $amount, $paymentMethod, $proofPath]);
            $topupMsg = t('Permintaan topup berhasil dikirim! Menunggu persetujuan admin.');
        }
    }
}

$history = getResellerTopupHistory($userId, 20);

$pageTitle = t('Topup Saldo Reseller');
require_once 'includes/header-klook.php';
?>

<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 me-2"></i><?= t('Topup Saldo Reseller') ?></h4>
                <p class="text-muted small mb-4"><?= t('Isi saldo untuk booking paket wisata dengan harga reseller.') ?></p>

                <?php if ($topupMsg): ?>
                    <div class="alert alert-success py-2"><?= $topupMsg ?></div>
                <?php endif; ?>
                <?php if ($topupErr): ?>
                    <div class="alert alert-danger py-2"><?= $topupErr ?></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3"><?= t('Saldo Saat Ini') ?></h6>
                        <div class="fs-3 fw-bold text-primary mb-3"><?= formatRupiah(getResellerBalance($userId)) ?></div>

                        <h6 class="fw-semibold mb-3"><?= t('Permintaan Topup Baru') ?></h6>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Jumlah Topup') ?></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="amount" class="form-control" min="50000" step="10000" placeholder="500000" required>
                                </div>
                                <div class="form-text">Minimal Rp 50.000</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Metode Pembayaran') ?></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="bank_transfer"><?= t('Bank Transfer') ?></option>
                                    <option value="qris"><?= t('QRIS') ?></option>
                                    <option value="ewallet"><?= t('E-Wallet') ?></option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Bukti Transfer (opsional)') ?></label>
                                <input type="file" name="proof" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">JPG/PNG/WebP, maks 5MB</div>
                            </div>
                            <button type="submit" class="btn btn-primary fw-semibold"><?= t('Kirim Permintaan Topup') ?></button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($history)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3"><?= t('Riwayat Topup') ?></h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th><?= t('Tanggal') ?></th><th><?= t('Jumlah') ?></th><th><?= t('Metode') ?></th><th><?= t('Status') ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td class="small"><?= date('d M Y H:i', strtotime($h['created_at'])) ?></td>
                                        <td class="fw-semibold"><?= formatRupiah((float)$h['amount']) ?></td>
                                        <td class="small text-capitalize"><?= str_replace('_', ' ', $h['payment_method']) ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = match($h['status']) {
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                default => 'bg-warning text-dark',
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($h['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer-klook.php'; ?>

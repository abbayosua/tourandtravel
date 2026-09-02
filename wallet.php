<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/wallet.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=wallet.php');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = getWalletBalance($userId);

$typeFilter = $_GET['type'] ?? '';
$validTypes = ['earn', 'spend', 'refund', 'bonus'];

$sql = "SELECT * FROM wallet_transactions WHERE user_id = ?";
$params = [$userId];
if (in_array($typeFilter, $validTypes)) {
    $sql .= " AND type = ?";
    $params[] = $typeFilter;
}
$sql .= " ORDER BY created_at DESC LIMIT 100";
$txns = db()->prepare($sql);
$txns->execute($params);
$txns = $txns->fetchAll();

// Summary per type
$summary = ['earn' => 0, 'spend' => 0, 'refund' => 0, 'bonus' => 0];
foreach (getWalletTransactions($userId, 1000) as $t) {
    if (isset($summary[$t['type']])) $summary[$t['type']] += (float)$t['amount'];
}

$pageTitle = 'KlookCash Saya';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-wallet2 text-primary me-2"></i>KlookCash Saya</h4>

        <!-- Balance card -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-white-50"><?= t('Saldo KlookCash') ?></small>
                        <div class="text-white fs-2 fw-bold"><?= formatRupiah($balance) ?></div>
                        <small class="text-white-50"><?= t('Gunakan untuk potongan booking berikutnya') ?></small>
                    </div>
                    <div class="text-white text-center">
                        <i class="bi bi-coin display-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter tabs -->
        <div class="d-flex flex-wrap gap-1 mb-3">
            <a href="wallet.php" class="btn btn-sm <?= !$typeFilter ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><?= t('Semua') ?></a>
            <a href="wallet.php?type=earn" class="btn btn-sm <?= $typeFilter === 'earn' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><i class="bi bi-plus-circle text-success me-1"></i><?= t('Earn') ?></a>
            <a href="wallet.php?type=spend" class="btn btn-sm <?= $typeFilter === 'spend' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><i class="bi bi-dash-circle text-danger me-1"></i><?= t('Spend') ?></a>
            <a href="wallet.php?type=refund" class="btn btn-sm <?= $typeFilter === 'refund' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><i class="bi bi-arrow-counterclockwise text-info me-1"></i><?= t('Refund') ?></a>
            <a href="wallet.php?type=bonus" class="btn btn-sm <?= $typeFilter === 'bonus' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><i class="bi bi-gift text-warning me-1"></i><?= t('Bonus') ?></a>
        </div>

        <!-- Transactions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0"><?= t('Riwayat Transaksi') ?></h6>
            </div>
            <div class="card-body p-0">
                <?php if (count($txns) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Tipe</th><th>Deskripsi</th><th>Jumlah</th><th>Tanggal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($txns as $t): ?>
                            <tr>
                                <td><?= $t['id'] ?></td>
                                <td>
                                    <?php
                                    $badge = match($t['type']) {
                                        'earn' => 'success', 'spend' => 'danger', 'refund' => 'info', 'bonus' => 'warning text-dark', default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($t['type']) ?></span>
                                </td>
                                <td><small><?= e($t['description'] ?? '-') ?></small></td>
                                <td class="fw-bold <?= $t['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $t['amount'] >= 0 ? '+' : '' ?><?= formatRupiah(abs($t['amount'])) ?>
                                </td>
                                <td><small class="text-muted"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-wallet fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Belum ada transaksi.') ?></p>
                    <a href="tours.php" class="btn btn-primary rounded-pill px-4"><?= t('Booking untuk dapat KlookCash') ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
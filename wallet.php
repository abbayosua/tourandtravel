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
require_once 'includes/points.php';

// Redeem points → KlookCash (1 point = Rp 100)
$redeemMsg = '';
$redeemErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['redeem_points'] ?? '') === '1') {
    $pts = (int)($_POST['points'] ?? 0);
    $rate = 100; // 1 point = Rp 100
    if ($pts < 100) {
        $redeemErr = t('Minimal penukaran 100 points');
    } elseif (getPointsBalance($userId) < $pts) {
        $redeemErr = t('Saldo points tidak cukup');
    } else {
        $newBal = redeemPoints($userId, $pts, 'wallet', null, 'Redeem ke KlookCash');
        if ($newBal !== null) {
            addWalletTransaction($userId, $pts * $rate, 'earn', 'Penukaran ' . $pts . ' points');
            $balance = getWalletBalance($userId);
            $redeemMsg = str_replace([':p', ':a'], [$pts, formatRupiah($pts * $rate)], t('Berhasil menukar :p points menjadi :a'));
        } else {
            $redeemErr = t('Saldo points tidak cukup');
        }
    }
}
$pointsBalance = getPointsBalance($userId);
$pointsLedger = getPointsLedger($userId, 50);

$showPoints = ($_GET['tab'] ?? '') === 'points';
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

$pageTitle = t('KlookCash Saya');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-wallet2 text-primary me-2"></i><?= t('KlookCash Saya') ?></h4>

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

        <!-- Tab utama: KlookCash / Points -->
        <ul class="nav nav-pills mb-3" data-testid="wallet-tabs">
            <li class="nav-item"><a class="nav-link <?= !$showPoints ? 'active' : '' ?>" href="wallet.php" data-testid="tab-cash"><?= t('KlookCash') ?></a></li>
            <li class="nav-item"><a class="nav-link <?= $showPoints ? 'active' : '' ?>" href="wallet.php?tab=points" data-testid="tab-points"><?= t('Points') ?> <span class="badge bg-danger ms-1"><?= $pointsBalance ?></span></a></li>
        </ul>

        <?php if ($showPoints): ?>
        <!-- Kartu Points -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="background: linear-gradient(135deg, #d63384 0%, #6f42c1 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <small class="text-white-50"><?= t('Saldo Points') ?></small>
                        <div class="text-white fs-2 fw-bold" data-testid="points-balance"><?= number_format($pointsBalance) ?></div>
                        <small class="text-white-50"><?= t('1 point = Rp 100 · diperoleh dari booking yang dibayar') ?></small>
                    </div>
                    <form method="POST" action="?tab=points" class="d-flex gap-2 align-items-end" data-testid="redeem-form">
                        <input type="hidden" name="redeem_points" value="1">
                        <div>
                            <label class="form-label small text-white-50 mb-0"><?= t('Tukar points') ?></label>
                            <input type="number" name="points" class="form-control form-control-sm" min="100" step="100" value="100" style="width:120px;">
                        </div>
                        <button type="submit" class="btn btn-light btn-sm" data-testid="redeem-btn"><?= t('Tukar') ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php if ($redeemMsg): ?><div class="alert alert-success py-2" data-testid="redeem-success"><?= e($redeemMsg) ?></div><?php endif; ?>
        <?php if ($redeemErr): ?><div class="alert alert-danger py-2" data-testid="redeem-error"><?= e($redeemErr) ?></div><?php endif; ?>

        <!-- Points ledger -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><?= t('Riwayat Points') ?></h6></div>
            <div class="card-body p-0">
                <?php if (count($pointsLedger) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" data-testid="points-ledger">
                        <thead class="table-light"><tr><th><?= t('Deskripsi') ?></th><th><?= t('Points') ?></th><th><?= t('Tanggal') ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($pointsLedger as $pl): ?>
                            <tr>
                                <td><small><?= e($pl['note'] ?: $pl['reason']) ?><?= $pl['booking_code'] ? ' · ' . e($pl['booking_code']) : '' ?></small></td>
                                <td class="<?= $pl['points'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold"><?= $pl['points'] > 0 ? '+' : '' ?><?= (int)$pl['points'] ?></td>
                                <td><small><?= e(date('d M Y H:i', strtotime($pl['created_at']))) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4 small"><?= t('Belum ada mutasi points') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>

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
                            <tr><th>#</th><th><?= t('Tipe') ?></th><th><?= t('Deskripsi') ?></th><th><?= t('Jumlah') ?></th><th><?= t('Tanggal') ?></th></tr>
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
        <?php endif; ?>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
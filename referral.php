<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/wallet.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=referral.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Pastikan user punya referral code
$user = getUser();
$referralCode = $user['referral_code'] ?? '';
if (!$referralCode) {
    $referralCode = 'REF-' . $userId . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    db()->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$referralCode, $userId]);
}

// Referral link
$refLink = BASE_URL . '/register.php?ref=' . urlencode($referralCode);

// List referrals
$referrals = db()->prepare("SELECT * FROM referrals WHERE referrer_id = ? ORDER BY created_at DESC LIMIT 100");
$referrals->execute([$userId]);
$referrals = $referrals->fetchAll();

// Stats
$totalReferrals = count($referrals);
$totalCompleted = 0;
$totalReward = 0;
foreach ($referrals as $r) {
    if ($r['status'] === 'completed' || $r['status'] === 'rewarded') $totalCompleted++;
    if ($r['reward_amount']) $totalReward += (float)$r['reward_amount'];
}
// Tambah reward dari wallet bonus tipe referral
$bonusTotal = 0;
foreach (getWalletTransactions($userId, 1000) as $t) {
    if ($t['type'] === 'bonus' && strpos($t['description'] ?? '', 'referral') !== false) {
        $bonusTotal += (float)$t['amount'];
    }
}
$totalReward = max($totalReward, $bonusTotal);

$pageTitle = t('Referral Saya');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-people text-primary me-2"></i><?= t('Referral Program') ?></h4>

        <!-- Hero card -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="fw-bold text-white mb-1"><?= t('Ajak Teman, Dapatkan KlookCash!') ?></h5>
                        <p class="text-white-50 small mb-2"><?= t('Bagikan link di bawah — Anda & teman dapat reward Rp50.000 saat teman berhasil daftar.') ?></p>
                        <div class="input-group">
                            <input type="text" class="form-control" id="refLinkInput" value="<?= e($refLink) ?>" readonly>
                            <button class="btn btn-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLinkInput').value);this.textContent='<?= t('✓ Disalin!') ?>';setTimeout(()=>this.textContent='<?= t('Salin') ?>',1500);"><?= t('Salin') ?></button>
                        </div>
                    </div>
                    <div class="col-md-4 text-center text-white">
                        <div class="fs-1"><i class="bi bi-gift"></i></div>
                        <div class="fs-3 fw-bold"><?= formatRupiah(50000) ?></div>
                        <small class="text-white-50"><?= t('per referral') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="fs-3 fw-bold text-primary"><?= $totalReferrals ?></div>
                        <small class="text-muted"><?= t('Total Referral') ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="fs-3 fw-bold text-success"><?= $totalCompleted ?></div>
                        <small class="text-muted"><?= t('Berhasil Daftar') ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="fs-3 fw-bold text-warning"><?= formatRupiah($totalReward) ?></div>
                        <small class="text-muted"><?= t('Total Reward') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referral list -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><?= t('Daftar Referral') ?></h6>
            </div>
            <div class="card-body p-0">
                <?php if (count($referrals) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th><?= t('Email') ?></th><th><?= t('Status') ?></th><th><?= t('Reward') ?></th><th><?= t('Tanggal') ?></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($referrals as $r): ?>
                            <tr>
                                <td><?= e($r['referred_email']) ?></td>
                                <td>
                                    <?php
                                    $badge = match($r['status']) {
                                        'completed' => 'success', 'rewarded' => 'primary', 'pending' => 'warning text-dark', default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($r['status']) ?></span>
                                </td>
                                <td class="fw-bold text-success"><?= $r['reward_amount'] ? formatRupiah($r['reward_amount']) : '-' ?></td>
                                <td><small class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Belum ada referral. Bagikan link Anda!') ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
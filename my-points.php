<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/points.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-points.php');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = getPointsBalance($userId);
$ledger = getPointsLedger($userId, 100);
$userTier = $_SESSION['user_tier'] ?? db()->prepare("SELECT tier FROM users WHERE id = ?")->execute([$userId]) ? db()->prepare("SELECT tier FROM users WHERE id = ?")->execute([$userId]) : 'explorer';
$stmt = db()->prepare("SELECT tier FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userTier = $stmt->fetchColumn() ?: 'explorer';

$bookingCount = db()->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('confirmed','paid','completed')");
$bookingCount->execute([$userId]);
$bookingCount = (int)$bookingCount->fetchColumn();

$silverThreshold = (int)getSetting('loyalty_silver_threshold', '2');
$goldThreshold = (int)getSetting('loyalty_gold_threshold', '5');
$platinumThreshold = (int)getSetting('loyalty_joyplus_threshold', '10');

$thresholds = [
    'explorer' => ['min' => 0, 'next' => $silverThreshold, 'next_name' => 'Silver', 'color' => '#6c757d', 'icon' => 'bi-compass'],
    'silver'   => ['min' => $silverThreshold, 'next' => $goldThreshold, 'next_name' => 'Gold', 'color' => '#adb5bd', 'icon' => 'bi-star'],
    'gold'     => ['min' => $goldThreshold, 'next' => $platinumThreshold, 'next_name' => 'Platinum', 'color' => '#ffc107', 'icon' => 'bi-trophy'],
    'platinum' => ['min' => $platinumThreshold, 'next' => null, 'next_name' => null, 'color' => '#0d6efd', 'icon' => 'bi-gem'],
];

$currentTier = $thresholds[$userTier] ?? $thresholds['explorer'];

if ($currentTier['next'] !== null && $bookingCount < $currentTier['next']) {
    $progressPct = $currentTier['min'] > 0
        ? (($bookingCount - $currentTier['min']) / ($currentTier['next'] - $currentTier['min'])) * 100
        : ($bookingCount / $currentTier['next']) * 100;
} else {
    $progressPct = 100;
}
$bookingsToNext = $currentTier['next'] !== null ? max(0, $currentTier['next'] - $bookingCount) : 0;

$pageTitle = t('Poin & Loyalitas');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-4"><i class="bi bi-gift me-2"></i><?= t('Poin & Loyalitas') ?></h4>

        <!-- Tier Card -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-4" style="background: linear-gradient(135deg, <?= $currentTier['color'] ?>22, <?= $currentTier['color'] ?>08);">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; background: <?= $currentTier['color'] ?>22;">
                            <i class="bi <?= $currentTier['icon'] ?> fs-2" style="color: <?= $currentTier['color'] ?>;"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="fw-bold mb-0"><?= ucfirst($userTier) ?></h5>
                            <span class="badge" style="background: <?= $currentTier['color'] ?>; color: #fff; font-size: 11px;"><?= t('Tier Anda') ?></span>
                        </div>
                        <p class="text-muted small mb-2"><?= t('Total') ?> <?= $bookingCount ?> <?= t('booking selesai') ?></p>
                        <?php if ($currentTier['next']): ?>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= min(100, $progressPct) ?>%; background: <?= $currentTier['color'] ?>;" aria-valuenow="<?= min(100, $progressPct) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted"><?= $bookingsToNext ?> <?= t('booking lagi untuk') ?> <?= $currentTier['next_name'] ?></small>
                        <?php else: ?>
                        <small class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i><?= t('Tier maksimum tercapai!') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto text-end">
                        <div class="fs-3 fw-bold text-primary"><?= number_format($balance) ?></div>
                        <small class="text-muted"><?= t('poin tersedia') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Points Ledger -->
        <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i><?= t('Riwayat Poin') ?></h5>
        <?php if (count($ledger) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?= t('Tanggal') ?></th>
                        <th><?= t('Keterangan') ?></th>
                        <th><?= t('Tipe') ?></th>
                        <th class="text-end"><?= t('Poin') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ledger as $entry): ?>
                    <tr>
                        <td class="small"><?= date('d M Y H:i', strtotime($entry['created_at'])) ?></td>
                        <td>
                            <?= e($entry['reason'] === 'earn' ? t('Earn dari booking') : ($entry['reason'] === 'redeem' ? t('Redeem poin') : ($entry['reason'] === 'refund' ? t('Refund poin') : t('Penyesuaian')))) ?>
                            <?php if (!empty($entry['booking_code'])): ?>
                                <span class="badge bg-light text-dark ms-1" style="font-size: 10px;"><?= e($entry['booking_code']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($entry['note'])): ?>
                                <br><small class="text-muted"><?= e($entry['note']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $entry['booking_type'] === 'tour' ? 'primary' : ($entry['booking_type'] === 'hotel' ? 'success' : ($entry['booking_type'] === 'flight' ? 'info' : 'secondary')) ?>"><?= e($entry['booking_type'] ?? '-') ?></span></td>
                        <td class="text-end fw-semibold <?= $entry['points'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $entry['points'] >= 0 ? '+' . number_format($entry['points']) : number_format($entry['points']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2"><?= t('Belum ada riwayat poin.') ?></p>
            <p class="small"><?= t('Poin akan ditambahkan setelah booking selesai.') ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

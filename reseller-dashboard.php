<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=reseller-dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (!isReseller($userId)) {
    header('Location: index.php');
    exit;
}

$balance = getResellerBalance($userId);
$history = getResellerTopupHistory($userId, 10);

// Recent reseller bookings
$stmt = db()->prepare("SELECT b.*, t.title AS tour_title FROM bookings b LEFT JOIN tours t ON b.tour_id = t.id WHERE b.user_id = ? AND b.booking_source = 'reseller' ORDER BY b.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$recentBookings = $stmt->fetchAll();

// Stats
$stmt = db()->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND booking_source = 'reseller'");
$stmt->execute([$userId]);
$totalBookings = (int)$stmt->fetchColumn();

$stmt = db()->prepare("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE user_id = ? AND booking_source = 'reseller' AND status IN ('confirmed','paid')");
$stmt->execute([$userId]);
$totalSpent = (float)$stmt->fetchColumn();

$pageTitle = t('Dashboard Reseller');
require_once 'includes/header-klook.php';
?>

<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2"></i><?= t('Dashboard Reseller') ?></h4>
        <p class="text-muted small mb-4"><?= t('Kelola saldo dan booking Anda sebagai reseller.') ?></p>

        <!-- Balance Card + Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="text-muted small mb-1"><?= t('Saldo Reseller') ?></div>
                        <div class="fs-3 fw-bold text-primary"><?= formatRupiah($balance) ?></div>
                        <a href="reseller-topup.php" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-plus-circle me-1"></i><?= t('Topup') ?></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="text-muted small mb-1"><?= t('Total Booking') ?></div>
                        <div class="fs-3 fw-bold"><?= $totalBookings ?></div>
                        <a href="tours.php" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-plus-circle me-1"></i><?= t('Booking Baru') ?></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="text-muted small mb-1"><?= t('Total Pengeluaran') ?></div>
                        <div class="fs-3 fw-bold text-success"><?= formatRupiah($totalSpent) ?></div>
                        <a href="my-bookings.php" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-ticket-perforated me-1"></i><?= t('Lihat Booking') ?></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Bookings -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3"><?= t('Booking Terbaru') ?></h6>
                        <?php if (empty($recentBookings)): ?>
                            <p class="text-muted small mb-0"><?= t('Belum ada booking reseller.') ?></p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th><?= t('Kode') ?></th><th><?= t('Tour') ?></th><th><?= t('Harga') ?></th><th><?= t('Status') ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($recentBookings as $b): ?>
                                    <tr>
                                        <td class="small fw-semibold"><?= e($b['booking_code'] ?? '-') ?></td>
                                        <td class="small"><?= e($b['tour_title'] ?? '-') ?></td>
                                        <td class="small"><?= formatRupiah((float)$b['total_price']) ?></td>
                                        <td>
                                            <?php
                                            $badge = match($b['status']) {
                                                'confirmed', 'paid' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-warning text-dark',
                                            };
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= ucfirst($b['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Topup History -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-semibold mb-0"><?= t('Riwayat Topup') ?></h6>
                            <a href="reseller-topup.php" class="btn btn-sm btn-outline-primary"><?= t('Topup') ?></a>
                        </div>
                        <?php if (empty($history)): ?>
                            <p class="text-muted small mb-0"><?= t('Belum ada riwayat topup.') ?></p>
                        <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($history as $h): ?>
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="small fw-semibold"><?= formatRupiah((float)$h['amount']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem"><?= date('d M Y', strtotime($h['created_at'])) ?></div>
                                </div>
                                <?php
                                $badge = match($h['status']) {
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };
                                ?>
                                <span class="badge <?= $badge ?> badge-sm"><?= ucfirst($h['status']) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require_once 'includes/footer-klook.php'; ?>

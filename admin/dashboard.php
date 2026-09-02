<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

// Statistik
$totalTours = db()->query("SELECT COUNT(*) FROM tours WHERE is_active = 1")->fetchColumn();
$totalBookings = db()->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalPending = db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$totalConfirmed = db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$totalRevenue = db()->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'confirmed'")->fetchColumn();

// Booking terbaru
$recentBookings = db()->query("
    SELECT b.*, t.title as tour_title 
    FROM bookings b 
    JOIN tours t ON b.tour_id = t.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard';
require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Dashboard</h4>
    <span class="text-muted small">Selamat datang, <?= e($_SESSION['admin_username']) ?></span>
</div>

<!-- Stat Cards with Icons -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Tour Aktif</div>
                        <div class="fs-3 fw-bold text-dark"><?= $totalTours ?></div>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-map fs-4"></i>
                    </div>
                </div>
                <div class="small text-success mt-2"><i class="bi bi-arrow-up"></i> Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Total Booking</div>
                        <div class="fs-3 fw-bold text-dark"><?= $totalBookings ?></div>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-ticket-perforated fs-4"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2"><i class="bi bi-clock"></i> Semua status</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Pending</div>
                        <div class="fs-3 fw-bold text-warning"><?= $totalPending ?></div>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
                <div class="small text-warning mt-2"><i class="bi bi-arrow-up"></i> Menunggu</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Confirmed</div>
                        <div class="fs-3 fw-bold text-success"><?= $totalConfirmed ?></div>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-check-circle fs-4"></i>
                    </div>
                </div>
                <div class="small text-success mt-2"><i class="bi bi-arrow-up"></i> Terkonfirmasi</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Revenue</div>
                        <div class="fs-5 fw-bold text-primary"><?= formatRupiah($totalRevenue) ?></div>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-currency-dollar fs-4"></i>
                    </div>
                </div>
                <div class="small text-success mt-2"><i class="bi bi-arrow-up"></i> Pendapatan</div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0">Booking Terbaru</h6>
        <a href="bookings.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Tour</th>
                        <th>Peserta</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $b): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><?= e($b['name']) ?></td>
                        <td><small><?= e($b['tour_title']) ?></small></td>
                        <td><?= $b['participants'] ?></td>
                        <td><?= formatRupiah($b['total_price']) ?></td>
                        <td>
                            <span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                <?= ucfirst($b['status']) ?>
                            </span>
                        </td>
                        <td><small><?= date('d/m/Y', strtotime($b['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentBookings)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Belum ada booking</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>

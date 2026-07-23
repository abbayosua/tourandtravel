<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-bookings.php');
    exit;
}

$userId = $_SESSION['user_id'];

$bookings = db()->prepare("
    SELECT b.*, t.title as tour_title, t.slug as tour_slug, td.departure_date, td.return_date
    FROM bookings b
    JOIN tours t ON b.tour_id = t.id
    JOIN tour_dates td ON b.tour_date_id = td.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$bookings->execute([$userId]);
$bookings = $bookings->fetchAll();

$pageTitle = 'Riwayat Booking';
require_once 'includes/header.php';
?>

<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-ticket-perforated me-2"></i>Riwayat Booking</h4>

        <?php if (count($bookings) > 0): ?>
        <div class="row g-3">
            <?php foreach ($bookings as $b): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-semibold mb-0"><?= e($b['tour_title']) ?></h6>
                                <small class="text-muted">ID Booking: #<?= $b['id'] ?></small>
                            </div>
                            <span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                <?= ucfirst($b['status']) ?>
                            </span>
                        </div>
                        <div class="row small text-muted g-2">
                            <div class="col-6">
                                <i class="bi bi-calendar me-1"></i><?= tglIndonesia($b['departure_date']) ?>
                            </div>
                            <div class="col-6">
                                <i class="bi bi-people me-1"></i><?= $b['participants'] ?> peserta
                            </div>
                            <div class="col-6">
                                <i class="bi bi-cash me-1"></i><?= formatRupiah($b['total_price']) ?>
                            </div>
                            <div class="col-6">
                                <i class="bi bi-clock me-1"></i><?= date('d/m/Y', strtotime($b['created_at'])) ?>
                            </div>
                        </div>
                        <a href="tour-detail.php?slug=<?= e($b['tour_slug']) ?>" class="btn btn-sm btn-outline-primary rounded-pill mt-2 px-3">Lihat Tour</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-ticket fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Belum ada pemesanan.</p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4">Booking Sekarang</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

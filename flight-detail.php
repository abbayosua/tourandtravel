<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM flights WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$flight = $stmt->fetch();
if (!$flight) { header('Location: flights.php'); exit; }

$pageTitle = $flight['airline'] . ' ' . $flight['flight_number'];

require_once 'includes/header.php';
?>
<section class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="flights.php">Pesawat</a></li>
            <li class="breadcrumb-item active"><?= e($flight['flight_number']); ?></li>
        </ol></nav>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold"><?= e($flight['airline']) ?></h4>
                            <span class="badge bg-primary"><?= e($flight['flight_number']) ?></span>
                            <span class="badge bg-secondary ms-1"><?= ucfirst($flight['class']) ?></span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center gap-4 mb-4">
                            <div class="text-center">
                                <div class="fs-3 fw-bold"><?= date('H:i', strtotime($flight['departure_time'])) ?></div>
                                <small class="text-muted"><?= e($flight['from_city']) ?></small>
                            </div>
                            <div class="text-center">
                                <div class="text-muted small"><?= e($flight['duration']) ?></div>
                                <i class="bi bi-airplane-fill fs-4 text-primary"></i>
                            </div>
                            <div class="text-center">
                                <div class="fs-3 fw-bold"><?= date('H:i', strtotime($flight['arrival_time'])) ?></div>
                                <small class="text-muted"><?= e($flight['to_city']) ?></small>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="fs-4 fw-bold text-primary"><?= formatRupiah($flight['price']) ?><small class="fw-normal text-muted fs-6">/org</small></div>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn btn-primary btn-lg rounded-pill px-5 mt-3" onclick="alert('Penerbangan berhasil dipesan! Silakan cek menu Booking Saya.')">Pesan Sekarang</button>
                            <?php else: ?>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary btn-lg rounded-pill px-5 mt-3">Masuk untuk Pesan</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

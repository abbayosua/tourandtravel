<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM rental_cars WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$car = $stmt->fetch();
if (!$car) { header('Location: rental-cars.php'); exit; }

$pageTitle = $car['name'];

$bookingSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = (int)($_POST['days'] ?? 1);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name && $phone && $days > 0) {
        $total = $car['price_per_day'] * $days;
        $bookingSuccess = "Booking berhasil! Total: " . formatRupiah($total) . " ($days hari)";
    }
}

require_once 'includes/header.php';
?>
<section class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="rental-cars.php">Rental Mobil</a></li>
            <li class="breadcrumb-item active"><?= e($car['name']); ?></li>
        </ol></nav>
        <div class="row">
            <div class="col-lg-8">
                <img src="https://picsum.photos/seed/<?= urlencode($car['slug']) ?>/800/400" class="w-100 rounded-4 shadow-sm mb-3" style="max-height: 400px; object-fit: cover;" alt="">
                <h4 class="fw-bold"><?= e($car['name']); ?></h4>
                <div class="d-flex flex-wrap gap-3 mb-2 small">
                    <span><i class="bi bi-geo-alt text-primary"></i> <?= e($car['city']) ?></span>
                    <span><i class="bi bi-car-front text-primary"></i> <?= e($car['car_type']) ?></span>
                    <span><i class="bi bi-people text-primary"></i> <?= $car['passenger_capacity'] ?> kursi</span>
                    <span><i class="bi bi-gear text-primary"></i> <?= ucfirst($car['transmission']) ?></span>
                </div>
                <p>Nikmati perjalanan Anda dengan <?= e($car['name']) ?>. Mobil dalam kondisi prima, terawat, dan siap pakai. Harga sudah termasuk asuransi dasar dan bantuan darurat 24 jam.</p>
                <h6 class="fw-semibold">Termasuk</h6>
                <div class="row g-2">
                    <?php foreach (['Asuransi Dasar','AC','Bantuan Darurat 24 Jam','Bensin Penuh'] as $f): ?>
                    <div class="col-6"><i class="bi bi-check-circle text-success me-1"></i><small><?= $f ?></small></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body">
                        <h5 class="fw-bold text-primary"><?= formatRupiah($car['price_per_day']) ?> <small class="fw-normal text-muted">/hari</small></h5>
                        <?php if ($bookingSuccess): ?><div class="alert alert-success py-2 small"><?= $bookingSuccess ?></div><?php endif; ?>
                        <form method="POST">
                            <div class="mb-2"><label class="form-label small">Jumlah Hari</label><input type="number" name="days" class="form-control form-control-sm" min="1" value="1" required></div>
                            <div class="mb-2"><label class="form-label small">Nama</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                            <div class="mb-2"><label class="form-label small">No. Telepon</label><input type="text" name="phone" class="form-control form-control-sm" required></div>
                            <button type="submit" class="btn btn-primary w-100 mt-2">Sewa Sekarang</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

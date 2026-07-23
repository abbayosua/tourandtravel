<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM hotels WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$hotel = $stmt->fetch();
if (!$hotel) { header('Location: hotels.php'); exit; }

$pageTitle = $hotel['name'];

$bookingSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkin = $_POST['checkin'] ?? '';
    $checkout = $_POST['checkout'] ?? '';
    $rooms = (int)($_POST['rooms'] ?? 1);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($checkin && $checkout && $name && $phone) {
        $days = max(1, (strtotime($checkout) - strtotime($checkin)) / 86400);
        $total = $hotel['price_per_night'] * $days * $rooms;
        $bookingSuccess = "Booking berhasil! Total: " . formatRupiah($total) . " ($days malam, $rooms kamar)";
    }
}

require_once 'includes/header.php';
?>
<section class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="hotels.php">Hotel</a></li>
            <li class="breadcrumb-item active"><?= e($hotel['name']); ?></li>
        </ol></nav>
        <div class="row">
            <div class="col-lg-8">
                <img src="https://picsum.photos/seed/<?= urlencode($hotel['slug']) ?>/800/400" class="w-100 rounded-4 shadow-sm mb-3" style="max-height: 400px; object-fit: cover;" alt="">
                <h4 class="fw-bold"><?= e($hotel['name']); ?></h4>
                <div class="d-flex gap-2 mb-2">
                    <?php for ($i=0; $i<$hotel['star_rating']; $i++): ?><i class="bi bi-star-fill text-warning"></i><?php endfor; ?>
                    <span class="text-muted small"><i class="bi bi-geo-alt"></i> <?= e($hotel['city']); ?></span>
                </div>
                <p><?= nl2br(e($hotel['description'])); ?></p>
                <h6 class="fw-semibold mt-3">Fasilitas</h6>
                <div class="row g-2">
                    <?php foreach (['WiFi','Kolam Renang','AC','Restoran','Parkir','Gym','Spa','Room Service'] as $f): ?>
                    <div class="col-6 col-md-3"><i class="bi bi-check-circle text-success me-1"></i><small><?= $f ?></small></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body">
                        <h5 class="fw-bold text-primary"><?= formatRupiah($hotel['price_per_night']) ?> <small class="fw-normal text-muted">/malam</small></h5>
                        <?php if ($bookingSuccess): ?><div class="alert alert-success py-2 small"><?= $bookingSuccess ?></div><?php endif; ?>
                        <form method="POST">
                            <div class="mb-2"><label class="form-label small">Check-in</label><input type="date" name="checkin" class="form-control form-control-sm" required></div>
                            <div class="mb-2"><label class="form-label small">Check-out</label><input type="date" name="checkout" class="form-control form-control-sm" required></div>
                            <div class="mb-2"><label class="form-label small">Kamar</label><input type="number" name="rooms" class="form-control form-control-sm" min="1" value="1" required></div>
                            <div class="mb-2"><label class="form-label small">Nama</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                            <div class="mb-2"><label class="form-label small">No. Telepon</label><input type="text" name="phone" class="form-control form-control-sm" required></div>
                            <button type="submit" class="btn btn-primary w-100 mt-2">Pesan Sekarang</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

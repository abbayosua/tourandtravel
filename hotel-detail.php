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
$checkin = $_GET['checkin'] ?? date('Y-m-d');
$checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+2 days'));
$guests = (int)($_GET['guests'] ?? 2);

$bookingSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ci = $_POST['checkin'] ?? $checkin;
    $co = $_POST['checkout'] ?? $checkout;
    $rooms = (int)($_POST['rooms'] ?? 1);
    $g = (int)($_POST['guests'] ?? $guests);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($ci && $co && $name && $phone) {
        $nights = max(1, (strtotime($co) - strtotime($ci)) / 86400);
        $total = $hotel['price_per_night'] * $nights * $rooms;
        $bookingSuccess = "Booking berhasil! Total: " . formatRupiah($total);
    }
}

// Similar hotels
$similar = db()->prepare("SELECT * FROM hotels WHERE city = ? AND id != ? AND is_active = 1 LIMIT 3");
$similar->execute([$hotel['city'], $hotel['id']]);
$similar = $similar->fetchAll();

$nights = max(1, (strtotime($checkout) - strtotime($checkin)) / 86400);
$totalPrice = $hotel['price_per_night'] * $nights;

require_once 'includes/header.php';
?>
<section class="py-4 bg-light">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="hotels.php">Hotel</a></li>
            <li class="breadcrumb-item"><a href="hotels.php?city=<?= urlencode($hotel['city']) ?>"><?= e($hotel['city']) ?></a></li>
            <li class="breadcrumb-item active"><?= e($hotel['name']) ?></li>
        </ol></nav>

        <div class="row">
            <!-- Gallery -->
            <div class="col-12 mb-3">
                <div class="row g-2">
                    <div class="col-md-8">
                        <img src="https://picsum.photos/seed/<?= urlencode($hotel['slug']) ?>/800/400" class="w-100 rounded-4 shadow-sm" style="height: 350px; object-fit: cover;" alt="">
                    </div>
                    <div class="col-md-4">
                        <div class="row g-2">
                            <?php for ($i=1; $i<=2; $i++): ?>
                            <div class="col-6 col-md-12">
                                <img src="https://picsum.photos/seed/<?= urlencode($hotel['slug']) . $i ?>/400/200" class="w-100 rounded-3 shadow-sm" style="height: 170px; object-fit: cover;" alt="">
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="fw-bold mb-1"><?= e($hotel['name']) ?></h4>
                                <div class="d-flex gap-3 align-items-center">
                                    <span class="text-warning"><?= str_repeat('★', $hotel['star_rating']) ?><?= str_repeat('☆', 5 - $hotel['star_rating']) ?></span>
                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($hotel['city']) ?></small>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h6 class="fw-semibold">Fasilitas Hotel</h6>
                        <div class="row g-2 mb-3">
                            <?php 
                            $fasilitas = ['WiFi Gratis','Kolam Renang','AC','Restoran','Parkir','Gym','Spa','Layanan Kamar','Sarapan','Bandara'];
                            foreach ($fasilitas as $f): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 12px;"></i>
                                    <small><?= $f ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-muted small"><?= nl2br(e($hotel['description'])) ?></p>
                    </div>
                </div>

                <!-- Similar Hotels -->
                <?php if (count($similar) > 0): ?>
                <h5 class="fw-bold mb-3">Hotel Lain di <?= e($hotel['city']) ?></h5>
                <div class="row g-3 mb-4">
                    <?php foreach ($similar as $s): ?>
                    <div class="col-md-4">
                        <a href="hotel-detail.php?slug=<?= e($s['slug']) ?>" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100">
                                <img src="https://picsum.photos/seed/<?= urlencode($s['slug']) ?>/400/200" class="card-img-top" style="height: 140px; object-fit: cover;" alt="">
                                <div class="card-body p-2">
                                    <h6 class="fw-semibold small mb-0 text-dark"><?= e($s['name']) ?></h6>
                                    <span class="text-warning" style="font-size: 11px;"><?= str_repeat('★', $s['star_rating']) ?></span>
                                    <div class="fw-bold text-primary small mt-1"><?= formatRupiah($s['price_per_night']) ?><small class="fw-normal text-muted">/malam</small></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Booking Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-primary mb-3"><?= formatRupiah($hotel['price_per_night']) ?> <small class="fw-normal text-muted fs-6">/malam</small></h5>

                        <?php if ($bookingSuccess): ?>
                            <div class="alert alert-success py-2 small"><?= $bookingSuccess ?></div>
                        <?php endif; ?>

                        <?php if (!isLoggedIn()): ?>
                            <div class="text-center py-3">
                                <p class="fw-semibold mb-2">Login untuk Booking</p>
                                <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary w-100">Masuk / Daftar</a>
                            </div>
                        <?php else: ?>
                        <form method="POST">
                            <div class="mb-2">
                                <label class="form-label small">Check-in</label>
                                <input type="date" name="checkin" class="form-control" value="<?= e($checkin) ?>" onchange="updateTotal()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Check-out</label>
                                <input type="date" name="checkout" class="form-control" value="<?= e($checkout) ?>" onchange="updateTotal()">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small">Kamar</label>
                                    <select name="rooms" class="form-select" onchange="updateTotal()">
                                        <?php for ($r=1; $r<=5; $r++): ?>
                                        <option value="<?= $r ?>"><?= $r ?> Kamar</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Tamu</label>
                                    <select name="guests" class="form-select">
                                        <?php for ($g=1; $g<=10; $g++): ?>
                                        <option value="<?= $g ?>" <?= $guests === $g ? 'selected' : '' ?>><?= $g ?> Tamu</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="bg-light rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Harga x <span id="nightsDisplay"><?= $nights ?></span> malam</span>
                                    <span><?= formatRupiah($hotel['price_per_night']) ?> × <span id="nightsDisplay2"><?= $nights ?></span></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                    <span>Total</span>
                                    <span class="text-primary fs-5" id="totalDisplay"><?= formatRupiah($totalPrice) ?></span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" value="<?= e(getUser()['name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">No. Telepon</label>
                                <input type="text" name="phone" class="form-control" value="<?= e(getUser()['phone'] ?? '') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Pesan Sekarang</button>
                        </form>

                        <script>
                        var pricePerNight = <?= $hotel['price_per_night'] ?>;
                        var checkinInput = document.querySelector('input[name="checkin"]');
                        var checkoutInput = document.querySelector('input[name="checkout"]');
                        var roomsSelect = document.querySelector('select[name="rooms"]');

                        function updateTotal() {
                            var ci = new Date(checkinInput.value);
                            var co = new Date(checkoutInput.value);
                            var diff = Math.max(1, Math.round((co - ci) / (1000 * 60 * 60 * 24)));
                            var rooms = parseInt(roomsSelect.value);
                            var total = pricePerNight * diff * rooms;
                            document.getElementById('nightsDisplay').textContent = diff;
                            document.getElementById('nightsDisplay2').textContent = diff;
                            document.getElementById('totalDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
                        }
                        </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$scheduleId = (int)($_GET['schedule_id'] ?? 0);
$stmt = db()->prepare("
    SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, 
           f.departure_time, f.arrival_time, f.duration, f.class
    FROM flight_schedules fs 
    JOIN flights f ON fs.flight_id = f.id 
    WHERE fs.id = ? AND fs.is_active = 1
");
$stmt->execute([$scheduleId]);
$schedule = $stmt->fetch();
if (!$schedule) { header('Location: flights.php'); exit; }

$pageTitle = $schedule['airline'] . ' ' . $schedule['flight_number'];

$bookingSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $passengers = (int)($_POST['passengers'] ?? 1);
    if ($name && $phone && $passengers > 0) {
        $total = $schedule['price'] * $passengers;
        $bookingSuccess = "Penerbangan berhasil dipesan! Total: " . formatRupiah($total);
    }
}

require_once 'includes/header.php';
?>
<section class="py-4 bg-light" style="min-height: 80vh;">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="flights.php">Pesawat</a></li>
            <li class="breadcrumb-item active"><?= e($schedule['flight_number']); ?></li>
        </ol></nav>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 text-center">
                        <h4 class="fw-bold"><?= e($schedule['airline']) ?></h4>
                        <span class="badge bg-primary"><?= e($schedule['flight_number']) ?></span>
                        <span class="badge bg-<?= $schedule['class'] === 'economy' ? 'success' : ($schedule['class'] === 'business' ? 'warning text-dark' : 'danger') ?> ms-1"><?= ucfirst($schedule['class']) ?></span>
                        
                        <div class="d-flex justify-content-center align-items-center gap-4 my-4">
                            <div class="text-center">
                                <div class="fs-3 fw-bold"><?= date('H:i', strtotime($schedule['departure_time'])) ?></div>
                                <small class="text-muted"><?= e(explode('(', $schedule['from_city'])[0]) ?></small>
                            </div>
                            <div class="text-center">
                                <i class="bi bi-airplane-fill fs-3 text-primary d-block mb-1"></i>
                                <span class="text-muted small"><?= e($schedule['duration']) ?></span>
                                <div class="text-muted small"><?= tglIndonesia($schedule['departure_date']) ?></div>
                            </div>
                            <div class="text-center">
                                <div class="fs-3 fw-bold"><?= date('H:i', strtotime($schedule['arrival_time'])) ?></div>
                                <small class="text-muted"><?= e(explode('(', $schedule['to_city'])[0]) ?></small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-center gap-4 text-muted small mb-3">
                            <span>Langsung</span>
                            <span>Sisa <?= $schedule['available_seats'] ?> kursi</span>
                        </div>
                    </div>
                </div>

                <!-- Booking -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Pesan Penerbangan</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <span class="fw-semibold"><?= e($schedule['airline']) ?> · <?= e($schedule['flight_number']) ?></span>
                            <span class="fw-bold text-primary fs-5"><?= formatRupiah($schedule['price']) ?><small class="fw-normal fs-6 text-muted">/org</small></span>
                        </div>

                        <?php if ($bookingSuccess): ?>
                            <div class="alert alert-success py-2"><?= $bookingSuccess ?></div>
                        <?php endif; ?>

                        <?php if (!isLoggedIn()): ?>
                            <div class="text-center py-3">
                                <p class="fw-semibold mb-2">Login untuk Memesan</p>
                                <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary w-100">Masuk / Daftar</a>
                            </div>
                        <?php elseif ($schedule['available_seats'] < 1): ?>
                            <div class="alert alert-danger py-2">Kursi penuh untuk jadwal ini.</div>
                        <?php else: ?>
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" name="name" class="form-control" placeholder="Nama Lengkap" value="<?= e(getUser()['name'] ?? '') ?>" required></div>
                                <div class="col-md-6"><input type="text" name="phone" class="form-control" placeholder="No. Telepon" value="<?= e(getUser()['phone'] ?? '') ?>" required></div>
                                <div class="col-md-4">
                                    <select name="passengers" class="form-select">
                                        <?php for ($i=1; $i<=min(9, $schedule['available_seats']); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> Penumpang</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-8 d-grid">
                                    <button type="submit" class="btn btn-primary fw-semibold">Pesan Sekarang</button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

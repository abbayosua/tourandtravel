<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (!isReseller($userId)) {
    header('Location: index.php');
    exit;
}

$tourId = (int)($_GET['tour_id'] ?? 0);
if (!$tourId) {
    header('Location: tours.php');
    exit;
}

$tour = db()->prepare("SELECT * FROM tours WHERE id = ? AND is_active = 1");
$tour->execute([$tourId]);
$tour = $tour->fetch();
if (!$tour) {
    header('Location: tours.php');
    exit;
}

$resellerPrice = getResellerTourPrice($tourId);
if (!$resellerPrice) {
    header('Location: tour-detail.php?slug=' . urlencode($tour['slug']));
    exit;
}

$balance = getResellerBalance($userId);
$errors = [];
$success = false;

// Get available dates
$stmt = db()->prepare("SELECT * FROM tour_dates WHERE tour_id = ? AND is_active = 1 AND departure_date >= CURDATE() ORDER BY departure_date ASC");
$stmt->execute([$tourId]);
$dates = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateId = (int)($_POST['tour_date_id'] ?? 0);
    $passengers = max($resellerPrice['min_pax'], (int)($_POST['passengers'] ?? $resellerPrice['min_pax']));
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (strlen($name) < 2) $errors[] = t('Nama lengkap wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('Email tidak valid.');
    if (strlen($phone) < 8) $errors[] = t('Nomor telepon tidak valid.');
    if (!$dateId) $errors[] = t('Pilih tanggal keberangkatan.');

    $totalPrice = $resellerPrice['price'] * $passengers;

    if (!$errors) {
        if ($balance < $totalPrice) {
            $errors[] = t('Saldo tidak cukup. Butuh ') . formatRupiah($totalPrice) . t(', saldo Anda ') . formatRupiah($balance) . '.';
        } else {
            // Deduct balance
            $newBal = spendResellerBalance($userId, $totalPrice, 'Booking tour: ' . $tour['title'], null);
            if ($newBal === false) {
                $errors[] = t('Gagal memotong saldo. Silakan coba lagi.');
            } else {
                // Generate booking code
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $bookingCode = 'TAT-';
                for ($i = 0; $i < 5; $i++) $bookingCode .= $chars[random_int(0, strlen($chars) - 1)];

                $parts = preg_split('/\s+/', $name, 2);
                $stmt = db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, booking_source, reseller_id, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'reseller', ?, ?, 'pending')");
                $stmt->execute([$bookingCode, $tourId, $dateId, $name, $email, $phone, $passengers, $totalPrice, $userId, $userId]);

                // Update wallet transaction with booking ref
                $bookingId = (int)db()->lastInsertId();
                try {
                    db()->prepare("UPDATE wallet_transactions SET reference_id = ?, description = ? WHERE user_id = ? AND reference_type = 'reseller_booking' AND reference_id IS NULL ORDER BY id DESC LIMIT 1")
                        ->execute([$bookingId, 'Booking: ' . $bookingCode . ' - ' . $tour['title'], $userId]);
                } catch (Throwable $e) {}

                // Reduce available slots
                db()->prepare("UPDATE tour_dates SET available_slots = available_slots - ? WHERE id = ? AND available_slots >= ?")
                    ->execute([$passengers, $dateId, $passengers]);

                $success = $bookingCode;
                $balance = getResellerBalance($userId);
            }
        }
    }
}

$pageTitle = t('Booking Reseller') . ' — ' . e($tour['title']);
require_once 'includes/header-klook.php';
?>

<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <h4 class="fw-bold mb-1"><i class="bi bi-ticket-perforated me-2"></i><?= t('Booking Reseller') ?></h4>
                <p class="text-muted small mb-4"><?= e($tour['title']) ?></p>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i><?= t('Booking berhasil! Kode booking: ') ?>
                        <strong><?= e($success) ?></strong>
                        <br><small class="text-muted"><?= t('Saldo tersisa: ') ?><?= formatRupiah($balance) ?></small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="my-bookings.php" class="btn btn-primary"><?= t('Lihat Booking Saya') ?></a>
                        <a href="tours.php" class="btn btn-outline-secondary"><?= t('Cari Tour Lain') ?></a>
                    </div>
                <?php else: ?>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger py-2"><?php foreach ($errors as $e) echo '<div>' . $e . '</div>'; ?></div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h6 class="fw-semibold mb-3"><?= t('Detail Tour') ?></h6>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <?php $img = getTourImage($tour, 'small'); ?>
                                        <img src="<?= $img ?>" alt="<?= e($tour['title']) ?>" class="rounded-3" style="width:80px;height:60px;object-fit:cover;" onerror="this.src='<?= getTourImageFallback($tour, 'small') ?>'">
                                        <div>
                                            <div class="fw-semibold small"><?= e($tour['title']) ?></div>
                                            <div class="text-muted" style="font-size:0.8rem"><?= e($tour['category']) ?> · <?= t('Max') ?> <?= $tour['max_participants'] ?> <?= t('pax') ?></div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small"><?= t('Harga Normal') ?></span>
                                        <span class="text-decoration-line-through text-muted"><?= formatRupiah((float)$tour['price']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold text-primary"><?= t('Harga Reseller') ?></span>
                                        <span class="fw-bold text-primary fs-5"><?= formatRupiah($resellerPrice['price']) ?><?= t('/pax') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small"><?= t('Minimal') ?></span>
                                        <span class="small"><?= $resellerPrice['min_pax'] ?> <?= t('pax') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h6 class="fw-semibold mb-3"><?= t('Saldo Anda') ?></h6>
                                    <div class="fs-4 fw-bold text-primary mb-3"><?= formatRupiah($balance) ?></div>
                                    <a href="reseller-topup.php" class="btn btn-sm btn-outline-primary mb-3 w-100"><?= t('Topup Saldo') ?></a>

                                    <form method="POST">
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold"><?= t('Tanggal Keberangkatan') ?></label>
                                            <select name="tour_date_id" class="form-select" required>
                                                <option value=""><?= t('Pilih tanggal') ?></option>
                                                <?php foreach ($dates as $d): ?>
                                                    <option value="<?= $d['id'] ?>"><?= date('d M Y', strtotime($d['departure_date'])) ?> — <?= t('Sisa') ?> <?= $d['available_slots'] ?> <?= t('slot') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold"><?= t('Jumlah Peserta') ?></label>
                                            <input type="number" name="passengers" class="form-control" min="<?= $resellerPrice['min_pax'] ?>" max="<?= $tour['max_participants'] ?>" value="<?= $resellerPrice['min_pax'] ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold"><?= t('Nama Lengkap') ?></label>
                                            <input type="text" name="name" class="form-control" value="<?= e($_SESSION['user_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold"><?= t('Email') ?></label>
                                            <input type="email" name="email" class="form-control" value="<?= e($_SESSION['user_email'] ?? '') ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold"><?= t('No. Telepon') ?></label>
                                            <input type="text" name="phone" class="form-control" value="<?= e($_SESSION['user_phone'] ?? '') ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 fw-semibold"><?= t('Bayar dari Saldo Reseller') ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer-klook.php'; ?>

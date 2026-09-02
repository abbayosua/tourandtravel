<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM transfers WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$transfer = $stmt->fetch();

if (!$transfer) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = t('Transfer Tidak Ditemukan');
    require_once 'includes/header-klook.php';
    echo '<div class="container py-5 text-center"><h3>' . t('Transfer tidak ditemukan') . '</h3><a href="transfers.php" class="btn btn-primary mt-3">' . t('Kembali ke Katalog') . '</a></div>';
    require_once 'includes/footer-klook.php';
    exit;
}

$pageTitle = $transfer['name'];

$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $pickupDate = trim($_POST['pickup_date'] ?? '');
    $pickupTime = trim($_POST['pickup_time'] ?? '');
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $passengers = (int)($_POST['passengers'] ?? 1);
    $flightNumber = trim($_POST['flight_number'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];
    if (!$pickupDate) $errors[] = t('Tanggal penjemputan harus diisi');
    if (!$pickupTime) $errors[] = t('Jam penjemputan harus diisi');
    if (!$pickupLocation) $errors[] = t('Lokasi penjemputan harus diisi');
    if ($passengers < 1) $errors[] = t('Jumlah penumpang minimal 1');
    if ($passengers > $transfer['max_passengers']) $errors[] = t('Maksimal') . ' ' . $transfer['max_passengers'] . ' ' . t('penumpang');
    if (!$name) $errors[] = t('Nama harus diisi');
    if (!$phone) $errors[] = t('No. WhatsApp harus diisi');

    if (empty($errors)) {
        $totalPrice = $transfer['price'];
        $bookingCode = generateBookingCode();
        $walletDeduct = 0;
        if (!empty($_SESSION['user_id']) && !empty($_POST['use_wallet'])) {
            require_once 'includes/wallet.php';
            $balance = getWalletBalance($_SESSION['user_id']);
            if ($balance > 0) {
                $walletDeduct = min($balance, $totalPrice);
                $totalPrice -= $walletDeduct;
            }
        }
        $ins = db()->prepare("INSERT INTO transfer_bookings (transfer_id, user_id, name, email, phone, pickup_date, pickup_time, pickup_location, flight_number, passengers, total_price, booking_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $ins->execute([$transfer['id'], $_SESSION['user_id'] ?? null, $name, $email, $phone, $pickupDate, $pickupTime, $pickupLocation, $flightNumber ?: null, $passengers, $totalPrice, $bookingCode]);
        $bookingId = (int)db()->lastInsertId();
        if ($walletDeduct > 0 && !empty($_SESSION['user_id'])) {
            require_once 'includes/wallet.php';
            spendWallet($_SESSION['user_id'], $walletDeduct, 'transfer_booking', $bookingId);
        }
        header("Location: booking-success.php?code=$bookingCode");
        exit;
    } else {
        $bookingError = implode('<br>', $errors);
    }
}

// Similar transfers (same from_city)
$similar = db()->prepare("SELECT * FROM transfers WHERE from_city = ? AND id != ? AND is_active = 1 LIMIT 3");
$similar->execute([$transfer['from_city'], $transfer['id']]);
$similar = $similar->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<div class="container py-4">
    <?php renderBreadcrumb([
        ['label' => t('Transfer Bandara'), 'url' => 'transfers.php'],
        ['label' => $transfer['name'], 'url' => null],
    ]); ?>

    <div class="row">
        <div class="col-lg-8">
            <img src="https://placehold.co/800x450?text=Airport+Transfer" class="w-100 rounded-4 shadow-sm mb-3" style="max-height: 350px; object-fit: cover;" alt="">

            <h2 class="fw-bold"><?= e($transfer['name']) ?></h2>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <span class="badge bg-primary"><?= e($transfer['vehicle_type'] ?? 'Transfer') ?></span>
                <span class="text-muted"><i class="bi bi-arrow-left-right me-1"></i><?= e($transfer['from_city']) ?> → <?= e($transfer['to_city']) ?></span>
                <span class="text-muted"><i class="bi bi-people me-1"></i>Max <?= $transfer['max_passengers'] ?> pax</span>
                <?php if (!empty($transfer['instant_confirmation'])): ?><span class="badge bg-success"><?= t('Konfirmasi Instan') ?></span><?php endif; ?>
                <?php if (!empty($transfer['free_cancellation'])): ?><span class="badge bg-info"><?= t('Batal Gratis') ?></span><?php endif; ?>
            </div>
            <p class="lead"><?= nl2br(e($transfer['description'])) ?></p>

            <h6 class="fw-semibold"><?= t('Termasuk') ?></h6>
            <div class="row g-2 mb-4">
                <?php foreach ([t('Driver profesional'), t('Bahan bakar'), t('Asuransi perjalanan'), t('Bantuan 24 jam')] as $f): ?>
                <div class="col-6"><i class="bi bi-check-circle text-success me-1"></i><small><?= $f ?></small></div>
                <?php endforeach; ?>
            </div>

            <?php if (count($similar) > 0): ?>
            <h5 class="fw-bold mt-4 mb-3"><?= t('Transfer Lain') ?></h5>
            <div class="row g-3">
                <?php foreach ($similar as $s): ?>
                <div class="col-md-4">
                    <a href="transfer-detail.php?slug=<?= e($s['slug']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <h6 class="fw-semibold small mb-1 text-dark"><?= e($s['name']) ?></h6>
                                <small class="text-muted"><?= e($s['from_city']) ?> → <?= e($s['to_city']) ?></small>
                                <div class="fw-bold text-primary small mt-1"><?= formatCurrencySpan($s['price'], $s['price_currency'] ?? 'IDR') ?></div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Booking -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                <div class="card-body p-4">
                    <h4 class="fw-bold text-primary mb-0"><?= formatCurrencySpan($transfer['price'], $transfer['price_currency'] ?? 'IDR') ?></h4>
                    <p class="text-muted">/ <?= t('kendaraan') ?></p>

                    <?php if ($bookingError): ?>
                        <div class="alert alert-danger py-2 small"><?= $bookingError ?></div>
                    <?php endif; ?>

                    <?php if (!isLoggedIn()): ?>
                        <div class="text-center py-3">
                            <p class="fw-semibold mb-2"><?= t('Login untuk Booking') ?></p>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary w-100"><?= t('Masuk / Daftar') ?></a>
                        </div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="form_submitted" value="1">
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Tanggal Penjemputan') ?></label>
                            <input type="date" name="pickup_date" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Jam Penjemputan') ?></label>
                            <input type="time" name="pickup_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Lokasi Penjemputan') ?></label>
                            <input type="text" name="pickup_location" class="form-control form-control-sm" placeholder="<?= t('Alamat / terminal / hotel') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Jumlah Penumpang') ?></label>
                            <select name="passengers" class="form-select form-select-sm">
                                <?php for ($p = 1; $p <= $transfer['max_passengers']; $p++): ?>
                                <option value="<?= $p ?>"><?= $p ?> pax</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('No. Penerbangan (opsional)') ?></label>
                            <input type="text" name="flight_number" class="form-control form-control-sm" placeholder="GA-412">
                        </div>
                        <hr>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Nama Lengkap') ?></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="<?= e(getUser()['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Email') ?></label>
                            <input type="email" name="email" class="form-control form-control-sm" value="<?= e(getUser()['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small"><?= t('No. WhatsApp') ?></label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="<?= e(getUser()['phone'] ?? '') ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold"><?= t('Pesan Transfer') ?></button>
                        <?php if (!empty($_SESSION['user_id'])): require_once 'includes/wallet.php'; $walletBal = getWalletBalance($_SESSION['user_id']); ?>
                            <?php if ($walletBal > 0): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWalletTransfer">
                                <label class="form-check-label small" for="useWalletTransfer"><?= t('Gunakan KlookCash') ?> <strong><?= formatRupiah($walletBal) ?></strong></label>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer-klook.php'; ?>
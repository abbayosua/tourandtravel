<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM trains WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$train = $stmt->fetch();

if (!$train) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = t('Kereta Tidak Ditemukan');
    require_once 'includes/header-klook.php';
    echo '<div class="container py-5 text-center"><h3>' . t('Kereta tidak ditemukan') . '</h3><a href="trains.php" class="btn btn-primary mt-3">' . t('Kembali ke Katalog') . '</a></div>';
    require_once 'includes/footer-klook.php';
    exit;
}

$pageTitle = $train['name'];

$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $travelDate = trim($_POST['travel_date'] ?? '');
    $seats = (int)($_POST['seats'] ?? 1);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];
    if (!$travelDate) $errors[] = t('Tanggal perjalanan harus diisi');
    if ($seats < 1) $errors[] = t('Jumlah kursi minimal 1');
    if (!$name) $errors[] = t('Nama harus diisi');
    if (!$phone) $errors[] = t('No. WhatsApp harus diisi');

    if (empty($errors)) {
        $totalPrice = $train['price'] * $seats;
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
        $ins = db()->prepare("INSERT INTO train_bookings (train_id, user_id, name, email, phone, travel_date, seats, total_price, booking_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $ins->execute([$train['id'], $_SESSION['user_id'] ?? null, $name, $email, $phone, $travelDate, $seats, $totalPrice, $bookingCode]);
        $bookingId = (int)db()->lastInsertId();
        if ($walletDeduct > 0 && !empty($_SESSION['user_id'])) {
            require_once 'includes/wallet.php';
            spendWallet($_SESSION['user_id'], $walletDeduct, 'train_booking', $bookingId);
        }
        header("Location: booking-success.php?code=$bookingCode");
        exit;
    } else {
        $bookingError = implode('<br>', $errors);
    }
}

$similar = db()->prepare("SELECT * FROM trains WHERE route_from = ? AND id != ? AND is_active = 1 LIMIT 3");
$similar->execute([$train['route_from'], $train['id']]);
$similar = $similar->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<div class="container py-4">
    <?php renderBreadcrumb([
        ['label' => t('Kereta Api'), 'url' => 'trains.php'],
        ['label' => $train['name'], 'url' => null],
    ]); ?>

    <div class="row">
        <div class="col-lg-8">
            <img src="https://placehold.co/800x450?text=Train" class="w-100 rounded-4 shadow-sm mb-3" style="max-height: 350px; object-fit: cover;" alt="">

            <h2 class="fw-bold"><?= e($train['name']) ?></h2>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <span class="badge bg-primary"><?= e($train['class']) ?></span>
                <span class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($train['route_from']) ?> → <?= e($train['route_to']) ?></span>
                <span class="text-muted"><i class="bi bi-clock me-1"></i><?= substr($train['departure_time'], 0, 5) ?> - <?= substr($train['arrival_time'], 0, 5) ?> (<?= e($train['duration']) ?>)</span>
            </div>
            <p class="lead"><?= t('Nikmati perjalanan') ?> <?= e($train['route_from']) ?> <?= t('menuju') ?> <?= e($train['route_to']) ?> <?= t('dengan') ?> <?= e($train['name']) ?> <?= t('kelas') ?> <?= e($train['class']) ?>. <?= t('Jadwal keberangkatan pukul') ?> <?= substr($train['departure_time'], 0, 5) ?> <?= t('dan tiba pukul') ?> <?= substr($train['arrival_time'], 0, 5) ?> <?= t('dengan estimasi perjalanan') ?> <?= e($train['duration']) ?>.</p>

            <h6 class="fw-semibold"><?= t('Fasilitas') ?></h6>
            <div class="row g-2 mb-4">
                <?php foreach ([t('AC'), t('Toilet'), t('Colokan listrik'), t('Pramusaji'), t('Selimut'), t('Bangku reclining')] as $f): ?>
                <div class="col-6"><i class="bi bi-check-circle text-success me-1"></i><small><?= $f ?></small></div>
                <?php endforeach; ?>
            </div>

            <?php if (count($similar) > 0): ?>
            <h5 class="fw-bold mt-4 mb-3"><?= t('Kereta Lain') ?></h5>
            <div class="row g-3">
                <?php foreach ($similar as $s): ?>
                <div class="col-md-4">
                    <a href="train-detail.php?slug=<?= e($s['slug']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <h6 class="fw-semibold small mb-1 text-dark"><?= e($s['name']) ?></h6>
                                <small class="text-muted"><?= e($s['route_from']) ?> → <?= e($s['route_to']) ?></small>
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
                    <h4 class="fw-bold text-primary mb-0"><?= formatCurrencySpan($train['price'], $train['price_currency'] ?? 'IDR') ?></h4>
                    <p class="text-muted">/ <?= t('orang') ?></p>

                    <?php if ($bookingError): ?>
                        <div class="alert alert-danger py-2 small"><?= $bookingError ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="form_submitted" value="1">
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Tanggal Perjalanan') ?></label>
                            <input type="date" name="travel_date" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Jumlah Kursi') ?></label>
                            <select name="seats" class="form-select form-select-sm">
                                <?php for ($p = 1; $p <= 6; $p++): ?>
                                <option value="<?= $p ?>"><?= $p ?> <?= t('kursi') ?></option>
                                <?php endfor; ?>
                            </select>
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
                        <button type="submit" class="btn btn-primary w-100 fw-semibold"><?= t('Pesan Tiket') ?></button>
                        <?php if (!empty($_SESSION['user_id'])): require_once 'includes/wallet.php'; $walletBal = getWalletBalance($_SESSION['user_id']); ?>
                            <?php if ($walletBal > 0): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWalletTrain">
                                <label class="form-check-label small" for="useWalletTrain"><?= t('Gunakan KlookCash') ?> <strong><?= formatRupiah($walletBal) ?></strong></label>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer-klook.php'; ?>
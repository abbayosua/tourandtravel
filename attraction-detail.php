<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM attractions WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$attraction = $stmt->fetch();

if (!$attraction) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = t('Tiket Tidak Ditemukan');
    require_once 'includes/header-klook.php';
    echo '<div class="container py-5 text-center"><h3>' . t('Tiket tidak ditemukan') . '</h3><a href="attractions.php" class="btn btn-primary mt-3">' . t('Kembali ke Katalog') . '</a></div>';
    require_once 'includes/footer-klook.php';
    exit;
}

$pageTitle = $attraction['name'];

$bookingSuccess = '';
$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $visitDate = trim($_POST['visit_date'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];
    if (!$visitDate) $errors[] = t('Tanggal kunjungan harus diisi');
    if ($quantity < 1) $errors[] = t('Jumlah tiket minimal 1');
    if (!$name) $errors[] = t('Nama harus diisi');
    if (!$phone) $errors[] = t('No. WhatsApp harus diisi');

    if (empty($errors)) {
        $totalPrice = $attraction['price'] * $quantity;
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
        $ins = db()->prepare("INSERT INTO attraction_bookings (attraction_id, user_id, name, email, phone, visit_date, quantity, total_price, booking_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $ins->execute([$attraction['id'], $_SESSION['user_id'] ?? null, $name, $email, $phone, $visitDate, $quantity, $totalPrice, $bookingCode]);
        $bookingId = (int)db()->lastInsertId();
        if ($walletDeduct > 0 && !empty($_SESSION['user_id'])) {
            require_once 'includes/wallet.php';
            spendWallet($_SESSION['user_id'], $walletDeduct, 'attraction_booking', $bookingId);
        }
        header("Location: booking-success.php?code=$bookingCode");
        exit;
    } else {
        $bookingError = implode('<br>', $errors);
    }
}

// Similar attractions (same city)
$similar = db()->prepare("SELECT * FROM attractions WHERE city = ? AND id != ? AND is_active = 1 LIMIT 3");
$similar->execute([$attraction['city'], $attraction['id']]);
$similar = $similar->fetchAll();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<div class="container py-4">
    <?php renderBreadcrumb([
        ['label' => t('Tiket Tempat Wisata'), 'url' => 'attractions.php'],
        ['label' => $attraction['name'], 'url' => null],
    ]); ?>

    <div class="row">
        <div class="col-lg-8">
            <img src="<?= e($attraction['cover_image'] ?: 'https://placehold.co/800x450?text=' . urlencode($attraction['name'])) ?>" class="w-100 rounded-4 shadow-sm mb-3" style="max-height: 400px; object-fit: cover;" alt="<?= e($attraction['name']) ?>">

            <h2 class="fw-bold"><?= e($attraction['name']) ?></h2>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <span class="badge bg-primary"><?= e($attraction['city']) ?></span>
                <?php if ($attraction['category']): ?><span class="badge bg-info"><?= e($attraction['category']) ?></span><?php endif; ?>
                <?php if ($attraction['duration']): ?><span class="text-muted"><i class="bi bi-clock me-1"></i><?= e($attraction['duration']) ?></span><?php endif; ?>
                <?php if (!empty($attraction['instant_confirmation'])): ?><span class="badge bg-success"><?= t('Konfirmasi Instan') ?></span><?php endif; ?>
                <?php if (!empty($attraction['free_cancellation'])): ?><span class="badge bg-info"><?= t('Batal Gratis') ?></span><?php endif; ?>
            </div>
            <p class="lead"><?= nl2br(e($attraction['description'])) ?></p>

            <?php if (count($similar) > 0): ?>
            <h5 class="fw-bold mt-5 mb-3"><?= t('Tiket Lain di') ?> <?= e($attraction['city']) ?></h5>
            <div class="row g-3">
                <?php foreach ($similar as $s): ?>
                <div class="col-md-4">
                    <a href="attraction-detail.php?slug=<?= e($s['slug']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100">
                            <img src="<?= e($s['cover_image'] ?: 'https://placehold.co/400x200?text=' . urlencode($s['name'])) ?>" class="card-img-top" style="height: 140px; object-fit: cover;" alt="">
                            <div class="card-body p-2">
                                <h6 class="fw-semibold small mb-0 text-dark"><?= e($s['name']) ?></h6>
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
                    <h4 class="fw-bold text-primary mb-0"><?= formatCurrencySpan($attraction['price'], $attraction['price_currency'] ?? 'IDR') ?></h4>
                    <p class="text-muted">/ <?= t('orang') ?></p>

                    <?php if ($bookingError): ?>
                        <div class="alert alert-danger py-2 small"><?= $bookingError ?></div>
                    <?php endif; ?>
                    <?php if ($bookingSuccess): ?>
                        <div class="alert alert-success py-2 small"><?= $bookingSuccess ?></div>
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
                            <label class="form-label small"><?= t('Tanggal Kunjungan') ?></label>
                            <input type="date" name="visit_date" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Jumlah Tiket') ?></label>
                            <select name="quantity" class="form-select form-select-sm">
                                <?php for ($q = 1; $q <= 10; $q++): ?>
                                <option value="<?= $q ?>"><?= $q ?> <?= t('tiket') ?></option>
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
                                <input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWalletAttraction">
                                <label class="form-check-label small" for="useWalletAttraction"><?= t('Gunakan KlookCash') ?> <strong><?= formatRupiah($walletBal) ?></strong></label>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="small text-muted text-center mt-2"><i class="bi bi-lightning-charge-fill text-success"></i> <?= t('Konfirmasi instan via WhatsApp') ?></div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer-klook.php'; ?>
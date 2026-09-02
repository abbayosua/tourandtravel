<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM connectivity_products WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = t('Produk Tidak Ditemukan');
    require_once 'includes/header-klook.php';
    echo '<div class="container py-5 text-center"><h3>' . t('Produk tidak ditemukan') . '</h3><a href="esim.php" class="btn btn-primary mt-3">' . t('Kembali ke Katalog') . '</a></div>';
    require_once 'includes/footer-klook.php';
    exit;
}

$pageTitle = $product['name'];

$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);

    $errors = [];
    if (!$name) $errors[] = t('Nama harus diisi');
    if (!$phone) $errors[] = t('No. WhatsApp harus diisi');
    if ($quantity < 1) $errors[] = t('Jumlah minimal 1');

    if (empty($errors)) {
        $totalPrice = $product['price'] * $quantity;
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
        $ins = db()->prepare("INSERT INTO connectivity_bookings (product_id, user_id, name, email, phone, quantity, total_price, booking_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $ins->execute([$product['id'], $_SESSION['user_id'] ?? null, $name, $email, $phone, $quantity, $totalPrice, $bookingCode]);
        $bookingId = (int)db()->lastInsertId();
        if ($walletDeduct > 0 && !empty($_SESSION['user_id'])) {
            require_once 'includes/wallet.php';
            spendWallet($_SESSION['user_id'], $walletDeduct, 'esim_booking', $bookingId);
        }
        header("Location: booking-success.php?code=$bookingCode");
        exit;
    } else {
        $bookingError = implode('<br>', $errors);
    }
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<div class="container py-4">
    <?php renderBreadcrumb([
        ['label' => t('eSIM & Connectivity'), 'url' => 'esim.php'],
        ['label' => $product['name'], 'url' => null],
    ]); ?>

    <div class="row">
        <div class="col-lg-8">
            <img src="https://placehold.co/800x450?text=<?= urlencode($product['type'])?>" class="w-100 rounded-4 shadow-sm mb-3" style="max-height: 350px; object-fit: cover;" alt="">

            <h2 class="fw-bold"><?= e($product['name']) ?></h2>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <span class="badge bg-primary"><?= strtoupper(e($product['type'])) ?></span>
                <span class="text-muted"><i class="bi bi-globe me-1"></i><?= e($product['country']) ?></span>
                <span class="text-muted"><i class="bi bi-wifi me-1"></i><?= e($product['data_quota']) ?></span>
                <span class="text-muted"><i class="bi bi-clock me-1"></i><?= $product['duration_days'] ?> <?= t('hari') ?></span>
            </div>
            <p class="lead"><?= nl2br(e($product['description'] ?? '')) ?></p>

            <h6 class="fw-semibold"><?= t('Detail Produk') ?></h6>
            <div class="row g-2 mb-4">
                <div class="col-6"><i class="bi bi-check-circle text-success me-1"></i><small><?= t('Aktivasi instan setelah pembayaran') ?></small></div>
                <div class="col-6"><i class="bi bi-check-circle text-success me-1"></i><small><?= t('Cakupan') ?>: <?= e($product['coverage'] ?? $product['country']) ?></small></div>
                <div class="col-6"><i class="bi bi-check-circle text-success me-1"></i><small><?= t('Kuota') ?>: <?= e($product['data_quota']) ?></small></div>
                <div class="col-6"><i class="bi bi-check-circle text-success me-1"></i><small><?= t('Masa aktif') ?>: <?= $product['duration_days'] ?> <?= t('hari') ?></small></div>
            </div>
        </div>

        <!-- Sidebar Booking -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                <div class="card-body p-4">
                    <h4 class="fw-bold text-primary mb-0"><?= formatCurrencySpan($product['price'], $product['price_currency'] ?? 'IDR') ?></h4>

                    <?php if ($bookingError): ?>
                        <div class="alert alert-danger py-2 small"><?= $bookingError ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="form_submitted" value="1">
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Jumlah') ?></label>
                            <select name="quantity" class="form-select form-select-sm">
                                <?php for ($q = 1; $q <= 10; $q++): ?>
                                <option value="<?= $q ?>"><?= $q ?></option>
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
                        <button type="submit" class="btn btn-primary w-100 fw-semibold"><?= t('Beli Sekarang') ?></button>
                        <?php if (!empty($_SESSION['user_id'])): require_once 'includes/wallet.php'; $walletBal = getWalletBalance($_SESSION['user_id']); ?>
                            <?php if ($walletBal > 0): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWalletEsim">
                                <label class="form-check-label small" for="useWalletEsim"><?= t('Gunakan KlookCash') ?> <strong><?= formatRupiah($walletBal) ?></strong></label>
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
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/easybook.php';

// Validate required trip params
$company      = trim($_GET['company'] ?? '');
$routeFrom    = trim($_GET['from'] ?? '');
$routeTo      = trim($_GET['to'] ?? '');
$departDate   = trim($_GET['date'] ?? '');
$departTime   = trim($_GET['time'] ?? '');
$pricePerPax  = (float)($_GET['price'] ?? 0);
$passengers   = max(1, (int)($_GET['passengers'] ?? 1));
$vesselName   = trim($_GET['vessel'] ?? '');
$fromTerminal = trim($_GET['from_terminal'] ?? '');
$toTerminal   = trim($_GET['to_terminal'] ?? '');
$arrivalTime  = trim($_GET['arrival_time'] ?? '');

if (!$company || !$routeFrom || !$routeTo || !$departDate || !$departTime || $pricePerPax <= 0) {
    header('Location: ferries.php');
    exit;
}

$totalPrice = $pricePerPax * $passengers;
$pageTitle  = t('Pesan Ferry') . ' — ' . e($company);
$errors     = [];
$success    = false;

// Auto-fill from session
$defaultName  = $_SESSION['user_name'] ?? '';
$defaultEmail = $_SESSION['user_email'] ?? '';
$defaultPhone = $_SESSION['user_phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passengers = max(1, min(9, (int)($_POST['passengers'] ?? $passengers)));
    $totalPrice = $pricePerPax * $passengers;

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (strlen($name) < 2) $errors[] = t('Nama lengkap wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('Email tidak valid.');
    if (strlen($phone) < 8) $errors[] = t('Nomor telepon tidak valid.');

    // Passenger names
    $paxData = [];
    for ($i = 1; $i <= $passengers; $i++) {
        $paxName = ($passengers === 1) ? $name : trim($_POST["pax_name_$i"] ?? '');
        if (strlen($paxName) < 2) {
            $errors[] = t('Nama penumpang') . " #$i " . t('wajib diisi.');
        }
        $paxData[] = ['name' => $paxName, 'seat' => $i];
    }

    if (empty($errors)) {
        $bookingCode = 'FB' . strtoupper(bin2hex(random_bytes(5)));
        $userId = $_SESSION['user_id'] ?? null;

        $stmt = db()->prepare("INSERT INTO ferry_bookings 
            (booking_code, user_id, company, vessel_name, route_from, route_to, 
             departure_date, departure_time, arrival_time, passengers, price_per_pax, total_price,
             passenger_data, name, email, phone, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $bookingCode, $userId, $company, $vesselName, $fromTerminal ?: $routeFrom, $toTerminal ?: $routeTo,
            $departDate, $departTime, $arrivalTime, $passengers, $pricePerPax, $totalPrice,
            json_encode($paxData), $name, $email, $phone, 'pending'
        ]);

        $success = true;
        $pageTitle = t('Booking Berhasil');
    } else {
        $defaultName  = $name;
        $defaultEmail = $email;
        $defaultPhone = $phone;
    }
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>

<section class="py-4 bg-light" style="min-height:80vh;">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('Ferry'), 'url' => 'ferries.php'],
            ['label' => t('Pesan'),  'url' => null]
        ]); ?>

        <?php if ($success): ?>
        <!-- Booking Success -->
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5 px-4">
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size:64px;"></i>
                        </div>
                        <h3 class="fw-bold mb-2"><?= t('Booking Berhasil!') ?></h3>
                        <p class="text-muted mb-4"><?= t('Kode booking Anda:') ?></p>
                        <div class="bg-light rounded-pill px-4 py-2 d-inline-block mb-4">
                            <span class="fs-4 fw-bold text-primary" data-testid="booking-code"><?= e($bookingCode) ?></span>
                        </div>

                        <div class="text-start bg-light rounded-3 p-4 mb-4">
                            <div class="row mb-3">
                                <div class="col-5 text-muted"><?= t('Rute') ?></div>
                                <div class="col-7 fw-semibold"><?= e($fromTerminal ?: $routeFrom) ?> → <?= e($toTerminal ?: $routeTo) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 text-muted"><?= t('Tanggal') ?></div>
                                <div class="col-7 fw-semibold"><?= e($departDate) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 text-muted"><?= t('Waktu') ?></div>
                                <div class="col-7 fw-semibold"><?= e($departTime) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 text-muted"><?= t('Perusahaan') ?></div>
                                <div class="col-7 fw-semibold"><?= e($company) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 text-muted"><?= t('Penumpang') ?></div>
                                <div class="col-7 fw-semibold"><?= $passengers ?> <?= t('orang') ?></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-5 text-muted"><?= t('Total Bayar') ?></div>
                                <div class="col-7 fw-bold text-primary fs-5"><?= formatRupiah($totalPrice) ?></div>
                            </div>
                        </div>

                        <div class="alert alert-info small mb-3" style="border-left:3px solid var(--primary);">
                            <i class="bi bi-info-circle me-1"></i>
                            <?= t('Simpan kode booking Anda. Petugas akan meminta kode ini saat check-in di pelabuhan.') ?>
                        </div>

                        <div class="d-flex gap-2 justify-content-center">
                            <a href="my-bookings.php" class="btn btn-primary rounded-pill px-4" data-testid="btn-my-bookings">
                                <i class="bi bi-ticket-perforated me-1"></i><?= t('Lihat Booking') ?>
                            </a>
                            <a href="ferries.php" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="bi bi-arrow-left me-1"></i><?= t('Cari Ferry Lagi') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Booking Form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-3"><?= t('Pesan Ferry') ?></h4>

                <!-- Trip Summary Card -->
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <?php $logo = easybookCompanyLogo($company); ?>
                            <?php if ($logo): ?>
                            <img src="<?= e($logo) ?>" alt="<?= e($company) ?>" style="height:36px;">
                            <?php else: ?>
                            <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                <i class="bi bi-ship text-primary fs-5"></i>
                            </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-5 mb-1"><?= e($company) ?> <?= $vesselName ? '— ' . e($vesselName) : '' ?></div>
                                <div class="text-muted small mb-2">
                                    <?= e($fromTerminal ?: $routeFrom) ?> → <?= e($toTerminal ?: $routeTo) ?>
                                </div>
                                <div class="d-flex gap-3 flex-wrap">
                                    <span class="badge bg-light text-dark"><i class="bi bi-calendar3 me-1"></i><?= e($departDate) ?></span>
                                    <span class="badge bg-light text-dark"><i class="bi bi-clock me-1"></i><?= e($departTime) ?></span>
                                    <?php if ($arrivalTime): ?>
                                    <span class="badge bg-light text-dark"><i class="bi bi-clock-history me-1"></i><?= t('Tiba') ?>: <?= e($arrivalTime) ?></span>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-dark"><i class="bi bi-people me-1"></i><?= $passengers ?> <?= t('orang') ?></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small"><?= t('Harga/pax') ?></div>
                                <div class="fw-bold text-primary"><?= formatRupiah($pricePerPax) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" data-testid="booking-errors">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" id="ferryBookingForm">
                    <!-- Passenger Info -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-person-lines-fill me-2"></i><?= t('Data Penumpang Utama') ?>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="name"><?= t('Nama Lengkap') ?> <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?= e($defaultName) ?>" required data-testid="input-name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email"><?= t('Email') ?> <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?= e($defaultEmail) ?>" required data-testid="input-email">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="phone"><?= t('Telepon') ?> <span class="text-danger">*</span></label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?= e($defaultPhone) ?>" placeholder="+62..." required data-testid="input-phone">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="passengers"><?= t('Jumlah Penumpang') ?> <span class="text-danger">*</span></label>
                                    <select id="passengers" name="passengers" class="form-select" data-testid="select-passengers" data-price-per-pax="<?= (float)$pricePerPax ?>">
                                        <?php for ($p = 1; $p <= 9; $p++): ?>
                                        <option value="<?= $p ?>" <?= $p === (int)$passengers ? 'selected' : '' ?>><?= $p ?> <?= t('orang') ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Per-passenger names (all 9 slots, JS toggles visibility) -->
                    <div class="card border-0 shadow-sm mb-4" id="paxNamesCard">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-people me-2"></i><?= t('Nama Penumpang') ?>
                        </div>
                        <div class="card-body">
                            <div class="row g-3" id="paxFields">
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                <div class="col-md-6 pax-field <?= $i > (int)$passengers ? 'd-none' : '' ?>" data-pax="<?= $i ?>">
                                    <label class="form-label fw-semibold" for="pax_name_<?= $i ?>">
                                        <?= t('Penumpang') ?> #<?= $i ?> <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="pax_name_<?= $i ?>" name="pax_name_<?= $i ?>"
                                           class="form-control pax-input <?= $i > (int)$passengers ? 'd-none' : '' ?>"
                                           <?= $i <= (int)$passengers ? 'required' : '' ?> data-testid="input-pax-<?= $i ?>">
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted"><?= t('Harga per penumpang') ?></span>
                                <span><?= formatRupiah($pricePerPax) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted"><?= t('Jumlah penumpang') ?></span>
                                <span>× <span id="paxCountDisplay"><?= $passengers ?></span></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold fs-5"><?= t('Total') ?></span>
                                <span class="fw-bold text-primary fs-5" data-testid="total-price" id="totalPriceDisplay"><?= formatRupiah($totalPrice) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <?php if (isLoggedIn() && isReseller((int)($_SESSION['user_id'] ?? 0))): ?>
                    <div class="alert alert-info py-2 small mb-3 d-flex justify-content-between align-items-center" data-testid="reseller-balance-ferry">
                        <span><i class="bi bi-wallet2 me-1"></i><?= t('Saldo Reseller') ?>: <strong><?= formatRupiah(getResellerBalance((int)$_SESSION['user_id'])) ?></strong></span>
                        <a href="reseller-topup.php" class="btn btn-sm btn-outline-info"><?= t('Topup') ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 flex-grow-1" data-testid="btn-confirm-booking">
                            <i class="bi bi-check2-circle me-2"></i><?= t('Konfirmasi Pesanan') ?>
                        </button>
                        <a href="ferries.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </form>

                <div class="alert alert-info small mb-4" style="border-left:3px solid var(--primary);">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= t('Dengan memesan, Anda menyetujui syarat & ketentuan pemesanan ferry kami.') ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer-klook.php'; ?>
<script>
document.getElementById('passengers').addEventListener('change', function() {
    var count = parseInt(this.value);
    var pricePerPax = parseFloat(this.dataset.pricePerPax);
    var total = pricePerPax * count;

    // Update price display
    document.getElementById('paxCountDisplay').textContent = count;
    document.getElementById('totalPriceDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');

    // Show/hide pax name fields
    document.querySelectorAll('.pax-field').forEach(function(el) {
        var n = parseInt(el.dataset.pax);
        var input = el.querySelector('.pax-input');
        if (n <= count) {
            el.classList.remove('d-none');
            input.classList.remove('d-none');
            input.required = true;
        } else {
            el.classList.add('d-none');
            input.classList.add('d-none');
            input.required = false;
            input.value = '';
        }
    });
});
</script>

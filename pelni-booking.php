<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/pelni.php';
require_once 'includes/tripay.php';

$shipName     = trim($_GET['ship_name'] ?? '');
$shipCode     = trim($_GET['ship_code'] ?? '');
$routeFrom    = trim($_GET['from'] ?? '');
$routeTo      = trim($_GET['to'] ?? '');
$departDate   = trim($_GET['date'] ?? '');
$departTime   = trim($_GET['time'] ?? '');
$pricePerPax  = (float)($_GET['price'] ?? 0);
$passengers   = max(1, (int)($_GET['passengers'] ?? 1));
$shipClass    = trim($_GET['ship_class'] ?? '');
$shipNumber   = trim($_GET['ship_number'] ?? '');
$arrivalTime  = trim($_GET['arrival_time'] ?? '');
$fromCode     = trim($_GET['from_code'] ?? '');
$toCode       = trim($_GET['to_code'] ?? '');

if (!$shipName || !$routeFrom || !$routeTo || !$departDate || $pricePerPax <= 0) {
    header('Location: pelni.php');
    exit;
}

// Fallback kode pelabuhan bila link lama tidak menyertakannya (dipakai untuk booking ke penyedia).
if ($fromCode === '') {
    $ports = pelniSearchPort($routeFrom);
    if (!empty($ports)) $fromCode = $ports[0]['label_code'] ?? '';
}
if ($toCode === '') {
    $ports = pelniSearchPort($routeTo);
    if (!empty($ports)) $toCode = $ports[0]['label_code'] ?? '';
}

$totalPrice = $pricePerPax * $passengers;
$pageTitle  = t('Pesan Kapal PELNI') . ' — ' . e($shipName);
$errors     = [];
$success    = false;
$supplierInvoice = $supplierVa = $supplierBank = $supplierDeadline = $supplierStatus = null;
$supplierTotal = null;
$supplierMethod = null;
$payMethodOptions = [
    'VA:BSI'           => t('Virtual Account') . ' — BSI',
    'VA:Permata'       => t('Virtual Account') . ' — Permata',
    'VA:Muamalat'      => t('Virtual Account') . ' — Muamalat',
    'TRANSFER:Mandiri' => t('Transfer Bank') . ' — Mandiri',
    'TRANSFER:BCA'     => t('Transfer Bank') . ' — BCA',
    'TRANSFER:BRI'     => t('Transfer Bank') . ' — BRI',
    'TRANSFER:BNI'     => t('Transfer Bank') . ' — BNI',
];
$defaultPayMethod = 'VA:Permata';

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

    $paxData = [];
    $paxForSupplier = [];
    for ($i = 1; $i <= $passengers; $i++) {
        $paxName  = trim($_POST["pax_name_$i"] ?? '');
        if ($passengers === 1 && $paxName === '') $paxName = $name;
        $paxTitle = trim($_POST["pax_title_$i"] ?? 'Mr');
        if (!in_array($paxTitle, ['Mr', 'Mrs', 'Ms'], true)) $paxTitle = 'Mr';
        $paxDob   = trim($_POST["pax_dob_$i"] ?? '');
        $paxId    = trim($_POST["pax_id_$i"] ?? '');

        if (strlen($paxName) < 2) $errors[] = t('Nama penumpang') . " #$i " . t('wajib diisi.');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paxDob)) $errors[] = t('Tanggal lahir penumpang') . " #$i " . t('tidak valid.');
        if (strlen($paxId) < 6) $errors[] = t('Nomor identitas penumpang') . " #$i " . t('wajib diisi.');

        $paxData[] = ['title' => $paxTitle, 'name' => $paxName, 'dob' => $paxDob, 'id' => $paxId, 'seat' => $i];
        $paxForSupplier[] = [
            'title' => $paxTitle,
            'name'  => $paxName,
            'dob'   => date('d-m-Y', strtotime($paxDob)), // penyedia: dd-mm-yyyy
            'id'    => $paxId,
        ];
    }

    if (empty($errors)) {
        $bookingCode = 'PB' . strtoupper(bin2hex(random_bytes(5)));
        $userId = $_SESSION['user_id'] ?? null;

        $stmt = db()->prepare("INSERT INTO pelni_bookings 
            (booking_code, user_id, ship_name, ship_number, ship_class, ship_code, route_from, route_to, 
             departure_date, departure_time, arrival_time, passengers, price_per_pax, total_price,
             passenger_data, name, email, phone, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $bookingCode, $userId, $shipName, $shipNumber, $shipClass, $shipCode, $routeFrom, $routeTo,
            $departDate, $departTime, $arrivalTime, $passengers, $pricePerPax, $totalPrice,
            json_encode($paxData), $name, $email, $phone, 'pending'
        ]);

        $pelniBookingId = (int)db()->lastInsertId();

        // Booking langsung ke penyedia (klikmbc.biz) — pembeli bayar via VA/transfer dari penyedia.
        $payMethod = trim($_POST['pay_method'] ?? $defaultPayMethod);
        if (!isset($payMethodOptions[$payMethod])) $payMethod = $defaultPayMethod;
        $supplier = pelniSupplierBook($routeFrom, $routeTo, $fromCode, $toCode, $departDate, $shipCode, $paxForSupplier, $phone, $email, $passengers, $payMethod);
        if (!empty($supplier['ok'])) {
            $supplierTotal = null;
            if (!empty($supplier['total'])) {
                $digits = preg_replace('/[^0-9]/', '', (string)$supplier['total']);
                $supplierTotal = $digits !== '' ? (float)$digits : null;
            }
            db()->prepare("UPDATE pelni_bookings SET supplier_invoice=?, supplier_va=?, supplier_bank=?, supplier_method=?, supplier_total=?, supplier_deadline=?, supplier_status=? WHERE id=?")
                ->execute([
                    $supplier['invoice'] ?? null,
                    $supplier['va'] ?? null,
                    $supplier['bank'] ?? null,
                    $supplier['method'] ?? null,
                    $supplierTotal,
                    $supplier['deadline'] ?? null,
                    $supplier['status'] ?? null,
                    $pelniBookingId,
                ]);
        } else {
            $errText = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string)($supplier['error'] ?? 'unknown')))), 0, 240);
            db()->prepare("UPDATE pelni_bookings SET supplier_status=? WHERE id=?")
                ->execute([t('Gagal ke penyedia') . ': ' . $errText, $pelniBookingId]);
        }

        // PRG: redirect ke GET agar refresh tidak membuat booking ganda.
        $redirectQuery = $_GET;
        $redirectQuery['booking'] = $bookingCode;
        header('Location: pelni-booking.php?' . http_build_query($redirectQuery));
        exit;
    } else {
        $defaultName  = $name;
        $defaultEmail = $email;
        $defaultPhone = $phone;
    }
}

// PRG: render halaman sukses dari GET (refresh tidak re-submit/membuat booking ganda).
if (!$success && $_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_GET['booking'])) {
    $bkStmt = db()->prepare("SELECT * FROM pelni_bookings WHERE booking_code = ?");
    $bkStmt->execute([trim((string)$_GET['booking'])]);
    if ($bk = $bkStmt->fetch()) {
        $success        = true;
        $bookingCode    = $bk['booking_code'];
        $pelniBookingId = (int)$bk['id'];
        $shipName       = $bk['ship_name'];
        $shipNumber     = $bk['ship_number'];
        $shipClass      = $bk['ship_class'];
        $shipCode       = $bk['ship_code'];
        $routeFrom      = $bk['route_from'];
        $routeTo        = $bk['route_to'];
        $departDate     = $bk['departure_date'];
        $departTime     = $bk['departure_time'];
        $arrivalTime    = $bk['arrival_time'];
        $passengers     = (int)$bk['passengers'];
        $pricePerPax    = (float)$bk['price_per_pax'];
        $totalPrice     = (float)$bk['total_price'];
        $supplierInvoice  = $bk['supplier_invoice'] ?? null;
        $supplierVa       = $bk['supplier_va'] ?? null;
        $supplierBank     = $bk['supplier_bank'] ?? null;
        $supplierMethod   = $bk['supplier_method'] ?? null;
        $supplierTotal    = $bk['supplier_total'] ?? null;
        $supplierDeadline = $bk['supplier_deadline'] ?? null;
        $supplierStatus   = $bk['supplier_status'] ?? null;
        $pageTitle      = t('Booking Berhasil');
    }
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-shared.php';
?>

<section class="py-4 bg-light" style="min-height:80vh;">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('PELNI'), 'url' => 'pelni.php'],
            ['label' => t('Pesan'),  'url' => null]
        ]); ?>

        <?php if ($success): ?>
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
                                <div class="col-7 fw-semibold"><?= e($routeFrom) ?> → <?= e($routeTo) ?></div>
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
                                <div class="col-5 text-muted"><?= t('Kapal') ?></div>
                                <div class="col-7 fw-semibold"><?= e($shipName) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 text-muted"><?= t('Kelas') ?></div>
                                <div class="col-7 fw-semibold"><?= e($shipClass) ?></div>
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

                        <?php
                        $pelniGatewayEnabled = false; // PELNI: payment gateway dinonaktifkan sementara → bayar langsung ke penyedia (klikmbc)
                        $pelniPayment = null;
                        if ($success) {
                            $pp = db()->prepare("SELECT * FROM payments WHERE booking_type='pelni' AND booking_id=? ORDER BY id DESC LIMIT 1");
                            $pp->execute([$pelniBookingId]);
                            $pelniPayment = $pp->fetch();
                        }
                        ?>
                        <?php if ($pelniGatewayEnabled && tripayInstantEnabled()): ?>
                        <div class="w-100 mb-3" data-testid="pelni-payment">
                            <?php if (tripayGateway() === 'tripay'): ?>
                            <label class="form-label small fw-semibold" for="pelniTripayMethod"><?= t('Pilih channel pembayaran') ?></label>
                            <select id="pelniTripayMethod" class="form-select form-select-sm mx-auto mb-2" style="max-width:320px">
                                <?php foreach (['BRIVA'=>'BRI VA','BCAVA'=>'BCA VA','BNIVA'=>'BNI VA','MANDIRIVA'=>'Mandiri VA','PERMATAVA'=>'Permata VA','QRIS'=>'QRIS','ALFAMART'=>'Alfamart','INDOMARET'=>'Indomaret'] as $tcode => $tname): ?>
                                <option value="<?= $tcode ?>"><?= e($tname) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                            <button type="button" id="pelniPayNowBtn" class="btn btn-success px-4" data-booking-id="<?= $pelniBookingId ?>" data-gateway="<?= e(tripayGateway()) ?>">
                                <i class="bi bi-credit-card me-1"></i><?= t('Bayar Sekarang') ?>
                            </button>
                            <form method="POST" action="ajax/create-payment.php" id="pelniCreatePaymentForm" style="display:none;"></form>
                            <div id="pelniPaymentStatusArea" class="small mt-2" data-order-id="<?= e($pelniPayment['order_id'] ?? '') ?>">
                                <?php if ($pelniPayment): ?><span class="text-muted"><?= t('Menunggu pembayaran...') ?></span><?php endif; ?>
                            </div>
                        </div>
                        <?php elseif ($pelniGatewayEnabled && !empty($pelniPayment['pay_code']) && ($pelniPayment['gateway'] ?? '') === 'tripay'): ?>
                        <div class="alert alert-info text-start mb-3" data-testid="pelni-paycode">
                            <div class="small text-muted"><?= t('Kode bayar Tripay') ?></div>
                            <div class="fs-4 fw-bold"><?= e($pelniPayment['pay_code']) ?></div>
                            <?php if (!empty($pelniPayment['checkout_url'])): ?>
                            <a href="<?= e($pelniPayment['checkout_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-2"><?= t('Buka halaman checkout') ?></a>
                            <?php endif; ?>
                        </div>
                        <?php elseif (!empty($supplierInvoice)): ?>
                        <div class="alert alert-warning text-start mb-3" data-testid="pelni-supplier-payment">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold"><i class="bi bi-cash-coin me-1"></i><?= t('Pembayaran ke penyedia PELNI') ?></span>
                                <?php if ($supplierStatus): ?><span class="badge bg-secondary"><?= e($supplierStatus) ?></span><?php endif; ?>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="small text-muted"><?= t('Kode Invoice') ?></div>
                                    <div class="fw-bold" data-testid="supplier-invoice"><?= e($supplierInvoice) ?></div>
                                </div>
                                <?php if ($supplierBank): ?>
                                <div class="col-6">
                                    <div class="small text-muted"><?= t('Bank') ?></div>
                                    <div class="fw-semibold"><?= e($supplierBank) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($supplierVa): ?>
                                <div class="col-12">
                                    <div class="small text-muted"><?= $supplierMethod === 'TRANSFER' ? t('Nomor Rekening Tujuan') : t('Nomor Virtual Account') ?></div>
                                    <div class="fs-4 fw-bold text-primary" data-testid="supplier-va"><?= e($supplierVa) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($supplierTotal)): ?>
                                <div class="col-6">
                                    <div class="small text-muted"><?= t('Total Bayar') ?></div>
                                    <div class="fw-bold"><?= formatRupiah((float)$supplierTotal) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($supplierDeadline): ?>
                                <div class="col-6">
                                    <div class="small text-muted"><?= t('Batas Bayar') ?></div>
                                    <div class="fw-semibold"><?= e($supplierDeadline) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted mt-3">
                                <i class="bi bi-info-circle me-1"></i><?= t('Bayar sesuai nomor Virtual Account di atas sebelum batas waktu. E-ticket PELNI diterbitkan otomatis setelah pembayaran diterima penyedia.') ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info text-start mb-3" data-testid="pelni-no-supplier">
                            <i class="bi bi-exclamation-triangle me-1"></i><?= t('Booking Anda tercatat. Konfirmasi tiket sedang diproses oleh tim kami.') ?>
                            <?php if ($supplierStatus): ?><div class="small text-muted mt-1"><?= e($supplierStatus) ?></div><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2 justify-content-center">
                            <a href="my-bookings.php" class="btn btn-primary rounded-pill px-4" data-testid="btn-my-bookings">
                                <i class="bi bi-ticket-perforated me-1"></i><?= t('Lihat Booking') ?>
                            </a>
                            <a href="pelni.php" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="bi bi-arrow-left me-1"></i><?= t('Cari Kapal Lagi') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-3"><?= t('Pesan Kapal PELNI') ?></h4>

                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <?php $logo = pelniLogo(); ?>
                            <?php if ($logo): ?>
                            <img src="<?= e($logo) ?>" alt="PELNI" style="height:36px;">
                            <?php else: ?>
                            <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                <i class="bi bi-ship text-primary fs-5"></i>
                            </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-5 mb-1"><?= e($shipName) ?> <?= $shipClass ? '— ' . e($shipClass) : '' ?></div>
                                <div class="text-muted small mb-2">
                                    <?= e($routeFrom) ?> → <?= e($routeTo) ?>
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

                <form method="POST" id="pelniBookingForm" data-submit-once>
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

                    <div class="card border-0 shadow-sm mb-4" id="paxNamesCard">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-people me-2"></i><?= t('Nama Penumpang') ?>
                        </div>
                        <div class="card-body">
                            <div class="row g-3" id="paxFields">
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                <div class="col-12 pax-field <?= $i > (int)$passengers ? 'd-none' : '' ?>" data-pax="<?= $i ?>">
                                    <div class="border rounded-3 p-3">
                                        <div class="fw-semibold small mb-2"><?= t('Penumpang') ?> #<?= $i ?></div>
                                        <div class="row g-2">
                                            <div class="col-4 col-md-2">
                                                <label class="form-label small text-muted" for="pax_title_<?= $i ?>"><?= t('Gelar') ?></label>
                                                <select id="pax_title_<?= $i ?>" name="pax_title_<?= $i ?>" class="form-select form-select-sm pax-input <?= $i > (int)$passengers ? 'd-none' : '' ?>" data-testid="input-pax-title-<?= $i ?>">
                                                    <option value="Mr">Mr</option>
                                                    <option value="Mrs">Mrs</option>
                                                    <option value="Ms">Ms</option>
                                                </select>
                                            </div>
                                            <div class="col-8 col-md-4">
                                                <label class="form-label small text-muted" for="pax_name_<?= $i ?>"><?= t('Nama Lengkap') ?></label>
                                                <input type="text" id="pax_name_<?= $i ?>" name="pax_name_<?= $i ?>"
                                                       class="form-control form-control-sm pax-input <?= $i > (int)$passengers ? 'd-none' : '' ?>"
                                                       <?= $i <= (int)$passengers ? 'required' : '' ?> data-testid="input-pax-<?= $i ?>">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label small text-muted" for="pax_dob_<?= $i ?>"><?= t('Tanggal Lahir') ?></label>
                                                <input type="date" id="pax_dob_<?= $i ?>" name="pax_dob_<?= $i ?>"
                                                       class="form-control form-control-sm pax-input <?= $i > (int)$passengers ? 'd-none' : '' ?>"
                                                       <?= $i <= (int)$passengers ? 'required' : '' ?> data-testid="input-pax-dob-<?= $i ?>">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label small text-muted" for="pax_id_<?= $i ?>"><?= t('No. Identitas (NIK/Paspor)') ?></label>
                                                <input type="text" id="pax_id_<?= $i ?>" name="pax_id_<?= $i ?>"
                                                       class="form-control form-control-sm pax-input <?= $i > (int)$passengers ? 'd-none' : '' ?>"
                                                       <?= $i <= (int)$passengers ? 'required' : '' ?> data-testid="input-pax-id-<?= $i ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-credit-card me-2"></i><?= t('Metode Pembayaran') ?>
                        </div>
                        <div class="card-body">
                            <label class="form-label small text-muted" for="pay_method"><?= t('Pilih metode pembayaran ke penyedia') ?></label>
                            <select id="pay_method" name="pay_method" class="form-select" data-testid="select-pay-method">
                                <?php foreach ($payMethodOptions as $pmVal => $pmLabel): ?>
                                <option value="<?= e($pmVal) ?>" <?= $pmVal === $defaultPayMethod ? 'selected' : '' ?>><?= e($pmLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

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

                    <?php if (isLoggedIn() && isReseller((int)($_SESSION['user_id'] ?? 0))): ?>
                    <div class="alert alert-info py-2 small mb-3 d-flex justify-content-between align-items-center" data-testid="reseller-balance-pelni">
                        <span><i class="bi bi-wallet2 me-1"></i><?= t('Saldo Reseller') ?>: <strong><?= formatRupiah(getResellerBalance((int)$_SESSION['user_id'])) ?></strong></span>
                        <a href="reseller-topup.php" class="btn btn-sm btn-outline-info"><?= t('Topup') ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 flex-grow-1" data-testid="btn-confirm-booking">
                            <i class="bi bi-check2-circle me-2"></i><?= t('Konfirmasi Pesanan') ?>
                        </button>
                        <a href="pelni.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </form>

                <div class="alert alert-info small mb-4" style="border-left:3px solid var(--primary);">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= t('Dengan memesan, Anda menyetujui syarat & ketentuan pemesanan kapal PELNI kami.') ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-shared.php'; ?>
<script>
document.getElementById('passengers').addEventListener('change', function() {
    var count = parseInt(this.value);
    var pricePerPax = parseFloat(this.dataset.pricePerPax);
    var total = pricePerPax * count;

    document.getElementById('paxCountDisplay').textContent = count;
    document.getElementById('totalPriceDisplay').textContent = 'Rp ' + total.toLocaleString((window.I18N && window.I18N.locale) || 'id-ID');

    document.querySelectorAll('.pax-field').forEach(function(el) {
        var n = parseInt(el.dataset.pax);
        var show = n <= count;
        el.classList.toggle('d-none', !show);
        el.querySelectorAll('.pax-input').forEach(function(input) {
            if (show) {
                input.classList.remove('d-none');
                input.required = true;
            } else {
                input.classList.add('d-none');
                input.required = false;
                if (input.tagName !== 'SELECT') input.value = '';
            }
        });
    });
});
</script>
<script>
(function () {
    var btn = document.getElementById('pelniPayNowBtn');
    if (!btn) return;
    var statusArea = document.getElementById('pelniPaymentStatusArea');

    function pollStatus(orderId) {
        if (!orderId) return;
        var retries = 0;
        var timer = setInterval(function () {
            fetch('<?= BASE_URL ?>/ajax/payment-status.php?order_id=' + encodeURIComponent(orderId))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    retries = 0;
                    if (d.status === 'paid') {
                        clearInterval(timer);
                        if (statusArea) statusArea.innerHTML = '<span class="text-success fw-bold"><?= t('Pembayaran diterima. Terima kasih!') ?></span>';
                        btn.remove();
                    }
                }).catch(function () {
                    retries++;
                    if (retries >= 5) {
                        clearInterval(timer);
                        if (statusArea) statusArea.innerHTML = '<span class="text-warning small"><?= t('Gagal memuat hasil. Coba lagi.') ?></span>';
                    }
                });
        }, 3000);
    }

    btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><?= t('Memproses...') ?>';
        fetch('<?= BASE_URL ?>/ajax/create-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'booking_type=pelni&booking_id=' + btn.dataset.bookingId + (btn.dataset.gateway === 'tripay' ? '&method=' + encodeURIComponent((document.getElementById('pelniTripayMethod') || {}).value || 'BRIVA') : '')
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.ok && d.gateway === 'tripay') {
                window.location.reload();
            } else if (d.ok && d.redirect_url) {
                window.location.href = d.redirect_url;
            } else {
                btn.disabled = false;
                btn.innerHTML = '<?= t('Bayar Sekarang') ?>';
                if (statusArea) statusArea.innerHTML = '<span class="text-danger"><?= t('Gagal memulai pembayaran. Coba lagi.') ?></span>';
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<?= t('Bayar Sekarang') ?>';
        });
    });
})();
</script>

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/kereta.php';

// Flow booking ke penyedia butuh beberapa request berurutan (search→detail→book→VA→checkout).
@set_time_limit(180);

$pageTitle = t('Pesan Tiket KAI');

$token = trim($_GET['sel'] ?? ($_POST['sel'] ?? ''));
$sel = ($token !== '' && !empty($_SESSION['kereta_sel'][$token])) ? $_SESSION['kereta_sel'][$token] : null;

$doneCode = trim($_GET['done'] ?? '');
$booking = null;
if ($doneCode !== '') {
    $stmt = db()->prepare("SELECT * FROM train_bookings WHERE booking_code = ? AND provider = 'klikmbc' LIMIT 1");
    $stmt->execute([$doneCode]);
    $booking = $stmt->fetch() ?: null;
}

$errors = [];
$paxCount = 1;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_submitted'] ?? '') === '1' && !$booking) {
    $paxCount = max(1, min(6, (int)($_POST['pax_count'] ?? 1)));
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $pax = [];
    for ($i = 0; $i < $paxCount; $i++) {
        $title = trim($_POST['title'][$i] ?? 'Mr');
        $nm = trim($_POST['pname'][$i] ?? '');
        $idn = preg_replace('/\D/', '', (string)($_POST['pid'][$i] ?? ''));
        if ($nm === '') $errors[] = sprintf(t('Nama penumpang %d harus diisi'), $i + 1);
        if (strlen($idn) < 8) $errors[] = sprintf(t('Nomor identitas penumpang %d tidak valid'), $i + 1);
        $pax[] = [
            'title' => in_array($title, ['Mr', 'Mrs', 'Ms'], true) ? $title : 'Mr',
            'name' => $nm,
            'id' => $idn,
        ];
    }

    if (!$sel) $errors[] = t('Sesi pemesanan kedaluwarsa. Silakan cari jadwal ulang.');
    if ($phone === '') $errors[] = t('No. WhatsApp harus diisi');

    $t = null;
    $d = null;
    if (empty($errors)) {
        $s = keretaApiSession();
        $r = keretaApiSearch($s, $sel['from'], $sel['to'], $sel['date'], $paxCount);
        if (isset($r['error'])) {
            $errors[] = t('Gagal mencari jadwal. Silakan coba lagi.');
        } else {
            foreach ($r['schedules'] as $row) {
                if (($row['train_code'] ?? '') === $sel['code']
                    && ($row['train_class'] ?? '') === $sel['class']
                    && ($row['train_subclass'] ?? '') === $sel['subclass']
                    && ($row['train_datetime'] ?? '') === $sel['datetime']) {
                    $t = $row;
                    break;
                }
            }
            if (!$t) $errors[] = t('Jadwal tidak lagi tersedia. Silakan cari jadwal ulang.');
        }
    }

    if (empty($errors)) {
        $d = keretaApiDetail($s, $t);
        if (isset($d['error'])) $errors[] = t('Gagal memuat detail tiket. Silakan coba lagi.');
    }

    if (empty($errors)) {
        $b = keretaApiBook($s, $t, $d, $pax, $phone, $email, $notes);
        if (isset($b['error'])) {
            $errors[] = t('Pemesanan gagal: ') . $b['error'];
        } else {
            $inv = $b['invoice'];

            $vaBank = '';
            $vaNumber = '';
            $totalStr = '';
            $deadline = '';
            $payMethod = trim($_POST['pay_method'] ?? $defaultPayMethod);
            if (!isset($payMethodOptions[$payMethod])) $payMethod = $defaultPayMethod;
            $payRes = keretaApiChooseAndConfirm($s, $inv, $payMethod);
            if (!empty($payRes['ok'])) {
                $vaBank = $payRes['bank'];
                $vaNumber = $payRes['account'];
                $totalStr = $payRes['total'];
                $deadline = $payRes['deadline'];
            }

            $totalNum = (float)preg_replace('/\D/', '', $totalStr);
            if ($totalNum <= 0) {
                $totalNum = (float)str_replace('.', '', (string)($t['train_fare'] ?? $sel['fare'])) * $paxCount;
            }

            $ins = db()->prepare("INSERT INTO train_bookings
                (train_id, provider, user_id, name, email, phone, travel_date, seats, total_price, status, payment_status, booking_code,
                 provider_invoice, train_name, train_code, train_class, route_from, route_to, departure_datetime,
                 va_bank, va_number, payment_method, payment_total, payment_deadline, passenger_data)
                VALUES (NULL, 'klikmbc', ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([
                $_SESSION['user_id'] ?? null,
                $pax[0]['name'],
                $email,
                $phone,
                $sel['date'],
                $paxCount,
                $totalNum,
                $inv,
                $inv,
                $t['train_name'] ?? $sel['name'],
                $t['train_code'] ?? $sel['code'],
                $t['train_class'] ?? $sel['class'],
                $t['train_from'] ?? $sel['from'],
                $t['train_to'] ?? $sel['to'],
                $t['train_datetime'] ?? $sel['datetime'],
                $vaBank,
                $vaNumber,
                $payRes['method'] ?? null,
                $totalNum,
                $deadline,
                json_encode($pax, JSON_UNESCAPED_UNICODE),
            ]);

            unset($_SESSION['kereta_sel'][$token]);
            if (!empty($_SESSION['user_id'])) {
                require_once 'includes/notifications.php';
                $kbName = $t['train_name'] ?? $sel['name'] ?? 'Kereta';
                $kbDetail = trim(($t['train_from'] ?? $sel['from'] ?? '') . ' → ' . ($t['train_to'] ?? $sel['to'] ?? '')) . ' • ' . $sel['date'] . ' • ' . $paxCount . ' ' . t('pax');
                notifyBookingCreated((int)$_SESSION['user_id'], t('Kereta'), $kbName, $kbDetail, $inv, 'train-booking.php?done=' . urlencode($inv));
            }
            header('Location: train-booking.php?done=' . urlencode($inv));
            exit;
        }
    }
}

// Helper render
function keretaPaxList($json) {
    $rows = json_decode((string)$json, true);
    if (!is_array($rows)) return [];
    return $rows;
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-shared.php';
?>
<div class="container py-4">
    <?php renderBreadcrumb([
        ['label' => t('KAI'), 'url' => 'trains.php'],
        ['label' => t('Pesan Tiket'), 'url' => null],
    ]); ?>

    <?php if ($booking): ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="display-1 text-warning mb-3"><i class="bi bi-clock-fill"></i></div>
                        <h3 class="fw-bold mb-2"><?= t('Pesanan Diterima') ?></h3>
                        <p class="text-muted mb-4"><?= t('Selesaikan pembayaran ke Virtual Account di bawah. Tiket diproses otomatis oleh penyedia.') ?></p>

                        <?php if (!empty($booking['va_number'])): ?>
                        <div class="bg-primary text-white rounded-4 p-4 mb-3" data-testid="kereta-va">
                            <small class="text-white d-block opacity-75"><?= ($booking['payment_method'] ?? '') === 'TRANSFER' ? t('Nomor Rekening Tujuan') : t('Nomor Virtual Account') ?> · <?= e($booking['va_bank']) ?></small>
                            <div class="fs-2 fw-bold mb-2" id="vaNumber" style="letter-spacing:1px;"><?= e($booking['va_number']) ?></div>
                            <div class="d-flex justify-content-between align-items-center gap-2 small mb-3 flex-wrap">
                                <span><?= t('Total Bayar') ?>: <strong><?= formatCurrencySpan($booking['payment_total'] ?: $booking['total_price'], 'IDR') ?></strong></span>
                                <?php if (!empty($booking['payment_deadline'])): ?>
                                <span class="badge bg-warning text-dark"><?= t('Batas Waktu') ?>: <?= e($booking['payment_deadline']) ?></span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-light w-100 fw-semibold" id="copyVaBtn" data-va="<?= e($booking['va_number']) ?>"><i class="bi bi-clipboard me-1"></i><?= t('Salin Nomor VA') ?></button>
                        </div>
                        <div class="small text-muted mb-4"><?= t('Kode Booking') ?>: <span class="fw-semibold text-dark"><?= e($booking['booking_code']) ?></span> <span class="opacity-75">· <?= t('pakai untuk lacak / konfirmasi WA') ?></span></div>
                        <?php else: ?>
                        <div class="bg-light rounded-4 p-3 mb-3 small">
                            <?= t('Kode Booking') ?>: <strong><?= e($booking['booking_code']) ?></strong>
                        </div>
                        <div class="alert alert-warning small text-start">
                            <?= t('Kode booking sudah dibuat. Silakan hubungi kami via WhatsApp untuk mendapatkan nomor pembayaran.') ?>
                        </div>
                        <?php endif; ?>

                        <div class="text-start bg-light rounded-4 p-4 mb-4">
                            <h6 class="fw-semibold mb-3"><?= t('Detail Tiket') ?></h6>
                            <table class="table table-borderless mb-0 small">
                                <tr><td class="text-muted ps-0"><?= t('Kereta') ?></td><td class="fw-semibold text-end"><?= e($booking['train_name']) ?> (<?= e($booking['train_code']) ?>)</td></tr>
                                <tr><td class="text-muted ps-0"><?= t('Kelas') ?></td><td class="fw-semibold text-end"><?= e($booking['train_class']) ?></td></tr>
                                <tr><td class="text-muted ps-0"><?= t('Rute') ?></td><td class="fw-semibold text-end"><?= e($booking['route_from']) ?> → <?= e($booking['route_to']) ?></td></tr>
                                <tr><td class="text-muted ps-0"><?= t('Keberangkatan') ?></td><td class="fw-semibold text-end"><?= formatDate($booking['travel_date']) ?> <?= e($booking['departure_datetime']) ?></td></tr>
                                <tr><td class="text-muted ps-0"><?= t('Penumpang') ?></td><td class="fw-semibold text-end">
                                    <?php foreach (keretaPaxList($booking['passenger_data']) as $i => $p): ?>
                                        <div><?= $i + 1 ?>. <?= e($p['name']) ?></div>
                                    <?php endforeach; ?>
                                </td></tr>
                                <tr><td class="text-muted ps-0"><?= t('Total') ?></td><td class="fw-semibold text-primary text-end"><?= formatCurrencySpan($booking['payment_total'] ?: $booking['total_price'], 'IDR') ?></td></tr>
                            </table>
                        </div>

                        <?php $waNum = preg_replace('/[^0-9]/', '', (string)getSetting('company_wa', getSetting('contact_wa', ''))); ?>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <?php if ($waNum !== ''): ?>
                            <a href="https://wa.me/<?= e($waNum) ?>?text=<?= rawurlencode(t('Konfirmasi pembayaran tiket KAI ') . $booking['booking_code']) ?>" target="_blank" rel="noopener" class="btn btn-success"><i class="bi bi-whatsapp me-1"></i><?= t('Hubungi Kami') ?></a>
                            <?php endif; ?>
                            <a href="trains.php" class="btn btn-outline-primary"><?= t('Cari Tiket Lain') ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function () {
            var btn = document.getElementById('copyVaBtn');
            if (!btn) return;
            btn.addEventListener('click', function () {
                navigator.clipboard.writeText(btn.dataset.va || '').then(function () {
                    btn.innerHTML = '<i class="bi bi-check2 me-1"></i><?= t('Tersalin') ?>';
                    setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard me-1"></i><?= t('Salin Nomor VA') ?>'; }, 2000);
                });
            });
        })();
        </script>

    <?php elseif (!$sel): ?>
        <div class="text-center py-5">
            <i class="bi bi-train-front fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Sesi pemesanan tidak ditemukan. Silakan cari jadwal kereta terlebih dahulu.') ?></p>
            <a href="trains.php" class="btn btn-primary rounded-pill px-4"><?= t('Cari Tiket KAI') ?></a>
        </div>

    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><?= t('Data Penumpang') ?></h5>

                        <?php if ($errors): ?>
                        <div class="alert alert-danger py-2 small"><?= implode('<br>', array_map('e', $errors)) ?></div>
                        <?php endif; ?>

                        <form method="POST" data-submit-once>
                            <input type="hidden" name="form_submitted" value="1">
                            <input type="hidden" name="sel" value="<?= e($token) ?>">

                            <div class="mb-3" style="max-width:180px;">
                                <label class="form-label small"><?= t('Jumlah Penumpang') ?></label>
                                <select name="pax_count" id="paxCount" class="form-select form-select-sm">
                                    <?php for ($p = 1; $p <= 6; $p++): ?>
                                    <option value="<?= $p ?>" <?= $p === $paxCount ? 'selected' : '' ?>><?= $p ?> <?= t('orang') ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="pax-block border rounded-3 p-3 mb-3 <?= $i > 0 ? 'd-none' : '' ?>" data-index="<?= $i ?>">
                                <div class="fw-semibold small mb-2"><?= t('Penumpang') ?> <?= $i + 1 ?></div>
                                <div class="row g-2">
                                    <div class="col-4 col-md-3">
                                        <select name="title[<?= $i ?>]" class="form-select form-select-sm">
                                            <option value="Mr">Mr</option>
                                            <option value="Mrs">Mrs</option>
                                            <option value="Ms">Ms</option>
                                        </select>
                                    </div>
                                    <div class="col-8 col-md-9">
                                        <input type="text" name="pname[<?= $i ?>]" class="form-control form-control-sm" placeholder="<?= t('Nama sesuai identitas') ?>" value="<?= $i === 0 ? e(getUser()['name'] ?? '') : '' ?>">
                                    </div>
                                    <div class="col-12">
                                        <input type="text" inputmode="numeric" name="pid[<?= $i ?>]" class="form-control form-control-sm" placeholder="<?= t('Nomor identitas (KTP/Paspor)') ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>

                            <hr>
                            <div class="mb-2">
                                <label class="form-label small"><?= t('Email') ?></label>
                                <input type="email" name="email" class="form-control form-control-sm" value="<?= e(getUser()['email'] ?? '') ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small"><?= t('No. WhatsApp') ?></label>
                                <input type="text" name="phone" class="form-control form-control-sm" value="<?= e(getUser()['phone'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small"><?= t('Catatan (opsional)') ?></label>
                                <input type="text" name="notes" class="form-control form-control-sm">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small"><?= t('Metode Pembayaran') ?></label>
                                <select name="pay_method" class="form-select form-select-sm" data-testid="select-pay-method">
                                    <?php foreach ($payMethodOptions as $pmVal => $pmLabel): ?>
                                    <option value="<?= e($pmVal) ?>" <?= $pmVal === $defaultPayMethod ? 'selected' : '' ?>><?= e($pmLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-semibold"><?= t('Pesan Sekarang') ?></button>
                            <p class="small text-muted mt-2 mb-0"><?= t('Pembayaran dilakukan langsung ke Virtual Account penyedia tiket.') ?></p>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><?= t('Ringkasan') ?></h5>
                        <table class="table table-borderless small mb-0">
                            <tr><td class="text-muted ps-0"><?= t('Kereta') ?></td><td class="fw-semibold text-end"><?= e($sel['name']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Kode') ?></td><td class="fw-semibold text-end"><?= e($sel['code']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Kelas') ?></td><td class="fw-semibold text-end"><?= e($sel['class']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Rute') ?></td><td class="fw-semibold text-end"><?= e($sel['from']) ?> → <?= e($sel['to']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Tanggal') ?></td><td class="fw-semibold text-end"><?= formatDate($sel['date']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Jam') ?></td><td class="fw-semibold text-end"><?= e($sel['datetime']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Harga/orang') ?></td><td class="fw-semibold text-primary text-end"><?= formatRupiah((float)str_replace('.', '', (string)$sel['fare'])) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function () {
            var sel = document.getElementById('paxCount');
            if (!sel) return;
            function sync() {
                var n = parseInt(sel.value, 10) || 1;
                document.querySelectorAll('.pax-block').forEach(function (b) {
                    b.classList.toggle('d-none', parseInt(b.dataset.index, 10) >= n);
                });
            }
            sel.addEventListener('change', sync);
            sync();
        })();
        </script>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer-shared.php'; ?>

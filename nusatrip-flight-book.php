<?php
/**
 * nusatrip-flight-book.php — Booking tiket pesawat native NusaTrip (search → item → submit → result → VA).
 * Pembayaran diteruskan langsung (guest checkout, tanpa login) seperti hotel (lihat nusatrip-book.php).
 *
 * Alur:
 *   1. GET ?of=<key>  → offer dari session (flights.php) → transaction_flight_item
 *      → checkout_attributes → validate → form penumpang + pilih pembayaran
 *   2. POST action=submit → transaction_submit → redirect ?step=result
 *   3. GET ?step=result → transaction_result (poll) → transaction_summary (VA)
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/nusatrip.php';

if (!nusaModuleEnabled()) {
    header('Location: flights.php');
    exit;
}

$step = $_GET['step'] ?? ($_POST['action'] ?? 'form');
$err = '';

function nusaFlightBookSess(): array {
    return $_SESSION['nusa_flight_book'] ?? [];
}

/** Simpan/update booking NusaTrip flight agar muncul & bisa dilanjutkan dari my-bookings (idempotent by task_id). */
function nusaFlightPersist(array $b, array $res, ?array $summary): void {
    if (empty($_SESSION['user_id']) || empty($b['taskId'])) return;
    $bookingCode = (string)($res['bookingCode'] ?? $summary['bookingCode'] ?? '');
    if ($bookingCode === '') return;
    $va = $summary['paymentTransfer'] ?? null;
    $payStatus = (int)($res['paymentResult']['paymentStatus'] ?? -1);
    $isPaid = $payStatus === 1;
    $amountDue = (float)($summary['amountTotal'] ?? ($b['validate']['totalPrice']['IDR'] ?? 0));
    $expires = null;
    $tl = (int)($res['timeLimit'] ?? 0);
    if ($tl > 0) $expires = date('Y-m-d H:i:s', $tl > 1000000000000 ? (int)($tl / 1000) : $tl);
    try {
        db()->prepare("INSERT INTO nusatrip_flight_bookings
            (user_id, airline, flight_number, origin, destination, departure_date, passengers, cabin,
             booking_code, provider_ref, task_id, checkout_id, total_price, va_bank, va_number, va_expires_at,
             payment_status, status, raw_summary)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE booking_code=VALUES(booking_code), provider_ref=VALUES(provider_ref),
             checkout_id=VALUES(checkout_id), total_price=VALUES(total_price), va_bank=VALUES(va_bank),
             va_number=VALUES(va_number), va_expires_at=VALUES(va_expires_at), payment_status=VALUES(payment_status),
             status=VALUES(status), raw_summary=VALUES(raw_summary)")
            ->execute([
                (int)$_SESSION['user_id'], (string)($b['airline_name'] ?? ''), (string)($b['flight_number'] ?? ''),
                (string)($b['from'] ?? ''), (string)($b['to'] ?? ''), $b['departure_date'] ?? null,
                (int)($b['pax'] ?? 1), (string)($b['class_type'] ?? ''),
                $bookingCode, (string)($res['ref'] ?? ''), (string)$b['taskId'], (string)($b['checkoutId'] ?? ''),
                $amountDue, $va['bank'] ?? null, $va['accountNo'] ?? null, $expires,
                $isPaid ? 'paid' : 'unpaid', $isPaid ? 'confirmed' : 'pending',
                $summary ? json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ]);
        $freshId = (int)db()->lastInsertId();
        if ($freshId > 0) {
            require_once __DIR__ . '/notifications.php';
            $nfTitle = trim(($b['airline_name'] ?? '') . ' ' . ($b['flight_number'] ?? '') . ' ' . ($b['from'] ?? '') . '→' . ($b['to'] ?? ''));
            $nfDetail = trim(($b['departure_date'] ?? '') . ' • ' . (int)($b['pax'] ?? 1) . ' ' . (function_exists('t') ? t('pax') : 'pax'));
            notifyBookingCreated((int)$_SESSION['user_id'], function_exists('t') ? t('Pesawat') : 'Pesawat', $nfTitle, $nfDetail, $bookingCode, 'my-bookings.php');
        }
    } catch (Throwable $e) {
        error_log('nusatrip_flight_bookings persist gagal: ' . $e->getMessage());
    }
}

// Lanjutkan booking tersimpan dari my-bookings (rehidrasi session lalu tampilkan VA/status).
$resumeId = (int)($_GET['booking_id'] ?? 0);
if ($resumeId > 0 && !empty($_SESSION['user_id'])) {
    $rs = db()->prepare("SELECT * FROM nusatrip_flight_bookings WHERE id = ? AND user_id = ? LIMIT 1");
    $rs->execute([$resumeId, (int)$_SESSION['user_id']]);
    if ($row = $rs->fetch()) {
        $_SESSION['nusa_flight_book'] = array_merge($_SESSION['nusa_flight_book'] ?? [], [
            'airline_name' => $row['airline'], 'flight_number' => $row['flight_number'],
            'from' => $row['origin'], 'to' => $row['destination'], 'departure_date' => $row['departure_date'],
            'pax' => (int)$row['passengers'], 'class_type' => $row['cabin'],
            'taskId' => $row['task_id'], 'checkoutId' => $row['checkout_id'],
        ]);
        $step = 'result';
    }
}

if ($step === 'form') {
    $ofKey = trim((string)($_GET['of'] ?? ''));
    $offer = $_SESSION['nusa_flight_offers'][$ofKey] ?? null;
    if (!$offer || empty($offer['param'])) {
        $err = t('Sesi penerbangan tidak valid atau kedaluwarsa. Silakan cari ulang.');
    } else {
        $pax = min(9, max(1, (int)($offer['pax'] ?? 1)));
        $it = nusaFlightItem((string)$offer['param'], $pax);
        $sess = $it['data'] ?? null;
        if (($it['http'] ?? 0) !== 200 || empty($sess['cartSession'])) {
            error_log('flight_item gagal: ' . mb_substr((string)($it['raw'] ?? ''), 0, 300));
            $err = t('Gagal membuat sesi booking. Silakan coba lagi.');
        } else {
            $attr = nusaCheckoutAttributes($sess['cartSession'], $sess['checkoutId'], $sess['bookingTime']);
            $val = nusaValidate($sess['cartSession'], $sess['checkoutId'], $sess['bookingTime']);
            $vd = $val['data'] ?? [];
            if (!empty($vd['error']) || empty($vd['totalPrice']['IDR'])) {
                $err = t('Harga tiket ini tidak tersedia') . ' (' . ($vd['error'][0]['message'] ?? t('validasi gagal')) . '). ' . t('Silakan cari penerbangan lain.');
            } else {
                $methods = array_values(array_filter($attr['data']['paymentInstruments'] ?? [], function ($m) {
                    $nm = strtolower((string)($m['name'] ?? ''));
                    if (str_contains($nm, 'test') || str_contains($nm, 'dummy')) return false;
                    if (str_contains($nm, 'deposit')) return false;
                    $bid = (string)($m['bank_id'] ?? '');
                    $bnm = strtolower((string)($m['bank_name'] ?? $m['name'] ?? ''));
                    if ($bid === '' && (str_contains($bnm, 'bank transfer') || str_contains($bnm, 'virtual account'))) return false;
                    return true;
                }));
                $_SESSION['nusa_flight_book'] = [
                    'of' => $ofKey,
                    'airline_name' => $offer['airline_name'] ?? '', 'flight_number' => $offer['flight_number'] ?? '',
                    'from' => $offer['from'] ?? '', 'to' => $offer['to'] ?? '',
                    'dep' => $offer['dep'] ?? '', 'arr' => $offer['arr'] ?? '',
                    'duration' => $offer['duration'] ?? 0, 'stops' => $offer['stops'] ?? 0,
                    'departure_date' => nusaFlightDate((string)($offer['dep'] ?? '')) ?: null,
                    'dep_time' => nusaFlightTime((string)($offer['dep'] ?? '')),
                    'arr_time' => nusaFlightTime((string)($offer['arr'] ?? '')),
                    'class_type' => $offer['class_type'] ?? '', 'flight_route' => $offer['flight_route'] ?? 'domestic',
                    'pax' => $pax,
                    'cartSession' => $sess['cartSession'], 'checkoutId' => $sess['checkoutId'],
                    'bookingTime' => $sess['bookingTime'],
                    'methods' => $methods,
                    'validate' => $val['data'] ?? null,
                ];
            }
        }
    }
    $b = nusaFlightBookSess();
    if (empty($_SESSION['nusa_csrf'])) $_SESSION['nusa_csrf'] = bin2hex(random_bytes(32));
}

if ($step === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = nusaFlightBookSess();
    $payMethod = (string)($_POST['pay_method'] ?? 'cc');
    $allowedMethods = [];
    foreach (($b['methods'] ?? []) as $m) {
        $mid = (int)($m['id'] ?? 0);
        if ($mid === 6) continue;
        $allowedMethods[] = $mid . '|' . (string)($m['bank_id'] ?? '');
    }
    if (!hash_equals($_SESSION['nusa_csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { $err = t('Sesi tidak valid. Kembali dan ulangi.'); $step = 'form'; }
    elseif (empty($b['cartSession'])) { $err = t('Sesi booking kedaluwarsa. Ulangi dari halaman pencarian.'); $step = 'form'; }
    elseif (empty($b['validate']['totalPrice']['IDR'])) { $err = t('Harga belum tervalidasi. Ulangi dari halaman pencarian.'); $step = 'form'; }
    elseif ($payMethod !== 'cc' && !in_array($payMethod, $allowedMethods, true)) { $err = t('Metode pembayaran tidak valid.'); $step = 'form'; }
    else {
        $n = min(9, max(1, (int)($b['pax'] ?? 1)));
        $paxData = [];
        for ($i = 0; $i < $n; $i++) {
            $paxData[] = [
                'title' => (string)($_POST['p_title'][$i] ?? 'MR'),
                'first' => trim((string)($_POST['p_first'][$i] ?? '')),
                'last' => trim((string)($_POST['p_last'][$i] ?? '')),
                'birth' => nusaFlightDob((string)($_POST['p_birth'][$i] ?? '1990-01-01')),
                'type' => 0, 'nationality' => 'ID',
            ];
        }
        $missingPax = false;
        foreach ($paxData as $p) { if ($p['first'] === '' || $p['last'] === '') { $missingPax = true; break; } }
        $phoneCc = (string)($_POST['phone_cc'] ?? '62');
        $cTitle = (string)($_POST['c_title'] ?? 'MR');
        $cFirst = trim((string)($_POST['c_first'] ?? ''));
        $cLast = trim((string)($_POST['c_last'] ?? ''));
        $cEmail = trim((string)($_POST['c_email'] ?? ''));
        $cPhone = trim((string)($_POST['c_phone'] ?? ''));
        if ($missingPax) { $err = t('Nama semua penumpang wajib diisi.'); $step = 'form'; }
        elseif ($cFirst === '' || $cEmail === '' || $cPhone === '') { $err = t('Data kontak wajib diisi.'); $step = 'form'; }
        else {
            $contact = nusaContact($cTitle, $cFirst, $cLast, $cEmail, $cPhone, true, $phoneCc);
            $items = nusaFlightItems($paxData, (string)$b['bookingTime'], (string)($b['flight_route'] ?? 'domestic'));
            if ($payMethod === 'cc') {
                $payment = json_encode(['displayCurrencyCode' => 'IDR', 'paymentInstrument' => 6,
                    'billInfo' => ['fullName' => $cFirst . ' ' . $cLast],
                    'cardNo' => preg_replace('/\D/', '', (string)($_POST['card_no'] ?? '')),
                    'cardExpiration' => preg_replace('/\D/', '', (string)($_POST['card_exp'] ?? '')),
                    'secureCode' => preg_replace('/\D/', '', (string)($_POST['card_cvv'] ?? ''))], JSON_UNESCAPED_SLASHES);
            } else {
                [$iid, $bankId] = array_pad(explode('|', $payMethod, 2), 2, '');
                $payment = nusaFlightPayment((int)$iid, $bankId);
            }
            $submitFields = ['cartSession' => $b['cartSession'], 'checkoutId' => $b['checkoutId'],
                'contact' => $contact, 'items' => $items, 'payment' => $payment,
                'createAccount' => 'true', 'deviceFingerPrint' => nusaFingerprint(), 'deviceInfo' => nusaDeviceInfo('en')];
            $r = nusaSubmit($submitFields);
            $resp = $r['data'] ?? [];
            if (!empty($resp['taskId']) && empty($resp['submitError'])) {
                $_SESSION['nusa_flight_book']['taskId'] = $resp['taskId'];
                header('Location: nusatrip-flight-book.php?step=result');
                exit;
            }
            error_log('flight submit gagal: ' . mb_substr((string)($r['raw'] ?? 'HTTP ' . ($r['http'] ?? 0)), 0, 300));
            $err = t('Submit gagal. Silakan coba lagi.');
            $step = 'form';
        }
        $b = nusaFlightBookSess();
    }
}

if ($step === 'result') {
    $b = nusaFlightBookSess();
    $res = null;
    $summary = null;
    if (!empty($b['taskId']) && !empty($b['checkoutId'])) {
        if (empty($_GET['polled'])) {
            $pr = nusaPollResult((string)$b['taskId'], (string)$b['checkoutId']);
            $res = $pr['data'] ?? ['raw_error' => $pr['raw'] ?? ''];
        } else {
            $r0 = nusaResult((string)$b['taskId'], (string)$b['checkoutId'], '0');
            $res = $r0['data'] ?? ['raw_error' => $r0['raw'] ?? ''];
        }
        if (!empty($res['ref'])) {
            $sm = nusaSummary((string)$res['ref']);
            $summary = $sm['data'] ?? null;
        }
        if (is_array($res) && !isset($res['raw_error'])) nusaFlightPersist($b, $res, $summary);
    } else $err = t('Tidak ada taskId. Ulangi booking.');
}

$pageTitle = t('Booking Tiket Pesawat');
require_once 'includes/header-shared.php';
?>
<section class="py-4 bg-light"><div class="container" style="max-width:760px">
<h4 class="fw-bold mb-3"><i class="bi bi-airplane me-2"></i><?= t('Booking Tiket Pesawat') ?></h4>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<?php if ($step === 'result' && !empty($res)): ?>
    <?php $payStatus = (int)($res['paymentResult']['paymentStatus'] ?? -1); $msgs = $res['messages'] ?? []; ?>
    <?php $va = $summary['paymentTransfer'] ?? null; ?>
    <div class="card border-0 shadow-sm"><div class="card-body p-4 text-center">
        <?php if (!empty($va['accountNo'])): ?>
            <i class="bi bi-bank text-primary" style="font-size:48px"></i>
            <h5 class="fw-bold mt-2"><?= t('Menunggu Pembayaran') ?></h5>
            <p class="mb-1"><?= t('Booking Code:') ?> <b><?= e((string)($res['bookingCode'] ?? $summary['bookingCode'] ?? '-')) ?></b></p>
            <div class="alert alert-info text-start mt-3 mb-0">
                <div><b><?= e((string)($va['bank'] ?? 'VA')) ?></b> <?= t('Virtual Account') ?></div>
                <h4 class="fw-bold my-1"><?= e((string)$va['accountNo']) ?></h4>
                <small class="text-muted">Rp<?= number_format((float)($summary['amountDue'] ?? 0), 0, ',', '.') ?><?php $tl = (int)($res['timeLimit'] ?? 0); ?><?= $tl > 0 ? ' · batas ' . e($tl > 1000000000000 ? date('d M Y H:i', (int)($tl / 1000)) : date('d M Y H:i', $tl)) : '' ?></small>
            </div>
        <?php elseif ($payStatus === 1): ?>
            <i class="bi bi-check-circle-fill text-success" style="font-size:48px"></i>
            <h5 class="fw-bold mt-2"><?= t('Pembayaran Berhasil') ?></h5>
            <p class="mb-1"><?= t('Booking Code:') ?> <b><?= e((string)($res['bookingCode'] ?? '-')) ?></b></p>
        <?php else: ?>
            <i class="bi bi-x-circle-fill text-danger" style="font-size:48px"></i>
            <h5 class="fw-bold mt-2"><?= t('Pembayaran Gagal / Ditolak') ?></h5>
            <?php foreach ($msgs as $m): ?><p class="text-muted small mb-1">[<?= (int)($m['code'] ?? 0) ?>] <?= e((string)($m['message'] ?? '')) ?></p><?php endforeach; ?>
            <?php if (!empty($res['bookingCode'])): ?><p class="mb-1"><?= t('Booking Code:') ?> <b><?= e((string)$res['bookingCode']) ?></b> (tercatat, belum terbayar)</p><?php endif; ?>
        <?php endif; ?>
        <p class="text-muted small"><?= t('Task:') ?> <?= e((string)($b['taskId'] ?? '-')) ?> · <?= t('Status bayar:') ?> <?= $payStatus ?></p>
        <a href="flights.php" class="btn btn-outline-secondary rounded-pill"><?= t('Kembali') ?></a>
    </div></div>
<?php elseif (!empty($b['cartSession']) && $step !== 'result'): ?>
    <?php $total = $b['validate']['totalPrice']['IDR'] ?? 0; $methods = $b['methods'] ?? []; $pax = min(9, max(1, (int)($b['pax'] ?? 1))); ?>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="fw-bold mb-1"><?= e($b['airline_name'] ?? '') ?> · <?= e($b['flight_number'] ?? '') ?></h6>
                <small class="text-muted d-block"><?= e($b['from'] ?? '') ?> → <?= e($b['to'] ?? '') ?> · <?= e($b['departure_date'] ?? '') ?><?php if (!empty($b['dep_time'])): ?> <?= e($b['dep_time']) ?><?php if (!empty($b['arr_time'])): ?>–<?= e($b['arr_time']) ?><?php endif; ?><?php endif; ?></small>
                <small class="text-muted d-block"><?= (int)($b['pax'] ?? 1) ?> <?= t('Penumpang') ?> · <?= e($b['class_type'] ?? '') ?></small>
            </div>
            <span class="badge bg-primary">NusaTrip</span>
        </div>
        <h5 class="fw-bold text-primary mt-2 mb-0">Rp<?= number_format((float)$total, 0, ',', '.') ?></h5>
    </div></div>
    <div class="card border-0 shadow-sm"><div class="card-body">
    <form method="POST" action="nusatrip-flight-book.php" data-submit-once>
        <input type="hidden" name="action" value="submit">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['nusa_csrf'] ?? '') ?>">
        <h6 class="fw-semibold"><?= t('Data Penumpang') ?></h6>
        <?php for ($i = 0; $i < $pax; $i++): ?>
        <div class="border rounded-3 p-2 mb-2">
            <small class="text-muted d-block mb-1"><?= t('Penumpang') ?> <?= $i + 1 ?></small>
            <div class="row g-2">
                <div class="col-3"><select name="p_title[<?= $i ?>]" class="form-select"><option value="MR"><?= t('MR') ?></option><option value="MRS"><?= t('MRS') ?></option><option value="MS"><?= t('MS') ?></option></select></div>
                <div class="col-4"><input name="p_first[<?= $i ?>]" class="form-control" placeholder="<?= e(t('Nama depan')) ?>" required></div>
                <div class="col-5"><input name="p_last[<?= $i ?>]" class="form-control" placeholder="<?= e(t('Nama belakang')) ?>" required></div>
                <div class="col-6"><input name="p_birth[<?= $i ?>]" type="date" class="form-control" value="1990-01-01" required></div>
            </div>
        </div>
        <?php endfor; ?>
        <h6 class="fw-semibold mt-3"><?= t('Data Kontak') ?></h6>
        <div class="row g-2 mb-3">
            <div class="col-3"><select name="c_title" class="form-select"><option value="MR"><?= t('MR') ?></option><option value="MRS"><?= t('MRS') ?></option><option value="MS"><?= t('MS') ?></option></select></div>
            <div class="col-4"><input name="c_first" class="form-control" placeholder="<?= e(t('Nama depan')) ?>" value="<?= e(getUser()['name'] ?? '') ?>" required></div>
            <div class="col-5"><input name="c_last" class="form-control" placeholder="<?= e(t('Nama belakang')) ?>" required></div>
            <div class="col-6"><input name="c_email" type="email" class="form-control" placeholder="Email" value="<?= e(getUser()['email'] ?? '') ?>" required></div>
            <div class="col-6">
                <label class="form-label small text-muted mb-1"><?= t('Nomor HP (pilih kode negara, tulis nomor lokal saja)') ?></label>
                <div class="input-group">
                    <select name="phone_cc" class="form-select" style="max-width:150px" data-testid="nusa-phone-cc">
                        <?php foreach (nusaCountryCodes() as $cc => $label): ?>
                            <option value="<?= e((string)$cc) ?>" <?= (string)$cc === '62' ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input name="c_phone" class="form-control" placeholder="8517488415" inputmode="tel" value="<?= e(getUser()['phone'] ?? '') ?>" required data-testid="nusa-phone">
                </div>
                <small class="text-muted" style="font-size:11px;"><?= t('Contoh: pilih +62 lalu tulis 08517488415 — otomatis dikirim "62 8517488415"') ?></small>
            </div>
        </div>
        <h6 class="fw-semibold"><?= t('Pembayaran') ?></h6>
        <div class="mb-2">
            <div class="form-check"><input class="form-check-input" type="radio" name="pay_method" value="cc" id="pmCC" checked>
            <label class="form-check-label" for="pmCC"><?= t('Kartu Kredit / Debit') ?></label></div>
            <div class="row g-2 mt-1 mb-2">
                <div class="col-6"><input name="card_no" class="form-control" placeholder="<?= e(t('Nomor kartu')) ?>" inputmode="numeric"></div>
                <div class="col-3"><input name="card_exp" class="form-control" placeholder="MMYY" inputmode="numeric"></div>
                <div class="col-3"><input name="card_cvv" class="form-control" placeholder="CVV" inputmode="numeric"></div>
            </div>
            <?php foreach ($methods as $m): ?>
                <?php if ((int)($m['id'] ?? 0) === 6) continue; $v = (string)$m['id'] . '|' . (string)($m['bank_id'] ?? ''); ?>
                <div class="form-check"><input class="form-check-input" type="radio" name="pay_method" value="<?= e($v) ?>" id="pm<?= (int)$m['id'] ?>">
                <label class="form-check-label" for="pm<?= (int)$m['id'] ?>"><?= e($m['name'] ?? '') ?><?= !empty($m['bank_name']) ? ' — ' . e($m['bank_name']) : '' ?></label></div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary rounded-pill w-100"><?= t('Bayar Sekarang') ?></button>
    </form>
    </div></div>
<?php endif; ?>
</div></section>
<?php require_once 'includes/footer-shared.php';

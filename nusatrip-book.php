<?php
/**
 * nusatrip-book.php — Booking hotel native (search → rates → submit → result).
 * Pembayaran diteruskan langsung (guest checkout, tanpa login).
 *
 * Alur:
 *   1. GET ?hotel_id=&checkin=&checkout=&guests=&city=&room_idx=
 *      → rates fresh → transaction_hotel_item → attributes → form tamu + bayar
 *   2. POST action=submit → transaction_submit → redirect ?step=result
 *   3. GET ?step=result → transaction_result (poll 1x, auto-refresh 1x)
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/hotelapi.php';

if (!nusaModuleEnabled()) {
    header('Location: hotels.php?live_error=1');
    exit;
}

$step = $_GET['step'] ?? ($_POST['action'] ?? 'form');
$err = '';

/** Ambil session booking. */
function nusaBookSess(): array {
    return $_SESSION['nusa_book'] ?? [];
}

/** Simpan/update booking NusaTrip agar muncul & bisa dilanjutkan dari my-bookings (idempotent by task_id). */
function nusaPersistBooking(array $b, array $res, ?array $summary): void {
    if (empty($_SESSION['user_id']) || empty($b['taskId'])) return;
    $bookingCode = (string)($res['bookingCode'] ?? $summary['bookingCode'] ?? '');
    if ($bookingCode === '') return;
    $va = $summary['paymentTransfer'] ?? null;
    $payStatus = (int)($res['paymentResult']['paymentStatus'] ?? -1);
    $isPaid = $payStatus === 1;
    $amountDue = (float)($summary['amountDue'] ?? ($b['validate']['totalPrice']['IDR'] ?? 0));
    $expires = null;
    $tl = (int)($res['timeLimit'] ?? 0);
    if ($tl > 0) $expires = date('Y-m-d H:i:s', $tl > 1000000000000 ? (int)($tl / 1000) : $tl);
    try {
        db()->prepare("INSERT INTO nusatrip_bookings
            (user_id, hotel_id, hotel_name, city, checkin, checkout, guests, room_category, room_board, room_rate,
             booking_code, provider_ref, task_id, checkout_id, total_price, va_bank, va_number, va_expires_at,
             payment_status, status, raw_summary)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE booking_code=VALUES(booking_code), provider_ref=VALUES(provider_ref),
             checkout_id=VALUES(checkout_id), total_price=VALUES(total_price), va_bank=VALUES(va_bank),
             va_number=VALUES(va_number), va_expires_at=VALUES(va_expires_at), payment_status=VALUES(payment_status),
             status=VALUES(status), raw_summary=VALUES(raw_summary)")
            ->execute([
                (int)$_SESSION['user_id'], (string)$b['hotel_id'], $b['hotel_name'] ?? null, $b['city'] ?? null,
                $b['checkin'], $b['checkout'], (int)($b['guests'] ?? 1),
                $b['room']['category'] ?? null, $b['room']['board'] ?? null, (float)($b['room']['rate'] ?? 0),
                $bookingCode, (string)($res['ref'] ?? ''), (string)$b['taskId'], (string)($b['checkoutId'] ?? ''),
                $amountDue, $va['bank'] ?? null, $va['accountNo'] ?? null, $expires,
                $isPaid ? 'paid' : 'unpaid', $isPaid ? 'confirmed' : 'pending',
                $summary ? json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ]);
        $freshId = (int)db()->lastInsertId();
        if ($freshId > 0) {
            require_once __DIR__ . '/includes/notifications.php';
            $nbDetail = $b['checkin'] . ' → ' . $b['checkout'] . ' • ' . (int)($b['guests'] ?? 1) . ' ' . (function_exists('t') ? t('pax') : 'pax');
            notifyBookingCreated((int)$_SESSION['user_id'], function_exists('t') ? t('Hotel') : 'Hotel', (string)($b['hotel_name'] ?? ''), $nbDetail, $bookingCode, 'my-bookings.php');
        }
    } catch (Throwable $e) {
        error_log('nusatrip_bookings persist gagal: ' . $e->getMessage());
    }
}

// Lanjutkan booking tersimpan dari my-bookings (rehidrasi session lalu tampilkan VA/status).
$resumeId = (int)($_GET['booking_id'] ?? 0);
if ($resumeId > 0 && !empty($_SESSION['user_id'])) {
    $rs = db()->prepare("SELECT * FROM nusatrip_bookings WHERE id = ? AND user_id = ? LIMIT 1");
    $rs->execute([$resumeId, (int)$_SESSION['user_id']]);
    if ($row = $rs->fetch()) {
        $_SESSION['nusa_book'] = array_merge($_SESSION['nusa_book'] ?? [], [
            'hotel_id' => $row['hotel_id'], 'hotel_name' => $row['hotel_name'],
            'checkin' => $row['checkin'], 'checkout' => $row['checkout'],
            'guests' => (int)$row['guests'], 'city' => $row['city'],
            'room' => ['category' => $row['room_category'], 'board' => $row['room_board'], 'rate' => $row['room_rate']],
            'taskId' => $row['task_id'], 'checkoutId' => $row['checkout_id'],
        ]);
        $step = 'result';
    }
}

if ($step === 'form') {
    $hotelId = trim((string)($_GET['hotel_id'] ?? ''));
    $checkin = (string)($_GET['checkin'] ?? '');
    $checkout = (string)($_GET['checkout'] ?? '');
    $guests = min(12, max(1, (int)($_GET['guests'] ?? 1)));
    $city = trim((string)($_GET['city'] ?? ''));
    $hotelName = trim((string)($_GET['hotel_name'] ?? ''));
    $roomCombo = trim((string)($_GET['room_combo'] ?? ''));
    $roomIdx = max(0, (int)($_GET['room_idx'] ?? 0));
    if ($hotelId === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkout) || strtotime($checkout) <= strtotime($checkin)) {
        $err = t('Parameter booking tidak lengkap.');
    } else {
        $rt = nusaHotelRates($hotelId, $checkin, $checkout, $guests);
        $rooms = $rt['data']['rooms'] ?? [];
        $room = null;
        if ($roomCombo !== '') {
            [$cc, $bb, $rr] = array_pad(explode('|', $roomCombo, 3), 3, '');
            foreach ($rooms as $r) {
                if ((string)($r['room_category'] ?? '') === $cc && (string)($r['board_type'] ?? '') === $bb && (string)($r['display_average_rate'] ?? $r['average_rate'] ?? '') === $rr) { $room = $r; break; }
            }
            if (!$room) $room = $rooms[$roomIdx] ?? null;
        } else {
            $room = $rooms[$roomIdx] ?? null;
        }
        if (!$room) {
            $err = t('Kamar tidak tersedia (rates kosong / index salah).');
        } else {
            $roomItems = nusaRoomItems($guests, $room['special_deal'] ?? null, (string)($room['book_reference'] ?? ''));
            $it = nusaHotelItem($hotelId, $checkin, $checkout, $roomItems);
            $sess = $it['data'] ?? null;
            if (($it['http'] ?? 0) !== 200 || empty($sess['cartSession'])) {
                error_log('hotel_item gagal: ' . mb_substr((string)($it['raw'] ?? ''), 0, 300));
                $err = t('Gagal membuat sesi booking. Silakan coba lagi atau pilih kamar lain.');
            } else {
                $attr = nusaCheckoutAttributes($sess['cartSession'], $sess['checkoutId'], $sess['bookingTime']);
                $val = nusaValidate($sess['cartSession'], $sess['checkoutId'], $sess['bookingTime']);
                $vd = $val['data'] ?? [];
                if (!empty($vd['error']) || empty($vd['totalPrice']['IDR'])) {
                    $err = t('Harga kamar ini tidak tersedia') . ' (' . ($vd['error'][0]['message'] ?? t('validasi gagal')) . '). ' . t('Pilih kamar lain.');
                    $b = nusaBookSess();
                } else {
                $_SESSION['nusa_book'] = [
                    'hotel_id' => $hotelId, 'hotel_name' => $hotelName !== '' ? $hotelName : $hotelId,
                    'checkin' => $checkin, 'checkout' => $checkout, 'guests' => $guests, 'city' => $city,
                    'room' => ['category' => $room['room_category'] ?? '', 'board' => $room['board_type'] ?? '',
                        'rate' => $room['display_average_rate'] ?? $room['average_rate'] ?? 0],
                    'cartSession' => $sess['cartSession'], 'checkoutId' => $sess['checkoutId'],
                    'bookingTime' => $sess['bookingTime'],
                    'all_methods' => $attr['data']['paymentInstruments'] ?? [],
                    'methods' => array_values(array_filter($attr['data']['paymentInstruments'] ?? [], function ($m) {
                        $nm = strtolower((string)($m['name'] ?? ''));
                        if (str_contains($nm, 'test') || str_contains($nm, 'dummy')) { $m['_hidden'] = true; return false; }
                        if (str_contains($nm, 'deposit')) { $m['_hidden'] = true; return false; }
                        $bid = (string)($m['bank_id'] ?? '');
                        $bnm = strtolower((string)($m['bank_name'] ?? $m['name'] ?? ''));
                        if ($bid === '' && (str_contains($bnm, 'bank transfer') || str_contains($bnm, 'virtual account'))) return false;
                        return true;
                    })),
                    'transactionId' => $attr['data']['transaction']['transactionId'] ?? null,
                    'validate' => $val['data'] ?? null,
                ];
                }
            }
        }
    }
    $b = nusaBookSess();
    if (empty($_SESSION['nusa_csrf'])) $_SESSION['nusa_csrf'] = bin2hex(random_bytes(32));
}

if ($step === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = nusaBookSess();
    $payMethod = (string)($_POST['pay_method'] ?? 'cc');
    $allowedMethods = [];
    foreach ($b['methods'] as $m) {
        $mid = (int)($m['id'] ?? 0);
        if ($mid === 6) continue;
        $allowedMethods[] = $mid . '|' . (string)($m['bank_id'] ?? '');
    }
    if (!hash_equals($_SESSION['nusa_csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { $err = t('Sesi tidak valid. Kembali dan ulangi.'); $step = 'form'; }
    elseif (empty($b['cartSession'])) { $err = t('Sesi booking kedaluwarsa. Ulangi dari halaman hotel.'); $step = 'form'; }
    elseif (empty($b['validate']['totalPrice']['IDR'])) { $err = t('Harga belum tervalidasi. Ulangi dari halaman hotel.'); $step = 'form'; }
    elseif ($payMethod !== 'cc' && !in_array($payMethod, $allowedMethods, true)) { $err = t('Metode pembayaran tidak valid.'); $step = 'form'; }
    else {
        $phoneCc = (string)($_POST['phone_cc'] ?? '62');
        $contact = nusaContact((string)($_POST['title'] ?? 'MR'), trim((string)($_POST['first_name'] ?? '')),
            trim((string)($_POST['last_name'] ?? '')), trim((string)($_POST['email'] ?? '')),
            trim((string)($_POST['phone'] ?? '')), false, $phoneCc);
        $items = nusaItems((string)($_POST['title'] ?? 'MR'), trim((string)($_POST['first_name'] ?? '')),
            trim((string)($_POST['last_name'] ?? '')), $b['bookingTime']);
        if ($payMethod === 'cc') {
            $payment = json_encode(['displayCurrencyCode' => 'IDR', 'paymentInstrument' => 6,
                'billInfo' => ['fullName' => trim((string)($_POST['first_name'] ?? '')) . ' ' . trim((string)($_POST['last_name'] ?? ''))],
                'cardNo' => preg_replace('/\D/', '', (string)($_POST['card_no'] ?? '')),
                'cardExpiration' => preg_replace('/\D/', '', (string)($_POST['card_exp'] ?? '')),
                'secureCode' => preg_replace('/\D/', '', (string)($_POST['card_cvv'] ?? ''))], JSON_UNESCAPED_SLASHES);
        } else {
            // VA / bank transfer: "bankId" dari paymentInstruments.
            [$iid, $bankId] = array_pad(explode('|', $payMethod, 2), 2, '');
            $payment = json_encode(array_filter(['displayCurrencyCode' => 'IDR', 'paymentInstrument' => (int)$iid,
                'bankId' => $bankId ?: null]), JSON_UNESCAPED_SLASHES);
        }
        $fp = nusaFingerprint();
        $dev = nusaDeviceInfo('en');
        $submitFields = ['cartSession' => $b['cartSession'], 'checkoutId' => $b['checkoutId'],
            'contact' => $contact, 'items' => $items, 'payment' => $payment,
            'deviceFingerPrint' => $fp, 'deviceInfo' => $dev];
        $r = nusaSubmit($submitFields);
        $resp = $r['data'] ?? [];
        if (!empty($resp['taskId']) && empty($resp['submitError'])) {
            $_SESSION['nusa_book']['taskId'] = $resp['taskId'];
            header('Location: nusatrip-book.php?step=result');
            exit;
        }
        error_log('submit gagal: ' . mb_substr((string)($r['raw'] ?? 'HTTP ' . ($r['http'] ?? 0)), 0, 300));
        $err = t('Submit gagal. Silakan coba lagi.');
        $step = 'form';
        $b = nusaBookSess();
    }
}

if ($step === 'result') {
    $b = nusaBookSess();
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
        if (is_array($res) && !isset($res['raw_error'])) nusaPersistBooking($b, $res, $summary);
    } else $err = t('Tidak ada taskId. Ulangi booking.');
}

$pageTitle = t('Booking Hotel');
require_once 'includes/header-shared.php';
?>
<section class="py-4 bg-light"><div class="container" style="max-width:720px">
<h4 class="fw-bold mb-3"><i class="bi bi-building me-2"></i><?= t('Booking Hotel') ?></h4>
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
        <a href="hotels.php?city=<?= urlencode((string)($b['city'] ?? '')) ?>" class="btn btn-outline-secondary rounded-pill"><?= t('Kembali') ?></a>
    </div></div>
<?php elseif (!empty($b['cartSession']) && $step !== 'result'): ?>
    <?php $total = $b['validate']['totalPrice']['IDR'] ?? $b['room']['rate'] ?? 0; $methods = $b['methods'] ?? []; ?>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <h6 class="fw-bold mb-1"><?= e($b['hotel_name'] ?? '') ?></h6>
        <small class="text-muted d-block"><?= e($b['room']['category'] ?? '') ?> · <?= e($b['room']['board'] ?? '') ?></small>
        <small class="text-muted d-block"><?= e($b['checkin'] ?? '') ?> → <?= e($b['checkout'] ?? '') ?> · <?= (int)($b['guests'] ?? 1) ?> tamu</small>
        <h5 class="fw-bold text-primary mt-2 mb-0">Rp<?= number_format((float)$total, 0, ',', '.') ?></h5>
    </div></div>
    <div class="card border-0 shadow-sm"><div class="card-body">
    <form method="POST" action="nusatrip-book.php" data-submit-once>
        <input type="hidden" name="action" value="submit">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['nusa_csrf'] ?? '') ?>">
        <h6 class="fw-semibold"><?= t('Data Tamu') ?></h6>
        <div class="row g-2 mb-3">
            <div class="col-3"><select name="title" class="form-select"><option value="MR"><?= t('MR') ?></option><option value="MRS"><?= t('MRS') ?></option><option value="MS"><?= t('MS') ?></option></select></div>
            <div class="col-4"><input name="first_name" class="form-control" placeholder="<?= e(t('Nama depan')) ?>" required></div>
            <div class="col-5"><input name="last_name" class="form-control" placeholder="<?= e(t('Nama belakang')) ?>" required></div>
            <div class="col-6"><input name="email" type="email" class="form-control" placeholder="Email" required></div>
            <div class="col-6">
                <label class="form-label small text-muted mb-1"><?= t('Nomor HP (pilih kode negara, tulis nomor lokal saja)') ?></label>
                <div class="input-group">
                    <select name="phone_cc" class="form-select" style="max-width:150px" data-testid="nusa-phone-cc">
                        <?php foreach (nusaCountryCodes() as $cc => $label): ?>
                            <option value="<?= e((string)$cc) ?>" <?= (string)$cc === '62' ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input name="phone" class="form-control" placeholder="8517488415" inputmode="tel" required data-testid="nusa-phone">
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

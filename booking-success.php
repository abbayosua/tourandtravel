<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

require_once __DIR__ . '/includes/payments.php';
require_once __DIR__ . '/includes/tripay.php';

$code = $_GET['code'] ?? '';

// Try to find the booking across all booking types
$booking = null;
$btype = 'tour';
$itemLink = '';

// 1) Tour bookings
$stmt = db()->prepare("
    SELECT b.*, t.title as tour_title, t.slug as tour_slug, td.departure_date, td.return_date
    FROM bookings b
    JOIN tours t ON b.tour_id = t.id
    JOIN tour_dates td ON b.tour_date_id = td.id
    WHERE b.booking_code = ?
");
$stmt->execute([$code]);
if ($row = $stmt->fetch()) {
    $booking = $row;
    $btype = 'tour';
    $booking['item_title'] = $row['tour_title'];
    $booking['date_label'] = $row['departure_date'];
    $booking['qty_label'] = $row['participants'] . ' ' . t('orang');
    $itemLink = 'tour-detail.php?slug=' . urlencode($row['tour_slug']);
}

// 2) Attraction bookings
if (!$booking) {
    $stmt = db()->prepare("
        SELECT ab.*, a.name as item_title, a.slug as item_slug
        FROM attraction_bookings ab
        JOIN attractions a ON ab.attraction_id = a.id
        WHERE ab.booking_code = ?
    ");
    $stmt->execute([$code]);
    if ($row = $stmt->fetch()) {
        $booking = $row;
        $btype = 'attraction';
        $booking['date_label'] = $row['visit_date'] ?? null;
        $booking['qty_label'] = $row['quantity'] . ' ' . t('tiket');
        $itemLink = 'attraction-detail.php?slug=' . urlencode($row['item_slug']);
    }
}

// 3) Transfer bookings
if (!$booking) {
    $stmt = db()->prepare("
        SELECT tb.*, tr.name as item_title, tr.slug as item_slug
        FROM transfer_bookings tb
        JOIN transfers tr ON tb.transfer_id = tr.id
        WHERE tb.booking_code = ?
    ");
    $stmt->execute([$code]);
    if ($row = $stmt->fetch()) {
        $booking = $row;
        $btype = 'transfer';
        $booking['date_label'] = $row['pickup_date'] ?? null;
        $booking['qty_label'] = $row['passengers'] . ' ' . t('pax');
        $itemLink = 'transfer-detail.php?slug=' . urlencode($row['item_slug']);
    }
}

// 4) Train bookings
if (!$booking) {
    $stmt = db()->prepare("
        SELECT tb.*, tr.name as item_title, tr.slug as item_slug
        FROM train_bookings tb
        JOIN trains tr ON tb.train_id = tr.id
        WHERE tb.booking_code = ?
    ");
    $stmt->execute([$code]);
    if ($row = $stmt->fetch()) {
        $booking = $row;
        $btype = 'train';
        $booking['date_label'] = $row['travel_date'] ?? null;
        $booking['qty_label'] = $row['seats'] . ' ' . t('kursi');
        $itemLink = 'train-detail.php?slug=' . urlencode($row['item_slug']);
    }
}

// 5) eSIM / connectivity bookings
if (!$booking) {
    $stmt = db()->prepare("
        SELECT cb.*, cp.name as item_title, cp.slug as item_slug
        FROM connectivity_bookings cb
        JOIN connectivity_products cp ON cb.product_id = cp.id
        WHERE cb.booking_code = ?
    ");
    $stmt->execute([$code]);
    if ($row = $stmt->fetch()) {
        $booking = $row;
        $btype = 'esim';
        $booking['date_label'] = null;
        $booking['qty_label'] = $row['quantity'] . ' ' . t('pcs');
        $itemLink = 'esim-detail.php?slug=' . urlencode($row['item_slug']);
    }
}

// 6) Flight & hotel bookings (kode = FLB-{id} / HTB-{id}, dari param btype)
if (!$booking && isset($_GET['btype']) && preg_match('/^(FLB|HTB)-(\d+)$/', $code, $mCode)) {
    $btype = $mCode[1] === 'FLB' ? 'flight' : 'hotel';
    $refId = (int)$mCode[2];
    if ($btype === 'flight') {
        $stmt = db()->prepare("SELECT fb.*, f.flight_number, f.airline FROM flight_bookings fb
            JOIN flight_schedules fs ON fs.id = fb.schedule_id JOIN flights f ON f.id = fs.flight_id
            WHERE fb.id = ? AND fb.user_id = ?");
    } else {
        $stmt = db()->prepare("SELECT hb.*, h.name AS airline FROM hotel_bookings hb
            JOIN hotels h ON h.id = hb.hotel_id
            WHERE hb.id = ? AND hb.user_id = ?");
    }
    $stmt->execute([$refId, $_SESSION['user_id'] ?? 0]);
    if ($row = $stmt->fetch()) {
        $booking = $row;
        $booking['booking_code'] = $code;
        $booking['item_title'] = $btype === 'flight' ? ($row['airline'] . ' ' . $row['flight_number']) : $row['airline'];
        $booking['date_label'] = $btype === 'flight' ? $row['departure_date'] : $row['checkin'];
        $booking['qty_label'] = $btype === 'flight' ? ($row['pax'] ?? 1) . ' ' . t('pax') : ($row['rooms'] . ' ' . t('Kamar'));
        $itemLink = $btype === 'flight' ? 'flights.php' : 'hotels.php';
    }
}

if (!$booking) {
    header('Location: tours.php');
    exit;
}

// Earn KlookCash (5% dari total) untuk user yang login — sekali per booking
$earnedPoints = 0;
if (!empty($booking['user_id'])) {
    require_once 'includes/wallet.php';
    // Cek belum pernah earn untuk booking ini
    $check = db()->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE reference_type = ? AND reference_id = ? AND type = 'earn'");
    $check->execute([$btype . '_booking', $booking['id']]);
    if ($check->fetchColumn() == 0) {
        $earnedPoints = round($booking['total_price'] * 0.05);
        if ($earnedPoints > 0) {
            addWalletTransaction($booking['user_id'], $earnedPoints, 'earn', 'Reward booking ' . $booking['booking_code'], $btype . '_booking', $booking['id']);
        }
    }
}

// Payment: manual (default) = admin approve; instant = gateway aktif
$paymentEnabled = tripayInstantEnabled() && ($booking['status'] ?? '') === 'pending';
$paymentStatus = 'unpaid';
$paymentOrderId = null;
if ($paymentEnabled && $btype === 'tour') {
    $pst = db()->prepare("SELECT order_id, gateway, pay_code, checkout_url, status FROM payments WHERE booking_type='tour' AND booking_id=? ORDER BY id DESC LIMIT 1");
    $pst->execute([$booking['id']]);
    if ($prow = $pst->fetch()) {
        $paymentStatus = $prow['status'];
        $paymentOrderId = $prow['order_id'];
        $paymentGateway = $prow['gateway'] ?? 'midtrans';
        $paymentPayCode = $prow['pay_code'] ?? null;
        $paymentCheckoutUrl = $prow['checkout_url'] ?? null;
    }
}

// Fase 5: bundle cross-sell — flight↔hotel dalam 24 jam → kupon 5% otomatis.
// Banner tampil di booking PERTAMA (flight/hotel) sebagai tawaran cross-sell;
// jika partner booking sudah ada dalam window, kupon tetap diberikan (satu per ref).
$bundle = ['eligible' => false, 'pct' => 0, 'ref_code' => null, 'coupon' => null, 'target' => null];
if (!empty($booking['user_id']) && in_array($btype, ['flight', 'hotel'], true)) {
    require_once 'includes/bundle.php';
    $bundle['eligible'] = true;
    $bundle['pct'] = BUNDLE_DISCOUNT_PCT;
    $bundle['ref_code'] = $code;
    $bundle['ref_booking_id'] = (int)$booking['id'];
    $bundle['coupon'] = generateBundleCoupon((int)$booking['user_id'], (int)$booking['id']);
    $bundle['target'] = $btype === 'flight' ? 'hotels.php' : 'flights.php';
}

$pageTitle = t('Booking Berhasil');
require_once 'includes/header-shared.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Confetti container -->
            <div id="confetti-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none;"></div>

            <div class="card border-0 shadow-sm text-center position-relative">
                <div class="card-body py-5">
                    <div class="display-1 text-success mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-2"><?= t('Booking Berhasil!') ?></h3>
                    <p class="text-muted mb-3"><?= t('Terima kasih, pemesanan Anda telah diterima.') ?></p>

                    <!-- Step Tracker -->
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <div class="text-center">
                            <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 32px; height: 32px;"><i class="bi bi-check-lg text-white"></i></div>
                            <small class="d-block text-muted" style="font-size: 10px;"><?= t('Booking') ?></small>
                        </div>
                        <div class="d-flex align-items-center" style="width: 40px;"><div class="border-top border-2 border-success w-100"></div></div>
                        <div class="text-center">
                            <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 32px; height: 32px;"><i class="bi bi-check-lg text-white"></i></div>
                            <small class="d-block text-muted" style="font-size: 10px;"><?= t('Diterima') ?></small>
                        </div>
                        <div class="d-flex align-items-center" style="width: 40px;"><div class="border-top border-2 border-success w-100"></div></div>
                        <div class="text-center">
                            <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 32px; height: 32px;"><i class="bi bi-clock text-white"></i></div>
                            <small class="d-block text-muted" style="font-size: 10px;"><?= t('Konfirmasi') ?></small>
                        </div>
                        <div class="d-flex align-items-center" style="width: 40px;"><div class="border-top border-2 border-secondary w-100"></div></div>
                        <div class="text-center">
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 32px; height: 32px;"><i class="bi bi-check2-all text-white"></i></div>
                            <small class="d-block text-muted" style="font-size: 10px;"><?= t('Selesai') ?></small>
                        </div>
                    </div>

                    <!-- Booking Code -->
                    <div class="bg-primary text-white rounded-4 p-4 mb-4 klook-booking-code">
                        <small class="text-white-50"><?= t('Kode Booking') ?></small>
                        <div class="fs-2 fw-bold tracking-code"><?= e($booking['booking_code']) ?></div>
                        <div class="mt-2 small text-white-50">
                            <i class="bi bi-link-45deg me-1"></i>
                            <a href="track.php?code=<?= urlencode($booking['booking_code']) ?>" class="text-white"><?= BASE_URL ?>/track.php?code=<?= e($booking['booking_code']) ?></a>
                        </div>
                    </div>

                    <!-- KlookCash earned -->
                    <?php if ($earnedPoints > 0): ?>
                    <div class="bg-success bg-opacity-10 text-success rounded-4 p-3 mb-4 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-coin fs-4"></i>
                        <div>
                            <div class="fw-bold">+ <?= number_format($earnedPoints, 0, ',', '.') ?> KlookCash</div>
                            <small class="d-block" style="font-size: 11px;"><?= t('Reward 5% dari total booking — bisa dipakai untuk booking berikutnya') ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Fase 5: Bundle cross-sell banner -->
                    <?php if ($bundle['eligible'] && $bundle['coupon']): ?>
                    <div class="bg-warning bg-opacity-10 border border-warning rounded-4 p-3 mb-4 text-center" data-testid="bundle-banner">
                        <div class="fw-bold mb-1"><i class="bi bi-stars text-warning me-1"></i><?= t('Lengkapi bundlemu, hemat 5%!') ?></div>
                        <p class="small text-muted mb-2"><?= t('Kamu baru memesan') ?> <?= $btype === 'flight' ? t('penerbangan') : t('hotel') ?> (<?= e($bundle['ref_code']) ?>). <?= t('Pesan') ?> <?= $btype === 'flight' ? t('hotel') : t('penerbangan') ?> <?= t('sekarang dan pakai kupon di bawah untuk hemat 5%.') ?></p>
                        <div class="bg-white rounded-3 d-inline-block px-3 py-2 mb-2">
                            <small class="text-muted d-block"><?= t('Kupon Bundlemu') ?></small>
                            <strong class="fs-5 text-warning" data-testid="bundle-coupon-code"><?= e($bundle['coupon']) ?></strong>
                        </div>
                        <div>
                            <a href="<?= $bundle['target'] ?>" class="btn btn-warning px-4"><?= t('Pesan Sekarang') ?> <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                        <small class="text-muted d-block mt-2"><i class="bi bi-clock me-1"></i><?= t('Berlaku') ?> <?= getBundleWindowHours() ?> <?= t('jam sejak booking pertama') ?></small>
                    </div>
                    <?php endif; ?>

                    <div class="text-start bg-light rounded-4 p-4 mb-4">
                        <h6 class="fw-semibold mb-3"><?= t('Detail Booking') ?></h6>
                        <table class="table table-borderless mb-0 small align-middle">
                            <tr><td class="text-muted ps-0"><?= t('Paket') ?></td><td class="fw-semibold"><?= e($booking['item_title']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Nama') ?></td><td class="fw-semibold"><?= e($booking['name']) ?></td></tr>
                            <?php if (!empty($booking['date_label'])): ?>
                            <tr><td class="text-muted ps-0"><?= t('Tanggal') ?></td><td class="fw-semibold"><?= formatDate($booking['date_label']) ?></td></tr>
                            <?php endif; ?>
                            <tr><td class="text-muted ps-0"><?= t('Peserta') ?></td><td class="fw-semibold"><?= $booking['qty_label'] ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Total Harga') ?></td><td class="fw-semibold text-primary"><?= formatRupiah($booking['total_price']) ?></td></tr>
                            <tr><td class="text-muted ps-0"><?= t('Status') ?></td><td>
                                <?php if ($paymentStatus === 'paid'): ?>
                                    <span class="badge bg-success"><?= t('Lunas') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?= t('Pending') ?></span>
                                <?php endif; ?>
                            </td></tr>
                        </table>
                    </div>

                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= t('Simpan kode booking dan link di atas untuk cek status pemesanan.') ?>
                        <br><?= tripayInstantEnabled() ? t('Lanjutkan pembayaran di bawah untuk konfirmasi instan.') : t('Kami akan menghubungi Anda via WhatsApp untuk konfirmasi.') ?>
                    </p>

                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <?php if ($paymentEnabled && $btype === 'tour' && $paymentStatus !== 'paid'): ?>
                            <?php
                            // Backlog #8: metode pembayaran tersimpan (1-click pay)
                            $savedMethods = [];
                            if (!empty($booking['user_id'])) {
                                require_once 'includes/saved-payments.php';
                                $savedMethods = getSavedPaymentMethods((int)$booking['user_id']);
                            }
                            ?>
                            <?php if (!empty($savedMethods)): ?>
                            <div class="w-100" data-testid="saved-methods">
                                <label class="form-label small fw-semibold"><?= t('Bayar dengan kartu tersimpan') ?></label>
                                <div class="d-flex gap-2 flex-wrap justify-content-center mb-2">
                                    <?php foreach ($savedMethods as $sm): ?>
                                    <button type="button" class="btn btn-outline-success btn-sm saved-pay-btn" data-method-id="<?= (int)$sm['id'] ?>" data-booking-id="<?= (int)$booking['id'] ?>">
                                        <i class="bi bi-credit-card-2-front me-1"></i><?= e($sm['brand'] ?: t('Kartu')) ?> <?= e($sm['masked_number'] ?? '') ?><?= $sm['is_default'] ? ' ★' : '' ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (($paymentGateway ?? tripayGateway()) === 'tripay'): ?>
                            <div class="w-100" data-testid="tripay-methods">
                                <label class="form-label small fw-semibold" for="tripayMethod"><?= t('Pilih channel pembayaran') ?></label>
                                <select id="tripayMethod" class="form-select form-select-sm mx-auto" style="max-width:320px">
                                    <?php foreach (['BRIVA'=>'BRI VA','BCAVA'=>'BCA VA','BNIVA'=>'BNI VA','MANDIRIVA'=>'Mandiri VA','PERMATAVA'=>'Permata VA','QRIS'=>'QRIS','ALFAMART'=>'Alfamart','INDOMARET'=>'Indomaret'] as $tcode => $tname): ?>
                                    <option value="<?= $tcode ?>"><?= e($tname) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <button type="button" id="payNowBtn" class="btn btn-success px-4" data-booking-id="<?= (int)$booking['id'] ?>" data-gateway="<?= e($paymentGateway ?? tripayGateway()) ?>">
                                <i class="bi bi-credit-card me-1"></i><?= t('Bayar Sekarang') ?>
                            </button>
                            <form method="POST" action="ajax/create-payment.php" id="createPaymentForm" style="display:none;"></form>
                        <?php endif; ?>
                        <a href="track.php?code=<?= urlencode($booking['booking_code']) ?>" class="btn btn-primary px-4"><i class="bi bi-binoculars me-1"></i><?= t('Tracking Booking') ?></a>
                        <a href="<?= $itemLink ?: 'tours.php' ?>" class="btn btn-outline-primary"><?= t('Lihat Detail') ?></a>
                    </div>

                    <?php if ($paymentEnabled && $btype === 'tour'): ?>
                    <?php if (!empty($paymentPayCode) && ($paymentGateway ?? '') === 'tripay'): ?>
                    <div class="alert alert-info text-start mt-3" data-testid="tripay-paycode">
                        <div class="small text-muted"><?= t('Kode bayar Tripay') ?></div>
                        <div class="fs-4 fw-bold"><?= e($paymentPayCode) ?></div>
                        <?php if (!empty($paymentCheckoutUrl)): ?>
                        <a href="<?= e($paymentCheckoutUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-2"><?= t('Buka halaman checkout') ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div id="paymentStatusArea" class="small mt-3" data-order-id="<?= e($paymentOrderId ?? '') ?>">
                        <span class="text-muted"><?= $paymentOrderId ? t('Menunggu pembayaran...') : '' ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes confetti-fall {
    0% { transform: translateY(-10px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
}
</style>
<?php require_once 'includes/footer-shared.php'; ?>
<script>
(function () {
    var btn = document.getElementById('payNowBtn');
    if (!btn) return;
    var statusArea = document.getElementById('paymentStatusArea');

    function pollStatus(orderId) {
        if (!orderId) return;
        var timer = setInterval(function () {
            fetch('<?= BASE_URL ?>/ajax/payment-status.php?order_id=' + encodeURIComponent(orderId))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status === 'paid') {
                        clearInterval(timer);
                        if (statusArea) statusArea.innerHTML = '<span class="text-success fw-bold"><?= t('Pembayaran diterima. Terima kasih!') ?></span>';
                        var badge = document.querySelector('.badge.bg-warning');
                        if (badge) { badge.className = 'badge bg-success'; badge.textContent = '<?= t('Lunas') ?>'; }
                        btn.remove();
                    }
                }).catch(function () {});
        }, 3000);
    }

    btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><?= t('Memproses...') ?>';
        fetch('<?= BASE_URL ?>/ajax/create-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'booking_type=tour&booking_id=' + btn.dataset.bookingId + (btn.dataset.gateway === 'tripay' ? '&method=' + encodeURIComponent((document.getElementById('tripayMethod') || {}).value || 'BRIVA') : '')
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

    if (statusArea && statusArea.dataset.orderId) pollStatus(statusArea.dataset.orderId);

    // Backlog #8: 1-click pay dengan metode tersimpan
    document.querySelectorAll('.saved-pay-btn').forEach(function (payBtn) {
        payBtn.addEventListener('click', function () {
            payBtn.disabled = true;
            payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><?= t('Memproses...') ?>';
            var fd = new FormData();
            fd.append('action', 'charge');
            fd.append('booking_type', 'tour');
            fd.append('booking_id', payBtn.dataset.bookingId);
            fd.append('method_id', payBtn.dataset.methodId);
            fetch('<?= BASE_URL ?>/ajax/saved-payments.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.success && d.paid) {
                        if (statusArea) statusArea.innerHTML = '<span class="text-success fw-bold"><?= t('Pembayaran diterima. Terima kasih!') ?></span>';
                        var badge = document.querySelector('.badge.bg-warning');
                        if (badge) { badge.className = 'badge bg-success'; badge.textContent = '<?= t('Lunas') ?>'; }
                        var snap = document.getElementById('payNowBtn'); if (snap) snap.remove();
                    } else {
                        payBtn.disabled = false;
                        payBtn.innerHTML = '<?= t('Bayar Sekarang') ?>';
                        if (statusArea) statusArea.innerHTML = '<span class="text-danger"><?= e(t('Pembayaran 1-klik gagal: ')) ?>' + (d.error || '') + '</span>';
                    }
                })
                .catch(function () {
                    payBtn.disabled = false;
                    payBtn.innerHTML = '<?= t('Bayar Sekarang') ?>';
                });
        });
    });
})();
</script>

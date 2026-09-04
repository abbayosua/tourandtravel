<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

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

$pageTitle = t('Booking Berhasil');
require_once 'includes/header-klook.php';
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
                            <tr><td class="text-muted ps-0"><?= t('Status') ?></td><td><span class="badge bg-warning text-dark"><?= t('Pending') ?></span></td></tr>
                        </table>
                    </div>

                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= t('Simpan kode booking dan link di atas untuk cek status pemesanan.') ?>
                        <br><?= t('Kami akan menghubungi Anda via WhatsApp untuk konfirmasi.') ?>
                    </p>

                    <div class="d-flex gap-2 justify-content-center">
                        <a href="track.php?code=<?= urlencode($booking['booking_code']) ?>" class="btn btn-primary px-4"><i class="bi bi-binoculars me-1"></i><?= t('Tracking Booking') ?></a>
                        <a href="<?= $itemLink ?: 'tours.php' ?>" class="btn btn-outline-primary"><?= t('Lihat Detail') ?></a>
                    </div>
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
<?php require_once 'includes/footer-klook.php'; ?>
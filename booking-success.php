<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$code = $_GET['code'] ?? '';

$stmt = db()->prepare("
    SELECT b.*, t.title as tour_title, t.slug as tour_slug, td.departure_date, td.return_date
    FROM bookings b
    JOIN tours t ON b.tour_id = t.id
    JOIN tour_dates td ON b.tour_date_id = td.id
    WHERE b.booking_code = ?
");
$stmt->execute([$code]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: tours.php');
    exit;
}

$pageTitle = 'Booking Berhasil';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-5">
                    <div class="display-1 text-success mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Booking Berhasil!</h3>
                    <p class="text-muted mb-3">Terima kasih, pemesanan Anda telah diterima.</p>

                    <div class="bg-primary text-white rounded-4 p-4 mb-4">
                        <small class="text-white-50">Kode Booking</small>
                        <div class="fs-2 fw-bold tracking-code"><?= e($booking['booking_code']) ?></div>
                        <div class="mt-2 small text-white-50">
                            <i class="bi bi-link-45deg me-1"></i>
                            <a href="track.php?code=<?= urlencode($booking['booking_code']) ?>" class="text-white">tourandtravel.web.id/track.php?code=<?= e($booking['booking_code']) ?></a>
                        </div>
                    </div>

                    <div class="text-start bg-light rounded-4 p-4 mb-4">
                        <h6 class="fw-semibold mb-3">Detail Booking</h6>
                        <table class="table table-borderless mb-0 small align-middle">
                            <tr><td class="text-muted ps-0">Paket Tour</td><td class="fw-semibold"><?= e($booking['tour_title']) ?></td></tr>
                            <tr><td class="text-muted ps-0">Tanggal Berangkat</td><td class="fw-semibold"><?= tglIndonesia($booking['departure_date']) ?></td></tr>
                            <tr><td class="text-muted ps-0">Peserta</td><td class="fw-semibold"><?= $booking['participants'] ?> orang</td></tr>
                            <tr><td class="text-muted ps-0">Total Harga</td><td class="fw-semibold text-primary"><?= formatRupiah($booking['total_price']) ?></td></tr>
                            <tr><td class="text-muted ps-0">Status</td><td><span class="badge bg-warning text-dark">Pending</span></td></tr>
                        </table>
                    </div>

                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Simpan kode booking dan link di atas untuk cek status pemesanan.
                        <br>Kami akan menghubungi Anda via WhatsApp untuk konfirmasi.
                    </p>

                    <div class="d-flex gap-2 justify-content-center">
                        <a href="track.php?code=<?= urlencode($booking['booking_code']) ?>" class="btn btn-primary px-4"><i class="bi bi-binoculars me-1"></i>Tracking Booking</a>
                        <a href="tours.php" class="btn btn-outline-primary">Lihat Tour Lainnya</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

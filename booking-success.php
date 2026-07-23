<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$bookingId = $_GET['id'] ?? 0;

$stmt = db()->prepare("
    SELECT b.*, t.title as tour_title, t.slug as tour_slug, td.departure_date, td.return_date
    FROM bookings b
    JOIN tours t ON b.tour_id = t.id
    JOIN tour_dates td ON b.tour_date_id = td.id
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
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
                    <p class="text-muted mb-4">Terima kasih, pemesanan Anda telah diterima.</p>

                    <div class="text-start bg-light rounded p-4 mb-4">
                        <h6 class="fw-semibold mb-3">Detail Booking</h6>
                        <table class="table table-borderless mb-0 small">
                            <tr>
                                <td class="text-muted ps-0">ID Booking</td>
                                <td class="fw-semibold">#<?= $booking['id'] ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Paket Tour</td>
                                <td class="fw-semibold"><?= e($booking['tour_title']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Tanggal Berangkat</td>
                                <td class="fw-semibold"><?= tglIndonesia($booking['departure_date']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Peserta</td>
                                <td class="fw-semibold"><?= $booking['participants'] ?> orang</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Total Harga</td>
                                <td class="fw-semibold text-primary"><?= formatRupiah($booking['total_price']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Status</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                            </tr>
                        </table>
                    </div>

                    <p class="small text-muted mb-3">
                        Kami akan menghubungi Anda via WhatsApp/email untuk konfirmasi pembayaran.
                        <br>Simpan ID Booking untuk referensi.
                    </p>

                    <div class="d-flex gap-2 justify-content-center">
                        <a href="tour-detail.php?slug=<?= e($booking['tour_slug']) ?>" class="btn btn-outline-primary">Kembali ke Tour</a>
                        <a href="tours.php" class="btn btn-primary">Lihat Tour Lainnya</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

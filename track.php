<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$code = trim($_GET['code'] ?? '');

$stmt = db()->prepare("
    SELECT b.*, t.title as tour_title, t.slug as tour_slug, t.price as tour_price,
           td.departure_date, td.return_date
    FROM bookings b
    JOIN tours t ON b.tour_id = t.id
    JOIN tour_dates td ON b.tour_date_id = td.id
    WHERE b.booking_code = ?
");
$stmt->execute([$code]);
$booking = $stmt->fetch();

$pageTitle = $booking ? 'Tracking: ' . $booking['booking_code'] : 'Tracking Booking';
require_once 'includes/header.php';
?>

<section class="py-5 bg-light" style="min-height: 70vh;">
    <div class="container">
        <?php if ($booking): ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 text-center">
                        <h5 class="fw-bold mb-1">Tracking Booking</h5>
                        <div class="fs-3 fw-bold text-primary my-2"><?= e($booking['booking_code']) ?></div>

                        <!-- Status Timeline -->
                        <div class="d-flex justify-content-center gap-4 my-4">
                            <div class="text-center">
                                <div class="rounded-circle bg-<?= $booking['status'] === 'pending' || $booking['status'] === 'confirmed' ? 'success' : 'secondary' ?> d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 40px; height: 40px;">
                                    <i class="bi bi-check-lg text-white"></i>
                                </div>
                                <small class="d-block" style="font-size: 10px;">Booking</small>
                                <small style="font-size: 10px;" class="text-muted"><?= date('d/m', strtotime($booking['created_at'])) ?></small>
                            </div>
                            <div class="text-center">
                                <div class="rounded-circle bg-<?= $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'secondary') ?> d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 40px; height: 40px;">
                                    <i class="bi bi-clock text-white"></i>
                                </div>
                                <small class="d-block" style="font-size: 10px;">Konfirmasi</small>
                            </div>
                            <div class="text-center">
                                <div class="rounded-circle bg-<?= $booking['status'] === 'confirmed' ? 'success' : 'secondary' ?> d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 40px; height: 40px;">
                                    <i class="bi bi-credit-card text-white"></i>
                                </div>
                                <small class="d-block" style="font-size: 10px;">Pembayaran</small>
                            </div>
                            <div class="text-center">
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 40px; height: 40px;">
                                    <i class="bi bi-check2-all text-white"></i>
                                </div>
                                <small class="d-block" style="font-size: 10px;">Selesai</small>
                            </div>
                        </div>

                        <div class="mb-0">
                            <?php if ($booking['status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark fs-6 px-4 py-2">Menunggu Konfirmasi</span>
                            <?php elseif ($booking['status'] === 'confirmed'): ?>
                                <span class="badge bg-success fs-6 px-4 py-2">✓ Dikonfirmasi</span>
                            <?php elseif ($booking['status'] === 'cancelled'): ?>
                                <span class="badge bg-danger fs-6 px-4 py-2">✕ Dibatalkan</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">Detail Pesanan</h6>
                        <table class="table table-borderless mb-0 small">
                            <tr><td class="text-muted ps-0">Paket Tour</td><td class="fw-semibold"><?= e($booking['tour_title']) ?></td></tr>
                            <tr><td class="text-muted ps-0">Nama</td><td class="fw-semibold"><?= e($booking['name']) ?></td></tr>
                            <tr><td class="text-muted ps-0">WhatsApp</td><td class="fw-semibold"><?= e($booking['phone']) ?></td></tr>
                            <tr><td class="text-muted ps-0">Keberangkatan</td><td class="fw-semibold"><?= tglIndonesia($booking['departure_date']) ?></td></tr>
                            <tr><td class="text-muted ps-0">Peserta</td><td class="fw-semibold"><?= $booking['participants'] ?> orang</td></tr>
                            <tr><td class="text-muted ps-0">Total</td><td class="fw-semibold text-primary"><?= formatRupiah($booking['total_price']) ?></td></tr>
                            <?php if ($booking['passport_photo']): ?>
                            <tr><td class="text-muted ps-0">Foto Paspor</td>
                                <td><a href="uploads/passports/<?= e($booking['passport_photo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat <i class="bi bi-box-arrow-up-right ms-1"></i></a></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($booking['notes']): ?>
                            <tr><td class="text-muted ps-0">Catatan</td><td class="fw-semibold"><?= nl2br(e($booking['notes'])) ?></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <p class="text-center mt-3">
                    <a href="tour-detail.php?slug=<?= e($booking['tour_slug']) ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Kembali ke halaman tour</a>
                </p>
            </div>
        </div>
        <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-6 text-center py-5">
                <i class="bi bi-search fs-1 text-muted"></i>
                <h5 class="mt-3 fw-bold">Cari Booking</h5>
                <p class="text-muted small">Masukkan kode booking untuk cek status pemesanan</p>
                <form method="GET" class="mt-3">
                    <div class="input-group">
                        <input type="text" name="code" class="form-control" placeholder="Contoh: TAT-7A2B1" required>
                        <button class="btn btn-primary" type="submit">Cari</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

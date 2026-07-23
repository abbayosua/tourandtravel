<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$tour = getTourBySlug($slug);

if (!$tour) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = 'Tour Tidak Ditemukan';
    require_once 'includes/header.php';
    echo '<div class="container py-5 text-center"><h3>Tour tidak ditemukan</h3><a href="tours.php" class="btn btn-primary mt-3">Kembali ke Catalog</a></div>';
    require_once 'includes/footer.php';
    exit;
}

$pageTitle = $tour['title'];
$tourDates = getTourDates($tour['id']);
$itineraries = getItineraries($tour['id']);

// Proses booking form
$bookingMessage = '';
$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tourDateId = $_POST['tour_date_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $participants = (int)($_POST['participants'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    // Validasi
    $errors = [];
    if (!$name) $errors[] = 'Nama harus diisi';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (!$phone) $errors[] = 'No. telepon harus diisi';
    if ($participants < 1) $errors[] = 'Jumlah peserta minimal 1';

    // Validasi slot
    $sisaSlot = getSisaSlot($tourDateId);
    if ($sisaSlot < $participants) {
        $errors[] = "Maaf, sisa slot hanya $sisaSlot kursi";
    }

    // Ambil data tanggal untuk harga
    $stmtDate = db()->prepare("SELECT * FROM tour_dates WHERE id = ? AND tour_id = ?");
    $stmtDate->execute([$tourDateId, $tour['id']]);
    $selectedDate = $stmtDate->fetch();

    if (!$selectedDate) {
        $errors[] = 'Tanggal keberangkatan tidak valid';
    }

    if (empty($errors)) {
        $totalPrice = $tour['price'] * $participants;

        $stmt = db()->prepare("INSERT INTO bookings (tour_id, tour_date_id, name, email, phone, participants, total_price, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$tour['id'], $tourDateId, $name, $email, $phone, $participants, $totalPrice, $notes]);
        $bookingId = db()->lastInsertId();

        header("Location: booking-success.php?id=$bookingId");
        exit;
    } else {
        $bookingError = implode('<br>', $errors);
    }
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="tours.php">Paket Tour</a></li>
            <li class="breadcrumb-item active"><?= e($tour['title']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Gambar -->
            <div class="card border-0 shadow-sm mb-4">
                <img src="<?= getTourImage($tour, 'large') ?>" class="card-img-top" style="max-height: 450px; object-fit: cover;" alt="<?= e($tour['title']) ?>">
            </div>

            <!-- Info Tour -->
            <h2 class="fw-bold"><?= e($tour['title']) ?></h2>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <span class="badge bg-primary"><?= e($tour['category']) ?></span>
                <span class="text-muted"><i class="bi bi-people-fill me-1"></i> Max <?= $tour['max_participants'] ?> peserta</span>
            </div>
            <p class="lead"><?= nl2br(e($tour['description'])) ?></p>

            <!-- Itinerary -->
            <?php if (count($itineraries) > 0): ?>
            <h4 class="fw-bold mt-5 mb-3"><i class="bi bi-journal-text me-2"></i>Itinerary</h4>
            <div class="timeline">
                <?php foreach ($itineraries as $it): ?>
                <div class="card border-0 shadow-sm mb-3 itinerary-card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="itinerary-day me-3 text-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <strong><?= $it['day_number'] ?></strong>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="fw-semibold">Hari <?= $it['day_number'] ?>: <?= e($it['title']) ?></h5>
                                <p class="mb-2"><?= nl2br(e($it['description'])) ?></p>
                                <?php if ($it['meals']): ?>
                                    <span class="badge bg-success me-1"><i class="bi bi-cup-hot"></i> <?= e($it['meals']) ?></span>
                                <?php endif; ?>
                                <?php if ($it['accommodation']): ?>
                                    <span class="badge bg-info"><i class="bi bi-building"></i> <?= e($it['accommodation']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Harga -->
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 80px; z-index: 100;">
                <div class="card-body">
                    <h4 class="fw-bold text-primary mb-0"><?= formatRupiah($tour['price']) ?></h4>
                    <p class="text-muted">/ orang</p>

                    <!-- Pilih Tanggal -->
                    <?php if (count($tourDates) > 0): ?>
                    <hr>
                    <h6 class="fw-semibold">Jadwal Keberangkatan</h6>
                    <div class="mb-3">
                        <?php foreach ($tourDates as $td): ?>
                            <?php $sisa = getSisaSlot($td['id']); ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom date-item">
                                <div>
                                    <strong><?= tglIndonesia($td['departure_date']) ?></strong>
                                    <span class="d-block small text-muted"><?= tglIndonesia($td['return_date']) ?></span>
                                </div>
                                <span class="badge <?= $sisa > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $sisa > 0 ? "$sisa slot" : 'Penuh' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Form Booking -->
                    <hr>
                    <h6 class="fw-semibold">Booking Sekarang</h6>
                    <?php if ($bookingError): ?>
                        <div class="alert alert-danger py-2 small"><?= $bookingError ?></div>
                    <?php endif; ?>
                    <?php if ($bookingMessage): ?>
                        <div class="alert alert-success py-2 small"><?= $bookingMessage ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-2">
                            <label class="form-label small">Pilih Tanggal</label>
                            <select name="tour_date_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Tanggal --</option>
                                <?php foreach ($tourDates as $td): ?>
                                    <?php $sisa = getSisaSlot($td['id']); ?>
                                    <?php if ($sisa > 0): ?>
                                    <option value="<?= $td['id'] ?>"><?= tglIndonesia($td['departure_date']) ?> (<?= $sisa ?> slot)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">No. Telepon/WA</label>
                            <input type="text" name="phone" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Jumlah Peserta</label>
                            <input type="number" name="participants" class="form-control form-control-sm" min="1" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Catatan (opsional)</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">Pesan Sekarang</button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i> Belum ada jadwal keberangkatan tersedia
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

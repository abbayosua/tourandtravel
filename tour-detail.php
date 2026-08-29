<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$tour = getTourBySlug($slug);

if (!$tour) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = t('Tour Tidak Ditemukan');
    require_once 'includes/header.php';
    echo '<div class="container py-5 text-center"><h3>' . t('Tour tidak ditemukan') . '</h3><a href="tours.php" class="btn btn-primary mt-3">' . t('Kembali ke Catalog') . '</a></div>';
    require_once 'includes/footer.php';
    exit;
}

$pageTitle = $tour['title'];
$tourDates = getTourDates($tour['id']);
$itineraries = getItineraries($tour['id']);

// Proses booking form
$bookingMessage = '';
$bookingError = '';
$bookingCode = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $tourDateId = (int)($_POST['tour_date_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $participants = (int)($_POST['participants'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    // Validasi
    $errors = [];
    if (!$name) $errors[] = t('Nama harus diisi');
    if (!$phone) $errors[] = t('No. WhatsApp harus diisi');
    if ($participants < 1) $errors[] = t('Jumlah peserta minimal 1');

    // Validasi slot
    $sisaSlot = getSisaSlot($tourDateId);
    if ($sisaSlot < $participants) {
        $errors[] = t('Maaf, sisa slot hanya') . " $sisaSlot " . t('kursi');
    }

    // Validasi tanggal
    $stmtDate = db()->prepare("SELECT * FROM tour_dates WHERE id = ? AND tour_id = ?");
    $stmtDate->execute([$tourDateId, $tour['id']]);
    $selectedDate = $stmtDate->fetch();
    if (!$selectedDate) $errors[] = t('Tanggal keberangkatan tidak valid');

    // Upload passport
    $passportFile = '';
    if (empty($errors) && isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadWebP($_FILES['passport_photo'], __DIR__ . '/uploads/passports');
        if ($upload['success']) {
            $passportFile = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    } else {
        $errors[] = 'Foto paspor wajib diupload';
    }

    if (empty($errors)) {
        $totalPrice = $tour['price'] * $participants;
        $bookingCode = generateBookingCode();

        $stmt = db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, notes, passport_photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$bookingCode, $tour['id'], $tourDateId, $name, $email, $phone, $participants, $totalPrice, $notes, $passportFile]);

        // Kirim notifikasi WhatsApp ke admin
        require_once 'includes/send-wa.php';
        sendBookingNotification($tour, $bookingCode, $name, $phone, $participants, $totalPrice, tglIndonesia($selectedDate['departure_date']));

        header("Location: booking-success.php?code=$bookingCode");
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
                <img src="<?= getTourImage($tour, 'large') ?>" onerror="this.src='<?= getTourImageFallback($tour, 'large') ?>'" class="card-img-top" style="max-height: 450px; object-fit: cover; cursor: pointer;" alt="<?= e($tour['title']) ?>" onclick="openGallery(0)">
            </div>

            <!-- Info Tour -->
            <h2 class="fw-bold"><?= e(t($tour['title'], null, $tour['content_language'] ?? 'id')) ?></h2>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <span class="badge bg-primary"><?= e($tour['category']) ?></span>
                <span class="text-muted"><i class="bi bi-people-fill me-1"></i> <?= t('Max') ?> <?= $tour['max_participants'] ?> <?= t('peserta') ?></span>
                <span class="text-muted"><?= renderStars($tour['rating']) ?> <?= $tour['rating'] ?> (<?= $tour['total_reviews'] ?> <?= t('ulasan') ?>)</span>
            </div>
            <p class="lead"><?= nl2br(e(t($tour['description'], null, $tour['content_language'] ?? 'id'))) ?></p>

            <!-- Gallery -->
            <?php $galleryImages = getTourGalleryUrls($tour); ?>
            <h5 class="fw-bold mt-4 mb-3"><i class="bi bi-images me-2"></i><?= t('Galeri Foto') ?></h5>
            <div class="row g-2 mb-4">
                <?php foreach ($galleryImages as $i => $galleryUrl):
                    $thumbUrl = str_contains($galleryUrl, 'loremflickr.com') ? str_replace('800/600', '320/240', $galleryUrl) : $galleryUrl;
                ?>
                <div class="col-4 col-md-2">
                    <img src="<?= e($thumbUrl) ?>" class="w-100 rounded-3 gallery-thumb" style="height: 100px; object-fit: cover; cursor: pointer;" alt="<?= e($tour['title']) ?>" loading="lazy" onerror="this.remove()" data-index="<?= $i ?>" onclick="openGallery(<?= $i ?>)">
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Gallery Modal -->
            <div class="modal fade" id="galleryModal" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content bg-dark border-0">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center py-2">
                            <div id="galleryCarousel" class="carousel slide">
                                <div class="carousel-inner">
                                    <?php foreach ($galleryImages as $i => $img): ?>
                                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                        <img src="<?= $img ?>" class="img-fluid rounded" style="max-height: 75vh; object-fit: contain;" alt="">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer border-0 justify-content-center">
                            <div class="d-flex gap-2">
                                <?php foreach ($galleryImages as $i => $img): ?>
                                <img src="<?= $img ?>" class="rounded-2 gallery-dot <?= $i === 0 ? 'active' : '' ?>" style="width: 50px; height: 40px; object-fit: cover; cursor: pointer; opacity: 0.6;" onerror="this.remove()" onclick="slideTo(<?= $i ?>)">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fasilitas -->
            <h5 class="fw-bold mt-4 mb-3"><i class="bi bi-check2-square me-2"></i><?= t('Fasilitas Termasuk') ?></h5>
            <div class="row g-2 mb-4">
                <?php foreach (getTourFacilities() as $f): ?>
                <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $f['icon'] ?> text-primary"></i>
                        <small><?= $f['label'] ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Peta -->
            <h5 class="fw-bold mt-4 mb-3"><i class="bi bi-geo-alt me-2"></i><?= t('Lokasi') ?></h5>
            <div class="rounded-3 overflow-hidden mb-4 border">
                <?php
                    $mapKw = urlencode(preg_replace('/\d+[dD]\d+[nN]?/i', '', $tour['title']));
                    $lat = -6.2 + (crc32($tour['id']) % 1000) / 1000;
                    $lng = 106.8 + (crc32($tour['id'] + 999) % 1000) / 1000;
                ?>
                <img src="https://maps.googleapis.com/maps/api/staticmap?center=<?= $mapKw ?>&zoom=5&size=800x200&maptype=roadmap&markers=color:red|<?= $mapKw ?>&key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8" alt="Peta <?= e($tour['title']) ?>" class="w-100" style="height: 200px; object-fit: cover;" onerror="this.style.display='none'">
            </div>

            <!-- Itinerary -->
            <?php if (count($itineraries) > 0): ?>
            <h4 class="fw-bold mt-5 mb-3"><i class="bi bi-journal-text me-2"></i><?= t('Itinerary') ?></h4>
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
                                <h5 class="fw-semibold"><?= t('Hari') ?> <?= $it['day_number'] ?>: <?= e($it['title']) ?></h5>
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

            <!-- Reviews -->
            <?php if (isset($_GET['review']) && $_GET['review'] === 'success'): ?>
                <div class="alert alert-success py-2"><?= t('Ulasan berhasil dikirim, terima kasih!') ?></div>
            <?php endif; ?>
            <h5 class="fw-bold mt-5 mb-3"><i class="bi bi-chat-square-text me-2"></i><?= t('Ulasan') ?></h5>
            <?php
                $reviews = getTourReviews($tour['id']);
                $realRating = getRealRating($tour['id']);
                $realCount = getReviewCount($tour['id']);
            ?>
            <?php if ($realCount > 0): ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="fs-4 fw-bold text-warning"><?= $realRating ?></span>
                    <span class="text-warning"><?= renderStars($realRating) ?></span>
                    <span class="text-muted small"><?= t('dari') ?> <?= $realCount ?> <?= t('ulasan') ?></span>
                </div>
                <div class="row g-3 mb-4">
                <?php foreach ($reviews as $r): ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold small"><?= e($r['user_name']) ?></span>
                                    <span class="text-warning small"><?= renderStars($r['rating']) ?></span>
                                </div>
                                <p class="small text-muted mb-0"><?= nl2br(e($r['comment'])) ?></p>
                                <small class="text-muted" style="font-size: 10px;"><?= date('d M Y', strtotime($r['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-4"><?= t('Belum ada ulasan untuk tour ini.') ?></p>
            <?php endif; ?>

            <!-- Review Form -->
            <?php if (isLoggedIn() && canReview($_SESSION['user_id'], $tour['id'])): ?>
                <div class="card border-0 shadow-sm mb-4 bg-light">
                    <div class="card-body p-3">
                        <h6 class="fw-semibold mb-2"><?= t('Tulis Ulasan') ?></h6>
                        <form method="POST" action="review-submit.php">
                            <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
                            <input type="hidden" name="slug" value="<?= e($tour['slug']) ?>">
                            <div class="mb-2">
                                <label class="form-label small"><?= t('Rating') ?></label>
                                <div class="rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                                    <label for="star<?= $i ?>" class="text-warning fs-5" style="cursor: pointer;"><i class="bi bi-star<?= $i === 5 ? '-fill' : '' ?>"></i></label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mb-2">
                                <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="<?= t('Bagikan pengalaman Anda...') ?>" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm"><?= t('Kirim Ulasan') ?></button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Harga -->
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 80px; z-index: 100;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 small mb-2">
                        <?= renderStars($tour['rating']) ?>
                        <span class="text-muted">(<?= $tour['total_reviews'] ?> <?= t('ulasan') ?>)</span>
                    </div>
                    <?php $diskon = getDiskonPersen($tour); ?>
                    <h4 class="fw-bold text-primary mb-0"><?= formatCurrencySpan($tour['price'], $tour['price_currency'] ?? 'IDR') ?></h4>
                    <?php if ($diskon > 0): ?>
                        <small class="text-decoration-line-through text-muted"><?= formatCurrencySpan($tour['original_price']) ?></small>
                        <span class="badge bg-danger ms-1">-<?= $diskon ?>%</span>
                    <?php endif; ?>
                    <p class="text-muted">/ <?= t('orang') ?></p>

                    <!-- Pilih Tanggal -->
                    <?php if (count($tourDates) > 0): ?>
                    <hr>
                    <h6 class="fw-semibold"><?= t('Jadwal Keberangkatan') ?></h6>
                    <div class="mb-3">
                        <?php foreach ($tourDates as $td): ?>
                            <?php $sisa = getSisaSlot($td['id']); ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom date-item">
                                <div>
                                    <strong><?= tglIndonesia($td['departure_date']) ?></strong>
                                    <span class="d-block small text-muted"><?= tglIndonesia($td['return_date']) ?></span>
                                </div>
                                <span class="badge <?= $sisa > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $sisa > 0 ? "$sisa " . t('slot') : t('Penuh') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Form Booking -->
                    <hr>
                    <h6 class="fw-semibold"><?= t('Booking Sekarang') ?></h6>
                    <?php if ($bookingError): ?>
                        <div class="alert alert-danger py-2 small"><?= $bookingError ?></div>
                    <?php endif; ?>
                    <?php if ($bookingMessage): ?>
                        <div class="alert alert-success py-2 small"><?= $bookingMessage ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_submitted" value="1">
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Pilih Tanggal') ?></label>
                            <select name="tour_date_id" class="form-select form-select-sm" required>
                                <option value=""><?= t('-- Pilih Tanggal --') ?></option>
                                <?php foreach ($tourDates as $td): ?>
                                    <?php $sisa = getSisaSlot($td['id']); ?>
                                    <?php if ($sisa > 0): ?>
                                    <option value="<?= $td['id'] ?>"><?= tglIndonesia($td['departure_date']) ?> (<?= $sisa ?> <?= t('slot') ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Nama Lengkap') ?></label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Email (opsional)') ?></label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="email@contoh.com">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('No. WhatsApp') ?></label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="0812xxxx" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Jumlah Peserta') ?></label>
                            <input type="number" name="participants" class="form-control form-control-sm" min="1" value="1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= t('Upload Foto Paspor') ?></label>
                            <input type="file" name="passport_photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required>
                            <div class="form-text"><?= t('Format JPG/PNG/WebP, max 2MB') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small"><?= t('Catatan (opsional)') ?></label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold" id="bookingSubmitBtn" onclick="var btn=this;btn.disabled=true;btn.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\'></span>Memproses...';setTimeout(function(){btn.form.submit();},100);return false;">Pesan Sekarang</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap === 'undefined') return;
    window._galleryModal = new bootstrap.Modal(document.getElementById('galleryModal'));
    window._galleryCarousel = new bootstrap.Carousel(document.getElementById('galleryCarousel'), { interval: false });
    var el = document.getElementById('galleryCarousel');
    if (el) {
        el.addEventListener('slid.bs.carousel', function(e) {
            updateDots(e.to);
        });
    }
});

function openGallery(index) {
    if (!window._galleryCarousel) return;
    window._galleryCarousel.to(index);
    window._galleryModal.show();
    updateDots(index);
}

function slideTo(index) {
    if (!window._galleryCarousel) return;
    window._galleryCarousel.to(index);
    updateDots(index);
}

function updateDots(index) {
    document.querySelectorAll('.gallery-dot').forEach(function(el, i) {
        el.style.opacity = i === index ? '1' : '0.6';
    });
}
</script>

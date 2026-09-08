<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/seo.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM hotels WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$hotel = $stmt->fetch();
if (!$hotel) { header('Location: hotels.php'); exit; }

$pageTitle = tContent($hotel, 'name');
$checkin = $_GET['checkin'] ?? date('Y-m-d');
$checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+2 days'));
$guests = (int)($_GET['guests'] ?? 2);

$bookingSuccess = '';
$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ci = $_POST['checkin'] ?? $checkin;
    $co = $_POST['checkout'] ?? $checkout;
    $rooms = (int)($_POST['rooms'] ?? 1);
    $g = (int)($_POST['guests'] ?? $guests);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roomId = (int)($_POST['room_id'] ?? ($_POST['room_id_select'] ?? 0));
    $room = null;
    if ($roomId > 0) {
        $roomStmt = db()->prepare("SELECT * FROM hotel_rooms WHERE id = ? AND hotel_id = ? AND is_active = 1");
        $roomStmt->execute([$roomId, $hotel['id']]);
        $room = $roomStmt->fetch();
    }
    if ($ci && $co && $name && $phone) {
        $overlapStmt = db()->prepare("SELECT COUNT(*) FROM hotel_bookings WHERE hotel_id = ? AND ? < checkout AND checkin < ?");
        $overlapStmt->execute([$hotel['id'], $ci, $co]);
        if ($overlapStmt->fetchColumn() > 0) {
            $bookingError = 'Tanggal sudah dibooking untuk hotel ini. Pilih tanggal lain.';
        } else {
            $nights = max(1, (strtotime($co) - strtotime($ci)) / 86400);
            $nightly = [];
            $cur = strtotime($ci);
            while ($cur < strtotime($co)) {
                $d = date('Y-m-d', $cur);
                $nightly[] = $room ? (float)$room['rate'] : (float)getPriceForDate('hotel', $hotel['id'], $d, $hotel['price_per_night']);
                $cur = strtotime('+1 day', $cur);
            }
            $total = array_sum($nightly) * $rooms;
            $walletDeduct = 0;
            if (!empty($_SESSION['user_id']) && !empty($_POST['use_wallet'])) {
                require_once 'includes/wallet.php';
                $balance = getWalletBalance($_SESSION['user_id']);
                if ($balance > 0) {
                    $walletDeduct = min($balance, $total);
                    $total -= $walletDeduct;
                }
            }
            $insert = db()->prepare("INSERT INTO hotel_bookings (hotel_id, room_id, user_id, checkin, checkout, rooms, guests, name, phone, email, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$hotel['id'], $room['id'] ?? null, $_SESSION['user_id'] ?? null, $ci, $co, $rooms, $g, $name, $phone, $email ?: null, $total]);
            $bookingId = (int)db()->lastInsertId();
            if ($walletDeduct > 0 && !empty($_SESSION['user_id'])) {
                require_once 'includes/wallet.php';
                spendWallet($_SESSION['user_id'], $walletDeduct, 'hotel_booking', $bookingId);
            }
            $bookingSuccess = "Booking berhasil! Total: " . formatRupiah($total);
        }
    }
}

// Similar hotels
$similar = db()->prepare("SELECT * FROM hotels WHERE city = ? AND id != ? AND is_active = 1 LIMIT 3");
$similar->execute([$hotel['city'], $hotel['id']]);
$similar = $similar->fetchAll();

$nights = max(1, (strtotime($checkout) - strtotime($checkin)) / 86400);
$nightlyPrices = [];
$cur = strtotime($checkin);
while ($cur < strtotime($checkout)) {
    $d = date('Y-m-d', $cur);
    $nightlyPrices[$d] = (float)getPriceForDate('hotel', $hotel['id'], $d, $hotel['price_per_night']);
    $cur = strtotime('+1 day', $cur);
}
$totalPrice = array_sum($nightlyPrices);
$hotelRooms = db()->prepare("SELECT * FROM hotel_rooms WHERE hotel_id = ? AND is_active = 1 ORDER BY rate ASC");
$hotelRooms->execute([$hotel['id']]);
$hotelRooms = $hotelRooms->fetchAll();
$hotelCalendar = [];
foreach (db()->query("SELECT date, price FROM price_calendar WHERE item_type = 'hotel' AND item_id = " . (int)$hotel['id'] . " AND date >= CURDATE() AND date <= CURDATE() + INTERVAL 90 DAY ORDER BY date")->fetchAll() as $pcRow) {
    $hotelCalendar[] = ['date' => $pcRow['date'], 'price' => (float)$pcRow['price']];
}

require_once 'includes/components/breadcrumb.php';
// SEO
$metaDesc = mb_substr(trim(strip_tags((string)tContent($hotel, 'description'))), 0, 160);
$jsonLd = seoHotel($hotel);
// SEO
$metaDesc = mb_substr(trim(strip_tags((string)tContent($hotel, 'description'))), 0, 160);
$jsonLd = seoHotel($hotel);
require_once 'includes/header-klook.php';
?>
<section class="py-4 bg-light">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('Hotel'), 'url' => 'hotels.php'],
            ['label' => $hotel['city'], 'url' => 'hotels.php?city=' . urlencode($hotel['city'])],
            ['label' => tContent($hotel, 'name'), 'url' => null],
        ]); ?>

        <div class="row">
            <!-- Gallery Grid -->
            <div class="col-12 mb-3">
                <div class="row g-2">
                    <div class="col-md-8">
                        <img src="https://placehold.co/800x400?text=<?= urlencode($hotel['name']) ?>" class="w-100 rounded-4 shadow-sm" style="height: 350px; object-fit: cover;" alt="">
                    </div>
                    <div class="col-md-4">
                        <div class="row g-2">
                            <?php for ($i=1; $i<=2; $i++): ?>
                            <div class="col-6 col-md-12">
                                <img src="https://placehold.co/400x200?text=Gallery+<?= $i ?>" class="w-100 rounded-3 shadow-sm" style="height: 170px; object-fit: cover;" alt="">
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="fw-bold mb-1"><?= e(tContent($hotel, 'name')) ?></h4>
                                <div class="d-flex gap-3 align-items-center">
                                    <span class="text-warning"><?= str_repeat('★', $hotel['star_rating']) ?><?= str_repeat('☆', 5 - $hotel['star_rating']) ?></span>
                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($hotel['city']) ?></small>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h6 class="fw-semibold"><?= t('Fasilitas Hotel') ?></h6>
                        <div class="row g-2 mb-3">
                            <?php 
                            $fasilitas = [t('WiFi Gratis'),t('Kolam Renang'),t('AC'),t('Restoran'),t('Parkir'),t('Gym'),t('Spa'),t('Layanan Kamar'),t('Sarapan'),t('Bandara')];
                            foreach ($fasilitas as $f): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 12px;"></i>
                                    <small><?= $f ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-muted small"><?= nl2br(e(tContent($hotel, 'description'))) ?></p>
                    </div>
                </div>

                <!-- Room Types -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-door-open me-2"></i><?= t('Pilih Tipe Kamar') ?></h6>
                        <?php if (count($hotelRooms) > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" data-testid="room-types-table">
                                <thead><tr class="small text-muted">
                                    <th><?= t('Tipe Kamar') ?></th>
                                    <th><?= t('Tipe Kasur') ?></th>
                                    <th><?= t('Kapasitas') ?></th>
                                    <th><?= t('Fasilitas') ?></th>
                                    <th class="text-end"><?= t('Harga/Malam (Rp)') ?></th>
                                    <th></th>
                                </tr></thead>
                                <tbody>
                                <?php foreach ($hotelRooms as $hr): ?>
                                    <tr data-room-id="<?= $hr['id'] ?>" data-room-rate="<?= (float)$hr['rate'] ?>" data-room-stock="<?= (int)$hr['stock'] ?>" data-room-breakfast="<?= (int)$hr['breakfast'] ?>" data-room-refundable="<?= (int)$hr['refundable'] ?>" data-room-max-guest="<?= (int)$hr['max_guest'] ?>">
                                        <td>
                                            <strong><?= e(getCurrentLang() === 'en' && $hr['name_en'] ? $hr['name_en'] : $hr['name']) ?></strong>
                                            <?php if ($hr['stock'] <= 2): ?><span class="badge bg-warning text-dark ms-1"><?= str_replace(':n', (string)$hr['stock'], t('Sisa :n')) ?></span><?php endif; ?>
                                        </td>
                                        <td class="small"><?= t(ucfirst($hr['bed_type'])) ?></td>
                                        <td class="small"><?= (int)$hr['max_guest'] ?> <?= t('Tamu') ?></td>
                                        <td class="small">
                                            <?php if ($hr['breakfast']): ?><span class="badge bg-success-subtle text-success me-1"><?= t('Sarapan') ?></span><?php endif; ?>
                                            <?php if ($hr['refundable']): ?><span class="badge bg-primary-subtle text-primary"><?= t('Refundable') ?></span><?php else: ?><span class="badge bg-secondary-subtle text-secondary"><?= t('Non-refundable') ?></span><?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-primary"><?= formatRupiah($hr['rate']) ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary room-select-btn" data-room="<?= $hr['id'] ?>" <?= $hr['stock'] < 1 ? 'disabled' : '' ?>>
                                                <?= $hr['stock'] < 1 ? t('Habis') : t('Pilih') ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="text-muted small"><?= t('Belum ada tipe kamar') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Map -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-geo-alt me-2"></i><?= t('Lokasi') ?></h6>
                        <div class="rounded-3 overflow-hidden border">
                            <iframe width="100%" height="250" frameborder="0" style="border:0;" 
                                src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?= urlencode($hotel['name'] . ' ' . $hotel['city']) ?>&center=<?= $hotel['lat'] ?? '-6.2' ?>,<?= $hotel['lng'] ?? '106.8' ?>&zoom=14" 
                                allowfullscreen loading="lazy">
                            </iframe>
                        </div>
                    </div>
                </div>

                <!-- Similar Hotels -->
                <?php if (count($similar) > 0): ?>
                <h5 class="fw-bold mb-3"><?= str_replace(':city', e($hotel['city']), t('Hotel Lain di :city')) ?></h5>
                <div class="row g-3 mb-4">
                    <?php foreach ($similar as $s): ?>
                    <div class="col-md-4">
                        <a href="hotel-detail.php?slug=<?= e($s['slug']) ?>" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100">
                                <img src="https://placehold.co/400x200?text=<?= urlencode($s['name']) ?>" class="card-img-top" style="height: 140px; object-fit: cover;" alt="">
                                <div class="card-body p-2">
                                    <h6 class="fw-semibold small mb-0 text-dark"><?= e($s['name']) ?></h6>
                                    <span class="text-warning" style="font-size: 11px;"><?= str_repeat('★', $s['star_rating']) ?></span>
                                    <div class="fw-bold text-primary small mt-1"><?= formatRupiah($s['price_per_night']) ?><small class="fw-normal text-muted">/<?= t('malam') ?></small></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Booking Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-primary mb-3"><?= formatRupiah($hotel['price_per_night']) ?> <small class="fw-normal text-muted fs-6">/malam</small></h5>

                        <?php if ($bookingSuccess): ?>
                            <div class="alert alert-success py-2 small"><?= $bookingSuccess ?></div>
                        <?php endif; ?>
                        <?php if ($bookingError): ?>
                            <div class="alert alert-danger py-2 small"><?= $bookingError ?></div>
                        <?php endif; ?>

                        <?php if (!isLoggedIn()): ?>
                            <div class="alert alert-warning py-2 small mb-2"><i class="bi bi-info-circle me-1"></i><?= t('Anda dapat booking sebagai tamu.') ?></div>
                        <?php endif; ?>
                        <form method="POST" id="hotelBookingForm">
                            <input type="hidden" name="room_id" id="roomIdInput" value="">
                            <input type="hidden" name="promo_code_rate" id="promoRateInput" value="0">
                            <div class="mb-2">
                                <label class="form-label small"><?= t('Tipe Kamar') ?></label>
                                <select name="room_id_select" id="roomSelect" class="form-select form-select-sm" data-testid="room-select">
                                    <option value=""><?= t('Pilih tipe kamar') ?></option>
                                    <?php foreach ($hotelRooms as $hr): if ($hr['stock'] < 1) continue; ?>
                                        <option value="<?= $hr['id'] ?>" data-rate="<?= (float)$hr['rate'] ?>" data-breakfast="<?= (int)$hr['breakfast'] ?>" data-refundable="<?= (int)$hr['refundable'] ?>" data-max-guest="<?= (int)$hr['max_guest'] ?>" data-stock="<?= (int)$hr['stock'] ?>">
                                            <?= e(getCurrentLang() === 'en' && $hr['name_en'] ? $hr['name_en'] : $hr['name']) ?> — <?= formatRupiah($hr['rate']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="small text-muted mt-1" id="roomBadges" data-testid="room-badges"></div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small"><?= t('Kode Promo (opsional)') ?></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="promo_code" class="form-control klook-promo-input" placeholder="HEMAT10" id="promoCodeHotel" autocomplete="off">
                                    <button type="button" class="btn btn-outline-primary klook-promo-btn" onclick="applyPromo('promoCodeHotel','promoResultHotel',<?= (float)$hotel['price_per_night'] ?>)"><?= t('Pakai') ?></button>
                                </div>
                                <div class="klook-promo-result small mt-1" id="promoResultHotel"></div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small"><?= t('Check-in') ?></label>
                                <input type="date" name="checkin" class="form-control" value="<?= e($checkin) ?>" onchange="updateTotal()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small"><?= t('Check-out') ?></label>
                                <input type="date" name="checkout" class="form-control" value="<?= e($checkout) ?>" onchange="updateTotal()">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small"><?= t('Kamar') ?></label>
                                    <select name="rooms" id="roomsSelect" class="form-select" onchange="updateTotal()">
                                        <?php for ($r=1; $r<=5; $r++): ?>
                                        <option value="<?= $r ?>"><?= $r ?> <?= t('Kamar') ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small"><?= t('Tamu') ?></label>
                                    <select name="guests" id="guestsSelect" class="form-select">
                                        <?php for ($g=1; $g<=10; $g++): ?>
                                        <option value="<?= $g ?>" <?= $guests === $g ? 'selected' : '' ?>><?= $g ?> <?= t('Tamu') ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="small text-danger d-none mt-1" id="guestOverflow" data-testid="guest-overflow"><?= t('Jumlah tamu melebihi kapasitas kamar') ?></div>
                                </div>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="bg-light rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted"><?= t('Harga') ?> × <span id="nightsDisplay"><?= $nights ?></span> <?= t('malam') ?></span>
                                    <span id="nightlySummary"><?= formatRupiah(array_sum($nightlyPrices)) ?><?= count($nightlyPrices) > 1 && count(array_unique($nightlyPrices)) > 1 ? ' (' . t('harga bervariasi per tanggal') . ')' : '' ?></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                    <span><?= t('Total') ?></span>
                                    <span class="text-primary fs-5" id="totalDisplay"><?= formatRupiah($totalPrice) ?></span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small"><?= t('Nama Lengkap') ?></label>
                                <input type="text" name="name" class="form-control" value="<?= e(getUser()['name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small"><?= t('No. Telepon') ?></label>
                                <input type="text" name="phone" class="form-control" value="<?= e(getUser()['phone'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small"><?= t('Email') ?></label>
                                <input type="email" name="email" class="form-control" value="<?= e(getUser()['email'] ?? '') ?>" placeholder="email@contoh.com">
                            </div>
                            <?php if (!empty($_SESSION['user_id'])): require_once 'includes/wallet.php'; $walletBal = getWalletBalance($_SESSION['user_id']); ?>
                                <?php if ($walletBal > 0): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWalletHotel">
                                    <label class="form-check-label small" for="useWalletHotel"><?= t('Gunakan KlookCash') ?> <strong><?= formatRupiah($walletBal) ?></strong></label>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2" id="bookingSubmitBtn"><?= t('Pesan Sekarang') ?></button>
                        </form>

                        <script>
                        var pricePerNight = <?= $hotel['price_per_night'] ?>;
                        var HOTEL_CAL = <?= json_encode($hotelCalendar) ?>;
                        var checkinInput = document.querySelector('input[name="checkin"]');
                        var checkoutInput = document.querySelector('input[name="checkout"]');
                        var roomsSelect = document.querySelector('select[name="rooms"]');

                        function calcNightTotal(ciStr, coStr, rooms, roomRate) {
                            var sum = 0, n = 0;
                            var byDate = {};
                            HOTEL_CAL.forEach(function(r) { byDate[r.date] = r.price; });
                            var cur = new Date(ciStr);
                            var end = new Date(coStr);
                            while (cur < end && n < 60) {
                                var key = cur.getFullYear() + '-' + String(cur.getMonth() + 1).padStart(2, '0') + '-' + String(cur.getDate()).padStart(2, '0');
                                sum += (typeof roomRate === 'number' && roomRate > 0) ? roomRate : ((typeof byDate[key] === 'number') ? byDate[key] : pricePerNight);
                                cur.setDate(cur.getDate() + 1);
                                n++;
                            }
                            return { total: sum * rooms, nights: Math.max(1, n) };
                        }
                        function updateTotal() {
                            var sel = document.getElementById('roomSelect');
                            var opt = sel ? sel.options[sel.selectedIndex] : null;
                            var roomRate = (opt && opt.value) ? parseFloat(opt.dataset.rate) : NaN;
                            var r = calcNightTotal(checkinInput.value, checkoutInput.value, parseInt(roomsSelect.value), roomRate);
                            document.getElementById('nightsDisplay').textContent = r.nights;
                            document.getElementById('nightlySummary').textContent = 'Rp ' + Math.round(r.total / r.nights * parseInt(roomsSelect.value)).toLocaleString(window.I18N ? window.I18N.locale : 'id-ID') + (r.nights > 1 ? ' × ' + r.nights : '');
                            document.getElementById('totalDisplay').textContent = 'Rp ' + Math.round(r.total).toLocaleString(window.I18N ? window.I18N.locale : 'id-ID');
                            syncRoomUI();
                        }
                        function syncRoomUI() {
                            var sel = document.getElementById('roomSelect');
                            var opt = sel ? sel.options[sel.selectedIndex] : null;
                            var badges = document.getElementById('roomBadges');
                            var hidden = document.getElementById('roomIdInput');
                            var overflow = document.getElementById('guestOverflow');
                            if (opt && opt.value) {
                                hidden.value = opt.value;
                                var tags = [];
                                if (opt.dataset.breakfast === '1') tags.push('<?= t('Sarapan') ?>');
                                tags.push(opt.dataset.refundable === '1' ? '<?= t('Refundable') ?>' : '<?= t('Non-refundable') ?>');
                                badges.textContent = tags.join(' · ');
                            } else {
                                hidden.value = '';
                                badges.textContent = '';
                            }
                            var guestsSel = document.getElementById('guestsSelect');
                            var maxGuest = (opt && opt.value) ? parseInt(opt.dataset.maxGuest) : 99;
                            var over = parseInt(guestsSel.value) > maxGuest;
                            overflow.classList.toggle('d-none', !over);
                            document.getElementById('bookingSubmitBtn').disabled = over;
                        }
                        document.addEventListener('DOMContentLoaded', function() {
                            var sel = document.getElementById('roomSelect');
                            if (sel) {
                                sel.addEventListener('change', updateTotal);
                                document.getElementById('guestsSelect').addEventListener('change', syncRoomUI);
                            }
                        });
                        document.querySelectorAll('.room-select-btn').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                var roomId = btn.dataset.room;
                                var sel = document.getElementById('roomSelect');
                                sel.value = roomId;
                                document.querySelector('#hotelBookingForm').scrollIntoView({ behavior: 'smooth' });
                                updateTotal();
                            });
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
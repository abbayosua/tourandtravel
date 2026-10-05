<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/booking-resume.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-bookings.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle booking modification (tour only) — ubah tanggal, data pemesan, & peserta (nama + paspor).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modify_booking') {
    require_once 'includes/participants.php';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $newDateId = (int)($_POST['new_date_id'] ?? 0);
    $fail = function (string $m) { header('Location: my-bookings.php?msg=modify_fail&rmsg=' . urlencode($m)); exit; };

    // Verify booking belongs to user and is modifiable
    $checkBooking = db()->prepare("SELECT b.*, t.max_participants, t.price AS tour_price, t.price_currency FROM bookings b JOIN tours t ON b.tour_id = t.id WHERE b.id = ? AND b.user_id = ? AND b.status IN ('pending','confirmed')");
    $checkBooking->execute([$bookingId, $userId]);
    $booking = $checkBooking->fetch();

    if (!$booking) $fail(t('Booking tidak dapat diubah'));
    // Pembayaran sudah terkonfirmasi → isi booking terkunci.
    if (($booking['payment_status'] ?? null) === 'paid') $fail(t('Pembayaran sudah terkonfirmasi — booking tidak dapat diubah.'));

    // Validate new date
    $checkDate = db()->prepare("SELECT * FROM tour_dates WHERE id = ? AND tour_id = ?");
    $checkDate->execute([$newDateId, $booking['tour_id']]);
    $newDate = $checkDate->fetch();
    if (!$newDate) $fail(t('Tanggal tidak valid'));
    if ($newDate['departure_date'] < date('Y-m-d')) $fail(t('Tanggal keberangkatan sudah lewat, silakan pilih tanggal lain'));

    // Data pemesan
    $cName = trim((string)($_POST['contact_name'] ?? $booking['name']));
    $cEmail = trim((string)($_POST['contact_email'] ?? $booking['email']));
    $cPhone = trim((string)($_POST['contact_phone'] ?? $booking['phone']));
    if ($cName === '') $fail(t('Nama pemesan wajib diisi'));

    // Peserta: yang dipertahankan + baru; yang dihapus dibuang.
    $existing = getBookingParticipants($bookingId);
    $removeIds = array_map('intval', (array)($_POST['pax_remove'] ?? []));
    $namesById = is_array($_POST['pax_name'] ?? null) ? $_POST['pax_name'] : [];
    $passById = is_array($_POST['pax_passport'] ?? null) ? $_POST['pax_passport'] : [];
    $kept = [];
    foreach ($existing as $p) {
        $pid = (int)$p['id'];
        if (in_array($pid, $removeIds, true)) continue;
        $nm = trim((string)($namesById[$pid] ?? ''));
        if ($nm === '') $nm = (string)$p['full_name'];
        $kept[] = ['id' => $pid, 'name' => $nm, 'passport' => (string)($passById[$pid] ?? '')];
    }
    $newNames = is_array($_POST['new_pax_name'] ?? null) ? $_POST['new_pax_name'] : [];
    $newPass = is_array($_POST['new_pax_passport'] ?? null) ? $_POST['new_pax_passport'] : [];
    $added = [];
    foreach ($newNames as $k => $nm) {
        $nm = trim((string)$nm);
        if ($nm === '') continue;
        $added[] = ['name' => $nm, 'passport' => (string)($newPass[$k] ?? '')];
    }
    $newCount = count($kept) + count($added);
    if ($newCount < 1) $fail(t('Jumlah peserta minimal 1'));
    if ($newCount > (int)$booking['max_participants']) $fail(t('Jumlah peserta melebihi kapasitas tour'));

    // Check slot availability (tambahkan kembali peserta booking saat ini)
    $sisaSlot = getSisaSlot($newDateId) + (int)$booking['participants'];
    if ($sisaSlot < $newCount) $fail(t('Slot tidak cukup'));

    // Harga mengikuti tanggal + jumlah peserta (diskon grup & korporat).
    $unitPrice = getPriceForDate('tour', $booking['tour_id'], $newDate['departure_date'], $booking['tour_price']);
    $unitPrice = getFlashSalePrice((float)$unitPrice, 'tour', (int)$booking['tour_id'])['price'];
    $newTotalPrice = $unitPrice * $newCount;
    $groupPct = $newCount >= 20 ? 15.0 : ($newCount >= 10 ? 10.0 : ($newCount >= 5 ? 5.0 : 0.0));
    if ($groupPct > 0) $newTotalPrice -= $newTotalPrice * ($groupPct / 100);
    if (getCorporateDiscount((int)$userId) > 0) $newTotalPrice = applyCorporateDiscount((int)$userId, $newTotalPrice);
    $newTotalPrice = max(0, round($newTotalPrice, 2));

    // FX buffer (global, %) — konsisten dengan pembuatan booking baru.
    $bufferPct = getFxBufferPct();
    $bufferAmount = $bufferPct > 0 ? round($newTotalPrice * $bufferPct / 100, 2) : 0.0;
    $newTotalPrice = max(0, round($newTotalPrice + $bufferAmount, 2));

    // Rate-lock ulang (source -> IDR) setelah perubahan.
    // Harga dihitung ulang dari harga tour (mata uang tour), jadi source = mata uang tour.
    $srcCurrency = $booking['price_currency'] ?? 'IDR';
    $fxRate = ($srcCurrency === 'IDR') ? 1.0 : (getFxRate($srcCurrency, 'IDR') ?? 1.0);
    $chargedAmount = round($newTotalPrice * $fxRate, 2);

    // Update booking
    db()->prepare("UPDATE bookings SET tour_date_id = ?, participants = ?, total_price = ?, name = ?, email = ?, phone = ?, source_amount = ?, source_currency = ?, charged_amount = ?, charged_currency = 'IDR', fx_rate = ?, rate_locked_at = NOW(), fx_buffer_amount = ? WHERE id = ?")
        ->execute([$newDateId, $newCount, $newTotalPrice, $cName, $cEmail, $cPhone, $newTotalPrice, $srcCurrency, $chargedAmount, $fxRate, $bufferAmount, $bookingId]);

    // Tulis peserta: hapus → ubah nama/paspor → tambah.
    foreach ($removeIds as $rid) deleteParticipant($bookingId, $rid);
    $updName = db()->prepare("UPDATE booking_participants SET full_name = ? WHERE id = ? AND booking_id = ?");
    $updPass = db()->prepare("UPDATE booking_participants SET passport_photo = ? WHERE id = ? AND booking_id = ?");
    foreach ($kept as $p) {
        $updName->execute([$p['name'], $p['id'], $bookingId]);
        if ($p['passport'] !== '' && isStoredPassportFile($p['passport'])) $updPass->execute([$p['passport'], $p['id'], $bookingId]);
    }
    foreach ($added as $p) {
        $pf = ($p['passport'] !== '' && isStoredPassportFile($p['passport'])) ? $p['passport'] : null;
        addParticipant($bookingId, $p['name'], $pf);
    }

    header('Location: my-bookings.php?msg=modified');
    exit;
}

// Fase 3: refund self-service — ajukan refund
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_refund') {
    require_once 'includes/refund.php';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') $reason = t('Tidak disebutkan');
    [$ok, $msg] = requestRefund($bookingId, (int)$userId, $reason);
    header('Location: my-bookings.php?msg=' . ($ok ? 'refund_requested' : 'refund_fail') . '&rmsg=' . urlencode($msg));
    exit;
}

// Handle cancel request (self-service cancellation) - supports all booking types
if (isset($_GET['cancel']) && (int)$_GET['cancel'] > 0) {
    $cancelId = (int)$_GET['cancel'];
    $type = $_GET['type'] ?? 'tour';
    $tableMap = [
        'tour' => 'bookings',
        'attraction' => 'attraction_bookings',
        'transfer' => 'transfer_bookings',
        'train' => 'train_bookings',
        'esim' => 'connectivity_bookings',
        'pelni' => 'pelni_bookings',
    ];
    if (isset($tableMap[$type])) {
        $table = $tableMap[$type];
        // Refund wallet if paid with TravelPoints (only refund the portion covered by wallet)
        $ref = db()->prepare("SELECT id, total_price, payment_status FROM `$table` WHERE id = ? AND user_id = ? AND status IN ('pending','confirmed')");
        $ref->execute([$cancelId, $userId]);
        if ($brow = $ref->fetch()) {
            // Booking yang sudah dibayar tidak bisa self-cancel — harus lewat refund
            if (($brow['payment_status'] ?? null) === 'paid') {
                header('Location: my-bookings.php?msg=cancel_paid');
                exit;
            }
            $walletPaid = db()->prepare("SELECT COALESCE(SUM(-amount),0) FROM wallet_transactions WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND amount < 0");
            $walletPaid->execute([$userId, $type . '_booking', $cancelId]);
            $paid = (float)$walletPaid->fetchColumn();
            if ($paid > 0) {
                require_once 'includes/wallet.php';
                refundWallet($userId, $paid, $type . '_booking', $cancelId);
            }
        }
        // Refund reseller balance if booking was made via reseller
        if ($type === 'tour') {
            $resCheck = db()->prepare("SELECT booking_source, total_price FROM bookings WHERE id = ? AND user_id = ? AND booking_source = 'reseller'");
            $resCheck->execute([$cancelId, $userId]);
            if ($resRow = $resCheck->fetch()) {
                topUpReseller($userId, (float)$resRow['total_price']);
            }
        }
        db()->prepare("UPDATE `$table` SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending','confirmed')")->execute([$cancelId, $userId]);
        // Release slot tour (idempotent — no-op bila belum pernah deduct)
        if ($type === 'tour') {
            require_once 'includes/availability.php';
            releaseTourSlotsOnCancel($cancelId);
        }
    }
    header('Location: my-bookings.php?msg=cancelled');
    exit;
}

// Combine all booking types
$all = [];

$tourBookings = db()->prepare("
    SELECT b.*, t.title as item_title, t.title_en as item_title_en, t.title_zh as item_title_zh, t.slug as item_slug, t.cover_image, t.max_participants, t.price as tour_price, t.price_currency, td.departure_date, 'tour' AS btype,
           b.participants AS qty_num, 'peserta' AS qty_unit, b.total_price,
           (SELECT amount FROM booking_addons ba WHERE ba.booking_type='tour' AND ba.booking_id = b.id AND ba.type='insurance') AS insurance_premi
    FROM bookings b
    JOIN tours t ON b.tour_id = t.id
    JOIN tour_dates td ON b.tour_date_id = td.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$tourBookings->execute([$userId]);
foreach ($tourBookings->fetchAll() as $b) { $b['item_title'] = tContent(['title' => $b['item_title'], 'title_en' => $b['item_title_en'] ?? '', 'title_zh' => $b['item_title_zh'] ?? ''], 'title'); $b['img'] = getTourImage($b, 'small'); $all[] = $b; }

$attrBookings = db()->prepare("
    SELECT ab.*, a.name as item_title, a.name_en as item_title_en, a.name_zh as item_title_zh, a.slug as item_slug, a.price_currency, 'attraction' AS btype,
           ab.quantity AS qty_num, 'tiket' AS qty_unit, ab.total_price, ab.visit_date AS date_label
    FROM attraction_bookings ab
    JOIN attractions a ON ab.attraction_id = a.id
    WHERE ab.user_id = ?
    ORDER BY ab.created_at DESC
");
$attrBookings->execute([$userId]);
foreach ($attrBookings->fetchAll() as $b) { $b['item_title'] = tContent(['title' => $b['item_title'], 'title_en' => $b['item_title_en'] ?? '', 'title_zh' => $b['item_title_zh'] ?? ''], 'title'); $b['img'] = 'https://placehold.co/300x200?text=Atraksi'; $all[] = $b; }

$transferBookings = db()->prepare("
    SELECT tb.*, tr.name as item_title, tr.name_en as item_title_en, tr.name_zh as item_title_zh, tr.slug as item_slug, tr.price_currency, 'transfer' AS btype,
           tb.passengers AS qty_num, 'pax' AS qty_unit, tb.total_price, tb.pickup_date AS date_label
    FROM transfer_bookings tb
    JOIN transfers tr ON tb.transfer_id = tr.id
    WHERE tb.user_id = ?
    ORDER BY tb.created_at DESC
");
$transferBookings->execute([$userId]);
foreach ($transferBookings->fetchAll() as $b) { $b['item_title'] = tContent(['title' => $b['item_title'], 'title_en' => $b['item_title_en'] ?? '', 'title_zh' => $b['item_title_zh'] ?? ''], 'title'); $b['img'] = 'https://placehold.co/300x200?text=Transfer'; $all[] = $b; }

$trainBookings = db()->prepare("
    SELECT tb.*, COALESCE(tr.name, tb.train_name) as item_title, tr.name_en as item_title_en, tr.slug as item_slug, COALESCE(tr.price_currency, 'IDR') as price_currency, 'train' AS btype,
           tb.seats AS qty_num, 'kursi' AS qty_unit, tb.total_price, tb.travel_date AS date_label
    FROM train_bookings tb
    LEFT JOIN trains tr ON tb.train_id = tr.id
    WHERE tb.user_id = ?
    ORDER BY tb.created_at DESC
");
$trainBookings->execute([$userId]);
foreach ($trainBookings->fetchAll() as $b) { $b['item_title'] = tContent(['title' => $b['item_title'], 'title_en' => $b['item_title_en'] ?? ''], 'title'); $b['img'] = 'assets/img/kai.jpeg'; $all[] = $b; }

$esimBookings = db()->prepare("
    SELECT cb.*, cp.name as item_title, cp.name_en as item_title_en, cp.name_zh as item_title_zh, cp.slug as item_slug, cp.price_currency, 'esim' AS btype,
           cb.quantity AS qty_num, 'pcs' AS qty_unit, cb.total_price
    FROM connectivity_bookings cb
    JOIN connectivity_products cp ON cb.product_id = cp.id
    WHERE cb.user_id = ?
    ORDER BY cb.created_at DESC
");
$esimBookings->execute([$userId]);
foreach ($esimBookings->fetchAll() as $b) { $b['item_title'] = tContent(['title' => $b['item_title'], 'title_en' => $b['item_title_en'] ?? '', 'title_zh' => $b['item_title_zh'] ?? ''], 'title'); $b['img'] = 'https://placehold.co/300x200?text=eSIM'; $b['date_label'] = null; $all[] = $b; }

$pelniBookings = db()->prepare("
    SELECT pb.*, CONCAT(pb.ship_name, ' · ', pb.route_from, ' → ', pb.route_to) as item_title,
           '' as item_slug, 'IDR' as price_currency, 'pelni' AS btype,
           pb.passengers AS qty_num, 'orang' AS qty_unit, pb.total_price, pb.departure_date AS date_label
    FROM pelni_bookings pb
    WHERE pb.user_id = ?
    ORDER BY pb.created_at DESC
");
$pelniBookings->execute([$userId]);
foreach ($pelniBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=PELNI'; $all[] = $b; }

$nusaBookings = db()->prepare("
    SELECT nb.*, nb.hotel_name as item_title, '' as item_slug, 'IDR' as price_currency, 'hotel' AS btype,
           nb.guests AS qty_num, 'tamu' AS qty_unit, nb.total_price, nb.checkin AS date_label
    FROM nusatrip_bookings nb
    WHERE nb.user_id = ?
    ORDER BY nb.created_at DESC
");
$nusaBookings->execute([$userId]);
foreach ($nusaBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=Hotel'; $all[] = $b; }

$nusaFlightBookings = db()->prepare("
    SELECT nfb.*, CONCAT(nfb.airline, ' ', nfb.flight_number, ' ', nfb.origin, '→', nfb.destination) as item_title,
           '' as item_slug, 'IDR' as price_currency, 'flight' AS btype,
           nfb.passengers AS qty_num, 'kursi' AS qty_unit, nfb.total_price, nfb.departure_date AS date_label
    FROM nusatrip_flight_bookings nfb
    WHERE nfb.user_id = ?
    ORDER BY nfb.created_at DESC
");
$nusaFlightBookings->execute([$userId]);
foreach ($nusaFlightBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=Pesawat'; $all[] = $b; }

$flightBookings = db()->prepare("
    SELECT fb.*, COALESCE(NULLIF(fb.title, ''), fb.name) as item_title, '' as item_slug, 'IDR' as price_currency, 'flight' AS btype,
           fb.seats AS qty_num, 'kursi' AS qty_unit, fb.total_price, fb.departure_date AS date_label
    FROM flight_bookings fb
    WHERE fb.user_id = ?
    ORDER BY fb.created_at DESC
");
$flightBookings->execute([$userId]);
foreach ($flightBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=Pesawat'; $all[] = $b; }

// Sort combined by created_at desc
usort($all, function ($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });

$typeIcon = ['tour' => 'map', 'attraction' => 'signpost-2', 'transfer' => 'arrow-left-right', 'train' => 'train-front', 'esim' => 'sim', 'pelni' => 'ship', 'hotel' => 'building', 'flight' => 'airplane'];
$typeName = ['tour' => t('Tour'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('KAI'), 'esim' => t('eSIM'), 'pelni' => t('PELNI'), 'hotel' => t('Hotel'), 'flight' => t('Pesawat')];
$typeLink = ['tour' => 'tour-detail.php', 'attraction' => 'attraction-detail.php', 'transfer' => 'transfer-detail.php', 'train' => 'train-detail.php', 'esim' => 'esim-detail.php', 'pelni' => 'pelni.php', 'hotel' => 'hotels.php', 'flight' => 'flights.php'];

require_once 'includes/participants.php';
$participantMap = getBookingParticipantsMap(array_map(fn($b) => (int)$b['id'], array_filter($all, fn($b) => ($b['btype'] ?? '') === 'tour')));

$pageTitle = t('Riwayat Booking');
require_once 'includes/header-shared.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-ticket-perforated me-2"></i><?= t('Riwayat Booking') ?></h4>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
            <div class="alert alert-success py-2 small"><?= t('Booking berhasil dibatalkan. TravelPoints yang digunakan telah dikembalikan.') ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'modified'): ?>
            <div class="alert alert-success py-2 small"><?= t('Booking berhasil diubah.') ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'modify_fail'): ?>
            <div class="alert alert-danger py-2 small"><?= e($_GET['rmsg'] ?? t('Gagal mengubah booking')) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'refund_requested'): ?>
            <div class="alert alert-success py-2 small" data-testid="refund-ok"><i class="bi bi-check-circle me-1"></i><?= e($_GET['rmsg'] ?? t('Pengajuan refund diterima')) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'refund_fail'): ?>
            <div class="alert alert-danger py-2 small" data-testid="refund-fail"><i class="bi bi-x-circle me-1"></i><?= e($_GET['rmsg'] ?? t('Pengajuan refund gagal')) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancel_paid'): ?>
            <div class="alert alert-warning py-2 small"><i class="bi bi-info-circle me-1"></i><?= t('Booking sudah dibayar tidak dapat dibatalkan sendiri. Silakan ajukan refund.') ?></div>
        <?php endif; ?>

        <?php if (count($all) > 0): ?>
        <div class="row g-3">
            <?php foreach ($all as $b): ?>
            <?php $btype = $b['btype']; ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="row g-0">
                        <!-- Foto mini -->
                        <div class="col-4 col-md-4">
                            <img src="<?= $b['img'] ?>" onerror="this.onerror=null;this.src='https://placehold.co/300x200?text=<?= $typeName[$btype] ?>'" class="w-100 h-100" style="object-fit: cover; min-height: 130px;" alt="">
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-semibold mb-0"><?= e($b['item_title']) ?></h6>
                                        <small class="text-muted"><i class="bi bi-<?= $typeIcon[$btype] ?> me-1"></i><?= $typeName[$btype] ?> · #<?= e($b['booking_code'] ?? $b['id']) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </div>
                                <div class="row small text-muted g-2">
                                    <?php if (!empty($b['date_label'])): ?>
                                    <div class="col-6">
                                        <i class="bi bi-calendar me-1"></i><?= formatDate($b['date_label']) ?>
                                    </div>
                                    <?php elseif (!empty($b['departure_date'])): ?>
                                    <div class="col-6">
                                        <i class="bi bi-calendar me-1"></i><?= formatDate($b['departure_date']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-6">
                                        <i class="bi bi-people me-1"></i><?= $b['qty_num'] . ' ' . t($b['qty_unit']) ?>
                                    </div>
                                    <div class="col-6">
                                        <i class="bi bi-cash me-1"></i><?= formatCurrencySpan($b['total_price'], $b['source_currency'] ?? $b['price_currency'] ?? 'IDR') ?>
                                        <?php if (!empty($b['insurance_premi'])): ?>
                                        <span class="badge bg-success-subtle text-success ms-1" data-testid="insurance-badge-<?= $b['id'] ?>" title="<?= t('Termasuk asuransi perjalanan') ?>"><i class="bi bi-shield-check"></i> +<?= formatCurrencySpan((float)$b['insurance_premi'], $b['source_currency'] ?? $b['price_currency'] ?? 'IDR') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <i class="bi bi-clock me-1"></i><?= date('d/m/Y', strtotime($b['created_at'])) ?>
                                    </div>
                                </div>
                                <?php if ($btype === 'tour' && !empty($participantMap[$b['id']])): ?>
                                <div class="small text-muted mt-2">
                                    <i class="bi bi-person-lines-fill me-1"></i><?= t('Data Peserta') ?>:
                                    <?= e(implode(', ', array_column($participantMap[$b['id']], 'full_name'))) ?>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <?php
                                    $detailUrl = $typeLink[$btype] . '?slug=' . urlencode((string)($b['item_slug'] ?? ''));
                                    if ($btype === 'train' && empty($b['item_slug'])) $detailUrl = 'train-booking.php?done=' . urlencode((string)($b['booking_code'] ?? ''));
                                    elseif ($btype === 'pelni') $detailUrl = 'pelni-booking.php?booking=' . urlencode((string)($b['booking_code'] ?? ''));
                                    elseif ($btype === 'flight') $detailUrl = 'booking-success.php?code=' . urlencode((string)($b['booking_code'] ?? '')) . '&btype=flight';
                                    elseif ($btype === 'hotel') $detailUrl = 'nusatrip-book.php?step=result&booking_id=' . (int)$b['id'];
                                    $resume = bookingResumeInfo($btype, $b);
                                    ?>
                                    <!-- HIDDEN: tombol Detail disembunyikan sementara (jangan hapus)
                                    <a href="<?= $detailUrl ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="bi bi-eye me-1"></i><?= t('Detail') ?></a>
                                    -->
                                    <?php if ($resume['has_pending']): ?>
                                    <a href="<?= e($resume['resume_url']) ?>" class="btn btn-sm btn-success rounded-pill px-3" data-testid="resume-<?= $b['id'] ?>"><i class="bi bi-credit-card me-1"></i><?= t('Lanjutkan Pembayaran') ?></a>
                                    <?php endif; ?>
                                    <?php if ($btype === 'tour' && !empty($b['booking_code'])): ?>
                                    <?php $tourUnpaid = !in_array($b['status'], ['cancelled', 'canceled'], true) && ($b['payment_status'] ?? null) !== 'paid'; ?>
                                    <?php if ($tourUnpaid): ?>
                                    <a href="booking-success.php?code=<?= urlencode($b['booking_code']) ?>" class="btn btn-sm btn-success rounded-pill px-3" data-testid="btn-resume-<?= $b['id'] ?>"><i class="bi bi-credit-card me-1"></i><?= t('Lanjutkan Pembayaran') ?></a>
                                    <?php else: ?>
                                    <a href="track.php?code=<?= urlencode($b['booking_code']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-testid="btn-track-<?= $b['id'] ?>"><i class="bi bi-geo-alt me-1"></i><?= t('Lacak Booking') ?></a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($btype === 'tour' && ($b['status'] === 'pending' || $b['status'] === 'confirmed') && ($b['payment_status'] ?? null) !== 'paid'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modifyModal<?= $b['id'] ?>"><i class="bi bi-pencil me-1"></i><?= t('Ubah') ?></button>
                                    <?php endif; ?>
                                    <?php if (($b['status'] === 'pending' || $b['status'] === 'confirmed') && $btype !== 'hotel' && $btype !== 'flight'): ?>
                                    <a href="my-bookings.php?cancel=<?= $b['id'] ?>&type=<?= $btype ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('<?= t('Batalkan booking ini?') ?>')"><i class="bi bi-x-circle me-1"></i><?= t('Batalkan') ?></a>
                                    <?php endif; ?>
                                    <?php if ($btype === 'tour' && $b['status'] === 'confirmed' && ($b['refund_status'] ?? 'none') === 'none'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#refundModal<?= $b['id'] ?>" data-testid="refund-btn-<?= $b['id'] ?>"><i class="bi bi-cash-coin me-1"></i><?= t('Minta Refund') ?></button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($btype === 'tour' && ($b['refund_status'] ?? 'none') !== 'none'): ?>
                                <?php
                                    $rs = $b['refund_status'];
                                    $timeline = [
                                        'requested' => ['bg-warning text-dark', t('Menunggu persetujuan admin')],
                                        'approved'  => ['bg-success', t('Disetujui') . ' — refund ' . formatCurrencySpan((float)($b['refund_amount'] ?? 0), $b['source_currency'] ?? $b['price_currency'] ?? 'IDR') . ' ' . t('ke TravelPoints')],
                                        'rejected'  => ['bg-danger', t('Ditolak admin')],
                                    ];
                                ?>
                                <div class="mt-2 p-2 rounded bg-light" data-testid="refund-timeline-<?= $b['id'] ?>">
                                    <div class="small fw-semibold mb-1"><i class="bi bi-arrow-repeat me-1"></i><?= t('Status Refund') ?></div>
                                    <div class="d-flex align-items-center gap-1 small">
                                        <span class="badge <?= $rs === 'requested' ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= t('Diajukan') ?></span>
                                        <i class="bi bi-arrow-right text-muted"></i>
                                        <span class="badge <?= $timeline[$rs][0] ?>"><?= $timeline[$rs][1] ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-ticket fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Belum ada pemesanan.') ?></p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4"><?= t('Booking Sekarang') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-shared.php'; ?>

<?php $rfModalsRendered = $rfModalsRendered ?? []; ?>
<?php foreach ($all as $b): if ($b['btype'] !== 'tour' || ($b['refund_status'] ?? 'none') !== 'none' || $b['status'] !== 'confirmed') continue; if (isset($rfModalsRendered[$b['id']])) continue; $rfModalsRendered[$b['id']] = true; ?>
<div class="modal fade" id="refundModal<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="my-bookings.php" data-submit-once>
                <input type="hidden" name="action" value="request_refund">
                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-cash-coin me-2"></i><?= t('Minta Refund') ?> — #<?= e($b['booking_code'] ?? $b['id']) ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= t('Tutup') ?>"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2"><?= t('Refund dihitung otomatis: 100% (≥H-8), 50% (H-4–H-7), 0% (<H-3) sesuai kebijakan produk.') ?></p>
                    <label class="form-label small fw-semibold"><?= t('Alasan Refund') ?></label>
                    <textarea name="reason" class="form-control form-control-sm" rows="3" required placeholder="<?= t('Contoh: Perubahan jadwal perjalanan') ?>"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= t('Batal') ?></button>
                    <button type="submit" class="btn btn-sm btn-warning" data-testid="refund-submit"><?= t('Ajukan Refund') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php $modModalsRendered = $modModalsRendered ?? []; ?>
<?php foreach ($all as $b): if ($b['btype'] !== 'tour' || !in_array($b['status'], ['pending','confirmed']) || ($b['payment_status'] ?? null) === 'paid') continue; if (isset($modModalsRendered[$b['id']])) continue; $modModalsRendered[$b['id']] = true; ?>
<?php
// Get available dates for this tour
$availDates = db()->prepare("SELECT td.*, (td.available_slots - COALESCE(SUM(b.participants), 0)) as sisa_slot
    FROM tour_dates td
    LEFT JOIN bookings b ON b.tour_date_id = td.id AND b.status IN ('pending','confirmed')
    WHERE td.tour_id = ? AND td.departure_date >= CURDATE()
    GROUP BY td.id
    HAVING sisa_slot > 0 OR td.id = ?
    ORDER BY td.departure_date");
$availDates->execute([$b['tour_id'], $b['tour_date_id']]);
$dates = $availDates->fetchAll();
// Harga per orang tiap tanggal (kalender + flash sale) — dipakai recalc JS.
$modUnitByDate = [];
foreach ($dates as $d) {
    $u = getFlashSalePrice((float)getPriceForDate('tour', $b['tour_id'], $d['departure_date'], $b['tour_price']), 'tour', (int)$b['tour_id'])['price'];
    $modUnitByDate[(int)$d['id']] = (float)$u;
}
$modCorpPct = getCorporateDiscount((int)$userId);
$modPaxRows = $participantMap[$b['id']] ?? [];
?>
<div class="modal fade" id="modifyModal<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="my-bookings.php" data-submit-once data-modify-form
                  data-units='<?= e(json_encode($modUnitByDate)) ?>' data-corp="<?= (float)$modCorpPct ?>" data-currency="<?= e($b['source_currency'] ?? $b['price_currency'] ?? 'IDR') ?>">
                <input type="hidden" name="action" value="modify_booking">
                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-pencil me-2"></i><?= t('Ubah Booking') ?> — #<?= e($b['booking_code'] ?? $b['id']) ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= t('Tutup') ?>"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-semibold small text-uppercase text-muted mb-2"><?= t('Data Pemesan') ?></h6>
                    <div class="row g-2 mb-3">
                        <div class="col-12"><input name="contact_name" class="form-control form-control-sm" value="<?= e($b['name']) ?>" placeholder="<?= e(t('Nama pemesan')) ?>" required></div>
                        <div class="col-6"><input type="email" name="contact_email" class="form-control form-control-sm" value="<?= e($b['email']) ?>" placeholder="Email"></div>
                        <div class="col-6"><input name="contact_phone" class="form-control form-control-sm" value="<?= e($b['phone']) ?>" placeholder="<?= e(t('No. WhatsApp')) ?>"></div>
                    </div>

                    <h6 class="fw-semibold small text-uppercase text-muted mb-2"><?= t('Tanggal Keberangkatan') ?></h6>
                    <select name="new_date_id" class="form-select form-select-sm mb-3 mod-date" required>
                        <?php foreach ($dates as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= $d['id'] == $b['tour_date_id'] ? 'selected' : '' ?>>
                            <?= formatDate($d['departure_date']) ?> (<?= (int)$d['sisa_slot'] ?> <?= t('slot') ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <h6 class="fw-semibold small text-uppercase text-muted mb-2 d-flex justify-content-between">
                        <span><?= t('Peserta') ?></span>
                        <span class="text-muted fw-normal">max <?= (int)$b['max_participants'] ?></span>
                    </h6>
                    <div class="mod-pax-list">
                        <?php foreach ($modPaxRows as $pi => $p): ?>
                        <div class="border rounded p-2 mb-2 mod-pax-row">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-light text-dark">#<?= $pi + 1 ?></span>
                                <div class="d-flex gap-1 align-items-center">
                                    <?php if (!empty($p['passport_photo'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-passport="uploads/passports/<?= e($p['passport_photo']) ?>" title="<?= e(t('Lihat paspor')) ?>"><i class="bi bi-image"></i></button>
                                    <?php endif; ?>
                                    <label class="btn btn-sm btn-outline-secondary py-0 px-2 mb-0" title="<?= e(t('Ganti paspor')) ?>"><i class="bi bi-upload"></i><input type="file" class="d-none mod-pax-file" accept="image/jpeg,image/png,image/webp"></label>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 mod-pax-remove" title="<?= e(t('Hapus peserta')) ?>"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <input type="text" name="pax_name[<?= (int)$p['id'] ?>]" class="form-control form-control-sm" value="<?= e($p['full_name']) ?>" placeholder="<?= e(t('Nama lengkap sesuai paspor')) ?>">
                            <input type="hidden" name="pax_passport[<?= (int)$p['id'] ?>]" class="mod-pax-pass">
                            <div class="form-text mod-pax-status"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <template class="mod-pax-tpl">
                        <div class="border rounded p-2 mb-2 mod-pax-row">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary-subtle text-primary"><?= t('Baru') ?></span>
                                <div class="d-flex gap-1 align-items-center">
                                    <label class="btn btn-sm btn-outline-secondary py-0 px-2 mb-0" title="<?= e(t('Unggah paspor')) ?>"><i class="bi bi-upload"></i><input type="file" class="d-none mod-pax-file" accept="image/jpeg,image/png,image/webp"></label>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 mod-pax-remove" title="<?= e(t('Hapus peserta')) ?>"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <input type="text" name="new_pax_name[]" class="form-control form-control-sm" placeholder="<?= e(t('Nama lengkap sesuai paspor')) ?>">
                            <input type="hidden" name="new_pax_passport[]" class="mod-pax-pass">
                            <div class="form-text mod-pax-status"></div>
                        </div>
                    </template>
                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mod-pax-add"><i class="bi bi-plus-lg me-1"></i><?= t('Tambah peserta') ?></button>

                    <div class="border rounded p-2 mt-3 bg-light small">
                        <div class="d-flex justify-content-between"><span><?= t('Subtotal') ?></span><span class="mod-sum-sub">-</span></div>
                        <div class="d-flex justify-content-between text-success d-none mod-sum-group-row"><span><?= t('Diskon Grup') ?> <span class="mod-sum-group-pct"></span></span><span class="mod-sum-group">-</span></div>
                        <div class="d-flex justify-content-between text-success d-none mod-sum-corp-row"><span><?= t('Diskon korporat') ?></span><span class="mod-sum-corp">-</span></div>
                        <div class="d-flex justify-content-between text-secondary d-none mod-sum-buffer-row"><span><?= t('Penyesuaian kurs') ?> <span class="mod-sum-buffer-pct"></span></span><span class="mod-sum-buffer">+</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold"><span><?= t('Total') ?></span><span class="mod-sum-total">-</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= t('Batal') ?></button>
                    <button type="submit" class="btn btn-sm btn-primary"><?= t('Simpan Perubahan') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php require_once 'includes/components/passport-modal.php'; ?>
<script>
(function () {
    var CSRF = <?= json_encode(csrfToken()) ?>;
    var bufferPct = <?= json_encode(getFxBufferPct()) ?>;
    function fmtRp(n, fromCur) {
        fromCur = fromCur || 'IDR';
        var cs = window.CurrencySwitcher;
        if (cs && typeof cs.format === 'function' && typeof cs.convert === 'function') {
            var cur = cs.currentCurrency || fromCur;
            return cs.format(cs.convert(n, fromCur, cur), cur);
        }
        return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }
    function recalc(form) {
        var units = {};
        try { units = JSON.parse(form.getAttribute('data-units') || '{}'); } catch (e) {}
        var corp = parseFloat(form.getAttribute('data-corp') || '0') || 0;
        var fromCur = form.getAttribute('data-currency') || 'IDR';
        var sel = form.querySelector('.mod-date');
        var unit = sel ? units[sel.value] : null;
        if (unit == null) { var keys = Object.keys(units); unit = keys.length ? units[keys[0]] : 0; }
        var pax = Math.max(1, form.querySelectorAll('.mod-pax-row').length);
        var gross = unit * pax;
        var gPct = pax >= 20 ? 15 : (pax >= 10 ? 10 : (pax >= 5 ? 5 : 0));
        var gAmt = gross * gPct / 100;
        var after = gross - gAmt;
        var cAmt = corp > 0 ? after * corp / 100 : 0;
        var total = after - cAmt;
        var bufferAmt = bufferPct > 0 ? total * bufferPct / 100 : 0;
        total += bufferAmt;
        form.querySelector('.mod-sum-sub').textContent = fmtRp(gross, fromCur);
        var gRow = form.querySelector('.mod-sum-group-row');
        if (gPct > 0) { gRow.classList.remove('d-none'); form.querySelector('.mod-sum-group-pct').textContent = '(' + gPct + '%)'; form.querySelector('.mod-sum-group').textContent = '-' + fmtRp(gAmt, fromCur); }
        else gRow.classList.add('d-none');
        var cRow = form.querySelector('.mod-sum-corp-row');
        if (cAmt > 0) { cRow.classList.remove('d-none'); form.querySelector('.mod-sum-corp').textContent = '-' + fmtRp(cAmt, fromCur); }
        else cRow.classList.add('d-none');
        var bRow = form.querySelector('.mod-sum-buffer-row');
        if (bufferAmt > 0) { bRow.classList.remove('d-none'); form.querySelector('.mod-sum-buffer-pct').textContent = '(' + bufferPct + '%)'; form.querySelector('.mod-sum-buffer').textContent = '+' + fmtRp(bufferAmt, fromCur); }
        else bRow.classList.add('d-none');
        form.querySelector('.mod-sum-total').textContent = fmtRp(total, fromCur);
    }
    function uploadPassport(inp) {
        var row = inp.closest('.mod-pax-row');
        var hid = row.querySelector('.mod-pax-pass');
        var st = row.querySelector('.mod-pax-status');
        var file = inp.files && inp.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('passport', file);
        if (st) st.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span><?= t('Mengunggah...') ?></span>';
        fetch('pax-upload-ajax.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) { if (hid) hid.value = d.filename; if (st) st.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> <?= t('Terunggah') ?></span>'; }
                else { if (hid) hid.value = ''; inp.value = ''; if (st) st.innerHTML = '<span class="text-danger">' + (d.message || '<?= t('Gagal mengunggah') ?>') + '</span>'; }
            })
            .catch(function () { if (st) st.innerHTML = '<span class="text-danger"><?= t('Gagal mengunggah') ?></span>'; });
    }
    document.querySelectorAll('[data-modify-form]').forEach(function (form) {
        var list = form.querySelector('.mod-pax-list');
        var tpl = form.querySelector('.mod-pax-tpl');
        form.addEventListener('click', function (e) {
            var rm = e.target.closest('.mod-pax-remove');
            if (rm) {
                var row = rm.closest('.mod-pax-row');
                var nameInput = row.querySelector('input[name^="pax_name["]');
                if (nameInput) {
                    var m = nameInput.getAttribute('name').match(/\[(\d+)\]/);
                    if (m) { var h = document.createElement('input'); h.type = 'hidden'; h.name = 'pax_remove[]'; h.value = m[1]; form.appendChild(h); }
                }
                row.remove(); recalc(form); return;
            }
            if (e.target.closest('.mod-pax-add')) { list.appendChild(tpl.content.cloneNode(true)); recalc(form); }
        });
        form.addEventListener('change', function (e) {
            if (e.target.classList && e.target.classList.contains('mod-pax-file')) uploadPassport(e.target);
            if (e.target.classList && e.target.classList.contains('mod-date')) recalc(form);
        });
        recalc(form);
    });
    document.addEventListener('currency:changed', function() {
        document.querySelectorAll('[data-modify-form]').forEach(function(form) {
            recalc(form);
        });
    });
})();
</script>

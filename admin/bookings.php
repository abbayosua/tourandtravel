<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$adminId = $_SESSION['user_id'] ?? 0;

// Update status (with table/type mapping)
$tableMap = [
    'tour' => 'bookings',
    'attraction' => 'attraction_bookings',
    'transfer' => 'transfer_bookings',
    'train' => 'train_bookings',
    'esim' => 'connectivity_bookings',
    'hotel' => 'hotel_bookings',
    'flight' => 'flight_bookings',
    'ferry' => 'ferry_bookings',
];



if (isset($_GET['update_status'])) {
    $id = (int)$_GET['update_status'];
    $status = $_GET['status'] ?? 'pending';
    $type = $_GET['type'] ?? 'tour';
    $note = trim($_POST['admin_note'] ?? ($_GET['note'] ?? ''));
    if (in_array($status, ['pending', 'confirmed', 'cancelled', 'paid', 'refunded']) && isset($tableMap[$type])) {
        $table = $tableMap[$type];
        $hasAdminNote = (bool)db()->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . db()->quote($table) . " AND COLUMN_NAME = 'admin_note'")->fetchColumn();
        $hasCogs = (bool)db()->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . db()->quote($table) . " AND COLUMN_NAME = 'cogs'")->fetchColumn();
        $cogs = isset($_POST['cogs']) && $_POST['cogs'] !== '' ? max(0, (float)$_POST['cogs']) : null;
        $sets = ["status = ?"];
        $vals = [$status];
        if ($hasAdminNote && $note !== '') { $sets[] = "admin_note = ?"; $vals[] = $note; }
        if ($hasCogs && $cogs !== null) { $sets[] = "cogs = ?"; $vals[] = $cogs; }
        $vals[] = $id;
        db()->prepare("UPDATE `$table` SET " . implode(", ", $sets) . " WHERE id = ?")->execute($vals);

        require_once '../includes/notifications.php';
        require_once '../includes/email.php';

        // Mapping kolom per vertikal
        $codeCol = $type === 'hotel' ? 'id' : ($type === 'flight' ? 'id' : 'booking_code');
        $nq = db()->prepare("SELECT * FROM `$table` WHERE id = ?");
        $nq->execute([$id]);
        $row = $nq->fetch();

        if ($row) {
            $userEmail = $row['email'] ?? null;
            $userId = isset($row['user_id']) ? (int)$row['user_id'] : 0;
            $code = $row['booking_code'] ?? ('#' . $id);
            $trackLink = $type === 'tour' ? (BASE_URL . '/track.php?code=' . $code) : BASE_URL . '/my-bookings.php';

            // Notifikasi in-app (semua vertikal yang punya user_id)
            if ($userId > 0) {
                addNotification($userId, 'status', 'Status booking: ' . $status, 'Booking ' . $code . ($note !== '' ? ' — ' . $note : ''), $trackLink);
            }
            // Email status (semua vertikal dengan email terdaftar)
            if (!empty($userEmail)) {
                sendEmailTemplate($userEmail, 'booking-status', [
                    'booking_code' => $code,
                    'status' => $status,
                    'admin_note' => $note,
                    'track_link' => $trackLink,
                    'subject' => 'Status Booking - ' . $code,
                ], null);
            }

            // Push notification ke user (bahasa sesuai cookie pref user, fallback id)
            if ($userId > 0 && defined('FCM_SERVER_KEY') && FCM_SERVER_KEY !== '') {
                $pushLang = 'id';
                try {
                    $lu = db()->prepare("SELECT lang FROM fcm_tokens WHERE user_id = ? AND lang IS NOT NULL ORDER BY updated_at DESC LIMIT 1");
                    $lu->execute([$userId]);
                    $langPref = $lu->fetchColumn();
                    if (isValidLang($langPref)) $pushLang = $langPref;
                } catch (Throwable $e) {}
                $pushMsgs = [
                    'id' => ['Status Booking: ' . ucfirst($status), 'Booking ' . $code . ($status === 'confirmed' ? ' telah dikonfirmasi' : '')],
                    'en' => ['Booking Status: ' . ucfirst($status), 'Booking ' . $code . ($status === 'confirmed' ? ' has been confirmed' : '')],
                    'zh' => ['订单状态：' . ucfirst($status), '订单 ' . $code . ($status === 'confirmed' ? ' 已确认' : '')],
                ];
                $pm = $pushMsgs[$pushLang] ?? $pushMsgs['id'];
                sendPushNotification([$userId], $pm[0], $pm[1], ['type' => 'booking', 'deeplink' => '/my-bookings/' . $code]);
            }
        }

        header('Location: bookings.php?msg=updated'); exit;
    }
}

// Hapus booking
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $type = $_GET['type'] ?? 'tour';
    if (isset($tableMap[$type])) {
        $table = $tableMap[$type];
        db()->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
    }
    header('Location: bookings.php?msg=deleted'); exit;
}

$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') $msg = t('Status booking berhasil diperbarui');
    if ($_GET['msg'] === 'deleted') $msg = t('Booking berhasil dihapus');
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';

$all = [];

// Tours
if (!$typeFilter || $typeFilter === 'tour') {
    $sql = "SELECT b.*, t.title as item_title, td.departure_date, 'tour' AS btype, CONCAT(b.participants, ' org') AS qty_label
            FROM bookings b JOIN tours t ON b.tour_id = t.id JOIN tour_dates td ON b.tour_date_id = td.id";
    $params = [];
    if ($statusFilter) { $sql .= " WHERE b.status = ?"; $params[] = $statusFilter; }
    $sql .= " ORDER BY b.created_at DESC";
    $st = db()->prepare($sql); $st->execute($params);
    foreach ($st->fetchAll() as $r) { $r['date_label'] = $r['departure_date']; $all[] = $r; }
}

// Attractions
if (!$typeFilter || $typeFilter === 'attraction') {
    $sql = "SELECT ab.*, a.name as item_title, 'attraction' AS btype, CONCAT(ab.quantity, ' tiket') AS qty_label, ab.visit_date AS date_label
            FROM attraction_bookings ab JOIN attractions a ON ab.attraction_id = a.id";
    $params = [];
    if ($statusFilter) { $sql .= " WHERE ab.status = ?"; $params[] = $statusFilter; }
    $sql .= " ORDER BY ab.created_at DESC";
    $st = db()->prepare($sql); $st->execute($params);
    $all = array_merge($all, $st->fetchAll());
}

// Transfers
if (!$typeFilter || $typeFilter === 'transfer') {
    $sql = "SELECT tb.*, tr.name as item_title, 'transfer' AS btype, CONCAT(tb.passengers, ' pax') AS qty_label, tb.pickup_date AS date_label
            FROM transfer_bookings tb JOIN transfers tr ON tb.transfer_id = tr.id";
    $params = [];
    if ($statusFilter) { $sql .= " WHERE tb.status = ?"; $params[] = $statusFilter; }
    $sql .= " ORDER BY tb.created_at DESC";
    $st = db()->prepare($sql); $st->execute($params);
    $all = array_merge($all, $st->fetchAll());
}

// Trains
if (!$typeFilter || $typeFilter === 'train') {
    $sql = "SELECT tb.*, tr.name as item_title, 'train' AS btype, CONCAT(tb.seats, ' kursi') AS qty_label, tb.travel_date AS date_label
            FROM train_bookings tb JOIN trains tr ON tb.train_id = tr.id";
    $params = [];
    if ($statusFilter) { $sql .= " WHERE tb.status = ?"; $params[] = $statusFilter; }
    $sql .= " ORDER BY tb.created_at DESC";
    $st = db()->prepare($sql); $st->execute($params);
    $all = array_merge($all, $st->fetchAll());
}

// eSIM
if (!$typeFilter || $typeFilter === 'esim') {
    $sql = "SELECT cb.*, cp.name as item_title, 'esim' AS btype, CONCAT(cb.quantity, ' pcs') AS qty_label
            FROM connectivity_bookings cb JOIN connectivity_products cp ON cb.product_id = cp.id";
    $params = [];
    if ($statusFilter) { $sql .= " WHERE cb.status = ?"; $params[] = $statusFilter; }
    $sql .= " ORDER BY cb.created_at DESC";
    $st = db()->prepare($sql); $st->execute($params);
    foreach ($st->fetchAll() as $r) { $r['date_label'] = null; $all[] = $r; }
}

// Hotels
if (!$typeFilter || $typeFilter === 'hotel') {
    $sql = "SELECT hb.*, h.name as item_title, hr.name as room_name, hr.bed_type, hr.max_guest, 'hotel' AS btype,
                   CONCAT(hb.rooms, ' kamar / ', hb.guests, ' tamu') AS qty_label,
                   CONCAT(hb.checkin, ' → ', hb.checkout) AS date_label
            FROM hotel_bookings hb JOIN hotels h ON hb.hotel_id = h.id LEFT JOIN hotel_rooms hr ON hb.room_id = hr.id";
    $params = [];
    if ($statusFilter) { $sql .= " WHERE hb.status = ?"; $params[] = $statusFilter; }
    $sql .= " ORDER BY hb.created_at DESC";
    $st = db()->prepare($sql); $st->execute($params);
    $all = array_merge($all, $st->fetchAll());
}

// Flights
if (!$typeFilter || $typeFilter === 'flight') {
    try {
        $sql = "SELECT fb.*, CONCAT(f.airline, ' ', f.flight_number) as item_title, f.from_city, f.to_city,
                       'flight' AS btype, CONCAT(fb.seats, ' pax') AS qty_label, fb.departure_date AS date_label
                FROM flight_bookings fb JOIN flight_schedules fs ON fb.schedule_id = fs.id JOIN flights f ON fs.flight_id = f.id";
        $params = [];
        if ($statusFilter) { $sql .= " WHERE fb.status = ?"; $params[] = $statusFilter; }
        $sql .= " ORDER BY fb.created_at DESC";
        $st = db()->prepare($sql); $st->execute($params);
        $all = array_merge($all, $st->fetchAll());
    } catch (Throwable $e) { /* tabel flight_bookings belum ada */ }
}

// Ferries
if (!$typeFilter || $typeFilter === 'ferry') {
    try {
        $sql = "SELECT fb.*, CONCAT(fb.company, ': ', fb.route_from, ' → ', fb.route_to) as item_title,
                       'ferry' AS btype, CONCAT(fb.passengers, ' pax') AS qty_label, fb.departure_date AS date_label
                FROM ferry_bookings fb";
        $params = [];
        if ($statusFilter) { $sql .= " WHERE fb.status = ?"; $params[] = $statusFilter; }
        $sql .= " ORDER BY fb.created_at DESC";
        $st = db()->prepare($sql); $st->execute($params);
        $all = array_merge($all, $st->fetchAll());
    } catch (Throwable $e) { /* tabel ferry_bookings belum ada */ }
}

usort($all, function ($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });

$typeName = ['tour' => t('Tour'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('Kereta'), 'esim' => 'eSIM', 'hotel' => t('Hotel'), 'flight' => t('Pesawat')];
$typeBadge = ['tour' => 'primary', 'attraction' => 'info', 'transfer' => 'warning text-dark', 'train' => 'success', 'esim' => 'secondary', 'hotel' => 'danger', 'flight' => 'dark'];

$pageTitle = t('Kelola Booking');
require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Kelola Booking') ?></h4>
    <div class="d-flex gap-2 flex-wrap">
        <a href="bookings.php" class="btn btn-sm <?= !$statusFilter && !$typeFilter ? 'btn-primary' : 'btn-outline-primary' ?>"><?= t('Semua') ?></a>
        <?php foreach (['pending', 'confirmed', 'cancelled'] as $st): ?>
        <a href="bookings.php?status=<?= $st ?><?= $typeFilter ? "&type=$typeFilter" : '' ?>" class="btn btn-sm <?= $statusFilter === $st ? 'btn-primary' : 'btn-outline-primary' ?>"><?= t(ucfirst($st)) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tab per vertikal -->
<ul class="nav nav-pills mb-3 flex-wrap" data-testid="booking-tabs">
    <li class="nav-item"><a class="nav-link <?= !$typeFilter ? 'active' : '' ?>" href="bookings.php<?= $statusFilter ? "?status=$statusFilter" : '' ?>"><?= t('Semua Tipe') ?></a></li>
    <?php foreach ($typeName as $tk => $tn): ?>
    <li class="nav-item"><a class="nav-link <?= $typeFilter === $tk ? 'active' : '' ?>" href="bookings.php?type=<?= $tk ?><?= $statusFilter ? "&status=$statusFilter" : '' ?>" data-testid="tab-<?= $tk ?>"><?= $tn ?></a></li>
    <?php endforeach; ?>
</ul>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible py-2"><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 admin-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th><?= t('Kode') ?></th>
                        <th><?= t('Nama') ?></th>
                        <th><?= t('Item') ?></th>
                        <th><?= t('Tipe') ?></th>
                        <th><?= t('Tanggal') ?></th>
                        <th><?= t('Qty') ?></th>
                        <th><?= t('Total') ?></th>
                        <th><?= t('COGS') ?></th>
                        <th><?= t('Kontak') ?></th>
                        <th><?= t('Status') ?></th>
                        <th><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all as $b): ?>
                    <?php $btype = $b['btype']; ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><strong class="small" style="font-size: 11px;"><?= e($b['booking_code'] ?? '-') ?></strong></td>
                        <td><strong><?= e($b['name']) ?></strong></td>
                        <td><small><?= e($b['item_title']) ?></small></td>
                        <td><span class="badge bg-<?= $typeBadge[$btype] ?>"><?= $typeName[$btype] ?></span></td>
                        <td><small><?= !empty($b['date_label']) ? tglIndonesia($b['date_label']) : '-' ?></small></td>
                        <td><?= $b['qty_label'] ?></td>
                        <td><?= formatRupiah($b['total_price']) ?></td>
                        <td data-testid="cogs-cell">
                            <small class="text-muted"><?= formatRupiah($b['cogs'] ?? 0) ?></small>
                            <form method="POST" action="bookings.php?update_status=<?= $b['id'] ?>&status=<?= e($b['status']) ?>&type=<?= $btype ?>" class="d-flex gap-1 mt-1" style="max-width:130px;">
                                <input type="number" name="cogs" class="form-control form-control-sm" value="<?= e($b['cogs'] ?? 0) ?>" min="0" step="0.01" aria-label="COGS">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= t('Simpan COGS') ?>"><i class="bi bi-check"></i></button>
                            </form>
                        </td>
                        <td>
                            <?php if ($btype === 'hotel'): ?>
                                <small class="d-block" data-testid="hotel-room-detail"><?= t('Kamar') ?>: <strong><?= e($b['room_name'] ?? '-') ?></strong></small>
                                <small class="d-block text-muted"><?= t('Kasur') ?>: <?= e($b['bed_type'] ?? '-') ?> · <?= (int)($b['max_guest'] ?? 0) ?> <?= t('Tamu') ?>/<?= t('Kamar') ?></small>
                            <?php elseif ($btype === 'flight'): ?>
                                <small class="d-block" data-testid="flight-offer-detail"><?= e($b['from_city'] ?? '') ?> → <?= e($b['to_city'] ?? '') ?></small>
                                <small class="d-block text-muted"><?= t('Jadwal') ?> #<?= (int)($b['schedule_id'] ?? 0) ?><?= !empty($b['offer_id']) ? ' · ' . t('Offer') . ' ' . e($b['offer_id']) : '' ?></small>
                            <?php elseif (!empty($b['passport_photo'])): ?>
                                <a href="../uploads/passports/<?= e($b['passport_photo']) ?>" target="_blank" class="text-primary small"><?= t('Foto') ?></a>
                            <?php endif; ?>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['phone']) ?>" target="_blank" class="text-success small"><?= e($b['phone']) ?></a>
                        </td>
                        <td>
                            <span class="badge bg-<?= in_array($b['status'], ['confirmed', 'paid'], true) ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : ($b['status'] === 'refunded' ? 'info' : 'danger')) ?>">
                                <?= ucfirst($b['status']) ?>
                            </span>
                            <?php if (!empty($b['admin_note'])): ?><small class="d-block text-muted" style="max-width:140px;" title="<?= e($b['admin_note']) ?>"><i class="bi bi-sticky"></i> <?= e(mb_strimwidth($b['admin_note'], 0, 24, '…')) ?></small><?php endif; ?>
                        </td>
                        <td class="table-action">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><?= t('Ubah Status') ?></button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="bookings.php?update_status=<?= $b['id'] ?>&status=pending&type=<?= $btype ?>"><?= t('Pending') ?></a></li>
                                    <li><a class="dropdown-item text-success" href="bookings.php?update_status=<?= $b['id'] ?>&status=confirmed&type=<?= $btype ?>"><?= t('Confirmed') ?></a></li>
                                    <li><a class="dropdown-item text-primary" href="bookings.php?update_status=<?= $b['id'] ?>&status=paid&type=<?= $btype ?>" data-testid="mark-paid"><?= t('Paid') ?></a></li>
                                    <li><a class="dropdown-item text-warning" href="bookings.php?update_status=<?= $b['id'] ?>&status=refunded&type=<?= $btype ?>" data-testid="mark-refund"><?= t('Refunded') ?></a></li>
                                    <li><a class="dropdown-item text-danger" href="bookings.php?update_status=<?= $b['id'] ?>&status=cancelled&type=<?= $btype ?>"><?= t('Cancelled') ?></a></li>
                                </ul>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary mt-1" data-bs-toggle="modal" data-bs-target="#noteModal<?= $b['id'] ?>" title="<?= t('Catatan internal') ?>"><i class="bi bi-sticky"></i></button>
                            <a href="bookings.php?delete=<?= $b['id'] ?>&type=<?= $btype ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('Hapus booking ini?')"><i class="bi bi-trash"></i></a>
                            <!-- Modal catatan internal -->
                            <div class="modal fade" id="noteModal<?= $b['id'] ?>" tabindex="-1">
                              <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                  <form method="POST" action="bookings.php?update_status=<?= $b['id'] ?>&status=<?= e($b['status']) ?>&type=<?= $btype ?>">
                                    <div class="modal-header py-2"><h6 class="modal-title"><?= t('Catatan internal') ?> — <?= e($b['name']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                      <textarea name="admin_note" class="form-control form-control-sm" rows="3" placeholder="<?= t('Catatan untuk tim (tidak dikirim ke pelanggan email)') ?>"><?= e($b['admin_note'] ?? '') ?></textarea>
                                    </div>
                                    <div class="modal-footer py-1"><button type="submit" class="btn btn-sm btn-primary"><?= t('Simpan') ?></button></div>
                                  </form>
                                </div>
                              </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($all)): ?>
                    <tr><td colspan="12" class="text-center py-4 text-muted"><?= t('Belum ada booking') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
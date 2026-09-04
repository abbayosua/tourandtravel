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
];

if (isset($_GET['update_status'])) {
    $id = (int)$_GET['update_status'];
    $status = $_GET['status'] ?? 'pending';
    $type = $_GET['type'] ?? 'tour';
    if (in_array($status, ['pending', 'confirmed', 'cancelled']) && isset($tableMap[$type])) {
        $table = $tableMap[$type];
        db()->prepare("UPDATE `$table` SET status = ? WHERE id = ?")->execute([$status, $id]);
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

usort($all, function ($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });

$typeName = ['tour' => t('Tour'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('Kereta'), 'esim' => 'eSIM'];
$typeBadge = ['tour' => 'primary', 'attraction' => 'info', 'transfer' => 'warning text-dark', 'train' => 'success', 'esim' => 'secondary'];

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
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><?= $typeFilter ? t($typeName[$typeFilter]) : t('Semua Tipe') ?></button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="bookings.php<?= $statusFilter ? "?status=$statusFilter" : '' ?>"><?= t('Semua Tipe') ?></a></li>
                <?php foreach ($typeName as $tk => $tn): ?>
                <li><a class="dropdown-item" href="bookings.php?type=<?= $tk ?><?= $statusFilter ? "&status=$statusFilter" : '' ?>"><?= t($tn) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

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
                        <td>
                            <small>
                                <?php if (!empty($b['passport_photo'])): ?>
                                    <a href="../uploads/passports/<?= e($b['passport_photo']) ?>" target="_blank" class="text-primary small"><?= t('Foto') ?></a><br>
                                <?php endif; ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['phone']) ?>" target="_blank" class="text-success"><?= e($b['phone']) ?></a>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                <?= ucfirst($b['status']) ?>
                            </span>
                        </td>
                        <td class="table-action">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><?= t('Ubah Status') ?></button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="bookings.php?update_status=<?= $b['id'] ?>&status=pending&type=<?= $btype ?>"><?= t('Pending') ?></a></li>
                                    <li><a class="dropdown-item text-success" href="bookings.php?update_status=<?= $b['id'] ?>&status=confirmed&type=<?= $btype ?>"><?= t('Confirmed') ?></a></li>
                                    <li><a class="dropdown-item text-danger" href="bookings.php?update_status=<?= $b['id'] ?>&status=cancelled&type=<?= $btype ?>"><?= t('Cancelled') ?></a></li>
                                </ul>
                            </div>
                            <a href="bookings.php?delete=<?= $b['id'] ?>&type=<?= $btype ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('Hapus booking ini?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($all)): ?>
                    <tr><td colspan="11" class="text-center py-4 text-muted"><?= t('Belum ada booking') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
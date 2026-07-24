<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

// Update status
if (isset($_GET['update_status'])) {
    $id = (int)$_GET['update_status'];
    $status = $_GET['status'] ?? 'pending';
    if (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
        $stmt = db()->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        header('Location: bookings.php?msg=updated');
        exit;
    }
}

// Hapus booking
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM bookings WHERE id = ?")->execute([$id]);
    header('Location: bookings.php?msg=deleted');
    exit;
}

$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') $msg = 'Status booking berhasil diperbarui';
    if ($_GET['msg'] === 'deleted') $msg = 'Booking berhasil dihapus';
}

// Filter status
$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT b.*, t.title as tour_title, td.departure_date, td.return_date
        FROM bookings b
        JOIN tours t ON b.tour_id = t.id
        JOIN tour_dates td ON b.tour_date_id = td.id";
$params = [];

if ($statusFilter) {
    $sql .= " WHERE b.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY b.created_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Kelola Booking';
require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Kelola Booking</h4>
    <div class="d-flex gap-2">
        <a href="bookings.php" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
        <a href="bookings.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Pending</a>
        <a href="bookings.php?status=confirmed" class="btn btn-sm <?= $statusFilter === 'confirmed' ? 'btn-success' : 'btn-outline-success' ?>">Confirmed</a>
        <a href="bookings.php?status=cancelled" class="btn btn-sm <?= $statusFilter === 'cancelled' ? 'btn-danger' : 'btn-outline-danger' ?>">Cancelled</a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible py-2"><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Tour</th>
                        <th>Berangkat</th>
                        <th>Peserta</th>
                        <th>Total</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><strong class="small" style="font-size: 11px;"><?= e($b['booking_code'] ?? '-') ?></strong></td>
                        <td><strong><?= e($b['name']) ?></strong></td>
                        <td><small><?= e($b['tour_title']) ?></small></td>
                        <td><small><?= tglIndonesia($b['departure_date']) ?></small></td>
                        <td><?= $b['participants'] ?> org</td>
                        <td><?= formatRupiah($b['total_price']) ?></td>
                        <td>
                            <small>
                                <?php if ($b['passport_photo']): ?>
                                    <a href="../uploads/passports/<?= e($b['passport_photo']) ?>" target="_blank" class="text-primary small">Foto</a><br>
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
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Ubah Status</button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="bookings.php?update_status=<?= $b['id'] ?>&status=pending">Pending</a></li>
                                    <li><a class="dropdown-item text-success" href="bookings.php?update_status=<?= $b['id'] ?>&status=confirmed">Confirmed</a></li>
                                    <li><a class="dropdown-item text-danger" href="bookings.php?update_status=<?= $b['id'] ?>&status=cancelled">Cancelled</a></li>
                                </ul>
                            </div>
                            <a href="bookings.php?delete=<?= $b['id'] ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('Hapus booking ini?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                    <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada booking</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>

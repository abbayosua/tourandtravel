<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? '';

$sql = "SELECT u.id, u.name, u.email, u.phone, u.reseller_balance, u.created_at,
        (SELECT COUNT(*) FROM bookings WHERE user_id = u.id AND booking_source = 'reseller') AS total_bookings,
        (SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE user_id = u.id AND booking_source = 'reseller' AND status IN ('confirmed','paid')) AS total_spent
        FROM users u WHERE u.role = 'reseller'";
$params = [];

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

if ($filter === 'active') {
    $sql .= " AND u.reseller_balance > 0";
} elseif ($filter === 'zero') {
    $sql .= " AND u.reseller_balance = 0";
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$resellers = $stmt->fetchAll();

$pageTitle = 'Kelola Reseller';
require_once 'includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><i class="bi bi-shop me-2"></i>Kelola Reseller</h4>

<!-- Search & Filter -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama, email, telepon..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="filter" class="form-select form-select-sm">
            <option value="">Semua</option>
            <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Ada Saldo</option>
            <option value="zero" <?= $filter === 'zero' ? 'selected' : '' ?>>Saldo Kosong</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Filter</button></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-center">Booking</th>
                        <th class="text-end">Total Belanja</th>
                        <th>Terdaftar</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($resellers)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada reseller.</td></tr>
                <?php else: ?>
                    <?php foreach ($resellers as $r): ?>
                    <tr>
                        <td class="small">#<?= $r['id'] ?></td>
                        <td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td class="small"><?= e($r['email']) ?></td>
                        <td class="small"><?= e($r['phone'] ?? '-') ?></td>
                        <td class="text-end fw-bold text-primary"><?= formatRupiah((float)$r['reseller_balance']) ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?= $r['total_bookings'] ?></span></td>
                        <td class="text-end small"><?= formatRupiah((float)$r['total_spent']) ?></td>
                        <td class="small text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <a href="reseller-topups.php?user_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Topup History"><i class="bi bi-clock-history"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($resellers)): ?>
<p class="text-muted small mt-2">Total: <?= count($resellers) ?> reseller</p>
<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>

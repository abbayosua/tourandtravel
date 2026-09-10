<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

require_once __DIR__ . '/../includes/reseller.php';

$filterUser = (int)($_GET['user_id'] ?? 0);
$filterStatus = $_GET['status'] ?? '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $topupId = (int)($_POST['topup_id'] ?? 0);
    $action = $_POST['action'];
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($topupId && in_array($action, ['approved', 'rejected'], true)) {
        $stmt = db()->prepare("SELECT rt.id, rt.user_id, rt.amount, rt.status, u.email AS user_email FROM reseller_topups rt JOIN users u ON rt.user_id = u.id WHERE rt.id = ? AND rt.status = 'pending'");
        $stmt->execute([$topupId]);
        $topup = $stmt->fetch();

        if ($topup) {
            $sets = ["status = ?", "admin_note = ?", "approved_at = NOW()"];
            $vals = [$action, $adminNote];
            $vals[] = $topupId;
            db()->prepare("UPDATE reseller_topups SET " . implode(", ", $sets) . " WHERE id = ?")->execute($vals);

            if ($action === 'approved') {
                topUpReseller((int)$topup['user_id'], (float)$topup['amount']);
            }

            // Email notification
            require_once '../includes/email.php';
            $emailTemplate = $action === 'approved' ? 'topup-approved' : 'topup-rejected';
            $emailSubject = $action === 'approved'
                ? 'Topup Disetujui - ' . formatRupiah((float)$topup['amount'])
                : 'Topup Ditolak - ' . formatRupiah((float)$topup['amount']);
            sendEmailTemplate($topup['user_email'], $emailTemplate, [
                'amount' => formatRupiah((float)$topup['amount']),
                'admin_note' => $adminNote,
                'track_link' => BASE_URL . '/reseller-dashboard.php',
                'subject' => $emailSubject,
            ], null);

            // In-app notification
            require_once '../includes/notifications.php';
            $notifMsg = $action === 'approved'
                ? 'Topup ' . formatRupiah((float)$topup['amount']) . ' telah disetujui. Saldo bertambah.'
                : 'Topup ' . formatRupiah((float)$topup['amount']) . ' telah ditolak.';
            addNotification((int)$topup['user_id'], 'payment', 'Status Topup', $notifMsg, 'reseller-dashboard.php');

            header('Location: reseller-topups.php?msg=' . $action . ($filterUser ? '&user_id=' . $filterUser : ''));
            exit;
        }
    }
}

// Query
$sql = "SELECT rt.*, u.name AS user_name, u.email AS user_email FROM reseller_topups rt JOIN users u ON rt.user_id = u.id";
$params = [];
$where = [];

if ($filterUser) {
    $where[] = "rt.user_id = ?";
    $params[] = $filterUser;
}
if ($filterStatus) {
    $where[] = "rt.status = ?";
    $params[] = $filterStatus;
} else {
    $where[] = "rt.status IN ('pending', 'approved', 'rejected')";
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY rt.created_at DESC LIMIT 100";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$topups = $stmt->fetchAll();

$pageTitle = 'Topup Reseller';
require_once 'includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2"></i>Topup Reseller</h4>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?= $_GET['msg'] === 'approved' ? 'success' : 'warning' ?> py-2">
        Topup berhasil di-<?= $_GET['msg'] ?>.
    </div>
<?php endif; ?>

<!-- Filter -->
<form method="GET" class="row g-2 mb-3">
    <?php if ($filterUser): ?><input type="hidden" name="user_id" value="<?= $filterUser ?>"><?php endif; ?>
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary">Filter</button></div>
</form>

<?php if (empty($topups)): ?>
<div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">Tidak ada data topup.</div></div>
<?php else: ?>
<?php foreach ($topups as $t): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="fw-semibold"><?= e($t['user_name']) ?> <small class="text-muted">(<?= e($t['user_email']) ?>)</small></div>
                <div class="fs-5 fw-bold text-primary my-1"><?= formatRupiah((float)$t['amount']) ?></div>
                <div class="small text-muted">
                    <span class="text-capitalize"><?= str_replace('_', ' ', $t['payment_method']) ?></span> · <?= date('d M Y H:i', strtotime($t['created_at'])) ?>
                </div>
                <?php if ($t['proof_path']): ?>
                    <a href="../<?= e($t['proof_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-1"><i class="bi bi-image me-1"></i>Lihat Bukti</a>
                <?php endif; ?>
                <?php if ($t['admin_note']): ?>
                    <div class="small text-muted mt-1"><i class="bi bi-sticky"></i> <?= e($t['admin_note']) ?></div>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <?php
                $badge = match($t['status']) {
                    'approved' => 'bg-success',
                    'rejected' => 'bg-danger',
                    default => 'bg-warning text-dark',
                };
                ?>
                <span class="badge <?= $badge ?> mb-2"><?= ucfirst($t['status']) ?></span>

                <?php if ($t['status'] === 'pending'): ?>
                <div class="d-flex gap-1 mt-1">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="topup_id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="action" value="approved">
                        <input type="text" name="admin_note" class="form-control form-control-sm mb-1" placeholder="Catatan (opsional)" style="width:150px">
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                    </form>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="topup_id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="action" value="rejected">
                        <input type="text" name="admin_note" class="form-control form-control-sm mb-1" placeholder="Alasan (opsional)" style="width:150px">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i> Reject</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>

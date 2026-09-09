<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

// Manual trigger check
if (isset($_GET['action']) && $_GET['action'] === 'check') {
    require_once '../includes/price-alert-checker.php';
    $result = checkPriceAlerts();
    header('Location: price-alerts.php?msg=checked&n=' . $result['notified']);
    exit;
}

// Delete alert
if (isset($_GET['delete'])) {
    db()->prepare("DELETE FROM price_alerts WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: price-alerts.php?msg=deleted');
    exit;
}

$rows = db()->query("SELECT pa.*, u.name AS uname, u.email, t.title AS tour_title, h.name AS hotel_name FROM price_alerts pa LEFT JOIN users u ON pa.user_id = u.id LEFT JOIN tours t ON pa.item_type = 'tour' AND pa.item_id = t.id LEFT JOIN hotels h ON pa.item_type = 'hotel' AND pa.item_id = h.id ORDER BY pa.active DESC, pa.created_at DESC")->fetchAll();

$pageTitle = t('Price Alerts');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><i class="bi bi-bell text-warning me-2"></i><?= t('Price Alerts') ?></h4>
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2">
        <?php if ($_GET['msg'] === 'checked'): ?>
            <?= t('Pengecekan selesai') ?> — <?= (int)($_GET['n'] ?? 0) ?> <?= t('alert dinotifikasi') ?>
        <?php else: ?>
            <?= t('Berhasil dihapus') ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="d-flex gap-2 mb-3">
    <a href="price-alerts.php?action=check" class="btn btn-warning btn-sm" onclick="return confirm('<?= t('Jalankan pengecekan harga sekarang?') ?>')">
        <i class="bi bi-play-circle me-1"></i><?= t('Jalankan Pengecekan') ?>
    </a>
    <span class="text-muted small align-self-center"><?= count($rows) ?> <?= t('total alert') ?></span>
</div>

<div class="card border-0 shadow-sm"><div class="card-body p-0">
<?php if (!count($rows)): ?>
    <div class="text-center py-4 text-muted"><?= t('Belum ada price alert.') ?></div>
<?php endif; ?>
<?php foreach ($rows as $r): ?>
    <div class="border-bottom p-3 d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-semibold small"><?= e($r['uname'] ?? 'User #' . $r['user_id']) ?></span>
                <span class="badge bg-<?= $r['item_type'] === 'tour' ? 'primary' : 'success' ?>"><?= e(ucfirst($r['item_type'])) ?></span>
                <?php if ($r['active']): ?>
                    <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                <?php endif; ?>
            </div>
            <div class="small text-muted">
                <?= e($r['tour_title'] ?? $r['hotel_name'] ?? '-') ?> · Target: <?= formatRupiah($r['target_price'], $r['currency']) ?>
                <?php if ($r['notified_at']): ?> · Notif: <?= date('d M Y H:i', strtotime($r['notified_at'])) ?><?php endif; ?>
            </div>
            <div class="small text-muted" style="font-size:11px;"><?= e($r['email'] ?? '') ?></div>
        </div>
        <div>
            <a href="price-alerts.php?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('Hapus alert ini?') ?>')"><i class="bi bi-trash"></i></a>
        </div>
    </div>
<?php endforeach; ?>
</div></div>

<?php require_once 'includes/admin-footer.php'; ?>

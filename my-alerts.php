<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-alerts.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Handle toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_alert'])) {
    $alertId = (int)$_POST['alert_id'];
    db()->prepare("UPDATE price_alerts SET active = 1 - active WHERE id = ? AND user_id = ?")->execute([$alertId, $userId]);
    header('Location: my-alerts.php?msg=updated');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    db()->prepare("DELETE FROM price_alerts WHERE id = ? AND user_id = ?")->execute([$deleteId, $userId]);
    header('Location: my-alerts.php?msg=deleted');
    exit;
}

// Fetch user alerts
$stmt = db()->prepare("SELECT pa.*, t.title AS tour_title, t.slug AS tour_slug, h.name AS hotel_name, h.slug AS hotel_slug FROM price_alerts pa LEFT JOIN tours t ON pa.item_type = 'tour' AND pa.item_id = t.id LEFT JOIN hotels h ON pa.item_type = 'hotel' AND pa.item_id = h.id WHERE pa.user_id = ? ORDER BY pa.created_at DESC");
$stmt->execute([$userId]);
$alerts = $stmt->fetchAll();

$pageTitle = t('Price Alert Saya');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-bell me-2 text-warning"></i><?= t('Price Alert Saya') ?></h4>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible py-2"><?= t('Berhasil diperbarui') ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (count($alerts) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?= t('Item') ?></th>
                        <th><?= t('Tipe') ?></th>
                        <th><?= t('Harga Target') ?></th>
                        <th class="text-center"><?= t('Status') ?></th>
                        <th><?= t('Terakhir Notif') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alerts as $a): ?>
                    <tr>
                        <td>
                            <?php if ($a['item_type'] === 'tour'): ?>
                                <a href="tour-detail.php?slug=<?= e($a['tour_slug']) ?>" class="text-decoration-none fw-semibold"><?= e($a['tour_title'] ?? '-') ?></a>
                            <?php else: ?>
                                <a href="hotel-detail.php?slug=<?= e($a['hotel_slug']) ?>" class="text-decoration-none fw-semibold"><?= e($a['hotel_name'] ?? '-') ?></a>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $a['item_type'] === 'tour' ? 'primary' : 'success' ?>"><?= e(ucfirst($a['item_type'])) ?></span></td>
                        <td class="fw-bold"><?= formatRupiah($a['target_price'], $a['currency']) ?></td>
                        <td class="text-center">
                            <?php if ($a['active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= $a['notified_at'] ? date('d M Y H:i', strtotime($a['notified_at'])) : '-' ?></td>
                        <td class="text-end">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="alert_id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" name="toggle_alert" class="btn btn-sm btn-outline-<?= $a['active'] ? 'warning' : 'success' ?>" title="<?= $a['active'] ? t('Nonaktifkan') : t('Aktifkan') ?>">
                                    <i class="bi bi-<?= $a['active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                                </button>
                            </form>
                            <a href="my-alerts.php?delete=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('Hapus alert ini?') ?>')" title="<?= t('Hapus') ?>">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-bell-slash fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Belum ada price alert.') ?></p>
            <p class="small text-muted"><?= t('Klik tombol "Set Price Alert" pada detail tour atau hotel untuk membuat alert.') ?></p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4 mt-2"><i class="bi bi-search me-1"></i><?= t('Jelajahi Tour') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

if (!isLoggedIn()) { header('Location: login.php?redirect=notifications.php'); exit; }

if (isset($_GET['read'])) {
    db()->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?")->execute([(int)$_GET['read'], $_SESSION['user_id']]);
    header('Location: notifications.php');
    exit;
}
if (isset($_GET['read_all'])) {
    db()->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL")->execute([$_SESSION['user_id']]);
    header('Location: notifications.php');
    exit;
}

$rows = getNotifications((int)$_SESSION['user_id']);
$unread = getUnreadCount((int)$_SESSION['user_id']);
$pageTitle = t('Notifikasi');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="bi bi-bell me-2"></i><?= t('Notifikasi') ?> <?php if ($unread): ?><span class="badge bg-danger"><?= $unread ?></span><?php endif; ?></h4>
            <?php if ($unread): ?><a href="?read_all=1" class="btn btn-sm btn-outline-primary"><?= t('Tandai semua dibaca') ?></a><?php endif; ?>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (!count($rows)): ?>
                    <div class="text-center py-5 text-muted"><i class="bi bi-bell-slash fs-1"></i><p class="mt-2"><?= t('Belum ada notifikasi.') ?></p></div>
                <?php endif; ?>
                <?php foreach ($rows as $n): ?>
                <div class="d-flex justify-content-between align-items-start px-4 py-3 border-bottom <?= $n['read_at'] ? '' : 'bg-light' ?>">
                    <div>
                        <div class="fw-semibold small <?= $n['read_at'] ? 'text-muted' : '' ?>"><?= e($n['title']) ?></div>
                        <?php if ($n['body']): ?><small class="text-muted"><?= e($n['body']) ?></small><?php endif; ?>
                    </div>
                    <div class="text-end">
                        <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>" class="btn btn-sm btn-outline-primary"><?= t('Buka') ?></a><?php endif; ?>
                        <?php if (!$n['read_at']): ?><a href="?read=<?= (int)$n['id'] ?>" class="btn btn-sm btn-link small"><?= t('Tandai dibaca') ?></a><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

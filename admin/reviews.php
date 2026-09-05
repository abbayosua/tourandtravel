<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Ulasan');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_review'])) {
    db()->prepare("UPDATE reviews SET reply_text = ?, reply_at = NOW() WHERE id = ?")->execute([trim($_POST['reply_text'] ?? ''), (int)$_POST['review_id']]);
    header('Location: reviews.php?msg=updated');
    exit;
}
if (isset($_GET['delete'])) {
    db()->prepare("DELETE FROM review_images WHERE review_id = ?")->execute([(int)$_GET['delete']]);
    db()->prepare("DELETE FROM reviews WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: reviews.php?msg=deleted');
    exit;
}

$rows = db()->query("SELECT rv.*, t.title AS tour_title, u.name AS uname FROM reviews rv JOIN tours t ON rv.tour_id = t.id LEFT JOIN users u ON rv.user_id = u.id ORDER BY rv.id DESC LIMIT 100")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><?= t('Ulasan') ?></h4>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success py-2 small"><?= t('Berhasil diperbarui') ?></div><?php endif; ?>

<div class="card border-0 shadow-sm"><div class="card-body p-0">
<?php if (!count($rows)): ?>
    <div class="text-center py-4 text-muted"><?= t('Belum ada ulasan.') ?></div>
<?php endif; ?>
<?php foreach ($rows as $r): ?>
    <div class="border-bottom p-3">
        <div class="d-flex justify-content-between">
            <div>
                <span class="fw-semibold small"><?= e($r['uname'] ?? 'Guest') ?></span>
                <span class="text-warning small"><?= renderStars($r['rating']) ?></span>
                <small class="text-muted">· <?= e($r['tour_title']) ?></small>
            </div>
            <a href="reviews.php?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('Hapus ulasan ini?') ?>')"><?= t('Hapus') ?></a>
        </div>
        <p class="small text-muted mt-2 mb-2"><?= nl2br(e($r['comment'])) ?></p>
        <form method="POST" class="d-flex gap-2">
            <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
            <input type="text" name="reply_text" class="form-control form-control-sm" placeholder="<?= t('Balas ulasan...') ?>" value="<?= e($r['reply_text'] ?? '') ?>">
            <button type="submit" name="reply_review" class="btn btn-sm btn-primary"><?= t('Balas') ?></button>
        </form>
    </div>
<?php endforeach; ?>
</div></div>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Blog');

if (isset($_GET['delete'])) {
    db()->prepare("DELETE FROM posts WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: posts.php?msg=deleted');
    exit;
}
if (isset($_GET['toggle'])) {
    db()->prepare("UPDATE posts SET status = IF(status='published','draft','published'), published_at = IF(status='published',NULL,NOW()) WHERE id = ?")->execute([(int)$_GET['toggle']]);
    header('Location: posts.php?msg=updated');
    exit;
}

$rows = db()->query("SELECT * FROM posts ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Blog') ?></h4>
    <a href="post-edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?= t('Tambah') ?></a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2 small"><?= t('Berhasil diperbarui') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th><?= t('Judul') ?></th><th><?= t('Kategori') ?></th><th><?= t('Status') ?></th><th><?= t('Tanggal') ?></th><th class="text-end"><?= t('Aksi') ?></th></tr>
            </thead>
            <tbody>
            <?php if (!count($rows)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4"><?= t('Belum ada artikel.') ?></td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="small fw-semibold"><?= e($r['title']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($r['category'] ?? '-') ?></span></td>
                    <td><span class="badge <?= $r['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst(t($r['status'])) ?></span></td>
                    <td><small class="text-muted"><?= $r['published_at'] ? date('d/m/y', strtotime($r['published_at'])) : '-' ?></small></td>
                    <td class="text-end">
                        <a href="post-edit.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><?= t('Edit') ?></a>
                        <a href="posts.php?toggle=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= t('Draft') ?>/<?= t('Publish') ?></a>
                        <a href="posts.php?delete=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= t('Hapus artikel ini?') ?>')"><?= t('Hapus') ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

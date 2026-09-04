<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => t('Berhasil ditambahkan'), 'updated' => t('Berhasil diperbarui'), 'deleted' => t('Berhasil dihapus'), default => '' };
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; db()->prepare("DELETE FROM faq_items WHERE id=?")->execute([$id]); header('Location: faq.php?msg=deleted'); exit; }

$categories = db()->query("SELECT * FROM faq_categories ORDER BY sort_order ASC, name ASC")->fetchAll();
$items = db()->query("SELECT fi.*, fc.name AS category_name FROM faq_items fi JOIN faq_categories fc ON fc.id = fi.category_id ORDER BY fc.sort_order ASC, fi.id ASC")->fetchAll();

$pageTitle = t('Kelola FAQ');
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('FAQ') ?></h4>
    <div>
        <a href="faq-category.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-folder-plus"></i> <?= t('Kategori') ?></a>
        <a href="faq-edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?= t('Tambah') ?></a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th><?= t('Pertanyaan') ?></th><th><?= t('Kategori') ?></th><th><?= t('Status') ?></th><th><?= t('Aksi') ?></th></tr></thead>
<tbody><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td><td><?=e($i['question'])?></td><td><span class="badge bg-info text-dark"><?=e($i['category_name'])?></span></td>
<td><span class="badge bg-<?=$i['is_active']?'success':'secondary'?>"><?=$i['is_active']?'Aktif':'Nonaktif'?></span></td>
<td><a href="faq-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="faq.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('Hapus?') ?>')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
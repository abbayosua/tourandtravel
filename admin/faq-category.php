<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => 'Berhasil ditambahkan', 'updated' => 'Berhasil diperbarui', 'deleted' => 'Berhasil dihapus', default => '' };
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; db()->prepare("DELETE FROM faq_categories WHERE id=?")->execute([$id]); header('Location: faq-category.php?msg=deleted'); exit; }

$items = db()->query("SELECT c.*, (SELECT COUNT(*) FROM faq_items fi WHERE fi.category_id = c.id) AS item_count FROM faq_categories c ORDER BY c.sort_order ASC, c.name ASC")->fetchAll();

$pageTitle = 'Kategori FAQ';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Kategori FAQ</h4>
    <a href="faq-category-edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah</a>
</div>
<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th>Nama</th><th>Urutan</th><th>Jumlah FAQ</th><th>Aksi</th></tr></thead>
<tbody><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td><td><?=e($i['name'])?></td><td><?=$i['sort_order']?></td><td><?=$i['item_count']?></td>
<td><a href="faq-category-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="faq-category.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
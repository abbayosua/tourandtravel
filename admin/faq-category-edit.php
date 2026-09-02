<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
$isAdd = $id === 0;
$item = null;

if (!$isAdd) {
    $item = db()->prepare("SELECT * FROM faq_categories WHERE id = ?");
    $item->execute([$id]);
    $item = $item->fetch();
    if (!$item) { header('Location: faq-category.php'); exit; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (!$name) $error = 'Nama kategori wajib diisi';

    if (!$error) {
        if ($isAdd) {
            $st = db()->prepare("INSERT INTO faq_categories (name, sort_order) VALUES (?, ?)");
            $st->execute([$name, $sortOrder]);
            header('Location: faq-category.php?msg=added'); exit;
        } else {
            $st = db()->prepare("UPDATE faq_categories SET name=?, sort_order=? WHERE id=?");
            $st->execute([$name, $sortOrder, $id]);
            header('Location: faq-category.php?msg=updated'); exit;
        }
    }
}

$pageTitle = $isAdd ? 'Tambah Kategori' : 'Edit Kategori';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= $isAdd ? 'Tambah' : 'Edit' ?> Kategori</h4>
    <a href="faq-category.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
</div>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label">Nama Kategori</label><input name="name" class="form-control" value="<?=e($item['name']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Urutan</label><input name="sort_order" type="number" class="form-control" value="<?=$item['sort_order']??0?>" min="0"></div>
</div></div>
<button type="submit" class="btn btn-primary w-100"><?= $isAdd ? 'Tambah' : 'Simpan' ?></button>
<a href="faq-category.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>
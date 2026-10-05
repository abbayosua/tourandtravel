<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once 'includes/admin-access.php';
requireAdminPage();

$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => t('Berhasil ditambahkan'), 'updated' => t('Berhasil diperbarui'), 'deleted' => t('Berhasil dihapus'), default => '' };
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; db()->prepare("DELETE FROM hotels WHERE id=?")->execute([$id]); header('Location: hotels.php?msg=deleted'); exit; }

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $st = db()->prepare("SELECT * FROM hotels WHERE name LIKE ? OR city LIKE ? ORDER BY created_at DESC");
    $st->execute(["%$q%", "%$q%"]);
    $items = $st->fetchAll();
} else {
    $items = db()->query("SELECT * FROM hotels ORDER BY created_at DESC")->fetchAll();
}

$pageTitle = t('Kelola Hotel');
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Hotel') ?></h4>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex gap-1">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="<?= t('Nama') ?> / <?= t('Kota') ?>" value="<?= e($q) ?>" data-testid="admin-hotel-search">
            <button type="submit" class="btn btn-sm btn-outline-primary"><?= t('Cari') ?></button>
        </form>
        <a href="hotel-add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?= t('Tambah') ?></a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<div class="card border-0 shadow-sm"><div class="card-body p-0 table-responsive">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th><?= t('Nama') ?></th><th><?= t('Kota') ?></th><th><?= t('Bintang') ?></th><th><?= t('Harga') ?></th><th><?= t('Aksi') ?></th></tr></thead>
<tbody><?php if (empty($items)): ?><tr><td colspan="10" class="text-center text-muted py-4"><?= $q !== '' ? t('Tidak ada hasil untuk pencarian Anda.') : t('Belum ada data.') ?></td></tr><?php endif; ?><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td><td><?=e($i['name'])?></td><td><?=e($i['city'])?></td>
<td><?=str_repeat('★',$i['star_rating'])?></td>
<td><?=formatRupiah($i['price_per_night'])?><?= t('/malam') ?></td>
<td><a href="hotel-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="hotels.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('Hapus?') ?>')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>

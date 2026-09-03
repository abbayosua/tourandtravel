<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => t('Berhasil ditambahkan'), 'updated' => t('Berhasil diperbarui'), 'deleted' => t('Berhasil dihapus'), default => '' };
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; db()->prepare("DELETE FROM trains WHERE id=?")->execute([$id]); header('Location: trains.php?msg=deleted'); exit; }

$items = db()->query("SELECT * FROM trains ORDER BY created_at DESC")->fetchAll();

$pageTitle = t('Kelola Kereta Api');
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Kereta Api') ?></h4>
    <a href="train-edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?= t('Tambah') ?></a>
</div>
<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th><?= t('Nama') ?></th><th><?= t('Rute') ?></th><th><?= t('Jadwal') ?></th><th><?= t('Durasi') ?></th><th><?= t('Kelas') ?></th><th><?= t('Harga') ?></th><th><?= t('Status') ?></th><th><?= t('Aksi') ?></th></tr></thead>
<tbody><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td><td><?=e($i['name'])?></td><td><?=e($i['route_from'])?> → <?=e($i['route_to'])?></td>
<td><?=substr($i['departure_time'],0,5)?> - <?=substr($i['arrival_time'],0,5)?></td>
<td><?=e($i['duration'])?></td><td><?=e($i['class'])?></td>
<td><?=formatRupiah($i['price'])?></td>
<td><span class="badge bg-<?=$i['is_active']?'success':'secondary'?>"><?=$i['is_active']?'Aktif':'Nonaktif'?></span></td>
<td><a href="train-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="trains.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
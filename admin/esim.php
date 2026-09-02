<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => 'Berhasil ditambahkan', 'updated' => 'Berhasil diperbarui', 'deleted' => 'Berhasil dihapus', default => '' };
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; db()->prepare("DELETE FROM connectivity_products WHERE id=?")->execute([$id]); header('Location: esim.php?msg=deleted'); exit; }

$items = db()->query("SELECT * FROM connectivity_products ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Kelola eSIM';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">eSIM & Connectivity</h4>
    <a href="esim-edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah</a>
</div>
<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th>Nama</th><th>Tipe</th><th>Negara</th><th>Kuota</th><th>Durasi</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td><td><?=e($i['name'])?></td><td><span class="badge bg-info text-dark"><?=strtoupper(e($i['type']))?></span></td>
<td><?=e($i['country'])?></td><td><?=e($i['data_quota'])?></td><td><?=$i['duration_days']?> hari</td>
<td><?=formatRupiah($i['price'])?></td>
<td><span class="badge bg-<?=$i['is_active']?'success':'secondary'?>"><?=$i['is_active']?'Aktif':'Nonaktif'?></span></td>
<td><a href="esim-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="esim.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => 'Berhasil ditambahkan', 'updated' => 'Berhasil diperbarui', 'deleted' => 'Berhasil dihapus', default => '' };
if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; db()->prepare("DELETE FROM transfers WHERE id=?")->execute([$id]); header('Location: transfers.php?msg=deleted'); exit; }

$items = db()->query("SELECT * FROM transfers ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Kelola Transfer';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Transfer Bandara</h4>
    <a href="transfer-edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah</a>
</div>
<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th>Nama</th><th>Rute</th><th>Kendaraan</th><th>Max Pax</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td><td><?=e($i['name'])?></td><td><?=e($i['from_city'])?> → <?=e($i['to_city'])?></td>
<td><?=e($i['vehicle_type'] ?? '-')?></td><td><?=$i['max_passengers']?></td>
<td><?=formatRupiah($i['price'])?></td>
<td><span class="badge bg-<?=$i['is_active']?'success':'secondary'?>"><?=$i['is_active']?'Aktif':'Nonaktif'?></span></td>
<td><a href="transfer-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="transfers.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
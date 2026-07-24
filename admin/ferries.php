<?php
require_once '../includes/config.php'; require_once '../includes/db.php'; require_once '../includes/functions.php'; require_once '../includes/auth.php'; cekLogin();
$msg=''; if(isset($_GET['msg'])) $msg=match($_GET['msg']){'deleted'=>'OK',default=>''};
if(isset($_GET['delete'])){$id=(int)$_GET['delete'];db()->prepare("DELETE FROM ferries WHERE id=?")->execute([$id]);header('Location: ferries.php?msg=deleted');exit;}
$items=db()->query("SELECT * FROM ferries ORDER BY company,route_from")->fetchAll();
$pageTitle='Kelola Ferry'; require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="fw-bold mb-0">Ferry</h4></div>
<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0"><thead class="table-light"><tr><th>#</th><th>Perusahaan</th><th>Rute</th><th>Berangkat</th><th>Tiba</th><th>Harga</th><th>Aksi</th></tr></thead>
<tbody><?php foreach($items as $i):?><tr>
<td><?=$i['id']?></td><td><?=e($i['company'])?></td><td><?=e($i['route_from'] .' → '. $i['route_to'])?></td>
<td><?=date('H:i',strtotime($i['departure_time']))?></td><td><?=date('H:i',strtotime($i['arrival_time']))?></td>
<td><?=formatRupiah($i['price'])?></td>
<td><a href="ferry-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="ferries.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach;?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php';?>

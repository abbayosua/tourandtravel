<?php
require_once '../includes/config.php'; require_once '../includes/db.php'; require_once '../includes/functions.php'; require_once '../includes/auth.php'; cekLogin();
$msg=''; if(isset($_GET['msg'])) $msg=match($_GET['msg']){'deleted'=>'OK',default=>''};
if(isset($_GET['delete'])){$id=(int)$_GET['delete'];db()->prepare("DELETE FROM rental_cars WHERE id=?")->execute([$id]);header('Location: rental-cars.php?msg=deleted');exit;}
$items=db()->query("SELECT * FROM rental_cars ORDER BY city,name")->fetchAll();
$pageTitle='Kelola Rental Mobil'; require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="fw-bold mb-0">Rental Mobil</h4></div>
<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0"><thead class="table-light"><tr><th>#</th><th>Nama</th><th>Tipe</th><th>Kota</th><th>Harga/Hari</th><th>Transmisi</th><th>Kursi</th><th>Aksi</th></tr></thead>
<tbody><?php foreach($items as $i):?><tr>
<td><?=$i['id']?></td><td><?=e($i['name'])?></td><td><?=e($i['car_type'])?></td><td><?=e($i['city'])?></td>
<td><?=formatRupiah($i['price_per_day'])?></td><td><?=ucfirst($i['transmission'])?></td><td><?=$i['passenger_capacity']?></td>
<td><a href="rental-car-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="rental-cars.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach;?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php';?>

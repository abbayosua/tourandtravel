<?php
require_once '../includes/config.php'; require_once '../includes/db.php'; require_once '../includes/functions.php'; require_once '../includes/auth.php'; cekLogin();
$msg=''; if(isset($_GET['msg'])) $msg=match($_GET['msg']){'added'=>'OK','updated'=>'OK','deleted'=>'OK',default=>''};
if(isset($_GET['delete'])){$id=(int)$_GET['delete'];db()->prepare("DELETE FROM flights WHERE id=?")->execute([$id]);header('Location: flights.php?msg=deleted');exit;}
$items=db()->query("SELECT * FROM flights ORDER BY airline,flight_number")->fetchAll();
$q = trim($_GET['q'] ?? '');
if ($q !== '') $items = array_values(array_filter($items, fn($r) => stripos(json_encode($r), $q) !== false));
$pageTitle=t('Kelola Pesawat'); require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="fw-bold mb-0"><?= t('Pesawat') ?></h4>
<a href="flight-add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?= t('Tambah') ?></a></div>
<form method="GET" class="mb-3" style="max-width:340px;"><div class="input-group input-group-sm"><input type="text" name="q" class="form-control" placeholder="<?= t('Cari') ?>" value="<?= e($q) ?>" data-testid="admin-list-search"><button type="submit" class="btn btn-outline-primary"><?= t('Cari') ?></button></div></form>
<?php if($msg):?><div class="alert alert-success py-2"><?=$msg?></div><?php endif;?>
<div class="card border-0 shadow-sm"><div class="card-body p-0 table-responsive">
<table class="table table-hover mb-0 admin-table"><thead class="table-light"><tr><th>#</th><th><?= t('Maskapai') ?></th><th><?= t('No.') ?></th><th><?= t('Rute') ?></th><th><?= t('Jam') ?></th><th><?= t('Harga') ?></th><th><?= t('Kelas') ?></th><th><?= t('Aksi') ?></th></tr></thead>
<tbody><?php if (empty($items)): ?><tr><td colspan="10" class="text-center text-muted py-4"><?= $q !== '' ? t('Tidak ada hasil untuk pencarian Anda.') : t('Belum ada data.') ?></td></tr><?php endif; ?><?php foreach($items as $i):?><tr>
<td><?=$i['id']?></td><td><?=e($i['airline'])?></td><td><?=e($i['flight_number'])?></td>
<td><?=e(substr($i['from_city'],0,10))?> → <?=e(substr($i['to_city'],0,10))?></td>
<td><?=date('H:i',strtotime($i['departure_time']))?>-<?=date('H:i',strtotime($i['arrival_time']))?></td>
<td><?=formatRupiah($i['price'])?></td><td><span class="badge bg-secondary"><?= e(enumLabel('flight_class', $i['class'])) ?></span></td>
<td><a href="flight-edit.php?id=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="flights.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('Hapus?') ?>')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach;?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php';?>

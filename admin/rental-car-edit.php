<?php
require_once '../includes/config.php'; require_once '../includes/db.php'; require_once '../includes/functions.php'; require_once '../includes/auth.php'; cekLogin();
$id=(int)($_GET['id']??0);
$stmt=db()->prepare("SELECT * FROM rental_cars WHERE id=?");
$stmt->execute([$id]); $item=$stmt->fetch();
if(!$item){header('Location: rental-cars.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['name']??''); $type=trim($_POST['car_type']??''); $city=trim($_POST['city']??'');
    $price=(float)($_POST['price']??0); $trans=$_POST['transmission']??'automatic'; $seats=(int)($_POST['seats']??5);
    if($name&&$type&&$city&&$price>0){
        $slug=buatSlug($name.'-'.$city); $s=db()->prepare("UPDATE rental_cars SET name=?,slug=?,car_type=?,city=?,price_per_day=?,transmission=?,passenger_capacity=? WHERE id=?");
        $s->execute([$name,$slug,$type,$city,$price,$trans,$seats,$id]);
        header('Location: rental-cars.php?msg=updated');exit;
    } else $error='Isi semua field';
}
$pageTitle='Edit Rental Mobil'; require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3">Edit Rental Mobil</h4>
<?php if($error):?><div class="alert alert-danger py-2"><?=$error?></div><?php endif;?>
<form method="POST">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="row g-2"><div class="col-md-6"><label class="form-label">Nama Mobil</label><input name="name" class="form-control" value="<?=e($item['name'])?>" required></div>
<div class="col-md-3"><label class="form-label">Tipe</label><input name="car_type" class="form-control" value="<?=e($item['car_type'])?>" required></div>
<div class="col-md-3"><label class="form-label">Kota</label><input name="city" class="form-control" value="<?=e($item['city'])?>" required></div></div>
<div class="row g-2 mt-2"><div class="col-md-4"><label class="form-label">Harga/Hari (Rp)</label><input name="price" type="number" class="form-control" value="<?=$item['price_per_day']?>" required></div>
<div class="col-md-4"><label class="form-label">Transmisi</label><select name="transmission" class="form-select"><option value="automatic" <?=$item['transmission']=='automatic'?'selected':''?>>Automatic</option><option value="manual" <?=$item['transmission']=='manual'?'selected':''?>>Manual</option></select></div>
<div class="col-md-4"><label class="form-label">Kapasitas</label><input name="seats" type="number" class="form-control" value="<?=$item['passenger_capacity']?>" min="2" max="20"></div></div>
</div></div>
<button type="submit" class="btn btn-primary">Simpan</button>
<a href="rental-cars.php" class="btn btn-outline-secondary">Batal</a></form>
<?php require_once 'includes/admin-footer.php';?>

<?php
require_once '../includes/config.php'; require_once '../includes/db.php'; require_once '../includes/functions.php'; require_once '../includes/auth.php'; cekLogin();
$id=(int)($_GET['id']??0);
$stmt=db()->prepare("SELECT * FROM ferries WHERE id=?");
$stmt->execute([$id]); $item=$stmt->fetch();
if(!$item){header('Location: ferries.php');exit;}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $company=trim($_POST['company']??''); $from=trim($_POST['route_from']??''); $to=trim($_POST['route_to']??'');
    $dep=$_POST['departure_time']??''; $arr=$_POST['arrival_time']??'';
    $price=(float)($_POST['price']??0); $vessel=trim($_POST['vessel_name']??'');
    if($company&&$from&&$to&&$dep&&$arr&&$price>0){
        $s=db()->prepare("UPDATE ferries SET company=?,route_from=?,route_to=?,departure_time=?,arrival_time=?,price=?,vessel_name=? WHERE id=?");
        $s->execute([$company,$from,$to,$dep,$arr,$price,$vessel,$id]);
        header('Location: ferries.php?msg=updated');exit;
    } else $error='Isi semua field';
}
$pageTitle='Edit Ferry'; require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3">Edit Ferry</h4>
<?php if($error):?><div class="alert alert-danger py-2"><?=$error?></div><?php endif;?>
<form method="POST">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="row g-2"><div class="col-md-4"><label class="form-label">Perusahaan</label><input name="company" class="form-control" value="<?=e($item['company'])?>" required></div>
<div class="col-md-4"><label class="form-label">Kapal</label><input name="vessel_name" class="form-control" value="<?=e($item['vessel_name']??'')?>"></div>
<div class="col-md-4"><label class="form-label">Harga (Rp)</label><input name="price" type="number" class="form-control" value="<?=$item['price']?>" required></div></div>
<div class="row g-2 mt-2"><div class="col-md-4"><label class="form-label">Dari</label><input name="route_from" class="form-control" value="<?=e($item['route_from'])?>" required></div>
<div class="col-md-4"><label class="form-label">Ke</label><input name="route_to" class="form-control" value="<?=e($item['route_to'])?>" required></div></div>
<div class="row g-2 mt-2"><div class="col-md-4"><label class="form-label">Berangkat</label><input name="departure_time" type="time" class="form-control" value="<?=$item['departure_time']?>" required></div>
<div class="col-md-4"><label class="form-label">Tiba</label><input name="arrival_time" type="time" class="form-control" value="<?=$item['arrival_time']?>" required></div></div>
</div></div>
<button type="submit" class="btn btn-primary">Simpan</button>
<a href="ferries.php" class="btn btn-outline-secondary">Batal</a></form>
<?php require_once 'includes/admin-footer.php';?>

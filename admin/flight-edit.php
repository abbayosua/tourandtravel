<?php
require_once '../includes/config.php'; require_once '../includes/db.php'; require_once '../includes/functions.php'; require_once '../includes/auth.php'; cekLogin();
$id=(int)($_GET['id']??0);
$item=db()->prepare("SELECT * FROM flights WHERE id=?")->execute([$id]);
$item=db()->prepare("SELECT * FROM flights WHERE id=?")->execute([$id]);

// Re-fetch properly
$stmt=db()->prepare("SELECT * FROM flights WHERE id=?");
$stmt->execute([$id]);
$item=$stmt->fetch();
if(!$item){header('Location: flights.php');exit;}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $airline=trim($_POST['airline']??''); $fn=trim($_POST['flight_number']??'');
    $from=trim($_POST['from_city']??''); $to=trim($_POST['to_city']??'');
    $dep=$_POST['departure_time']??''; $arr=$_POST['arrival_time']??'';
    $dur=trim($_POST['duration']??''); $price=(float)($_POST['price']??0);
    $class=$_POST['class']??'economy';
    if($airline&&$fn&&$from&&$to&&$dep&&$arr&&$dur&&$price>0){
        $stmt=db()->prepare("UPDATE flights SET airline=?,flight_number=?,from_city=?,to_city=?,departure_time=?,arrival_time=?,duration=?,price=?,class=? WHERE id=?");
        $stmt->execute([$airline,$fn,$from,$to,$dep,$arr,$dur,$price,$class,$id]);
        header('Location: flights.php?msg=updated');exit;
    } else $error='Semua field wajib diisi';
}
$pageTitle='Edit Pesawat'; require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3">Edit Pesawat</h4>
<?php if($error):?><div class="alert alert-danger py-2"><?=$error?></div><?php endif;?>
<form method="POST">
<div class="row"><div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="row g-2"><div class="col-md-6 mb-3"><label class="form-label">Maskapai</label><input name="airline" class="form-control" value="<?=e($item['airline'])?>" required></div>
<div class="col-md-3 mb-3"><label class="form-label">No. Penerbangan</label><input name="flight_number" class="form-control" value="<?=e($item['flight_number'])?>" required></div>
<div class="col-md-3 mb-3"><label class="form-label">Kelas</label><select name="class" class="form-select"><option value="economy" <?=$item['class']=='economy'?'selected':''?>>Ekonomi</option><option value="business" <?=$item['class']=='business'?'selected':''?>>Bisnis</option><option value="first" <?=$item['class']=='first'?'selected':''?>>First</option></select></div></div>
<div class="row g-2"><div class="col-md-4 mb-3"><label class="form-label">Dari</label><input name="from_city" class="form-control" value="<?=e($item['from_city'])?>" required></div>
<div class="col-md-4 mb-3"><label class="form-label">Ke</label><input name="to_city" class="form-control" value="<?=e($item['to_city'])?>" required></div>
<div class="col-md-4 mb-3"><label class="form-label">Durasi</label><input name="duration" class="form-control" value="<?=e($item['duration'])?>" required></div></div>
<div class="row g-2"><div class="col-md-4 mb-3"><label class="form-label">Berangkat</label><input name="departure_time" type="time" class="form-control" value="<?=$item['departure_time']?>" required></div>
<div class="col-md-4 mb-3"><label class="form-label">Tiba</label><input name="arrival_time" type="time" class="form-control" value="<?=$item['arrival_time']?>" required></div>
<div class="col-md-4 mb-3"><label class="form-label">Harga (Rp)</label><input name="price" type="number" class="form-control" value="<?=$item['price']?>" required></div></div>
</div></div></div>
<div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Simpan</button>
<a href="flights.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a></div></div></form>
<?php require_once 'includes/admin-footer.php';?>

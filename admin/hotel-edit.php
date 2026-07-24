<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
$item = db()->prepare("SELECT * FROM hotels WHERE id = ?");
$item->execute([$id]);
$item = $item->fetch();
if (!$item) { header('Location: hotels.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $stars = (int)($_POST['stars'] ?? 4);
    $price = (float)($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    if (!$name || !$city) $error = 'Nama & kota wajib diisi';
    if (!$error) {
        $slug = buatSlug($name);
        $st = db()->prepare("UPDATE hotels SET name=?, slug=?, city=?, star_rating=?, price_per_night=?, description=? WHERE id=?");
        $st->execute([$name, $slug, $city, $stars, $price, $desc, $id]);
        header('Location: hotels.php?msg=updated'); exit;
    }
}

$pageTitle = 'Edit Hotel';
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3">Edit Hotel</h4>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="mb-3"><label class="form-label">Nama Hotel</label><input name="name" class="form-control" value="<?=e($item['name'])?>" required></div>
<div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="5"><?=e($item['description'])?></textarea></div>
</div></div></div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="mb-3"><label class="form-label">Kota</label><input name="city" class="form-control" value="<?=e($item['city'])?>" required></div>
<div class="mb-3"><label class="form-label">Bintang</label><select name="stars" class="form-select"><?php for($s=1;$s<=5;$s++):?><option value="<?=$s?>" <?=$item['star_rating']==$s?'selected':''?>><?=$s?> Bintang</option><?php endfor;?></select></div>
<div class="mb-3"><label class="form-label">Harga/Malam (Rp)</label><input name="price" type="number" class="form-control" value="<?=$item['price_per_night']?>" required></div>
</div></div>
<button type="submit" class="btn btn-primary w-100">Simpan</button>
<a href="hotels.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>

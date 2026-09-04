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
    if (!$name || !$city) $error = t('Nama & kota wajib diisi');
    if (!$error) {
        $slug = buatSlug($name);
        $nameEn = trim($_POST['name_en'] ?? '');
        $descEn = trim($_POST['description_en'] ?? '');
        $st = db()->prepare("UPDATE hotels SET name=?, slug=?, city=?, star_rating=?, price_per_night=?, description=?, name_en=?, description_en=? WHERE id=?");
        $st->execute([$name, $slug, $city, $stars, $price, $desc, $nameEn ?: null, $descEn ?: null, $id]);
        header('Location: hotels.php?msg=updated'); exit;
    }
}

$pageTitle = t('Edit Hotel');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><?= t('Edit Hotel') ?></h4>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="mb-3"><label class="form-label"><?= t('Nama Hotel') ?> (ID)</label><input name="name" class="form-control" value="<?=e($item['name'])?>" required></div>
<div class="mb-3"><label class="form-label"><?= t('Nama Hotel') ?> (EN)</label><input name="name_en" class="form-control" value="<?=e($item['name_en'] ?? '')?>" placeholder="<?= t('Kosongkan untuk memakai versi ID') ?>"></div>
<div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?> (ID)</label><textarea name="description" class="form-control" rows="5"><?=e($item['description'])?></textarea></div>
<div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?> (EN)</label><textarea name="description_en" class="form-control" rows="3" placeholder="<?= t('Kosongkan untuk memakai versi ID') ?>"><?=e($item['description_en'] ?? '')?></textarea></div>
</div></div></div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="mb-3"><label class="form-label"><?= t('Kota') ?></label><input name="city" class="form-control" value="<?=e($item['city'])?>" required></div>
<div class="mb-3"><label class="form-label"><?= t('Bintang') ?></label><select name="stars" class="form-select"><?php for($s=1;$s<=5;$s++):?><option value="<?=$s?>" <?=$item['star_rating']==$s?'selected':''?>><?=$s?><?= t('Bintang') ?></option><?php endfor;?></select></div>
<div class="mb-3"><label class="form-label"><?= t('Harga/Malam (Rp)') ?></label><input name="price" type="number" class="form-control" value="<?=$item['price_per_night']?>" required></div>
</div></div>
<button type="submit" class="btn btn-primary w-100"><?= t('Simpan') ?></button>
<a href="hotels.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>

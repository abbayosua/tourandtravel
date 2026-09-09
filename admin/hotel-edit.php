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
        $nameZh = trim($_POST['name_zh'] ?? '');
        $descZh = trim($_POST['description_zh'] ?? '');
        $st = db()->prepare("UPDATE hotels SET name=?, slug=?, city=?, star_rating=?, price_per_night=?, description=?, name_en=?, description_en=?, name_zh=?, description_zh=? WHERE id=?");
        $st->execute([$name, $slug, $city, $stars, $price, $desc, $nameEn ?: null, $descEn ?: null, $nameZh ?: null, $descZh ?: null, $id]);
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
<div class="mb-3"><label class="form-label"><?= t('Nama Hotel') ?> (中文)</label><input name="name_zh" class="form-control" value="<?=e($item['name_zh'] ?? '')?>" placeholder="<?= t('Kosongkan untuk memakai versi ID') ?>"></div>
<div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?> (ID)</label><textarea name="description" class="form-control" rows="5"><?=e($item['description'])?></textarea></div>
<div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?> (EN)</label><textarea name="description_en" class="form-control" rows="3" placeholder="<?= t('Kosongkan untuk memakai versi ID') ?>"><?=e($item['description_en'] ?? '')?></textarea></div>
<div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?> (中文)</label><textarea name="description_zh" class="form-control" rows="3" placeholder="<?= t('Kosongkan untuk memakai versi ID') ?>"><?=e($item['description_zh'] ?? '')?></textarea></div>
</div></div></div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-2">
    <strong><?= t('Tipe Kamar') ?></strong>
    <a class="btn btn-sm btn-outline-primary" href="hotel-rooms.php?hotel_id=<?= $id ?>"><?= t('Kelola') ?></a>
</div>
<?php
$hrStmt = db()->prepare("SELECT name, rate, stock FROM hotel_rooms WHERE hotel_id = ? AND is_active = 1 ORDER BY rate");
$hrStmt->execute([$id]);
$hotelRoomsList = $hrStmt->fetchAll();
if ($hotelRoomsList): foreach ($hotelRoomsList as $hrl): ?>
    <div class="d-flex justify-content-between small border-bottom py-1">
        <span><?= e($hrl['name']) ?> (<?= (int)$hrl['stock'] ?>)</span>
        <span><?= formatRupiah($hrl['rate']) ?></span>
    </div>
<?php endforeach; else: ?>
    <div class="text-muted small"><?= t('Belum ada tipe kamar') ?></div>
<?php endif; ?>
</div></div>
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="mb-3"><label class="form-label"><?= t('Kota') ?></label><input name="city" class="form-control" value="<?=e($item['city'])?>" required></div>
<div class="mb-3"><label class="form-label"><?= t('Bintang') ?></label><select name="stars" class="form-select"><?php for($s=1;$s<=5;$s++):?><option value="<?=$s?>" <?=$item['star_rating']==$s?'selected':''?>><?=$s?><?= t('Bintang') ?></option><?php endfor;?></select></div>
<div class="mb-3"><label class="form-label"><?= t('Harga/Malam (Rp)') ?></label><input name="price" type="number" class="form-control" value="<?=$item['price_per_night']?>" required></div>
</div></div>
<button type="submit" class="btn btn-primary w-100"><?= t('Simpan') ?></button>
<a href="hotels.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>

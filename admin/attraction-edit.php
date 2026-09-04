<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
$isAdd = $id === 0;
$item = null;

if (!$isAdd) {
    $item = db()->prepare("SELECT * FROM attractions WHERE id = ?");
    $item->execute([$id]);
    $item = $item->fetch();
    if (!$item) { header('Location: attractions.php'); exit; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $duration = trim($_POST['duration'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $bestSeller = (int)($_POST['best_seller'] ?? 0);
    $instantConf = (int)($_POST['instant_confirmation'] ?? 1);
    $freeCancel = (int)($_POST['free_cancellation'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 1);

    if (!$name || !$city) $error = 'Nama dan kota wajib diisi';

    if (!$error) {
        $slug = buatSlug($name);
        $coverImage = $item['cover_image'] ?? null;

        // Upload image
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = uploadGambar($_FILES['cover_image'], __DIR__ . '/../uploads/attractions');
            if ($upload['success']) $coverImage = 'uploads/attractions/' . $upload['filename'];
        }

        if ($isAdd) {
            $st = db()->prepare("INSERT INTO attractions (name, slug, city, category, description, price, duration, cover_image, best_seller, instant_confirmation, free_cancellation, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([$name, $slug, $city, $category, $desc, $price, $duration, $coverImage, $bestSeller, $instantConf, $freeCancel, $isActive]);
            $newId = (int)db()->lastInsertId();
            db()->prepare("UPDATE attractions SET name_en=?, description_en=? WHERE id=?")->execute([trim($_POST['name_en'] ?? '') ?: null, trim($_POST['description_en'] ?? '') ?: null, $newId]);
            header('Location: attractions.php?msg=added'); exit;
        } else {
            $st = db()->prepare("UPDATE attractions SET name=?, slug=?, city=?, category=?, description=?, price=?, duration=?, cover_image=?, best_seller=?, instant_confirmation=?, free_cancellation=?, is_active=? WHERE id=?");
            $st->execute([$name, $slug, $city, $category, $desc, $price, $duration, $coverImage, $bestSeller, $instantConf, $freeCancel, $isActive, $id]);
            db()->prepare("UPDATE attractions SET name_en=?, description_en=? WHERE id=?")->execute([trim($_POST['name_en'] ?? '') ?: null, trim($_POST['description_en'] ?? '') ?: null, $id]);
            header('Location: attractions.php?msg=updated'); exit;
        }
    }
}

$pageTitle = $isAdd ? 'Tambah Tiket Wisata' : 'Edit Tiket Wisata';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= $isAdd ? t('Tambah') : t('Edit') ?> <?= t('Tiket Tempat Wisata') ?></h4>
    <a href="attractions.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
</div>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label"><?= t('Nama Tiket') ?> (ID)</label><input name="name" class="form-control" value="<?=e($item['name']??'')?>" required></div>
    <div class="mb-3"><label class="form-label"><?= t('Nama Tiket') ?> (EN)</label><input name="name_en" class="form-control" value="<?=e($item['name_en']??'')?>" placeholder="<?= t('Kosongkan untuk memakai versi ID') ?>"></div>
    <div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?> (ID)</label><textarea name="description" class="form-control" rows="5"><?=e($item['description']??'')?></textarea></div>
    <div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?> (EN)</label><textarea name="description_en" class="form-control" rows="3" placeholder="<?= t('Kosongkan untuk memakai versi ID') ?>"><?=e($item['description_en']??'')?></textarea></div>
    <div class="mb-3"><label class="form-label"><?= t('Cover Image (Upload)') ?></label>
        <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($item['cover_image'])): ?><small class="text-muted"><?= t('Current:') ?><?=e($item['cover_image'])?></small><?php endif; ?>
    </div>
</div></div>
</div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label"><?= t('Kota') ?></label><input name="city" class="form-control" value="<?=e($item['city']??'')?>" required></div>
    <div class="mb-3"><label class="form-label"><?= t('Kategori') ?></label><input name="category" class="form-control" value="<?=e($item['category']??'')?>" placeholder="Taman & Hiburan, Landmark, ..."></div>
    <div class="mb-3"><label class="form-label"><?= t('Harga (Rp)') ?></label><input name="price" type="number" class="form-control" value="<?=$item['price']??0?>" required></div>
    <div class="mb-3"><label class="form-label"><?= t('Durasi') ?></label><input name="duration" class="form-control" value="<?=e($item['duration']??'')?>" placeholder="1 hari, 2-3 jam, ..."></div>
    <div class="mb-3">
        <label class="form-label"><?= t('Best Seller') ?></label>
        <select name="best_seller" class="form-select"><option value="0" <?=empty($item['best_seller'])?'selected':''?>><?= t('Tidak') ?></option><option value="1" <?=!empty($item['best_seller'])?'selected':''?>><?= t('Ya') ?></option></select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= t('Konfirmasi Instan') ?></label>
        <select name="instant_confirmation" class="form-select"><option value="1" <?=($item['instant_confirmation']??1)?'selected':''?>><?= t('Ya') ?></option><option value="0" <?=empty($item['instant_confirmation'])?'selected':''?>><?= t('Tidak') ?></option></select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= t('Batal Gratis') ?></label>
        <select name="free_cancellation" class="form-select"><option value="0" <?=empty($item['free_cancellation'])?'selected':''?>><?= t('Tidak') ?></option><option value="1" <?=!empty($item['free_cancellation'])?'selected':''?>><?= t('Ya') ?></option></select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= t('Status') ?></label>
        <select name="is_active" class="form-select"><option value="1" <?=($item['is_active']??1)?'selected':''?>><?= t('Aktif') ?></option><option value="0" <?=empty($item['is_active'])?'selected':''?>><?= t('Nonaktif') ?></option></select>
    </div>
</div></div>
<button type="submit" class="btn btn-primary w-100"><?= $isAdd ? 'Tambah' : 'Simpan' ?></button>
<a href="attractions.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>
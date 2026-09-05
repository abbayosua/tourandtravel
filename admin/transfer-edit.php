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
    $item = db()->prepare("SELECT * FROM transfers WHERE id = ?");
    $item->execute([$id]);
    $item = $item->fetch();
    if (!$item) { header('Location: transfers.php'); exit; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $fromCity = trim($_POST['from_city'] ?? '');
    $toCity = trim($_POST['to_city'] ?? '');
    $fromType = $_POST['from_type'] ?? 'airport';
    $toType = $_POST['to_type'] ?? 'city';
    $price = (float)($_POST['price'] ?? 0);
    $vehicleType = trim($_POST['vehicle_type'] ?? '');
    $maxPassengers = (int)($_POST['max_passengers'] ?? 4);
    $desc = trim($_POST['description'] ?? '');
    $instantConf = (int)($_POST['instant_confirmation'] ?? 1);
    $freeCancel = (int)($_POST['free_cancellation'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 1);

    if (!$name || !$fromCity || !$toCity) $error = 'Nama, asal, dan tujuan wajib diisi';

    if (!$error) {
        $slug = buatSlug($name);
        if ($isAdd) {
            $st = db()->prepare("INSERT INTO transfers (name, slug, from_city, to_city, from_type, to_type, price, vehicle_type, max_passengers, description, instant_confirmation, free_cancellation, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([$name, $nameEn, $slug, $fromCity, $toCity, $fromType, $toType, $price, $vehicleType, $maxPassengers, $desc, $instantConf, $freeCancel, $isActive]);
            header('Location: transfers.php?msg=added'); exit;
        } else {
            $st = db()->prepare("UPDATE transfers SET name=?, name_en=?, slug=?, from_city=?, to_city=?, from_type=?, to_type=?, price=?, vehicle_type=?, max_passengers=?, description=?, instant_confirmation=?, free_cancellation=?, is_active=? WHERE id=?");
            $st->execute([$name, $nameEn, $slug, $fromCity, $toCity, $fromType, $toType, $price, $vehicleType, $maxPassengers, $desc, $instantConf, $freeCancel, $isActive, $id]);
            header('Location: transfers.php?msg=updated'); exit;
        }
    }
}

$pageTitle = $isAdd ? t('Tambah Transfer') : t('Edit Transfer');
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= $isAdd ? t('Tambah') : t('Edit') ?><?= t('Transfer') ?></h4>
    <a href="transfers.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
</div>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label"><?= t('Nama Transfer') ?></label><input name="name" class="form-control" value="<?=e($item['name']??'')?>" required></div>
<div class="mb-3"><label class="form-label">Nama Transfer (EN)</label><input name="name_en" class="form-control" value="<?= e($item["name_en"] ?? "") ?>"></div>
    <div class="mb-3"><label class="form-label"><?= t('Deskripsi') ?></label><textarea name="description" class="form-control" rows="5"><?=e($item['description']??'')?></textarea></div>
</div></div>
</div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label"><?= t('Dari') ?></label><input name="from_city" class="form-control" value="<?=e($item['from_city']??'')?>" required></div>
    <div class="mb-3"><label class="form-label"><?= t('Tipe Asal') ?></label><select name="from_type" class="form-select">
        <option value="airport" <?=($item['from_type']??'airport')==='airport'?'selected':''?>><?= t('Bandara') ?></option>
        <option value="city" <?=($item['from_type']??'')==='city'?'selected':''?>><?= t('Kota') ?></option>
        <option value="port" <?=($item['from_type']??'')==='port'?'selected':''?>><?= t('Pelabuhan') ?></option>
        <option value="hotel" <?=($item['from_type']??'')==='hotel'?'selected':''?>><?= t('Hotel') ?></option>
    </select></div>
    <div class="mb-3"><label class="form-label"><?= t('Ke') ?></label><input name="to_city" class="form-control" value="<?=e($item['to_city']??'')?>" required></div>
    <div class="mb-3"><label class="form-label"><?= t('Tipe Tujuan') ?></label><select name="to_type" class="form-select">
        <option value="city" <?=($item['to_type']??'city')==='city'?'selected':''?>><?= t('Kota') ?></option>
        <option value="airport" <?=($item['to_type']??'')==='airport'?'selected':''?>><?= t('Bandara') ?></option>
        <option value="port" <?=($item['to_type']??'')==='port'?'selected':''?>><?= t('Pelabuhan') ?></option>
        <option value="hotel" <?=($item['to_type']??'')==='hotel'?'selected':''?>><?= t('Hotel') ?></option>
    </select></div>
    <div class="mb-3"><label class="form-label"><?= t('Harga (Rp)') ?></label><input name="price" type="number" class="form-control" value="<?=$item['price']??0?>" required></div>
    <div class="mb-3"><label class="form-label"><?= t('Tipe Kendaraan') ?></label><input name="vehicle_type" class="form-control" value="<?=e($item['vehicle_type']??'')?>" placeholder="Sedan, MVP, ..."></div>
    <div class="mb-3"><label class="form-label"><?= t('Max Penumpang') ?></label><input name="max_passengers" type="number" class="form-control" value="<?=$item['max_passengers']??4?>" min="1"></div>
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
<button type="submit" class="btn btn-primary w-100"><?= $isAdd ? t('Tambah') : t('Simpan') ?></button>
<a href="transfers.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>
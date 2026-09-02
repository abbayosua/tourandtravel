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
    $item = db()->prepare("SELECT * FROM connectivity_products WHERE id = ?");
    $item->execute([$id]);
    $item = $item->fetch();
    if (!$item) { header('Location: esim.php'); exit; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'esim';
    $country = trim($_POST['country'] ?? '');
    $coverage = trim($_POST['coverage'] ?? '');
    $dataQuota = trim($_POST['data_quota'] ?? '');
    $durationDays = (int)($_POST['duration_days'] ?? 7);
    $price = (float)($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $isActive = (int)($_POST['is_active'] ?? 1);

    if (!$name || !$country || !$dataQuota) $error = 'Nama, negara, dan kuota wajib diisi';

    if (!$error) {
        $slug = buatSlug($name);
        if ($isAdd) {
            $st = db()->prepare("INSERT INTO connectivity_products (name, slug, type, country, coverage, data_quota, duration_days, price, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([$name, $slug, $type, $country, $coverage, $dataQuota, $durationDays, $price, $desc, $isActive]);
            header('Location: esim.php?msg=added'); exit;
        } else {
            $st = db()->prepare("UPDATE connectivity_products SET name=?, slug=?, type=?, country=?, coverage=?, data_quota=?, duration_days=?, price=?, description=?, is_active=? WHERE id=?");
            $st->execute([$name, $slug, $type, $country, $coverage, $dataQuota, $durationDays, $price, $desc, $isActive, $id]);
            header('Location: esim.php?msg=updated'); exit;
        }
    }
}

$pageTitle = $isAdd ? 'Tambah eSIM' : 'Edit eSIM';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= $isAdd ? 'Tambah' : 'Edit' ?> eSIM</h4>
    <a href="esim.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
</div>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label">Nama Produk</label><input name="name" class="form-control" value="<?=e($item['name']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="5"><?=e($item['description']??'')?></textarea></div>
</div></div>
</div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label">Tipe</label><select name="type" class="form-select">
        <option value="esim" <?=($item['type']??'esim')==='esim'?'selected':''?>>eSIM</option>
        <option value="sim" <?=($item['type']??'')==='sim'?'selected':''?>>SIM</option>
        <option value="wifi" <?=($item['type']??'')==='wifi'?'selected':''?>>Pocket WiFi</option>
    </select></div>
    <div class="mb-3"><label class="form-label">Negara</label><input name="country" class="form-control" value="<?=e($item['country']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Cakupan</label><input name="coverage" class="form-control" value="<?=e($item['coverage']??'')?>" placeholder="Nasional, Regional, ..."></div>
    <div class="mb-3"><label class="form-label">Kuota Data</label><input name="data_quota" class="form-control" value="<?=e($item['data_quota']??'')?>" placeholder="5GB, 10GB, ..." required></div>
    <div class="mb-3"><label class="form-label">Durasi (hari)</label><input name="duration_days" type="number" class="form-control" value="<?=$item['duration_days']??7?>" min="1"></div>
    <div class="mb-3"><label class="form-label">Harga (Rp)</label><input name="price" type="number" class="form-control" value="<?=$item['price']??0?>" required></div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="is_active" class="form-select"><option value="1" <?=($item['is_active']??1)?'selected':''?>>Aktif</option><option value="0" <?=empty($item['is_active'])?'selected':''?>>Nonaktif</option></select>
    </div>
</div></div>
<button type="submit" class="btn btn-primary w-100"><?= $isAdd ? 'Tambah' : 'Simpan' ?></button>
<a href="esim.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>
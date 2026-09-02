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
    $item = db()->prepare("SELECT * FROM trains WHERE id = ?");
    $item->execute([$id]);
    $item = $item->fetch();
    if (!$item) { header('Location: trains.php'); exit; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $routeFrom = trim($_POST['route_from'] ?? '');
    $routeTo = trim($_POST['route_to'] ?? '');
    $departureTime = trim($_POST['departure_time'] ?? '');
    $arrivalTime = trim($_POST['arrival_time'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $class = trim($_POST['class'] ?? '');
    $isActive = (int)($_POST['is_active'] ?? 1);

    if (!$name || !$routeFrom || !$routeTo) $error = 'Nama, asal, dan tujuan wajib diisi';

    if (!$error) {
        $slug = buatSlug($name);
        $depTime = $departureTime ?: '00:00:00';
        $arrTime = $arrivalTime ?: '00:00:00';
        if ($isAdd) {
            $st = db()->prepare("INSERT INTO trains (name, slug, route_from, route_to, departure_time, arrival_time, duration, price, class, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([$name, $slug, $routeFrom, $routeTo, $depTime, $arrTime, $duration, $price, $class, $isActive]);
            header('Location: trains.php?msg=added'); exit;
        } else {
            $st = db()->prepare("UPDATE trains SET name=?, slug=?, route_from=?, route_to=?, departure_time=?, arrival_time=?, duration=?, price=?, class=?, is_active=? WHERE id=?");
            $st->execute([$name, $slug, $routeFrom, $routeTo, $depTime, $arrTime, $duration, $price, $class, $isActive, $id]);
            header('Location: trains.php?msg=updated'); exit;
        }
    }
}

$pageTitle = $isAdd ? 'Tambah Kereta' : 'Edit Kereta';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= $isAdd ? 'Tambah' : 'Edit' ?> Kereta</h4>
    <a href="trains.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
</div>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label">Nama Kereta</label><input name="name" class="form-control" value="<?=e($item['name']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Durasi (contoh: 5j 30m)</label><input name="duration" class="form-control" value="<?=e($item['duration']??'')?>" placeholder="5j 30m"></div>
</div></div>
</div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label">Stasiun Asal</label><input name="route_from" class="form-control" value="<?=e($item['route_from']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Stasiun Tujuan</label><input name="route_to" class="form-control" value="<?=e($item['route_to']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Keberangkatan</label><input name="departure_time" type="time" class="form-control" value="<?=e($item['departure_time']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Tiba</label><input name="arrival_time" type="time" class="form-control" value="<?=e($item['arrival_time']??'')?>" required></div>
    <div class="mb-3"><label class="form-label">Harga (Rp)</label><input name="price" type="number" class="form-control" value="<?=$item['price']??0?>" required></div>
    <div class="mb-3"><label class="form-label">Kelas</label><input name="class" class="form-control" value="<?=e($item['class']??'')?>" placeholder="Eksekutif, Bisnis, ..."></div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="is_active" class="form-select"><option value="1" <?=($item['is_active']??1)?'selected':''?>>Aktif</option><option value="0" <?=empty($item['is_active'])?'selected':''?>>Nonaktif</option></select>
    </div>
</div></div>
<button type="submit" class="btn btn-primary w-100"><?= $isAdd ? 'Tambah' : 'Simpan' ?></button>
<a href="trains.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>
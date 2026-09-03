<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => 'Berhasil ditambahkan', 'updated' => 'Berhasil diperbarui', 'deleted' => 'Berhasil dihapus', default => '' };

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM promo_codes WHERE id=?")->execute([$id]);
    header('Location: promo-codes.php?msg=deleted'); exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    $discountType = $_POST['discount_type'] ?? 'percentage';
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $minPurchase = $_POST['min_purchase'] !== '' ? (float)$_POST['min_purchase'] : null;
    $maxDiscount = $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
    $usageLimit = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
    $validFrom = $_POST['valid_from'] ?? date('Y-m-d');
    $validUntil = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+1 year'));
    $isActive = (int)($_POST['is_active'] ?? 1);

    $error = '';
    if (!$code) $error = t('Kode promo wajib diisi');
    elseif ($discountValue <= 0) $error = 'Nilai diskon harus > 0';

    if (!$error) {
        if ($id > 0) {
            $st = db()->prepare("UPDATE promo_codes SET code=?, description=?, discount_type=?, discount_value=?, min_purchase=?, max_discount=?, usage_limit=?, valid_from=?, valid_until=?, is_active=? WHERE id=?");
            $st->execute([$code, $desc, $discountType, $discountValue, $minPurchase, $maxDiscount, $usageLimit, $validFrom, $validUntil, $isActive, $id]);
            header('Location: promo-codes.php?msg=updated'); exit;
        } else {
            $st = db()->prepare("SELECT COUNT(*) FROM promo_codes WHERE code = ?");
            $st->execute([$code]);
            if ($st->fetchColumn() > 0) {
                $error = t('Kode promo sudah ada');
            } else {
                $st = db()->prepare("INSERT INTO promo_codes (code, description, discount_type, discount_value, min_purchase, max_discount, usage_limit, valid_from, valid_until, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $st->execute([$code, $desc, $discountType, $discountValue, $minPurchase, $maxDiscount, $usageLimit, $validFrom, $validUntil, $isActive]);
                header('Location: promo-codes.php?msg=added'); exit;
            }
        }
    }
}

// Edit mode: load item
$editItem = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $st = db()->prepare("SELECT * FROM promo_codes WHERE id = ?");
    $st->execute([$editId]);
    $editItem = $st->fetch();
}

$items = db()->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll();

$pageTitle = t('Kode Promo');
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Kode Promo') ?></h4>
    <a href="?edit=0" class="btn btn-primary btn-sm <?= $editId === 0 && !isset($_POST['save']) ? 'd-none' : '' ?>"><i class="bi bi-plus-lg"></i> <?= t('Tambah') ?></a>
</div>

<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

<?php if ($editId >= 0 && ($editItem || $editId === 0)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <h5 class="fw-semibold mb-3"><?= $editId > 0 ? t('Edit') : t('Tambah') ?> Kode Promo</h5>
        <form method="POST" class="row g-2">
            <input type="hidden" name="id" value="<?= $editId ?>">
            <div class="col-md-3">
                <label class="form-label small"><?= t('Kode') ?></label>
                <input name="code" class="form-control form-control-sm" value="<?= e($editItem['code'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small"><?= t('Tipe Diskon') ?></label>
                <select name="discount_type" class="form-select form-select-sm">
                    <option value="percentage" <?= ($editItem['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Persentase (%)</option>
                    <option value="fixed" <?= ($editItem['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Nominal (Rp)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small"><?= t('Nilai') ?></label>
                <input name="discount_value" type="number" class="form-control form-control-sm" value="<?= $editItem['discount_value'] ?? 0 ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Min. Pembelian</label>
                <input name="min_purchase" type="number" class="form-control form-control-sm" value="<?= $editItem['min_purchase'] ?? '' ?>" placeholder="Kosongkan">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Max. Diskon</label>
                <input name="max_discount" type="number" class="form-control form-control-sm" value="<?= $editItem['max_discount'] ?? '' ?>" placeholder="Kosongkan">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Batas Pemakaian</label>
                <input name="usage_limit" type="number" class="form-control form-control-sm" value="<?= $editItem['usage_limit'] ?? '' ?>" placeholder="Kosongkan">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Berlaku Dari</label>
                <input name="valid_from" type="date" class="form-control form-control-sm" value="<?= $editItem['valid_from'] ?? date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Berlaku Sampai</label>
                <input name="valid_until" type="date" class="form-control form-control-sm" value="<?= $editItem['valid_until'] ?? date('Y-m-d', strtotime('+1 year')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small"><?= t('Aktif') ?></label>
                <select name="is_active" class="form-select form-select-sm">
                    <option value="1" <?= ($editItem['is_active'] ?? 1) ? 'selected' : '' ?>><?= t('Ya') ?></option>
                    <option value="0" <?= empty($editItem['is_active']) ? 'selected' : '' ?>><?= t('Tidak') ?></option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" name="save" class="btn btn-primary btn-sm"><?= t('Simpan') ?></button>
                <a href="promo-codes.php" class="btn btn-outline-secondary btn-sm"><?= t('Batal') ?></a>
            </div>
            <div class="col-12">
                <small class="text-muted">Deskripsi: <input name="description" class="form-control form-control-sm mt-1" value="<?= e($editItem['description'] ?? '') ?>" placeholder="Deskripsi (opsional)"></small>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th>Kode</th><th>Tipe</th><th>Nilai</th><th>Min. Beli</th><th>Max. Diskon</th><th>Pemakaian</th><th>Berlaku</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td><td><strong><?=e($i['code'])?></strong></td>
<td><?=$i['discount_type']==='percentage'?'%':'Rp'?></td>
<td><?=$i['discount_type']==='percentage'?$i['discount_value'].'%':formatRupiah($i['discount_value'])?></td>
<td><?=$i['min_purchase']?formatRupiah($i['min_purchase']):'-'?></td>
<td><?=$i['max_discount']?formatRupiah($i['max_discount']):'-'?></td>
<td><?=$i['used_count']?> / <?=$i['usage_limit']??'∞'?></td>
<td><?=e($i['valid_from'])?> → <?=e($i['valid_until'])?></td>
<td><span class="badge bg-<?=$i['is_active']?'success':'secondary'?>"><?=$i['is_active']?'Aktif':'Nonaktif'?></span></td>
<td><a href="promo-codes.php?edit=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="promo-codes.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kode promo?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
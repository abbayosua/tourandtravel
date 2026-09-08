<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$hotelId = (int)($_GET['hotel_id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$hotelId]);
$hotel = $stmt->fetch();
if (!$hotel) { header('Location: hotels.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '') ?: null;
        $bedType = in_array($_POST['bed_type'] ?? '', ['single', 'double', 'twin', 'king', 'suite']) ? $_POST['bed_type'] : 'double';
        $maxGuest = max(1, min(10, (int)($_POST['max_guest'] ?? 2)));
        $rate = (float)($_POST['rate'] ?? 0);
        $breakfast = isset($_POST['breakfast']) ? 1 : 0;
        $refundable = isset($_POST['refundable']) ? 1 : 0;
        $stock = max(0, min(999, (int)($_POST['stock'] ?? 0)));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if (!$name || $rate <= 0) {
            $error = t('Nama & harga wajib diisi');
        } else {
            if ($id > 0) {
                $st = db()->prepare("UPDATE hotel_rooms SET name=?, name_en=?, bed_type=?, max_guest=?, rate=?, breakfast=?, refundable=?, stock=?, is_active=? WHERE id=? AND hotel_id=?");
                $st->execute([$name, $nameEn, $bedType, $maxGuest, $rate, $breakfast, $refundable, $stock, $isActive, $id, $hotelId]);
            } else {
                $st = db()->prepare("INSERT INTO hotel_rooms (hotel_id, name, name_en, bed_type, max_guest, rate, breakfast, refundable, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $st->execute([$hotelId, $name, $nameEn, $bedType, $maxGuest, $rate, $breakfast, $refundable, $stock, $isActive]);
            }
            header('Location: hotel-rooms.php?hotel_id=' . $hotelId . '&msg=saved');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare("DELETE FROM hotel_rooms WHERE id = ? AND hotel_id = ?");
        $st->execute([$id, $hotelId]);
        header('Location: hotel-rooms.php?hotel_id=' . $hotelId . '&msg=deleted');
        exit;
    }
}

$editRoom = null;
if (!empty($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM hotel_rooms WHERE id = ? AND hotel_id = ?");
    $st->execute([(int)$_GET['edit'], $hotelId]);
    $editRoom = $st->fetch() ?: null;
}

$rooms = db()->prepare("SELECT * FROM hotel_rooms WHERE hotel_id = ? ORDER BY is_active DESC, rate ASC");
$rooms->execute([$hotelId]);
$rooms = $rooms->fetchAll();

$pageTitle = t('Tipe Kamar');
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Tipe Kamar') ?> — <?= e($hotel['name']) ?></h4>
    <a href="hotels.php" class="btn btn-outline-secondary btn-sm"><?= t('Kembali') ?></a>
</div>
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2"><?= t('Perubahan tersimpan') ?></div>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr>
                    <th><?= t('Nama') ?></th><th><?= t('Tipe Kasur') ?></th><th><?= t('Kapasitas') ?></th>
                    <th><?= t('Harga/Malam (Rp)') ?></th><th><?= t('Sarapan') ?></th><th><?= t('Refund') ?></th>
                    <th><?= t('Stok') ?></th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rooms as $r): ?>
                    <tr class="<?= $r['is_active'] ? '' : 'table-secondary' ?>">
                        <td>
                            <?= e($r['name']) ?>
                            <?php if ($r['name_en']): ?><small class="text-muted d-block"><?= e($r['name_en']) ?></small><?php endif; ?>
                            <?php if (!$r['is_active']): ?><span class="badge bg-secondary"><?= t('Nonaktif') ?></span><?php endif; ?>
                        </td>
                        <td><?= e($r['bed_type']) ?></td>
                        <td><?= (int)$r['max_guest'] ?></td>
                        <td><?= formatRupiah($r['rate']) ?></td>
                        <td><?= $r['breakfast'] ? '✓' : '—' ?></td>
                        <td><?= $r['refundable'] ? '✓' : '—' ?></td>
                        <td><?= (int)$r['stock'] ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="hotel-rooms.php?hotel_id=<?= $hotelId ?>&edit=<?= $r['id'] ?>"><?= t('Edit') ?></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('<?= t('Hapus kamar ini?') ?>')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><?= t('Hapus') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!count($rooms)): ?>
                    <tr><td colspan="8" class="text-muted"><?= t('Belum ada tipe kamar') ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <h6 class="fw-semibold mb-3"><?= $editRoom ? t('Edit Tipe Kamar') : t('Tambah Tipe Kamar') ?></h6>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int)($editRoom['id'] ?? 0) ?>">
                <div class="mb-2"><label class="form-label small"><?= t('Nama') ?> (ID)</label><input name="name" class="form-control form-control-sm" value="<?= e($editRoom['name'] ?? '') ?>" required></div>
                <div class="mb-2"><label class="form-label small"><?= t('Nama') ?> (EN)</label><input name="name_en" class="form-control form-control-sm" value="<?= e($editRoom['name_en'] ?? '') ?>"></div>
                <div class="mb-2"><label class="form-label small"><?= t('Tipe Kasur') ?></label>
                    <select name="bed_type" class="form-select form-select-sm">
                        <?php foreach (['single', 'double', 'twin', 'king', 'suite'] as $bt): ?>
                            <option value="<?= $bt ?>" <?= ($editRoom['bed_type'] ?? '') === $bt ? 'selected' : '' ?>><?= t(ucfirst($bt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-2"><label class="form-label small"><?= t('Kapasitas') ?></label><input name="max_guest" type="number" min="1" max="10" class="form-control form-control-sm" value="<?= (int)($editRoom['max_guest'] ?? 2) ?>"></div>
                    <div class="col-6 mb-2"><label class="form-label small"><?= t('Stok') ?></label><input name="stock" type="number" min="0" max="999" class="form-control form-control-sm" value="<?= (int)($editRoom['stock'] ?? 5) ?>"></div>
                </div>
                <div class="mb-2"><label class="form-label small"><?= t('Harga/Malam (Rp)') ?></label><input name="rate" type="number" step="0.01" min="1" class="form-control form-control-sm" value="<?= e((string)($editRoom['rate'] ?? $hotel['price_per_night'])) ?>" required></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="breakfast" id="hrBf" <?= !empty($editRoom['breakfast']) ? 'checked' : '' ?>><label class="form-check-label small" for="hrBf"><?= t('Termasuk sarapan') ?></label></div>
                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="refundable" id="hrRf" <?= !isset($editRoom) || !empty($editRoom['refundable']) ? 'checked' : '' ?>><label class="form-check-label small" for="hrRf"><?= t('Dapat direfund') ?></label></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="hrAc" <?= !isset($editRoom) || !empty($editRoom['is_active']) ? 'checked' : '' ?>><label class="form-check-label small" for="hrAc"><?= t('Aktif') ?></label></div>
                <button type="submit" class="btn btn-primary w-100 btn-sm"><?= t('Simpan') ?></button>
                <?php if ($editRoom): ?><a href="hotel-rooms.php?hotel_id=<?= $hotelId ?>" class="btn btn-outline-secondary w-100 btn-sm mt-2"><?= t('Batal') ?></a><?php endif; ?>
            </form>
        </div></div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>

<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $itemType = in_array($_POST['item_type'] ?? '', ['tour', 'hotel', 'attraction', 'esim'], true) ? $_POST['item_type'] : 'tour';
        $itemId = (int)($_POST['item_id'] ?? 0);
        $discount = max(5, min(70, (int)($_POST['discount_percent'] ?? 10)));
        $startsAt = $_POST['starts_at'] ?? '';
        $endsAt = $_POST['ends_at'] ?? '';
        $stockLimitRaw = trim($_POST['stock_limit'] ?? '');
        $stockLimit = $stockLimitRaw !== '' ? max(1, (int)$stockLimitRaw) : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if (!$itemId || !$startsAt || !$endsAt || strtotime($endsAt) <= strtotime($startsAt)) {
            $error = t('Item, periode wajib diisi; akhir harus setelah mulai');
        } else {
            if ($id > 0) {
                db()->prepare("UPDATE flash_sales SET item_type=?, item_id=?, discount_percent=?, starts_at=?, ends_at=?, stock_limit=?, is_active=? WHERE id=?")
                    ->execute([$itemType, $itemId, $discount, $startsAt, $endsAt, $stockLimit, $isActive, $id]);
            } else {
                db()->prepare("INSERT IGNORE INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$itemType, $itemId, $discount, $startsAt, $endsAt, $stockLimit, $isActive]);
            }
            header('Location: flash-sales.php?msg=saved');
            exit;
        }
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM flash_sales WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
        header('Location: flash-sales.php?msg=deleted');
        exit;
    }
}

$editFs = null;
if (!empty($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM flash_sales WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $editFs = $st->fetch() ?: null;
}

$rows = db()->query("SELECT fs.*, 'tour' placeholder FROM flash_sales fs ORDER BY fs.is_active DESC, fs.ends_at ASC")->fetchAll();

$pageTitle = t('Flash Sale');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i><?= t('Flash Sale') ?></h4>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success py-2"><?= t('Perubahan tersimpan') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th><?= t('Item') ?></th><th><?= t('Diskon') ?></th><th><?= t('Mulai') ?></th>
                    <th><?= t('Akhir') ?></th><th><?= t('Terjual/Kuota') ?></th><th><?= t('Status') ?></th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $fs):
                    $live = $fs['is_active'] && strtotime($fs['starts_at']) <= time() && strtotime($fs['ends_at']) > time() && ($fs['stock_limit'] === null || (int)$fs['sold_count'] < (int)$fs['stock_limit']);
                ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= e($fs['item_type']) ?></span> #<?= (int)$fs['item_id'] ?></td>
                        <td><strong class="text-danger">-<?= (int)$fs['discount_percent'] ?>%</strong></td>
                        <td><small><?= e(date('d M Y H:i', strtotime($fs['starts_at']))) ?></small></td>
                        <td><small><?= e(date('d M Y H:i', strtotime($fs['ends_at']))) ?></small></td>
                        <td><small><?= (int)$fs['sold_count'] ?>/<?= $fs['stock_limit'] === null ? '∞' : (int)$fs['stock_limit'] ?></small></td>
                        <td><span class="badge bg-<?= $live ? 'success' : 'secondary' ?>" data-testid="fs-status"><?= $live ? t('Aktif') : t('Nonaktif') ?></span></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="flash-sales.php?edit=<?= $fs['id'] ?>"><?= t('Edit') ?></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('<?= t('Hapus flash sale ini?') ?>')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $fs['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><?= t('Hapus') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!count($rows)): ?><tr><td colspan="7" class="text-muted text-center py-4"><?= t('Belum ada flash sale') ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold mb-3"><?= $editFs ? t('Edit Flash Sale') : t('Tambah Flash Sale') ?></h6>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int)($editFs['id'] ?? 0) ?>">
                <div class="mb-2"><label class="form-label small"><?= t('Tipe item') ?></label>
                    <select name="item_type" class="form-select form-select-sm">
                        <?php foreach (['tour', 'hotel', 'attraction', 'esim'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($editFs['item_type'] ?? 'tour') === $t ? 'selected' : '' ?>><?= t(ucfirst($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><label class="form-label small"><?= t('ID item') ?></label>
                    <input name="item_id" type="number" min="1" class="form-control form-control-sm" value="<?= (int)($editFs['item_id'] ?? 61) ?>" required>
                </div>
                <div class="mb-2"><label class="form-label small"><?= t('Diskon (%)') ?>: 5–70</label>
                    <input name="discount_percent" type="number" min="5" max="70" class="form-control form-control-sm" value="<?= (int)($editFs['discount_percent'] ?? 15) ?>" required>
                </div>
                <div class="mb-2"><label class="form-label small"><?= t('Mulai') ?></label>
                    <input name="starts_at" type="datetime-local" class="form-control form-control-sm" value="<?= e(date('Y-m-d\TH:i', strtotime($editFs['starts_at'] ?? 'now'))) ?>" required>
                </div>
                <div class="mb-2"><label class="form-label small"><?= t('Akhir') ?></label>
                    <input name="ends_at" type="datetime-local" class="form-control form-control-sm" value="<?= e(date('Y-m-d\TH:i', strtotime($editFs['ends_at'] ?? '+7 days'))) ?>" required>
                </div>
                <div class="mb-2"><label class="form-label small"><?= t('Kuota (kosong = tanpa batas)') ?></label>
                    <input name="stock_limit" type="number" min="1" class="form-control form-control-sm" value="<?= $editFs['stock_limit'] !== null && $editFs['stock_limit'] !== '' ? (int)$editFs['stock_limit'] : '' ?>">
                </div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="fsActive" <?= !isset($editFs) || !empty($editFs['is_active']) ? 'checked' : '' ?>><label class="form-check-label small" for="fsActive"><?= t('Aktif') ?></label></div>
                <button type="submit" class="btn btn-primary w-100 btn-sm"><?= t('Simpan') ?></button>
                <?php if ($editFs): ?><a href="flash-sales.php" class="btn btn-outline-secondary w-100 btn-sm mt-2"><?= t('Batal') ?></a><?php endif; ?>
            </form>
        </div></div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>

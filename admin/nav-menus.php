<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Menu Navigasi');

$icons = ['bi-map','bi-building','bi-airplane','bi-ship','bi-car-front','bi-train-front','bi-signpost-2','bi-arrow-left-right','bi-sim','bi-circle','bi-house','bi-grid'];
$msg = '';
if (isset($_GET['msg'])) $msg = match($_GET['msg']) { 'added' => t('Berhasil ditambahkan'), 'updated' => t('Berhasil diperbarui'), 'deleted' => t('Berhasil dihapus'), 'toggled' => t('Status diubah'), default => '' };

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    db()->prepare("UPDATE nav_menus SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
    header('Location: nav-menus.php?msg=toggled'); exit;
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM nav_menus WHERE id=?")->execute([$id]);
    header('Location: nav-menus.php?msg=deleted'); exit;
}

$editItem = null;
$editId = -1;
if (isset($_GET['edit'])) $editId = (int)$_GET['edit'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $label = trim($_POST['label'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-circle');
    $matchKey = trim($_POST['match_key'] ?? '');
    $showTabs = isset($_POST['show_in_tabs']) ? 1 : 0;
    $showMenu = isset($_POST['show_in_menu']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($label === '' || $url === '') $error = t('Label dan URL wajib diisi');
    if (!$error) {
        if ($id > 0) {
            db()->prepare("UPDATE nav_menus SET label=?, url=?, icon=?, match_key=?, show_in_tabs=?, show_in_menu=?, sort_order=?, is_active=? WHERE id=?")
                ->execute([$label, $url, $icon, $matchKey, $showTabs, $showMenu, $sortOrder, $isActive, $id]);
            $msgType = 'updated';
        } else {
            db()->prepare("INSERT INTO nav_menus (label, url, icon, match_key, show_in_tabs, show_in_menu, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$label, $url, $icon, $matchKey, $showTabs, $showMenu, $sortOrder, $isActive]);
            $msgType = 'added';
        }
        header("Location: nav-menus.php?msg=$msgType"); exit;
    }
}

if ($editId > 0) {
    $st = db()->prepare("SELECT * FROM nav_menus WHERE id = ?");
    $st->execute([$editId]);
    $editItem = $st->fetch();
    if (!$editItem) $editId = -1;
}

$items = db()->query("SELECT * FROM nav_menus ORDER BY sort_order ASC, id ASC")->fetchAll();

require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><?= t('Menu Navigasi') ?></h4>
        <small class="text-muted"><?= t('Atur menu yang muncul di SELURUH header website (tab + menu mobile).') ?></small>
    </div>
    <a href="?edit=new" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?= t('Tambah Menu') ?></a>
</div>

<?php if ($msg): ?><div class="alert alert-success py-2"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<?php if ($editId >= 0 && ($editItem || $editId === 0)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <h5 class="fw-semibold mb-3"><?= $editId > 0 ? t('Edit') : t('Tambah') ?> <?= t('Menu') ?></h5>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $editId ?>">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small"><?= t('Label') ?></label>
                    <input name="label" class="form-control form-control-sm" value="<?= e($editItem['label'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?= t('URL') ?></label>
                    <input name="url" class="form-control form-control-sm" value="<?= e($editItem['url'] ?? '') ?>" placeholder="tours.php" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= t('Ikon') ?></label>
                    <select name="icon" class="form-select form-select-sm">
                        <?php foreach ($icons as $ic): ?>
                        <option value="<?= $ic ?>" <?= ($editItem['icon'] ?? '') === $ic ? 'selected' : '' ?>><?= $ic ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= t('Match key') ?></label>
                    <input name="match_key" class="form-control form-control-sm" value="<?= e($editItem['match_key'] ?? '') ?>" placeholder="tour">
                </div>
                <div class="col-md-1">
                    <label class="form-label small"><?= t('Urutan') ?></label>
                    <input name="sort_order" type="number" class="form-control form-control-sm" value="<?= e($editItem['sort_order'] ?? 0) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-3">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_tabs" id="fTabs" <?= ($editItem['show_in_tabs'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label small" for="fTabs"><?= t('Tab') ?></label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_menu" id="fMenu" <?= ($editItem['show_in_menu'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label small" for="fMenu"><?= t('Menu') ?></label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="fAct" <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label small" for="fAct"><?= t('Aktif') ?></label></div>
                </div>
            </div>
            <div class="mt-2 d-flex gap-1">
                <button type="submit" name="save" class="btn btn-primary btn-sm"><?= t('Simpan') ?></button>
                <a href="nav-menus.php" class="btn btn-outline-secondary btn-sm"><?= t('Batal') ?></a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th><?= t('Label') ?></th><th>URL</th><th>Tab</th><th>Menu</th><th><?= t('Status') ?></th><th><?= t('Aksi') ?></th></tr></thead>
<tbody><?php foreach ($items as $i): ?><tr>
<td><?=$i['id']?></td>
<td><i class="bi <?=e($i['icon'])?> me-1"></i><strong><?=e($i['label'])?></strong></td>
<td><code><?=e($i['url'])?></code></td>
<td><?= $i['show_in_tabs'] ? '✅' : '—' ?></td>
<td><?= $i['show_in_menu'] ? '✅' : '—' ?></td>
<td><a href="nav-menus.php?toggle=<?=$i['id']?>" class="badge text-decoration-none bg-<?=$i['is_active']?'success':'secondary'?>"><?=$i['is_active']?t('Aktif'):t('Nonaktif')?></a></td>
<td><a href="nav-menus.php?edit=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="nav-menus.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('Hapus menu?') ?>')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>

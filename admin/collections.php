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
    db()->prepare("DELETE FROM collections WHERE id=?")->execute([$id]);
    header('Location: collections.php?msg=deleted'); exit;
}

// Handle add/edit
$editItem = null;
$editId = -1;
if (isset($_GET['edit'])) $editId = (int)$_GET['edit'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $isActive = (int)($_POST['is_active'] ?? 1);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (!$name) $error = 'Nama collection wajib diisi';

    if (!$error) {
        $slug = buatSlug($name);
        if ($id > 0) {
            $st = db()->prepare("UPDATE collections SET name=?, slug=?, description=?, is_active=?, sort_order=? WHERE id=?");
            $st->execute([$name, $slug, $desc, $isActive, $sortOrder, $id]);
            $msgType = 'updated';
        } else {
            $st = db()->prepare("INSERT INTO collections (name, slug, description, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
            $st->execute([$name, $slug, $desc, $isActive, $sortOrder]);
            $id = (int)db()->lastInsertId();
            $msgType = 'added';
        }

        // Handle collection_items (tour_id list)
        if ($id > 0) {
            db()->prepare("DELETE FROM collection_items WHERE collection_id = ?")->execute([$id]);
            $tourIds = $_POST['tour_ids'] ?? [];
            if (is_array($tourIds)) {
                $ins = db()->prepare("INSERT INTO collection_items (collection_id, item_type, item_id, sort_order) VALUES (?, 'tour', ?, ?)");
                foreach ($tourIds as $order => $tid) {
                    $tid = (int)$tid;
                    if ($tid > 0) $ins->execute([$id, $tid, $order]);
                }
            }
        }
        header("Location: collections.php?msg=$msgType"); exit;
    }
}

// Edit mode: load item + its items
if ($editId > 0) {
    $st = db()->prepare("SELECT * FROM collections WHERE id = ?");
    $st->execute([$editId]);
    $editItem = $st->fetch();
    if (!$editItem) $editId = -1;

    if ($editItem) {
        $st = db()->prepare("SELECT item_id FROM collection_items WHERE collection_id = ? AND item_type = 'tour' ORDER BY sort_order");
        $st->execute([$editId]);
        $selectedTourIds = $st->fetchAll(PDO::FETCH_COLUMN);
    }
}

// All tours for selector
$allTours = db()->query("SELECT id, title FROM tours WHERE is_active = 1 ORDER BY title")->fetchAll();

$items = db()->query("SELECT * FROM collections ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Koleksi Tour';
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Koleksi Tour</h4>
    <a href="?edit=new" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Koleksi</a>
</div>

<?php if ($msg): ?><div class="alert alert-success py-2"><?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= $error ?></div><?php endif; ?>

<?php if ($editId >= 0 && ($editItem || $editId === 0)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <h5 class="fw-semibold mb-3"><?= $editId > 0 ? 'Edit' : 'Tambah' ?> Koleksi</h5>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $editId ?>">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Nama</label>
                    <input name="name" class="form-control form-control-sm" value="<?= e($editItem['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Urutan</label>
                    <input name="sort_order" type="number" class="form-control form-control-sm" value="<?= $editItem['sort_order'] ?? 0 ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Aktif</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="1" <?= ($editItem['is_active'] ?? 1) ? 'selected' : '' ?>>Ya</option>
                        <option value="0" <?= empty($editItem['is_active']) ? 'selected' : '' ?>>Tidak</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-1">
                    <button type="submit" name="save" class="btn btn-primary btn-sm">Simpan</button>
                    <a href="collections.php" class="btn btn-outline-secondary btn-sm">Batal</a>
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label small">Deskripsi</label>
                <input name="description" class="form-control form-control-sm" value="<?= e($editItem['description'] ?? '') ?>">
            </div>
            <div class="mt-3">
                <label class="form-label small fw-semibold">Pilih Tour (centang untuk menambahkan)</label>
                <div class="row g-1" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($allTours as $t): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tour_ids[]" value="<?= $t['id'] ?>" id="tour<?= $t['id'] ?>"
                                <?= in_array($t['id'], $selectedTourIds ?? []) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="tour<?= $t['id'] ?>"><?= e($t['title']) ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm"><div class="card-body p-0">
<table class="table table-hover mb-0 admin-table">
<thead class="table-light"><tr><th>#</th><th>Nama</th><th>Slug</th><th>Item</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody><?php foreach ($items as $i): 
    $cnt = db()->prepare("SELECT COUNT(*) FROM collection_items WHERE collection_id = ?");
    $cnt->execute([$i['id']]);
    $itemCount = $cnt->fetchColumn();
?><tr>
<td><?=$i['id']?></td><td><strong><?=e($i['name'])?></strong></td>
<td><?=e($i['slug'])?></td>
<td><?=$itemCount?> tour</td>
<td><span class="badge bg-<?=$i['is_active']?'success':'secondary'?>"><?=$i['is_active']?'Aktif':'Nonaktif'?></span></td>
<td><a href="collections.php?edit=<?=$i['id']?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="collections.php?delete=<?=$i['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus koleksi?')"><i class="bi bi-trash"></i></a></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
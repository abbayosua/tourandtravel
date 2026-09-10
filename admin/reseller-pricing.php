<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$msg = '';
$editId = (int)($_GET['edit'] ?? 0);

// Handle delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId) {
        db()->prepare("DELETE FROM reseller_tour_prices WHERE id = ?")->execute([$delId]);
        header('Location: reseller-pricing.php?msg=deleted');
        exit;
    }
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $togId = (int)$_GET['toggle'];
    if ($togId) {
        db()->prepare("UPDATE reseller_tour_prices SET active = NOT active WHERE id = ?")->execute([$togId]);
        header('Location: reseller-pricing.php?msg=toggled');
        exit;
    }
}

// Handle save (create/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tourId = (int)($_POST['tour_id'] ?? 0);
    $resellerPrice = (float)($_POST['reseller_price'] ?? 0);
    $minPax = max(1, (int)($_POST['min_pax'] ?? 1));
    $active = isset($_POST['active']) ? 1 : 0;
    $postEditId = (int)($_POST['edit_id'] ?? 0);

    if (!$tourId || $resellerPrice <= 0) {
        $msg = 'error: Tour dan harga wajib diisi.';
    } else {
        if ($postEditId) {
            db()->prepare("UPDATE reseller_tour_prices SET tour_id = ?, reseller_price = ?, min_pax = ?, active = ? WHERE id = ?")
                ->execute([$tourId, $resellerPrice, $minPax, $active, $postEditId]);
            $msg = 'success: Harga reseller berhasil diupdate.';
        } else {
            try {
                db()->prepare("INSERT INTO reseller_tour_prices (tour_id, reseller_price, min_pax, active) VALUES (?, ?, ?, ?)")
                    ->execute([$tourId, $resellerPrice, $minPax, $active]);
                $msg = 'success: Harga reseller berhasil ditambahkan.';
            } catch (Throwable $e) {
                $msg = 'error: Tour sudah memiliki harga reseller. Edit yang sudah ada.';
            }
        }
        header('Location: reseller-pricing.php?msg=' . urlencode($msg));
        exit;
    }
}

// Edit data
$editData = null;
if ($editId) {
    $stmt = db()->prepare("SELECT * FROM reseller_tour_prices WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

// List
$prices = db()->query("SELECT rtp.*, t.title AS tour_title, t.price AS normal_price FROM reseller_tour_prices rtp JOIN tours t ON rtp.tour_id = t.id ORDER BY t.title ASC")->fetchAll();

// All tours for dropdown
$tours = db()->query("SELECT id, title, price FROM tours WHERE is_active = 1 ORDER BY title ASC")->fetchAll();

$pageTitle = 'Harga Reseller';
require_once 'includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><i class="bi bi-tags me-2"></i>Harga Reseller per Tour</h4>

<?php if ($msg): ?>
    <div class="alert alert-<?= str_starts_with($msg, 'success') ? 'success' : 'danger' ?> py-2"><?= e(substr($msg, strpos($msg, ':') + 2)) ?></div>
<?php endif; ?>

<!-- Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><?= $editData ? 'Edit Harga Reseller' : 'Tambah Harga Reseller' ?></h6>
        <form method="POST">
            <?php if ($editData): ?><input type="hidden" name="edit_id" value="<?= $editData['id'] ?>"><?php endif; ?>
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tour</label>
                    <select name="tour_id" class="form-select" required>
                        <option value="">Pilih tour...</option>
                        <?php foreach ($tours as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($editData && $editData['tour_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= e($t['title']) ?> (<?= formatRupiah((float)$t['price']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Harga Reseller</label>
                    <input type="number" name="reseller_price" class="form-control" min="0" step="1000" value="<?= $editData ? $editData['reseller_price'] : '' ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Min Pax</label>
                    <input type="number" name="min_pax" class="form-control" min="1" value="<?= $editData ? $editData['min_pax'] : 1 ?>" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="active" class="form-check-input" id="activeCheck" <?= (!$editData || $editData['active']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="activeCheck">Aktif</label>
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><?= $editData ? 'Update' : 'Tambah' ?></button>
                    <?php if ($editData): ?>
                        <a href="reseller-pricing.php" class="btn btn-outline-secondary btn-sm mt-1">Batal</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- List -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tour</th>
                        <th class="text-end">Harga Normal</th>
                        <th class="text-end">Harga Reseller</th>
                        <th class="text-center">Min Pax</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($prices)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada harga reseller.</td></tr>
                <?php else: ?>
                    <?php foreach ($prices as $p): ?>
                    <tr>
                        <td class="small fw-semibold"><?= e($p['tour_title']) ?></td>
                        <td class="text-end small"><?= formatRupiah((float)$p['normal_price']) ?></td>
                        <td class="text-end fw-bold text-primary"><?= formatRupiah((float)$p['reseller_price']) ?></td>
                        <td class="text-center"><?= $p['min_pax'] ?></td>
                        <td class="text-center">
                            <span class="badge <?= $p['active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $p['active'] ? 'Aktif' : 'Nonaktif' ?></span>
                        </td>
                        <td class="text-end">
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="?toggle=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Toggle" onclick="return confirm('Toggle status?')"><i class="bi bi-toggle-<?= $p['active'] ? 'on' : 'off' ?>"></i></a>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Hapus harga reseller ini?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>

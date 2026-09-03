<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

// Hapus tour
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = db()->prepare("DELETE FROM tours WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: tours.php?msg=deleted');
    exit;
}

// Bulk set content language
$bulkMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_set_lang'])) {
    $lang = in_array($_POST['bulk_lang'] ?? '', ['id', 'en']) ? $_POST['bulk_lang'] : 'id';
    $ids = $_POST['tour_ids'] ?? [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("UPDATE tours SET content_language = ? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$lang], $ids));

        // Auto-translate content for each tour
        $targetLang = $lang === 'id' ? 'en' : 'id';
        foreach ($ids as $tourId) {
            $tour = db()->prepare("SELECT title, description FROM tours WHERE id = ?");
            $tour->execute([$tourId]);
            $t = $tour->fetch();
            if ($t) {
                saveTranslation($t['title'], $targetLang, $t['title']);
                if (strlen($t['description']) > 10) {
                    saveTranslation($t['description'], $targetLang, $t['description']);
                }
            }
        }
        $bulkMsg = count($ids) . ' tour berhasil diatur ke bahasa ' . strtoupper($lang);
    } else {
        $bulkMsg = t('Pilih minimal 1 tour');
    }
    header('Location: tours.php?msg=bulk_lang&detail=' . urlencode($bulkMsg));
    exit;
}

$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $msg = 'Tour berhasil ditambahkan';
    if ($_GET['msg'] === 'updated') $msg = 'Tour berhasil diperbarui';
    if ($_GET['msg'] === 'deleted') $msg = 'Tour berhasil dihapus';
    if ($_GET['msg'] === 'bulk_lang') $msg = $_GET['detail'] ?? 'Bulk update selesai';
}

$tours = db()->query("SELECT * FROM tours ORDER BY created_at DESC")->fetchAll();

$pageTitle = t('Kelola Tour');
require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Kelola Tour') ?></h4>
    <a href="tour-add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= t('Tambah Tour') ?></a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible py-2"><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Bulk Set Content Language -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="POST" class="d-flex align-items-center gap-2 flex-wrap" id="bulkLangForm">
            <input type="hidden" name="bulk_set_lang" value="1">
            <strong class="small">Bulk Bahasa Konten:</strong>
            <select name="bulk_lang" class="form-select form-select-sm" style="width: auto;">
                <option value="id">🇮🇩 Indonesia</option>
                <option value="en">🇬🇧 English</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Set bahasa konten untuk tour yang dipilih?')">
                <i class="bi bi-translate me-1"></i>Terapkan
            </button>
            <span class="text-muted small" id="bulkCount"></span>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-tour admin-table">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" id="checkAll" class="form-check-input"></th>
                        <th><?= t('Gambar') ?></th>
                        <th><?= t('Judul') ?></th>
                        <th><?= t('Kategori') ?></th>
                        <th><?= t('Harga') ?></th>
                        <th><?= t('Max Peserta') ?></th>
                        <th><?= t('Status') ?></th>
                        <th><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tours as $t): ?>
                    <tr>
                        <td><input type="checkbox" class="form-check-input tour-check" value="<?= $t['id'] ?>"></td>
                        <td>
                            <?php if ($t['cover_image']): ?>
                                <img src="../uploads/<?= e($t['cover_image']) ?>" alt="">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center text-white" style="width:60px;height:40px;"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= e($t['title']) ?></strong></td>
                        <td><span class="badge bg-primary"><?= e($t['category']) ?></span></td>
                        <td><?= formatRupiah($t['price']) ?></td>
                        <td><?= $t['max_participants'] ?></td>
                        <td>
                            <span class="badge bg-<?= $t['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $t['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="table-action">
                            <a href="../tour-detail.php?slug=<?= htmlspecialchars($t['slug']) ?>" target="_blank" class="btn btn-sm btn-info text-white" title="Lihat"><i class="bi bi-eye"></i></a>
                            <a href="tour-edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="tours.php?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus tour ini?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tours)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada tour</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.tour-check').forEach(cb => {
        cb.checked = this.checked;
        cb.dispatchEvent(new Event('change'));
    });
});
document.querySelectorAll('.tour-check').forEach(cb => {
    cb.addEventListener('change', function() {
        const checked = document.querySelectorAll('.tour-check:checked').length;
        document.getElementById('bulkCount').textContent = checked ? checked + ' tour dipilih' : '';
        document.querySelectorAll('.tour-check').forEach(c => {
            c.closest('tr').style.backgroundColor = c.checked ? '#e8f4fd' : '';
        });
    });
});
document.getElementById('bulkLangForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.tour-check:checked');
    if (checked.length === 0) { e.preventDefault(); alert('<?= t('Pilih minimal 1 tour') ?>!'); return; }
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'tour_ids[]';
        input.value = cb.value;
        this.appendChild(input);
    });
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>

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

$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $msg = 'Tour berhasil ditambahkan';
    if ($_GET['msg'] === 'updated') $msg = 'Tour berhasil diperbarui';
    if ($_GET['msg'] === 'deleted') $msg = 'Tour berhasil dihapus';
}

$tours = db()->query("SELECT * FROM tours ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Kelola Tour';
require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Kelola Tour</h4>
    <a href="tour-add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Tour</a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible py-2"><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-tour">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Max Peserta</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tours as $t): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
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

<?php require_once 'includes/admin-footer.php'; ?>

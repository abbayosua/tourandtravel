<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Hero Slides');

// Hapus (dengan konfirmasi di sisi klien)
if (isset($_GET['delete'])) {
    db()->prepare("DELETE FROM hero_slides WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: hero-slides.php?msg=deleted');
    exit;
}

// Toggle status cepat
if (isset($_GET['toggle'])) {
    db()->prepare("UPDATE hero_slides SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$_GET['toggle']]);
    header('Location: hero-slides.php?msg=updated');
    exit;
}

$focusLabels = ['tour' => t('Tour'), 'hotel' => t('Hotel'), 'flight' => t('Pesawat'), 'all' => t('Semua Fokus')];
$slides = db()->query("SELECT * FROM hero_slides ORDER BY focus ASC, sort_order ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Hero Slides') ?></h4>
    <a href="hero-slide-edit.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> <?= t('Tambah') ?></a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
    <div class="alert alert-success py-2 small"><?= t('Berhasil ditambahkan') ?></div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success py-2 small"><?= t('Berhasil diperbarui') ?></div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success py-2 small"><?= t('Berhasil dihapus') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= t('Gambar') ?></th>
                        <th><?= t('Judul') ?></th>
                        <th><?= t('Fokus') ?></th>
                        <th><?= t('Urutan') ?></th>
                        <th><?= t('Status') ?></th>
                        <th class="text-end"><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!count($slides)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><?= t('Belum ada slide.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($slides as $s): ?>
                    <tr class="<?= !$s['is_active'] ? 'table-light text-muted' : '' ?>">
                        <td style="width: 120px;">
                            <img src="<?= e($s['image_url']) ?>" alt="" style="width: 110px; height: 60px; object-fit: cover;" class="rounded">
                        </td>
                        <td>
                            <div class="fw-semibold small"><?= e($s['title'] ?? '-') ?></div>
                            <div class="text-muted" style="font-size: 11px;"><?= e($s['subtitle'] ?? '') ?></div>
                        </td>
                        <td><span class="badge bg-secondary"><?= $focusLabels[$s['focus']] ?? e($s['focus']) ?></span></td>
                        <td><?= (int)$s['sort_order'] ?></td>
                        <td>
                            <a href="hero-slides.php?toggle=<?= (int)$s['id'] ?>" class="badge text-decoration-none <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $s['is_active'] ? t('Aktif') : t('Nonaktif') ?>
                            </a>
                        </td>
                        <td class="text-end">
                            <a href="hero-slide-edit.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary"><?= t('Edit') ?></a>
                            <a href="hero-slides.php?delete=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('<?= t('Hapus slide ini?') ?>')"><?= t('Hapus') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

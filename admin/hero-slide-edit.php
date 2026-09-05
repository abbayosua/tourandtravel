<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
$isAdd = $id === 0;

$item = ['image_url' => '', 'title' => '', 'subtitle' => '', 'cta_text' => '', 'cta_link' => '', 'focus' => 'all', 'sort_order' => 0, 'is_active' => 1];
if (!$isAdd) {
    $st = db()->prepare("SELECT * FROM hero_slides WHERE id = ?");
    $st->execute([$id]);
    $item = $st->fetch();
    if (!$item) { header('Location: hero-slides.php'); exit; }
}

$error = '';
$focusOptions = [
    'tour'   => t('Tour'),
    'hotel'  => t('Hotel'),
    'flight' => t('Pesawat'),
    'all'    => t('Semua Fokus'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $ctaText = trim($_POST['cta_text'] ?? '');
    $ctaLink = trim($_POST['cta_link'] ?? '');
    $focus = $_POST['focus'] ?? 'all';
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!array_key_exists($focus, $focusOptions)) $focus = 'all';
    if ($ctaLink !== '' && !preg_match('#^([a-z0-9\-]+\.php|/|https?://)#i', $ctaLink)) {
        $error = t('Link CTA harus nama file .php, path internal (diawali /), atau URL');
    }

    $imageUrl = $item['image_url'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['image']['name'])) {
        if (!is_dir(__DIR__ . '/../uploads/hero')) mkdir(__DIR__ . '/../uploads/hero', 0775, true);
        $upload = uploadGambar($_FILES['image'], __DIR__ . '/../uploads/hero');
        if ($upload['success']) {
            $imageUrl = 'uploads/hero/' . $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }

    if (!$error && !$imageUrl) {
        $error = t('Gambar slide wajib diupload');
    }

    if (!$error) {
        if ($isAdd) {
            db()->prepare("INSERT INTO hero_slides (image_url, title, subtitle, cta_text, cta_link, focus, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$imageUrl, $title ?: null, $subtitle ?: null, $ctaText ?: null, $ctaLink ?: null, $focus, $sortOrder, $isActive]);
        } else {
            db()->prepare("UPDATE hero_slides SET image_url=?, title=?, subtitle=?, cta_text=?, cta_link=?, focus=?, sort_order=?, is_active=? WHERE id=?")
                ->execute([$imageUrl, $title ?: null, $subtitle ?: null, $ctaText ?: null, $ctaLink ?: null, $focus, $sortOrder, $isActive, $id]);
        }
        header('Location: hero-slides.php?msg=' . ($isAdd ? 'added' : 'updated'));
        exit;
    }
}

$pageTitle = $isAdd ? t('Tambah Slide') : t('Edit Slide');
require_once __DIR__ . '/includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><?= $isAdd ? t('Tambah Slide') : t('Edit Slide') ?></h4>

<?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label"><?= t('Gambar') ?> *</label>
                    <input type="file" name="image" class="form-control" <?= $isAdd ? 'required' : '' ?>>
                    <div class="form-text"><?= t('JPG/PNG/WebP, maks 2MB. Rekomendasi 1920px lebar.') ?></div>
                    <?php if (!$isAdd): ?>
                        <img src="<?= e($item['image_url']) ?>" alt="" class="mt-2 rounded" style="max-height: 120px;">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Judul') ?> (ID)</label>
                    <input type="text" name="title" class="form-control" value="<?= e($item['title']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Subjudul') ?> (ID)</label>
                    <input type="text" name="subtitle" class="form-control" value="<?= e($item['subtitle']) ?>">
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label"><?= t('Teks CTA') ?></label>
                        <input type="text" name="cta_text" class="form-control" value="<?= e($item['cta_text']) ?>" placeholder="<?= t('Cari Sekarang') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label"><?= t('Link CTA') ?></label>
                        <input type="text" name="cta_link" class="form-control" value="<?= e($item['cta_link']) ?>" placeholder="tours.php">
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-md-5">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label"><?= t('Fokus') ?></label>
                    <select name="focus" class="form-select">
                        <?php foreach ($focusOptions as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= ($item['focus'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><?= t('Slide tampil di homepage dengan fokus ini (atau semua).') ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Urutan') ?></label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)$item['sort_order'] ?>" min="0">
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= !empty($item['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive"><?= t('Aktif') ?></label>
                </div>
                <button type="submit" class="btn btn-primary w-100"><?= t('Simpan') ?></button>
                <a href="hero-slides.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
            </div></div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

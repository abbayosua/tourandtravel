<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
$isAdd = $id === 0;
$item = ['title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'cover_image' => '', 'category' => '', 'status' => 'draft', 'title_en' => '', 'excerpt_en' => '', 'body_en' => ''];
if (!$isAdd) {
    $st = db()->prepare("SELECT * FROM posts WHERE id = ?");
    $st->execute([$id]);
    $item = $st->fetch();
    if (!$item) { header('Location: posts.php'); exit; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    if (!$title) $error = t('Judul wajib diisi');
    $slug = buatSlug($title);
    $cover = $item['cover_image'];
    if (!empty($_FILES['cover']['name'])) {
        if (!is_dir(__DIR__ . '/../uploads/blog')) mkdir(__DIR__ . '/../uploads/blog', 0775, true);
        $up = uploadGambar($_FILES['cover'], __DIR__ . '/../uploads/blog');
        if ($up['success']) $cover = 'uploads/blog/' . $up['filename'];
        else $error = $up['message'];
    }
    if (!$error) {
        $fields = [trim($_POST['title_en'] ?? ''), trim($_POST['excerpt'] ?? ''), trim($_POST['body'] ?? ''), $cover ?: null, trim($_POST['category'] ?? ''), $_POST['status'] ?? 'draft',
                   trim($_POST['title_en'] ?? ''), trim($_POST['excerpt_en'] ?? ''), trim($_POST['body_en'] ?? '')];
        if ($isAdd) {
            $pub = ($_POST['status'] ?? '') === 'published' ? date('Y-m-d H:i:s') : null;
            db()->prepare("INSERT INTO posts (title, slug, excerpt, body, cover_image, category, status, published_at, title_en, excerpt_en, body_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$title, $slug, $fields[1], $fields[2], $fields[3], $fields[4], $fields[5], $pub, $fields[6], $fields[7], $fields[8]]);
        } else {
            db()->prepare("UPDATE posts SET title=?, slug=?, excerpt=?, body=?, cover_image=?, category=?, status=?, title_en=?, excerpt_en=?, body_en=? WHERE id=?")
                ->execute([$title, $slug, $fields[1], $fields[2], $fields[3], $fields[4], $fields[5], $fields[6], $fields[7], $fields[8], $id]);
        }
        header('Location: posts.php?msg=updated');
        exit;
    }
}

$pageTitle = $isAdd ? t('Tambah Artikel') : t('Edit Artikel');
require_once __DIR__ . '/includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><?= $isAdd ? t('Tambah Artikel') : t('Edit Artikel') ?></h4>
<?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
                <div class="mb-3"><label class="form-label"><?= t('Judul') ?> (ID) *</label><input name="title" class="form-control" value="<?= e($item['title']) ?>" required></div>
                <div class="mb-3"><label class="form-label"><?= t('Judul') ?> (EN)</label><input name="title_en" class="form-control" value="<?= e($item['title_en']) ?>"></div>
                <div class="mb-3"><label class="form-label"><?= t('Ringkasan') ?> (ID)</label><textarea name="excerpt" class="form-control" rows="2"><?= e($item['excerpt']) ?></textarea></div>
                <div class="mb-3"><label class="form-label"><?= t('Ringkasan') ?> (EN)</label><textarea name="excerpt_en" class="form-control" rows="2"><?= e($item['excerpt_en']) ?></textarea></div>
                <div class="mb-3"><label class="form-label"><?= t('Isi') ?> (ID)</label><textarea name="body" class="form-control" rows="8"><?= e($item['body']) ?></textarea></div>
                <div class="mb-3"><label class="form-label"><?= t('Isi') ?> (EN)</label><textarea name="body_en" class="form-control" rows="6"><?= e($item['body_en']) ?></textarea></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
                <div class="mb-3"><label class="form-label"><?= t('Cover') ?></label>
                    <input type="file" name="cover" class="form-control" <?= $isAdd ? '' : '' ?>>
                    <?php if (!empty($item['cover_image'])): ?><img src="<?= e($item['cover_image']) ?>" class="mt-2 rounded w-100" alt=""><?php endif; ?>
                </div>
                <div class="mb-3"><label class="form-label"><?= t('Kategori') ?></label><input name="category" class="form-control" value="<?= e($item['category']) ?>"></div>
                <div class="mb-3"><label class="form-label"><?= t('Status') ?></label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>><?= t('Draft') ?></option>
                        <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>><?= t('Publish') ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100"><?= t('Simpan') ?></button>
                <a href="posts.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
            </div></div>
        </div>
    </div>
</form>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

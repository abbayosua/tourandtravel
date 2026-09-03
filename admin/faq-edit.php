<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
$isAdd = $id === 0;
$item = null;

if (!$isAdd) {
    $item = db()->prepare("SELECT * FROM faq_items WHERE id = ?");
    $item->execute([$id]);
    $item = $item->fetch();
    if (!$item) { header('Location: faq.php'); exit; }
}

$categories = db()->query("SELECT * FROM faq_categories ORDER BY sort_order ASC, name ASC")->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 1);

    if (!$question || !$answer || !$categoryId) $error = t('Pertanyaan, jawaban, dan kategori wajib diisi');

    if (!$error) {
        if ($isAdd) {
            $st = db()->prepare("INSERT INTO faq_items (category_id, question, answer, is_active) VALUES (?, ?, ?, ?)");
            $st->execute([$categoryId, $question, $answer, $isActive]);
            header('Location: faq.php?msg=added'); exit;
        } else {
            $st = db()->prepare("UPDATE faq_items SET category_id=?, question=?, answer=?, is_active=? WHERE id=?");
            $st->execute([$categoryId, $question, $answer, $isActive, $id]);
            header('Location: faq.php?msg=updated'); exit;
        }
    }
}

$pageTitle = $isAdd ? t('Tambah FAQ') : t('Edit FAQ');
require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= $isAdd ? t('Tambah') : t('Edit') ?> FAQ</h4>
    <a href="faq.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
</div>
<?php if ($error): ?><div class="alert alert-danger py-2"><?=$error?></div><?php endif; ?>
<form method="POST">
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3"><label class="form-label"><?= t('Pertanyaan') ?></label><input name="question" class="form-control" value="<?=e($item['question']??'')?>" required></div>
    <div class="mb-3"><label class="form-label"><?= t('Jawaban') ?></label><textarea name="answer" class="form-control" rows="6"><?=e($item['answer']??'')?></textarea></div>
</div></div>
</div>
<div class="col-md-4">
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="mb-3">
        <label class="form-label"><?= t('Kategori') ?></label>
        <select name="category_id" class="form-select" required>
            <option value="">-- Pilih --</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?=$c['id']?>" <?=($item['category_id']??0)==$c['id']?'selected':''?>><?=e($c['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= t('Status') ?></label>
        <select name="is_active" class="form-select"><option value="1" <?=($item['is_active']??1)?'selected':''?>>Aktif</option><option value="0" <?=empty($item['is_active'])?'selected':''?>>Nonaktif</option></select>
    </div>
</div></div>
<button type="submit" class="btn btn-primary w-100"><?= $isAdd ? t('Tambah') : t('Simpan') ?></button>
<a href="faq.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
</div></div></form>
<?php require_once 'includes/admin-footer.php'; ?>
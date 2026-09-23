<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brand'])) {
    $name = trim((string)($_POST['site_name'] ?? ''));
    $tagline = trim((string)($_POST['site_tagline'] ?? ''));
    if ($name === '') {
        $error = t('Nama travel wajib diisi');
    } elseif (mb_strlen($name) > 60) {
        $error = t('Nama travel maksimal 60 karakter');
    } else {
        setSetting('site_name', $name);
        setSetting('site_tagline', mb_substr($tagline, 0, 120));
        if (!empty($_POST['remove_logo'])) {
            setSetting('site_logo', '');
            $message = t('Nama tersimpan, logo dihapus');
        } elseif (!empty($_FILES['site_logo']['name'])) {
            $dir = __DIR__ . '/../uploads/brand';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $up = uploadGambar($_FILES['site_logo'], $dir);
            if ($up['success']) {
                setSetting('site_logo', 'uploads/brand/' . $up['filename']);
                $message = t('Brand & logo tersimpan');
            } else {
                $error = $up['message'];
            }
        } else {
            $message = t('Brand & logo tersimpan');
        }
    }
}

$siteName = siteName();
$siteTagline = (string)getSetting('site_tagline', '');
$logoUrl = function_exists('siteLogoUrl') ? siteLogoUrl() : '';
$pageTitle = t('Brand & Logo');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><i class="bi bi-award me-2"></i><?= t('Brand & Logo') ?></h4>
<?php if ($message): ?><div class="alert alert-success py-2" data-testid="brand-saved"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_brand" value="1">
                <div class="mb-3">
                    <label class="form-label"><?= t('Nama travel') ?></label>
                    <input name="site_name" class="form-control" maxlength="60" required value="<?= e($siteName) ?>" data-testid="brand-name">
                    <div class="form-text"><?= t('Tampil di navbar, footer, judul tab, email, notifikasi WA, dan PDF.') ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Tagline (opsional)') ?></label>
                    <input name="site_tagline" class="form-control" maxlength="120" value="<?= e($siteTagline) ?>" placeholder="Your World of Joy" data-testid="brand-tagline">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Logo (JPG/PNG/WebP, maks 2MB)') ?></label>
                    <input type="file" name="site_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp" data-testid="brand-logo">
                    <?php if ($logoUrl): ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img src="<?= e($logoUrl) ?>" alt="logo" style="height:48px" data-testid="brand-logo-preview">
                            <label class="form-check small text-muted"><input type="checkbox" class="form-check-input" name="remove_logo" value="1"> <?= t('Hapus logo') ?></label>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary" data-testid="brand-save"><?= t('Simpan') ?></button>
                <a href="dashboard.php" class="btn btn-outline-secondary"><?= t('Batal') ?></a>
            </form>
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold"><?= t('Pratinjau') ?></h6>
            <div class="d-flex align-items-center gap-2 border rounded p-2 mb-2">
                <?php if ($logoUrl): ?><img src="<?= e($logoUrl) ?>" alt="" style="height:32px">
                <?php else: ?><span class="voyage-brand-dot"></span><?php endif; ?>
                <b><?= e($siteName) ?></b>
            </div>
            <?php if ($siteTagline): ?><p class="small text-muted mb-0"><?= e($siteTagline) ?></p><?php endif; ?>
        </div></div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>

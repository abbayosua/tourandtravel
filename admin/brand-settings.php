<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once 'includes/admin-access.php';
requireAdminPage();

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
        foreach (['instagram', 'facebook', 'youtube', 'tiktok'] as $sn) {
            setSetting('social_' . $sn, trim((string)($_POST['social_' . $sn] ?? '')));
        }
        foreach (['address', 'phone', 'wa', 'email', 'hours_weekday', 'hours_sunday'] as $ck) {
            setSetting('contact_' . $ck, mb_substr(trim((string)($_POST['contact_' . $ck] ?? '')), 0, 160));
        }
        $emailFrom = trim((string)($_POST['email_from'] ?? ''));
        if ($emailFrom !== '' && !filter_var($emailFrom, FILTER_VALIDATE_EMAIL)) {
            $error = t('Alamat email pengirim tidak valid');
        } else {
            setSetting('email_from', mb_substr($emailFrom, 0, 160));
            $drv = strtolower(trim((string)($_POST['email_driver'] ?? 'log')));
            setSetting('email_driver', in_array($drv, ['log', 'api'], true) ? $drv : 'log');
            setSetting('email_api_key', mb_substr(trim((string)($_POST['email_api_key'] ?? '')), 0, 255));
            setSetting('email_api_endpoint', mb_substr(trim((string)($_POST['email_api_endpoint'] ?? 'https://api.resend.com/emails')), 0, 255));
        }
        if (!empty($_POST['remove_logo'])) {
            setSetting('site_logo', '');
            $message = t('Nama tersimpan, logo dihapus');
        } elseif (!empty($_FILES['site_logo']['name']) || !empty($_FILES['site_favicon']['name']) || !empty($_POST['remove_favicon']) || !empty($_FILES['site_og_image']['name']) || !empty($_POST['remove_og_image'])) {
            $dir = __DIR__ . '/../uploads/brand';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            if (!empty($_FILES['site_logo']['name'])) { $up = uploadGambar($_FILES['site_logo'], $dir); if ($up['success']) { setSetting('site_logo', 'uploads/brand/' . $up['filename']); $message = t('Brand & logo tersimpan'); } else { $error = $up['message']; } }
            if (!empty($_POST['remove_favicon'])) setSetting('site_favicon', ''); if (!empty($_POST['remove_og_image'])) setSetting('site_og_image', ''); if (!empty($_FILES['site_og_image']['name'])) { $og = uploadGambar($_FILES['site_og_image'], $dir); if ($og['success']) { setSetting('site_og_image', 'uploads/brand/' . $og['filename']); $message = t('Brand & logo tersimpan'); } else { $error = $og['message']; } }
            if (!empty($_FILES['site_favicon']['name'])) { $fv = $_FILES['site_favicon']; $fx = strtolower(pathinfo((string)$fv['name'], PATHINFO_EXTENSION)); if (!in_array($fx, ['ico','png','jpg','jpeg','webp','svg'], true)) { $error = t('Tipe favicon harus ICO/PNG/JPG/WebP/SVG'); } elseif (($fv['size'] ?? 0) > 524288) { $error = t('Ukuran favicon maksimal 512KB'); } else { $fn = uniqid('favicon') . '.' . ($fx === 'jpeg' ? 'jpg' : $fx); if (move_uploaded_file($fv['tmp_name'], $dir . '/' . $fn)) { setSetting('site_favicon', 'uploads/brand/' . $fn); $message = t('Brand & logo tersimpan'); } else { $error = t('Gagal menyimpan file'); } } }
            if ($message === '' && $error === '') $message = t('Brand & logo tersimpan');
        } else {
            $message = t('Brand & logo tersimpan');
        }
    }
}

$siteName = siteName();
$siteTagline = (string)getSetting('site_tagline', '');
$social = [];
foreach (['instagram', 'facebook', 'youtube', 'tiktok'] as $sn) { $social[$sn] = (string)getSetting('social_' . $sn, ''); }
$contact = [];
foreach (['address', 'phone', 'wa', 'email', 'hours_weekday', 'hours_sunday'] as $ck) { $contact[$ck] = siteContact($ck); }
$emailFrom = (string)getSetting('email_from', '');
$emailDriver = (string)getSetting('email_driver', 'log');
$emailApiKey = (string)getSetting('email_api_key', '');
$emailEndpoint = (string)getSetting('email_api_endpoint', 'https://api.resend.com/emails');
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
            <form method="POST" data-submit-once enctype="multipart/form-data">
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
                    <label class="form-label"><?= t('Media Sosial') ?></label>
                    <?php foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $sn => $snLabel): ?>
                    <input name="social_<?= $sn ?>" class="form-control form-control-sm mb-1" type="url" value="<?= e($social[$sn]) ?>" placeholder="<?= $snLabel ?> URL" data-testid="brand-social-<?= $sn ?>">
                    <?php endforeach; ?>
                    <div class="form-text"><?= t('Kosongkan untuk menyembunyikan.') ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Kontak & Alamat (tampil di footer)') ?></label>
                    <input name="contact_address" class="form-control form-control-sm mb-1" maxlength="160" value="<?= e($contact['address']) ?>" placeholder="<?= t('Alamat') ?>" data-testid="brand-contact-address">
                    <div class="row g-1 mb-1">
                        <div class="col-6"><input name="contact_phone" class="form-control form-control-sm" maxlength="160" value="<?= e($contact['phone']) ?>" placeholder="<?= t('Telepon') ?>" data-testid="brand-contact-phone"></div>
                        <div class="col-6"><input name="contact_wa" class="form-control form-control-sm" maxlength="160" value="<?= e($contact['wa']) ?>" placeholder="WhatsApp" data-testid="brand-contact-wa"></div>
                    </div>
                    <input name="contact_email" class="form-control form-control-sm mb-1" type="email" maxlength="160" value="<?= e($contact['email']) ?>" placeholder="Email" data-testid="brand-contact-email">
                    <div class="row g-1">
                        <div class="col-6"><input name="contact_hours_weekday" class="form-control form-control-sm" maxlength="160" value="<?= e($contact['hours_weekday']) ?>" placeholder="<?= t('Jam Senin-Sabtu') ?>" data-testid="brand-contact-hours-weekday"></div>
                        <div class="col-6"><input name="contact_hours_sunday" class="form-control form-control-sm" maxlength="160" value="<?= e($contact['hours_sunday']) ?>" placeholder="<?= t('Jam Minggu') ?>" data-testid="brand-contact-hours-sunday"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Email pengirim (transaksional)') ?></label>
                    <div class="row g-1 mb-1">
                        <div class="col-7"><input name="email_from" class="form-control form-control-sm" type="email" maxlength="160" value="<?= e($emailFrom) ?>" placeholder="noreply@royaldream.example" data-testid="brand-email-from"></div>
                        <div class="col-5"><select name="email_driver" class="form-select form-select-sm" data-testid="brand-email-driver"><option value="log"<?= $emailDriver === 'log' ? ' selected' : '' ?>>log (dev)</option><option value="api"<?= $emailDriver === 'api' ? ' selected' : '' ?>>api (Resend)</option></select></div>
                    </div>
                    <input name="email_api_key" class="form-control form-control-sm mb-1" type="password" autocomplete="new-password" maxlength="255" value="<?= e($emailApiKey) ?>" placeholder="<?= e(t('API key (kosongkan bila tidak diubah)')) ?>" data-testid="brand-email-api-key">
                    <input name="email_api_endpoint" class="form-control form-control-sm" maxlength="255" value="<?= e($emailEndpoint) ?>" placeholder="https://api.resend.com/emails" data-testid="brand-email-endpoint">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Logo (JPG/PNG/WebP, maks 2MB)') ?></label>
                    <input type="file" name="site_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp" data-testid="brand-logo">
                    <input type="file" name="site_favicon" class="form-control mt-1" accept=".ico,.png,.jpg,.jpeg,.webp,.svg" data-testid="brand-favicon-input" aria-label="favicon">
                    <input type="file" name="site_og_image" class="form-control mt-1" accept=".jpg,.jpeg,.png,.webp" data-testid="brand-og-input" aria-label="og image">
                    <?php $favUrl = function_exists('siteFaviconUrl') ? siteFaviconUrl() : ''; if ($favUrl !== ''): ?><div class="mt-1"><img src="<?= e($favUrl) ?>" alt="favicon" style="height:24px" data-testid="brand-favicon-preview"> <label class="form-check small text-muted"><input type="checkbox" class="form-check-input" name="remove_favicon" value="1"> <?= t('Hapus favicon') ?></label></div><?php endif; ?>
                    <?php $ogUrl = function_exists('siteOgImageUrl') ? siteOgImageUrl() : ''; if ($ogUrl !== ''): ?><div class="mt-1"><img src="<?= e($ogUrl) ?>" alt="og" style="height:48px" data-testid="brand-og-preview"> <label class="form-check small text-muted"><input type="checkbox" class="form-check-input" name="remove_og_image" value="1"> <?= t('Hapus OG image') ?></label></div><?php endif; ?>
                    <div class="form-text"><?= t('Favicon: ICO/PNG/JPG/WebP/SVG maks 512KB. OG image: JPG/PNG/WebP maks 2MB.') ?></div>
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

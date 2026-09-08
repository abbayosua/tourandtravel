<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

cekLogin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $propertyId = trim($_POST['tawk_property_id'] ?? '');
    $widgetId = trim($_POST['tawk_widget_id'] ?? '');

    if ($propertyId !== '' && !preg_match('/^[a-f0-9]{24}$/i', $propertyId)) {
        $error = t('Property ID tawk.to harus 24 karakter hex (contoh: 5f1a2b3c4d5e6f7a8b9c0d1e)');
    } elseif ($propertyId !== '' && $widgetId !== '' && !preg_match('/^[a-z0-9]{1,10}$/i', $widgetId)) {
        $error = t('Widget ID tawk.to hanya alfanumerik maks 10 karakter');
    } else {
        setSetting('tawk_property_id', $propertyId);
        setSetting('tawk_widget_id', $widgetId ?: 'default');
        $message = t('Pengaturan live chat tersimpan');
    }
}

$tawkPropertyId = getSetting('tawk_property_id', '');
$tawkWidgetId = getSetting('tawk_widget_id', 'default');

$pageTitle = t('Live Chat');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><i class="bi bi-chat-dots me-2"></i><?= t('Live Chat (tawk.to)') ?></h4>
<?php if ($message): ?><div class="alert alert-success py-2"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <form method="POST">
                <input type="hidden" name="save_settings" value="1">
                <div class="mb-3">
                    <label class="form-label"><?= t('Property ID') ?></label>
                    <input name="tawk_property_id" class="form-control" value="<?= e($tawkPropertyId) ?>" placeholder="5f1a2b3c4d5e6f7a8b9c0d1e" data-testid="tawk-property">
                    <div class="form-text"><?= t('Dari dashboard tawk.to: Administration → Property ID. Kosongkan untuk menonaktifkan widget.') ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= t('Widget ID') ?></label>
                    <input name="tawk_widget_id" class="form-control" value="<?= e($tawkWidgetId) ?>" placeholder="default" data-testid="tawk-widget">
                    <div class="form-text"><?= t('Umumnya "default" (1i[...]) — biarkan bila tidak yakin.') ?></div>
                </div>
                <button type="submit" class="btn btn-primary" data-testid="tawk-save"><?= t('Simpan') ?></button>
                <a href="dashboard.php" class="btn btn-outline-secondary"><?= t('Batal') ?></a>
            </form>
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-semibold"><?= t('Status') ?></h6>
            <?php if ($tawkPropertyId): ?>
                <span class="badge bg-success" data-testid="tawk-status-on"><?= t('Aktif') ?></span>
                <p class="small text-muted mt-2 mb-0">https://embed.tawk.to/<?= e($tawkPropertyId) ?>/<?= e($tawkWidgetId) ?></p>
            <?php else: ?>
                <span class="badge bg-secondary" data-testid="tawk-status-off"><?= t('Nonaktif') ?></span>
                <p class="small text-muted mt-2 mb-0"><?= t('Widget tidak dirender sampai Property ID diisi.') ?></p>
            <?php endif; ?>
        </div></div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>

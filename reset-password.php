<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Atur Password Baru');
$error = '';
$success = '';
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

$row = null;
if ($token !== '') {
    $st = db()->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $st->execute([hash('sha256', $token)]);
    $row = $st->fetch();
    if (!$row) $error = t('Tautan reset tidak valid atau sudah kedaluwarsa.');
} else {
    $error = t('Tautan reset tidak valid atau sudah kedaluwarsa.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) $error = t('Password minimal 6 karakter');
    elseif ($password !== $confirm) $error = t('Konfirmasi password tidak cocok');
    else {
        db()->prepare("UPDATE users SET password_hash = ? WHERE email = ?")
            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['email']]);
        db()->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?")->execute([$row['id']]);
        db()->prepare("DELETE FROM password_resets WHERE email = ? AND used_at IS NULL")->execute([$row['email']]);
        $success = t('Password berhasil diubah. Silakan masuk dengan password baru.');
    }
}

require_once 'includes/header-klook.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm klook-auth-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h5 class="fw-bold mb-1"><?= t('Atur Password Baru') ?></h5>
                        </div>
                        <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success py-2 small"><?= e($success) ?></div>
                            <p class="text-center mt-3 small"><a href="login.php" class="text-decoration-none fw-semibold"><?= t('Masuk') ?></a></p>
                        <?php elseif ($row): ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?= e($token) ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Password Baru') ?></label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Konfirmasi Password') ?></label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2"><?= t('Simpan Password') ?></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

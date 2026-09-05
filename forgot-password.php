<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/email.php';

$pageTitle = t('Lupa Password');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('Alamat email tidak valid');
    } else {
        // Rate limit: 1 permintaan/menit per email
        $st = db()->prepare("SELECT COUNT(*) FROM password_resets WHERE email = ? AND created_at > NOW() - INTERVAL 1 MINUTE");
        $st->execute([$email]);
        if ((int)$st->fetchColumn() > 0) {
            $error = t('Terlalu banyak permintaan. Coba lagi dalam satu menit.');
        } else {
            $u = db()->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
            $u->execute([$email]);
            $user = $u->fetch();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                db()->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
                    ->execute([$email, hash('sha256', $token)]);
                $link = BASE_URL . '/reset-password.php?token=' . $token;
                sendEmailTemplate($email, 'reset-password', [
                    'name' => $user['name'],
                    'reset_link' => $link,
                    'subject' => t('Reset Password') . ' - ' . SITE_NAME,
                ], null);
            }
            // Pesan sama baik email ada/tidak (jangan bocorkan keberadaan akun)
            $success = t('Bila email terdaftar, tautan reset telah dikirim. Periksa kotak masuk Anda.');
        }
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
                            <h5 class="fw-bold mb-1"><?= t('Lupa Password') ?></h5>
                            <p class="text-muted small mb-0"><?= t('Masukkan email Anda untuk menerima tautan reset.') ?></p>
                        </div>
                        <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success py-2 small"><?= e($success) ?></div>
                        <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Email') ?></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2"><?= t('Kirim Tautan Reset') ?></button>
                        </form>
                        <?php endif; ?>
                        <p class="text-center mt-3 small">
                            <a href="login.php" class="text-decoration-none fw-semibold"><?= t('Kembali') ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

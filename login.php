<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $redirect = $_GET['redirect'] ?? 'index.php';
        header("Location: $redirect");
        exit;
    } else {
        $error = t('Email atau password salah');
    }
}

$pageTitle = 'Login';
require_once 'includes/header-klook.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm klook-auth-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mb-3" style="width: 64px; height: 64px;"><i class="bi bi-person-circle fs-2"></i></div>
                            <h5 class="fw-bold mb-1"><?= t('Masuk') ?></h5>
                            <p class="text-muted small mb-0"><?= t('Selamat datang kembali!') ?></p>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><?= $error ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Email') ?></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Password') ?></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-2 text-end">
                                <a href="forgot-password.php" class="small text-decoration-none"><?= t('Lupa password?') ?></a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2"><?= t('Masuk') ?></button>
                        </form>
                        <p class="text-center mt-3 small">
                            <?= t('Belum punya akun?') ?> <a href="register.php" class="text-decoration-none fw-semibold"><?= t('Daftar') ?></a>
                        </p>
                    </div>
                </div>
                <p class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> <?= t('Kembali') ?></a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer-klook.php'; ?>

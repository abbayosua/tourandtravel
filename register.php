<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name) $error = 'Nama harus diisi';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Email tidak valid';
    elseif (strlen($password) < 6) $error = 'Password minimal 6 karakter';
    elseif ($password !== $confirm) $error = 'Konfirmasi password tidak cocok';

    if (!$error) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Email sudah terdaftar';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare("INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hash]);

            $_SESSION['user_id'] = db()->lastInsertId();
            $_SESSION['user_name'] = $name;
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Daftar';
require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-center mb-3">Daftar Akun Baru</h5>
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><?= $error ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">No. Telepon</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold">Daftar</button>
                        </form>
                        <p class="text-center mt-3 small">
                            Sudah punya akun? <a href="login.php" class="text-decoration-none">Masuk</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

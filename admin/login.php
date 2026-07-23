<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill display-6 text-primary"></i>
                            <h4 class="fw-bold mt-2">Admin Panel</h4>
                            <p class="text-muted small"><?= SITE_NAME ?></p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Username</label>
                                <input type="text" name="username" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-semibold">Masuk</button>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3 small text-muted">
                    <a href="../index.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke Website</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

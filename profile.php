<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name) $error = 'Nama harus diisi';

    if (!$error && $password) {
        if (strlen($password) < 6) $error = 'Password minimal 6 karakter';
        elseif ($password !== $confirm) $error = 'Konfirmasi password tidak cocok';
    }

    if (!$error) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare("UPDATE users SET name = ?, phone = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $hash, $userId]);
        } else {
            $stmt = db()->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $userId]);
        }
        $_SESSION['user_name'] = $name;
        $success = 'Profil berhasil diperbarui';
    }
}

$pageTitle = 'Profil Saya';
require_once 'includes/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h4 class="fw-bold mb-3"><i class="bi bi-person-circle me-2"></i>Profil Saya</h4>

                <?php if ($success): ?>
                    <div class="alert alert-success py-2"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= $error ?></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email</label>
                                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                                <small class="text-muted">Email tidak dapat diubah</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">No. Telepon</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                            </div>
                            <hr>
                            <h6 class="fw-semibold mb-3">Ganti Password</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Password Baru</label>
                                <input type="password" name="password" class="form-control" minlength="6" placeholder="Kosongkan jika tidak ingin ganti">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Kosongkan jika tidak ingin ganti">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-0">Aktivitas Akun</h6>
                        <div class="d-flex gap-4 mt-3">
                            <a href="my-bookings.php" class="text-decoration-none text-center">
                                <div class="fs-4 text-primary"><i class="bi bi-ticket-perforated"></i></div>
                                <small>Booking</small>
                            </a>
                            <a href="wishlist.php" class="text-decoration-none text-center">
                                <div class="fs-4 text-danger"><i class="bi bi-heart"></i></div>
                                <small>Wishlist</small>
                            </a>
                            <a href="logout.php" class="text-decoration-none text-center">
                                <div class="fs-4 text-muted"><i class="bi bi-box-arrow-right"></i></div>
                                <small>Keluar</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

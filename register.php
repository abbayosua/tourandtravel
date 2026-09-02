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
            $userId = (int)db()->lastInsertId();

            // Generate referral code: REF-{user_id}-{random4}
            $referralCode = 'REF-' . $userId . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $upd = db()->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
            $upd->execute([$referralCode, $userId]);

            // Handle referral param (?ref=CODE) dari pendaftar
            $refCode = trim($_GET['ref'] ?? '');
            if ($refCode) {
                $refStmt = db()->prepare("SELECT id FROM users WHERE referral_code = ?");
                $refStmt->execute([$refCode]);
                $referrerId = $refStmt->fetchColumn();
                if ($referrerId && (int)$referrerId !== $userId) {
                    db()->prepare("UPDATE users SET referred_by = ? WHERE id = ?")->execute([$referrerId, $userId]);
                    $ins = db()->prepare("INSERT INTO referrals (referrer_id, referred_email, referred_user_id, status) VALUES (?, ?, ?, 'completed')");
                    $ins->execute([$referrerId, $email, $userId]);
                    // Reward referrer (bonus KlookCash)
                    require_once 'includes/wallet.php';
                    addWalletTransaction($referrerId, 50000, 'bonus', 'Reward referral ' . $email, 'referral', (int)db()->lastInsertId());
                }
            }

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Daftar';
require_once 'includes/header-klook.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm klook-auth-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mb-3" style="width: 64px; height: 64px;"><i class="bi bi-person-plus fs-2"></i></div>
                            <h5 class="fw-bold mb-1">Daftar Akun Baru</h5>
                            <p class="text-muted small mb-0">Buat akun untuk mulai berpetualang</p>
                        </div>
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
                            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Daftar</button>
                        </form>
                        <p class="text-center mt-3 small">
                            Sudah punya akun? <a href="login.php" class="text-decoration-none fw-semibold">Masuk</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer-klook.php'; ?>

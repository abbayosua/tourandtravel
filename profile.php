<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$userId = $_SESSION['user_id'];
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

$user = getUser();

// Ringkasan statistik: total booking + wishlist count
$statBookings = db()->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$statBookings->execute([$userId]);
$totalBookings = (int)$statBookings->fetchColumn();

$statWishlist = db()->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
$statWishlist->execute([$userId]);
$totalWishlist = (int)$statWishlist->fetchColumn();

// Wallet balance (KlookCash) via helper
require_once 'includes/wallet.php';
$walletBalance = getWalletBalance($userId);

// Loyalty tier berdasarkan total booking
$loyaltyTier = 'Explorer';
$loyaltyIcon = 'bi-star';
$loyaltyColor = 'text-muted';
if ($totalBookings >= 10) { $loyaltyTier = 'Joy+'; $loyaltyIcon = 'bi-gem'; $loyaltyColor = 'text-warning'; }
elseif ($totalBookings >= 5) { $loyaltyTier = 'Gold'; $loyaltyIcon = 'bi-award'; $loyaltyColor = 'text-warning'; }
elseif ($totalBookings >= 2) { $loyaltyTier = 'Silver'; $loyaltyIcon = 'bi-star-fill'; $loyaltyColor = 'text-secondary'; }

// Referral
$refCode = $user['referral_code'] ?? '';
$refLink = $refCode ? BASE_URL . '/register.php?ref=' . urlencode($refCode) : '';
$totalRef = 0;
$refReward = 0;
if ($refCode) {
    $st = db()->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ?");
    $st->execute([$userId]);
    $totalRef = (int)$st->fetchColumn();
    foreach (getWalletTransactions($userId, 1000) as $t) {
        if ($t['type'] === 'bonus' && strpos($t['description'] ?? '', 'referral') !== false) $refReward += (float)$t['amount'];
    }
}

$initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));

$pageTitle = 'Profil Saya';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-3"><i class="bi bi-person-circle me-2"></i>Profil Saya</h4>

                <?php if ($success): ?>
                    <div class="alert alert-success py-2"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= $error ?></div>
                <?php endif; ?>

                <!-- Header Card: avatar + statistik -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center klook-avatar" style="width: 72px; height: 72px; font-size: 28px; font-weight: 700;"><?= e($initial) ?></div>
                            <div>
                                <h5 class="fw-bold mb-0"><?= e($user['name']) ?>
                                    <span class="badge bg-dark align-middle ms-1" style="font-size: 10px;"><i class="bi <?= $loyaltyIcon ?> me-1 <?= $loyaltyColor ?>"></i><?= $loyaltyTier ?></span>
                                </h5>
                                <small class="text-muted"><?= e($user['email']) ?></small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center g-2">
                            <div class="col-3">
                                <div class="fw-bold text-primary fs-4"><?= $totalBookings ?></div>
                                <small class="text-muted">Booking</small>
                            </div>
                            <div class="col-3">
                                <div class="fw-bold text-danger fs-4"><?= $totalWishlist ?></div>
                                <small class="text-muted">Wishlist</small>
                            </div>
                            <div class="col-3">
                                <a href="wallet.php" class="text-decoration-none">
                                    <div class="fw-bold text-success fs-4"><?= formatRupiah($walletBalance) ?></div>
                                    <small class="text-muted">KlookCash</small>
                                </a>
                            </div>
                            <div class="col-3">
                                <a href="my-bookings.php" class="text-decoration-none">
                                    <div class="fw-bold text-primary fs-4"><i class="bi bi-ticket-perforated"></i></div>
                                    <small class="text-muted">Lihat</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form 2 kolom -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Email</label>
                                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                                    <small class="text-muted">Email tidak dapat diubah</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">No. Telepon</label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                            <hr class="my-4">
                            <h6 class="fw-semibold mb-3">Ganti Password</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Password Baru</label>
                                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Kosongkan jika tidak ingin ganti">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Konfirmasi Password</label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Kosongkan jika tidak ingin ganti">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-4">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <!-- Aktivitas Akun -->
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

                <!-- Referral Card -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-people text-primary me-1"></i>Referral Program</h6>
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <div class="text-center">
                                <div class="fw-bold text-primary fs-5"><?= $totalRef ?></div>
                                <small class="text-muted">Referral</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold text-success fs-5"><?= formatRupiah($refReward) ?></div>
                                <small class="text-muted">Reward</small>
                            </div>
                            <div class="flex-grow-1">
                                <?php if ($refLink): ?>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="profileRefLink" value="<?= e($refLink) ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('profileRefLink').value);this.textContent='OK';setTimeout(()=>this.textContent='Salin',1500);">Salin</button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <a href="referral.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
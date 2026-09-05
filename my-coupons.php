<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) { header('Location: login.php?redirect=my-coupons.php'); exit; }

$pageTitle = t('Kupon Saya');
$userId = (int)$_SESSION['user_id'];

// Kupon dari promo_codes: aktif (belum kedaluwarsa & batas tersisa) + riwayat pakai tidak tersimpan per-user (tabel tidak ada) → tampilkan semua aktif & expired
$active = db()->query("SELECT * FROM promo_codes WHERE is_active = 1 AND valid_until >= CURDATE() ORDER BY valid_until ASC")->fetchAll();
$expired = db()->query("SELECT * FROM promo_codes WHERE is_active = 1 AND valid_until < CURDATE() ORDER BY valid_until DESC")->fetchAll();

require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-ticket-perforated me-2"></i><?= t('Kupon Saya') ?></h4>

        <h6 class="fw-semibold mt-4 mb-3"><?= t('Tersedia') ?></h6>
        <div class="row g-3">
            <?php foreach ($active as $c): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-success mb-0"><code><?= e($c['code']) ?></code></h5>
                            <span class="badge bg-success"><?= $c['discount_type'] === 'percentage' ? (int)$c['discount_value'] . '%' : formatRupiah($c['discount_value']) ?></span>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <?= t('Berlaku sampai') ?> <?= formatDate($c['valid_until']) ?>
                            <?php if ($c['min_purchase']): ?> · <?= t('Min') ?> <?= formatRupiah($c['min_purchase']) ?><?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!count($active)): ?>
                <div class="col-12"><div class="text-center text-muted py-4"><?= t('Belum ada kupon tersedia.') ?></div></div>
            <?php endif; ?>
        </div>

        <?php if (count($expired)): ?>
        <h6 class="fw-semibold mt-5 mb-3 text-muted"><?= t('Kedaluwarsa') ?></h6>
        <div class="row g-3">
            <?php foreach ($expired as $c): ?>
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100 opacity-75">
                    <div class="card-body">
                        <code><?= e($c['code']) ?></code> <span class="badge bg-secondary"><?= t('Kedaluwarsa') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

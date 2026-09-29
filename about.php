<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Tentang Kami');
$brand = siteName();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-shared.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('Beranda'), 'url' => 'index.php'],
            ['label' => t('Tentang Kami'), 'url' => null],
        ]); ?>

        <div class="text-center mb-4">
            <h2 class="fw-bold mb-1"><?= t('Tentang Kami') ?></h2>
            <p class="text-muted"><?= e(str_replace(':brand', $brand, t('Mengenal lebih dekat :brand'))) ?></p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                    <h5 class="fw-bold mb-2"><i class="bi bi-airplane-engines-fill text-primary me-2"></i><?= t('Siapa Kami') ?></h5>
                    <p class="text-muted small mb-2"><?= e(str_replace(':brand', $brand, t(':brand adalah platform pemesanan perjalanan online yang menyediakan paket tour domestik & internasional, hotel, tiket pesawat, ferry, kereta, transfer, atraksi, eSIM, dan rental mobil dalam satu tempat.'))) ?></p>
                    <p class="text-muted small mb-0"><?= e(t('Kami bekerja sama dengan penyedia layanan terpercaya untuk memberikan harga transparan, proses booking yang mudah, dan pembayaran yang aman.')) ?></p>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-center">
                            <i class="bi bi-tag fs-3 text-primary"></i>
                            <h6 class="fw-bold mt-2 mb-1"><?= t('Harga Terbaik') ?></h6>
                            <p class="text-muted small mb-0"><?= t('Harga transparan tanpa biaya tersembunyi.') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-center">
                            <i class="bi bi-shield-check fs-3 text-success"></i>
                            <h6 class="fw-bold mt-2 mb-1"><?= t('Pembayaran Aman') ?></h6>
                            <p class="text-muted small mb-0"><?= t('Didukung payment gateway resmi & terenkripsi.') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-center">
                            <i class="bi bi-headset fs-3 text-warning"></i>
                            <h6 class="fw-bold mt-2 mb-1"><?= t('Dukungan Pelanggan') ?></h6>
                            <p class="text-muted small mb-0"><?= t('Tim support siap membantu booking Anda.') ?></p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-telephone text-primary me-2"></i><?= t('Hubungi Kami') ?></h5>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="bi bi-geo-alt-fill me-2"></i><?= e(siteContact('address')) ?></li>
                        <li class="mb-2"><i class="bi bi-telephone-fill me-2"></i><?= e(siteContact('phone')) ?></li>
                        <li class="mb-2"><i class="bi bi-whatsapp me-2"></i><?= e(siteContact('wa')) ?></li>
                        <li class="mb-2"><i class="bi bi-envelope-fill me-2"></i><?= e(siteContact('email')) ?></li>
                        <li class="mb-0"><i class="bi bi-clock me-2"></i><?= e(siteContact('hours_weekday')) ?> &middot; <?= e(siteContact('hours_sunday')) ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-shared.php'; ?>

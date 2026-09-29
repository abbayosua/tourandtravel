<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Kebijakan Privasi');
$brand = siteName();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-shared.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('Beranda'), 'url' => 'index.php'],
            ['label' => t('Kebijakan Privasi'), 'url' => null],
        ]); ?>
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-1"><?= t('Kebijakan Privasi') ?></h2>
            <p class="text-muted small"><?= e(t('Terakhir diperbarui: September 2026')) ?></p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-2">1. <?= t('Data yang Kami Kumpulkan') ?></h5>
                    <p class="text-muted small"><?= e(str_replace(':brand', $brand, t(':brand mengumpulkan data yang Anda berikan saat mendaftar dan memesan: nama, email, nomor telepon, detail perjalanan, dan riwayat transaksi. Kami juga mencatat data teknis dasar (perangkat, browser) untuk keamanan.'))) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">2. <?= t('Penggunaan Data') ?></h5>
                    <p class="text-muted small"><?= e(t('Data digunakan untuk memproses pemesanan, verifikasi pembayaran melalui payment gateway resmi, mengirim e-tiket/notifikasi, dukungan pelanggan, dan peningkatan layanan. Kami tidak menjual data pribadi Anda.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">3. <?= t('Berbagi Data') ?></h5>
                    <p class="text-muted small"><?= e(t('Data dibagikan secara terbatas kepada pihak yang diperlukan untuk memenuhi pesanan: penyedia layanan (maskapai, hotel, operator tour), payment gateway, dan penyedia pengiriman notifikasi — hanya sebatas yang dibutuhkan.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">4. <?= t('Keamanan & Penyimpanan') ?></h5>
                    <p class="text-muted small"><?= e(t('Data pembayaran diproses langsung oleh payment gateway bersertifikat; kami tidak menyimpan nomor kartu. Akses data internal dibatasi dan dilindungi. Anda dapat meminta perbaikan atau penghapusan data melalui kontak di bawah.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">5. <?= t('Kontak Privasi') ?></h5>
                    <p class="text-muted small mb-0"><?= e(siteContact('email')) ?> / <?= e(siteContact('wa')) ?>.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-shared.php'; ?>

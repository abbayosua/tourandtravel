<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Ketentuan Layanan');
$brand = siteName();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-shared.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('Beranda'), 'url' => 'index.php'],
            ['label' => t('Ketentuan Layanan'), 'url' => null],
        ]); ?>
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-1"><?= t('Ketentuan Layanan') ?></h2>
            <p class="text-muted small"><?= e(t('Terakhir diperbarui: September 2026')) ?></p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-2">1. <?= t('Definisi Layanan') ?></h5>
                    <p class="text-muted small"><?= e(str_replace(':brand', $brand, t(':brand adalah platform pemesanan perjalanan online untuk paket tour, hotel, tiket pesawat, ferry, kereta, transfer, atraksi, eSIM, dan rental mobil. Dengan menggunakan situs ini, Anda menyetujui seluruh ketentuan di halaman ini.'))) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">2. <?= t('Pemesanan & Pembayaran') ?></h5>
                    <p class="text-muted small mb-2"><?= e(t('Harga yang tertera adalah harga final dalam Rupiah, kecuali dinyatakan lain. Pemesanan bersifat confirmed setelah pembayaran terverifikasi oleh payment gateway (transfer bank, virtual account, gerai retail, QRIS, atau e-wallet).')) ?></p>
                    <p class="text-muted small"><?= e(t('Batas waktu pembayaran mengikuti ketentuan tiap channel pembayaran. Pesanan yang melewati batas waktu otomatis dibatalkan oleh sistem.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">3. <?= t('Tanggung Jawab Pelanggan') ?></h5>
                    <p class="text-muted small"><?= e(t('Pelanggan wajib memberikan data yang benar (nama, kontak, tanggal perjalanan, jumlah peserta). Kesalahan data yang menyebabkan kegagalan layanan menjadi tanggung jawab pelanggan.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">4. <?= t('Perubahan & Pembatalan oleh Penyedia') ?></h5>
                    <p class="text-muted small"><?= e(t('Jadwal, maskapai, operator ferry/kereta, atau itinerary dapat berubah karena kondisi operasional atau force majeure. Bila layanan dibatalkan oleh penyedia, pelanggan berhak atas penjadwalan ulang atau refund sesuai Kebijakan Refund.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">5. <?= t('Batasan Tanggung Jawab') ?></h5>
                    <p class="text-muted small"><?= e(str_replace(':brand', $brand, t(':brand bertindak sebagai perantara pemesanan antara pelanggan dan penyedia layanan. Tanggung jawab atas pelaksanaan layanan (penerbangan, menginap, tour) berada pada masing-masing penyedia, sesuai syarat mereka.'))) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">6. <?= t('Kontak') ?></h5>
                    <p class="text-muted small mb-0"><?= e(t('Pertanyaan terkait ketentuan ini dapat disampaikan melalui:')) ?> <?= e(siteContact('email')) ?> / <?= e(siteContact('wa')) ?>.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-shared.php'; ?>

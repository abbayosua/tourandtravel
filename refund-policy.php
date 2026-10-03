<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Kebijakan Refund');
$brand = siteName();

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-shared.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([
            ['label' => t('Beranda'), 'url' => 'index.php'],
            ['label' => t('Kebijakan Refund'), 'url' => null],
        ]); ?>
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-1"><?= t('Kebijakan Refund') ?></h2>
            <p class="text-muted small"><?= e(t('Terakhir diperbarui: September 2026')) ?></p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-2">1. <?= t('Ketentuan Umum Refund') ?></h5>
                    <p class="text-muted small"><?= e(str_replace(':brand', $brand, t('Pengajuan refund hanya berlaku untuk booking berstatus confirmed yang belum digunakan. Dana refund yang disetujui dikreditkan ke saldo wallet (TravelPoints) akun Anda.'))) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">2. <?= t('Besaran Refund Paket Tour') ?></h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered small mb-2">
                            <thead class="table-light"><tr><th><?= t('Waktu Pengajuan') ?></th><th><?= t('Refund') ?></th></tr></thead>
                            <tbody class="text-muted">
                                <tr><td><?= t('H-8 atau lebih sebelum keberangkatan') ?></td><td>100%</td></tr>
                                <tr><td><?= t('H-4 sampai H-7 sebelum keberangkatan') ?></td><td>50%</td></tr>
                                <tr><td><?= t('H-3 atau kurang / setelah keberangkatan') ?></td><td><?= t('0% (non-refundable)') ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small"><?= e(t('Sebagian produk memiliki kebijakan khusus: full_refund (selalu 100%) atau non_refundable (selalu 0%), tercantum di halaman detail produk. Tiket pesawat, hotel, ferry, dan kereta mengikuti kebijakan maskapai/penyedia masing-masing.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">3. <?= t('Cara Mengajukan Refund') ?></h5>
                    <p class="text-muted small"><?= e(t('Buka halaman Booking Saya, pilih booking confirmed, klik Minta Refund, dan isi alasan. Tim kami akan meninjau pengajuan maksimal 3x24 jam hari kerja. Status pengajuan (requested / approved / rejected) dapat dipantau di halaman yang sama.')) ?></p>
                    <h5 class="fw-bold mb-2 mt-4">4. <?= t('Kontak Refund') ?></h5>
                    <p class="text-muted small mb-0"><?= e(siteContact('email')) ?> / <?= e(siteContact('wa')) ?>.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-shared.php'; ?>

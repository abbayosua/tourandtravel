<?php
/**
 * Section "Kenapa Pilih Kami" (trust badges) — reusable di semua preset homepage.
 * Kondisional ringan: preset hotel/flight menampilkan headline yang sesuai.
 */
$trustFocus = isset($siteFocus) ? $siteFocus : 'tour';
?>
<!-- Kenapa Pilih Kami – trust badges -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h5 class="fw-bold">
                <?php if ($trustFocus === 'hotel'): ?>
                    <?= t('Kenapa Booking Hotel di') ?> <?= SITE_NAME ?>?
                <?php elseif ($trustFocus === 'flight'): ?>
                    <?= t('Kenapa Pesan Tiket di') ?> <?= SITE_NAME ?>?
                <?php else: ?>
                    <?= t('Kenapa Pilih') ?> <?= SITE_NAME ?>?
                <?php endif; ?>
            </h5>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-tags-fill"></i></div>
                    <h6 class="fw-semibold"><?= t('Harga Transparan') ?></h6>
                    <small class="text-muted"><?= t('Tidak ada biaya tersembunyi') ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-shield-check"></i></div>
                    <h6 class="fw-semibold"><?= t('Terpercaya') ?></h6>
                    <small class="text-muted">7 <?= t('tahun melayani pelanggan') ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-headset"></i></div>
                    <h6 class="fw-semibold"><?= t('CS 24/7') ?></h6>
                    <small class="text-muted"><?= t('Siap bantu kapan saja') ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-4 h-100">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-wallet2"></i></div>
                    <h6 class="fw-semibold"><?= t('Mudah Booking') ?></h6>
                    <small class="text-muted"><?= t('Proses cepat & praktis') ?></small>
                </div>
            </div>
        </div>
    </div>
</section>

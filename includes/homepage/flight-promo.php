<?php
/**
 * Preset Flight — Banner promo + rute populer dengan harga mulai.
 * Sumber: flight_schedules JOIN flights (jadwal aktif), GROUP BY rute.
 * Link rute → flights.php?from=<city>&to=<city> (param existing).
 */
$flightRoutes = db()->query("
    SELECT f.from_city, f.to_city, MIN(s.price) AS min_price, COUNT(*) AS jadwal
    FROM flight_schedules s
    JOIN flights f ON s.flight_id = f.id
    WHERE s.is_active = 1
    GROUP BY f.from_city, f.to_city
    ORDER BY min_price ASC
    LIMIT 6
")->fetchAll();
?>
<section class="py-5">
    <div class="container">
        <!-- Banner promo -->
        <div class="rounded-4 p-4 p-md-5 mb-5 text-white" style="background: linear-gradient(135deg, #e33d2e 0%, #f26522 100%);">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-1"><i class="bi bi-lightning-charge-fill me-2"></i><?= t('Promo Tiket Setiap Hari') ?></h5>
                    <p class="mb-0 text-white-50"><?= t('Harga spesial untuk rute favorit — kuota terbatas, pesan sekarang.') ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="flights.php" class="btn btn-light rounded-pill px-4 fw-semibold"><?= t('Lihat Semua Promo') ?></a>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1"><?= t('Rute Populer') ?></h5>
                <p class="text-muted small mb-0"><?= t('Harga mulai dari — per penumpang, one way') ?></p>
            </div>
            <a href="flights.php" class="btn btn-outline-primary rounded-pill px-4"><?= t('Lihat Semua') ?> <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <?php if (count($flightRoutes) > 0): ?>
            <div class="row g-3">
                <?php foreach ($flightRoutes as $r):
                    $fromName = trim(explode('(', $r['from_city'])[0]);
                    $toName = trim(explode('(', $r['to_city'])[0]);
                ?>
                <div class="col-md-6 col-lg-4">
                    <a href="flights.php?from=<?= urlencode($fromName) ?>&to=<?= urlencode($toName) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 klook-hover-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-semibold small"><?= e($fromName) ?></div>
                                    <i class="bi bi-airplane-fill text-primary mx-2"></i>
                                    <div class="fw-semibold small text-end"><?= e($toName) ?></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <small class="text-muted"><?= $r['jadwal'] ?> <?= t('jadwal') ?></small>
                                    <div class="text-end">
                                        <span class="fw-bold text-primary"><?= formatRupiah($r['min_price']) ?></span>
                                        <small class="text-muted d-block" style="font-size: 10px;"><?= t('harga mulai') ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-airplane fs-1 text-muted"></i>
                <p class="mt-2 text-muted"><?= t('Belum ada jadwal penerbangan tersedia.') ?></p>
                <a href="flights.php" class="btn btn-primary rounded-pill px-4"><?= t('Cari Penerbangan') ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

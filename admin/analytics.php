<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/analytics.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Analytics');
[$from, $to] = analyticsRange($_GET['from'] ?? null, $_GET['to'] ?? null);
$kpi = analyticsKpi($from, $to);
$perDay = analyticsBookingsPerDay($from, $to);
$verticals = analyticsRevenuePerVertical($from, $to);
$topTours = analyticsTopTours($from, $to);
$funnel = analyticsFunnel($from, $to);

require_once __DIR__ . '/includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><?= t('Analytics') ?></h4>

<form method="GET" class="row g-2 align-items-end mb-4">
    <div class="col-auto"><label class="form-label small mb-0"><?= t('Dari') ?></label><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>"></div>
    <div class="col-auto"><label class="form-label small mb-0"><?= t('Sampai') ?></label><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>"></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary"><?= t('Filter') ?></button></div>
</form>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        [t('Booking'), number_format($kpi['bookings']), 'bi-calendar-check'],
        [t('Revenue'), formatRupiah($kpi['revenue']), 'bi-cash-stack'],
        [t('Pengguna'), number_format($kpi['users']), 'bi-people'],
        [t('Subscriber'), number_format($kpi['subscribers']), 'bi-envelope'],
    ];
    foreach ($cards as [$label, $val, $icon]): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-4">
            <i class="bi <?= $icon ?> fs-3 text-primary mb-2"></i>
            <div class="fs-4 fw-bold"><?= $val ?></div>
            <small class="text-muted"><?= $label ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><?= t('Booking per hari') ?></h6>
        <?php
        $max = 1;
        foreach ($perDay as $d) $max = max($max, (int)$d['n']);
        $w = max(count($perDay) * 26, 100);
        ?>
        <svg viewBox="0 0 <?= $w ?> 120" style="width:100%; height:120px;">
            <?php foreach ($perDay as $i => $d): $h = (int)($d['n'] / $max * 100); ?>
            <rect x="<?= $i * 26 + 4 ?>" y="<?= 110 - $h ?>" width="18" height="<?= $h ?>" fill="var(--primary)" rx="3"></rect>
            <text x="<?= $i * 26 + 13 ?>" y="118" font-size="7" text-anchor="middle" fill="#6b7280"><?= date('d', strtotime($d['d'])) ?></text>
            <text x="<?= $i * 26 + 13 ?>" y="<?= 106 - $h ?>" font-size="8" text-anchor="middle" fill="#111"><?= (int)$d['n'] ?></text>
            <?php endforeach; ?>
        </svg>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-4">
            <h6 class="fw-semibold mb-3"><?= t('Revenue per vertikal') ?></h6>
            <?php if (!count($verticals)): ?><p class="text-muted small mb-0"><?= t('Belum ada data.') ?></p><?php endif; ?>
            <?php foreach ($verticals as $v): ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="fw-semibold text-capitalize small"><?= e($v['type']) ?> <small class="text-muted">(<?= $v['n'] ?>)</small></span>
                <span class="fw-bold text-primary"><?= formatRupiah($v['total']) ?></span>
            </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-4">
            <h6 class="fw-semibold mb-3"><?= t('Top Tour') ?></h6>
            <?php if (!count($topTours)): ?><p class="text-muted small mb-0"><?= t('Belum ada data.') ?></p><?php endif; ?>
            <?php foreach ($topTours as $t): ?>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="small"><?= e($t['title']) ?> <small class="text-muted">(<?= $t['n'] ?>)</small></span>
                <span class="fw-semibold small"><?= formatRupiah($t['total']) ?></span>
            </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
    <h6 class="fw-semibold mb-3"><?= t('Funnel Booking') ?></h6>
    <div class="d-flex gap-3 flex-wrap">
        <?php foreach (['pending', 'confirmed', 'cancelled'] as $st): ?>
        <div class="text-center px-4 py-3 rounded-3 bg-light">
            <div class="fs-4 fw-bold"><?= $funnel[$st] ?? 0 ?></div>
            <small class="text-muted"><?= ucfirst(t($st)) ?></small>
        </div>
        <?php endforeach; ?>
    </div>
</div></div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

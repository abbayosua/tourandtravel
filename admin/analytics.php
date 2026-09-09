<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/analytics.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Analytics');
[$from, $to] = analyticsRange($_GET['from'] ?? null, $_GET['to'] ?? null);
$vertFilter = $_GET['type'] ?? '';
if ($vertFilter !== '' && !array_key_exists($vertFilter, analyticsBookingTables())) $vertFilter = '';
$kpi = analyticsKpi($from, $to);
$perDay = analyticsBookingsPerDay($from, $to);
$verticals = analyticsRevenuePerVertical($from, $to);
$topTours = analyticsTopTours($from, $to);
$funnel = analyticsFunnel($from, $to);
$verticalLabels = ['tour' => t('Tour'), 'hotel' => t('Hotel'), 'flight' => t('Pesawat'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('Kereta'), 'esim' => 'eSIM', 'ferry' => t('Ferry')];
// Top produk lintas vertikal (Top 10)
$topProducts = [];
foreach (analyticsSalesUnion($from, $to, $vertFilter ?: null) as $r) {
    if (in_array($r['status'], ['cancelled'], true)) continue;
    $key = $r['btype'] . '|' . $r['item_title'];
    if (!isset($topProducts[$key])) $topProducts[$key] = ['title' => $r['item_title'], 'type' => $r['btype'], 'n' => 0, 'total' => 0.0];
    $topProducts[$key]['n']++;
    $topProducts[$key]['total'] += (float)$r['total_price'];
}
usort($topProducts, fn($a, $b) => $b['total'] <=> $a['total']);
$topProducts = array_slice($topProducts, 0, 10);
$perVerticalCounts = analyticsBookingsPerVertical($from, $to);

require_once __DIR__ . '/includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><?= t('Analytics') ?></h4>

<!-- Tabs filter vertikal -->
<ul class="nav nav-pills mb-3 flex-wrap" data-testid="analytics-vertical-tabs">
    <li class="nav-item"><a class="nav-link <?= $vertFilter === '' ? 'active' : '' ?>" href="analytics.php?from=<?= e($from) ?>&to=<?= e($to) ?>"><?= t('Semua Tipe') ?></a></li>
    <?php foreach ($verticalLabels as $vk => $vLabel): ?>
    <li class="nav-item"><a class="nav-link <?= $vertFilter === $vk ? 'active' : '' ?>" href="analytics.php?from=<?= e($from) ?>&to=<?= e($to) ?>&type=<?= $vk ?>" data-testid="tab-<?= $vk ?>"><?= $vLabel ?></a></li>
    <?php endforeach; ?>
</ul>

<form method="GET" class="row g-2 align-items-end mb-4">
    <input type="hidden" name="type" value="<?= e($vertFilter) ?>">
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
                <span class="fw-semibold text-capitalize small"><?= e($verticalLabels[$v['type']] ?? $v['type']) ?> <small class="text-muted">(<?= $v['n'] ?>)</small></span>
                <span class="fw-bold text-primary"><?= formatRupiah($v['total']) ?></span>
            </div>
            <?php endforeach; ?>
            <div style="height:220px;" class="mt-3"><canvas id="revenuePieChart" data-testid="chart-revenue-pie"></canvas></div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-4">
            <h6 class="fw-semibold mb-3"><?= t('Top 10 Produk') ?><?= $vertFilter ? ' — ' . $verticalLabels[$vertFilter] : '' ?></h6>
            <?php if (!count($topProducts)): ?><p class="text-muted small mb-0"><?= t('Belum ada data.') ?></p><?php endif; ?>
            <?php foreach ($topProducts as $tp): ?>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="small"><span class="badge bg-<?= ['tour' => 'primary', 'hotel' => 'danger', 'flight' => 'dark', 'attraction' => 'info', 'transfer' => 'warning text-dark', 'train' => 'success', 'esim' => 'secondary', 'ferry' => 'primary'][$tp['type']] ?>"><?= $verticalLabels[$tp['type']] ?></span> <?= e($tp['title']) ?> <small class="text-muted">(<?= $tp['n'] ?>)</small></span>
                <span class="fw-semibold small"><?= formatRupiah($tp['total']) ?></span>
            </div>
            <?php endforeach; ?>
            <div style="height:220px;" class="mt-3"><canvas id="topProductsChart" data-testid="chart-top-products"></canvas></div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    // Pie revenue per vertikal
    var pieLabels = <?= json_encode(array_map(fn($v) => $verticalLabels[$v['type']] ?? $v['type'], $verticals), JSON_UNESCAPED_UNICODE) ?>;
    var pieData = <?= json_encode(array_map(fn($v) => (float)$v['total'], $verticals)) ?>;
    if (!pieData.length) { pieLabels = ['<?= t('Belum ada data') ?>']; pieData = [1]; }
    new Chart(document.getElementById('revenuePieChart'), {
        type: 'doughnut',
        data: { labels: pieLabels, datasets: [{ data: pieData, backgroundColor: ['#0d6efd', '#dc3545', '#212529', '#0dcaf0', '#ffc107', '#198754', '#6c757d', '#6610f2'] }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Bar top 10 produk
    var tpLabels = <?= json_encode(array_slice(array_map(fn($tp) => $tp['title'], $topProducts), 0, 10), JSON_UNESCAPED_UNICODE) ?>;
    var tpData = <?= json_encode(array_slice(array_map(fn($tp) => (float)$tp['total'], $topProducts), 0, 10)) ?>;
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: { labels: tpLabels, datasets: [{ label: '<?= t('Pendapatan') ?>', data: tpData, backgroundColor: '#0d6efd' }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/analytics.php';
require_once '../includes/auth.php';
cekLogin();

// ============================================================
// KPI Agregat (8 tabel booking, status-aware) — PRD ADMINPRD.md 3.1
// ============================================================
$agg = ['total' => 0, 'pending' => 0, 'success' => 0, 'cancelled' => 0, 'revenue' => 0.0, 'cogs' => 0.0];
foreach (analyticsBookingTables() as $table) {
    try {
        $r = db()->query("SELECT COUNT(*) total,
                COALESCE(SUM(status = 'pending'),0) pending,
                COALESCE(SUM(status IN ('confirmed','paid')),0) success,
                COALESCE(SUM(status = 'cancelled'),0) cancelled,
                COALESCE(SUM(CASE WHEN status IN ('confirmed','paid') THEN total_price ELSE 0 END),0) revenue,
                COALESCE(SUM(CASE WHEN status IN ('confirmed','paid') THEN cogs ELSE 0 END),0) cogs
            FROM `$table`")->fetch();
        $agg['total'] += (int)$r['total'];
        $agg['pending'] += (int)$r['pending'];
        $agg['success'] += (int)$r['success'];
        $agg['cancelled'] += (int)$r['cancelled'];
        $agg['revenue'] += (float)$r['revenue'];
        $agg['cogs'] += (float)$r['cogs'];
    } catch (Throwable $e) {}
}
$totalExpenses = 0.0;
try { $totalExpenses = (float)db()->query("SELECT COALESCE(SUM(amount),0) t FROM expenses")->fetchColumn(); } catch (Throwable $e) {}

$totalTours = (int)db()->query("SELECT COUNT(*) FROM tours WHERE is_active = 1")->fetchColumn();
$totalBookings = $agg['total'];
$totalPending = $agg['pending'];
$totalConfirmed = $agg['success'];
$totalRevenue = $agg['revenue'];
$netProfit = $totalRevenue - $agg['cogs'] - $totalExpenses;
$aov = $totalConfirmed > 0 ? $totalRevenue / $totalConfirmed : 0.0;
$convBase = $totalConfirmed + $agg['cancelled'];
$conversionRate = $convBase > 0 ? round($totalConfirmed / $convBase * 100, 1) : 0.0;

// Booking terbaru (union ternormalisasi)
$recentBookings = array_slice(analyticsSalesUnion(date('Y-m-d', strtotime('-90 day')), date('Y-m-d')), 0, 5);

// Activity feed: 10 aktivitas terakhir (booking baru + pembayaran) digabung urut waktu
$activities = [];
$verticalLabels = ['tour' => t('Tour'), 'hotel' => t('Hotel'), 'flight' => t('Pesawat'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('Kereta'), 'esim' => 'eSIM', 'ferry' => t('Ferry')];
foreach (analyticsSalesUnion(date('Y-m-d', strtotime('-7 day')), date('Y-m-d')) as $b) {
    $activities[] = [
        'icon' => 'bi-ticket-perforated',
        'color' => 'primary',
        'title' => t('Booking baru') . ' — ' . ($verticalLabels[$b['btype']] ?? $b['btype']),
        'desc' => ($b['booking_code'] ?: '#' . $b['id']) . ' · ' . $b['name'] . ' · ' . formatRupiah($b['total_price']),
        'at' => $b['created_at'],
    ];
}
try {
    $st = db()->query("SELECT p.booking_type, p.booking_code, p.order_id, p.gross_amount, p.status, p.created_at FROM payments p WHERE p.created_at >= NOW() - INTERVAL 7 DAY ORDER BY p.created_at DESC LIMIT 10");
    foreach ($st->fetchAll() as $p) {
        $activities[] = [
            'icon' => 'bi-credit-card',
            'color' => $p['status'] === 'paid' ? 'success' : ($p['status'] === 'pending' ? 'warning' : 'secondary'),
            'title' => t('Pembayaran') . ' — ' . ucfirst($p['status']),
            'desc' => $p['booking_code'] . ' · ' . formatRupiah($p['gross_amount']),
            'at' => $p['created_at'],
        ];
    }
} catch (Throwable $e) {}
usort($activities, fn($a, $b2) => strtotime($b2['at']) <=> strtotime($a['at']));
$activities = array_slice($activities, 0, 10);

// Chart data: revenue trend 30 hari + booking per vertikal
$chartFrom = date('Y-m-d', strtotime('-29 day'));
$chartTo = date('Y-m-d');
$trend = analyticsRevenueTrend($chartFrom, $chartTo);
$perVertical = analyticsBookingsPerVertical($chartFrom, $chartTo);

$pageTitle = t('Dashboard');
require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><?= t('Dashboard') ?></h4>
    <span class="text-muted small"><?= t('Selamat datang') ?>, <?= e($_SESSION['admin_username']) ?></span>
</div>

<!-- 8 KPI Cards -->
<div class="row g-3 mb-4">
    <?php
    $kpiCards = [
        [t('Tour Aktif'), number_format($totalTours), 'bi-map', 'primary', t('Aktif')],
        [t('Total Booking'), number_format($totalBookings), 'bi-ticket-perforated', 'info', t('Semua status')],
        [t('Pending'), number_format($totalPending), 'bi-hourglass-split', 'warning', t('Menunggu')],
        [t('Confirmed'), number_format($totalConfirmed), 'bi-check-circle', 'success', t('Terkonfirmasi')],
        [t('Revenue'), formatRupiah($totalRevenue), 'bi-currency-dollar', 'primary', t('Pendapatan')],
        [t('Net Profit'), formatRupiah($netProfit), 'bi-graph-up-arrow', $netProfit >= 0 ? 'success' : 'danger', t('Laba bersih')],
        [t('Avg Order Value'), formatRupiah($aov), 'bi-receipt', 'info', t('Rata-rata per transaksi')],
        [t('Conversion Rate'), $conversionRate . '%', 'bi-percent', 'warning', t('Tingkat konversi')],
    ];
    $valueClass = ['Revenue', 'Net Profit', 'Avg Order Value'];
    foreach ($kpiCards as [$label, $val, $icon, $color, $foot]):
    ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm stat-card h-100" data-testid="kpi-<?= preg_replace('/[^a-z]/', '', strtolower($label)) ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1"><?= $label ?></div>
                        <div class="<?= in_array($label, $valueClass, true) ? 'fs-6 fw-bold' : 'fs-3 fw-bold' ?> text-<?= $color === 'primary' ? 'dark' : $color ?>"><?= $val ?></div>
                    </div>
                    <div class="rounded-circle bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi <?= $icon ?> fs-4"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2"><?= $foot ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3"><?= t('Tren Pendapatan') ?> — <?= t('30 hari terakhir') ?></h6>
                <div style="height:280px;"><canvas id="revenueTrendChart" data-testid="chart-revenue-trend"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3"><?= t('Booking per Vertikal') ?></h6>
                <div style="height:280px;"><canvas id="verticalBarChart" data-testid="chart-vertical-bar"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions + Recent Bookings + Activity Feed -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3 d-flex flex-wrap gap-2 align-items-center" data-testid="quick-actions">
                <span class="text-muted small me-2"><i class="bi bi-lightning-charge"></i> <?= t('Aksi Cepat') ?>:</span>
                <a href="bookings.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-ticket-perforated"></i> <?= t('Kelola Booking') ?></a>
                <a href="sales-report.php" class="btn btn-sm btn-outline-success"><i class="bi bi-graph-up"></i> <?= t('Sales Report') ?></a>
                <a href="accounting.php" class="btn btn-sm btn-outline-warning"><i class="bi bi-cash-stack"></i> <?= t('Accounting') ?></a>
                <a href="tour-add.php" class="btn btn-sm btn-outline-info"><i class="bi bi-plus-circle"></i> <?= t('Tambah Tour') ?></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <!-- Recent Bookings -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0"><?= t('Booking Terbaru') ?></h6>
                <a href="bookings.php" class="btn btn-sm btn-outline-primary"><?= t('Lihat Semua') ?></a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th><?= t('Kode') ?></th>
                                <th><?= t('Nama') ?></th>
                                <th><?= t('Item') ?></th>
                                <th><?= t('Tipe') ?></th>
                                <th><?= t('Total') ?></th>
                                <th><?= t('Status') ?></th>
                                <th><?= t('Tanggal') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $typeName = $verticalLabels;
                            $typeBadge = ['tour' => 'primary', 'attraction' => 'info', 'transfer' => 'warning text-dark', 'train' => 'success', 'esim' => 'secondary', 'hotel' => 'danger', 'flight' => 'dark', 'ferry' => 'primary'];
                            foreach ($recentBookings as $b): ?>
                            <tr>
                                <td><?= (int)$b['id'] ?></td>
                                <td><strong class="small" style="font-size:11px;"><?= e($b['booking_code'] ?: ('#' . $b['id'])) ?></strong></td>
                                <td><?= e($b['name']) ?></td>
                                <td><small><?= e($b['item_title']) ?></small></td>
                                <td><span class="badge bg-<?= $typeBadge[$b['btype']] ?>"><?= $typeName[$b['btype']] ?></span></td>
                                <td><?= formatRupiah($b['total_price']) ?></td>
                                <td>
                                    <span class="badge bg-<?= in_array($b['status'], ['confirmed', 'paid'], true) ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : ($b['status'] === 'refunded' ? 'info' : 'danger')) ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($b['created_at'])) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentBookings)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= t('Belum ada booking') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <!-- Activity Feed -->
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><?= t('Aktivitas Terakhir') ?></h6>
            </div>
            <div class="card-body p-3" style="max-height: 420px; overflow-y: auto;" data-testid="activity-feed">
                <?php if (empty($activities)): ?>
                    <p class="text-muted small mb-0"><?= t('Belum ada aktivitas') ?></p>
                <?php endif; ?>
                <?php foreach ($activities as $a): ?>
                <div class="d-flex align-items-start gap-2 py-2 border-bottom">
                    <div class="rounded-circle bg-<?= $a['color'] ?> bg-opacity-10 text-<?= $a['color'] ?> d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px; height:34px;">
                        <i class="bi <?= $a['icon'] ?>"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="small fw-semibold text-truncate"><?= $a['title'] ?></div>
                        <div class="small text-muted text-truncate"><?= e($a['desc']) ?></div>
                        <div class="text-muted" style="font-size: 11px;"><?= date('d/m H:i', strtotime($a['at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    var trendLabels = <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['d'])), $trend), JSON_UNESCAPED_SLASHES) ?>;
    var trendData = <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $trend)) ?>;
    var vertLabels = <?= json_encode(array_map(fn($v) => $verticalLabels[$v['type']], $perVertical), JSON_UNESCAPED_UNICODE) ?>;
    var vertData = <?= json_encode(array_map(fn($v) => (int)$v['n'], $perVertical)) ?>;

    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: '<?= t('Pendapatan') ?>',
                data: trendData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { callback: function (v) { return 'Rp ' + (v >= 1000000 ? (v / 1000000).toFixed(1) + 'jt' : (v >= 1000 ? (v / 1000).toFixed(0) + 'rb' : v)); } } }
            }
        }
    });

    new Chart(document.getElementById('verticalBarChart'), {
        type: 'bar',
        data: {
            labels: vertLabels,
            datasets: [{
                label: '<?= t('Booking') ?>',
                data: vertData,
                backgroundColor: ['#0d6efd', '#dc3545', '#212529', '#0dcaf0', '#ffc107', '#198754', '#6c757d', '#6610f2']
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
})();
</script>

<?php require_once 'includes/admin-footer.php'; ?>

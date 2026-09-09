<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/analytics.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Sales Report');

[$from, $to] = analyticsRange($_GET['from'] ?? null, $_GET['to'] ?? null);
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
if ($type !== '' && !array_key_exists($type, analyticsBookingTables())) $type = '';
if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'refunded'], true)) $status = '';

$rows = analyticsSalesUnion($from, $to, $type ?: null, $status ?: null);

// Payment methods (dari payments table) — map booking_code -> payment_type
$payMap = [];
try {
    $st = db()->prepare("SELECT booking_code, payment_type, status FROM payments WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY AND payment_type IS NOT NULL");
    $st->execute([$from, $to]);
    foreach ($st->fetchAll() as $p) $payMap[$p['booking_code']] = $p['payment_type'] . ($p['status'] === 'paid' ? '' : ' (' . $p['status'] . ')');
} catch (Throwable $e) {}

// Summary
$totalTx = count($rows);
$activeRows = array_filter($rows, fn($r) => !in_array($r['status'], ['cancelled'], true));
$totalRevenue = array_sum(array_column($activeRows, 'total_price'));
$avgTx = count($activeRows) > 0 ? $totalRevenue / count($activeRows) : 0.0;
$perType = [];
foreach ($rows as $r) {
    $perType[$r['btype']] = ($perType[$r['btype']] ?? 0) + (in_array($r['status'], ['cancelled'], true) ? 0 : (float)$r['total_price']);
}
arsort($perType);
$topVertical = array_key_first($perType);
$verticalLabels = ['tour' => t('Tour'), 'hotel' => t('Hotel'), 'flight' => t('Pesawat'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('Kereta'), 'esim' => 'eSIM', 'ferry' => t('Ferry')];
$typeBadge = ['tour' => 'primary', 'attraction' => 'info', 'transfer' => 'warning text-dark', 'train' => 'success', 'esim' => 'secondary', 'hotel' => 'danger', 'flight' => 'dark', 'ferry' => 'primary'];

// Export CSV (sebelum output HTML)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales-report-' . $from . '-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, [t('Tanggal'), t('Kode'), t('Nama'), t('Email'), t('Item'), t('Tipe'), t('Qty'), t('Total'), t('Status'), t('Metode')]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['created_at'],
            $r['booking_code'] ?: ('#' . $r['id']),
            $r['name'], $r['email'], $r['item_title'],
            $verticalLabels[$r['btype']],
            $r['qty_label'],
            number_format((float)$r['total_price'], 2, '.', ''),
            $r['status'],
            $payMap[$r['booking_code']] ?? '-',
        ]);
    }
    fclose($out);
    exit;
}

require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><?= t('Sales Report') ?></h4>
    <a href="sales-report.php?from=<?= e($from) ?>&to=<?= e($to) ?><?= $type ? '&type=' . e($type) : '' ?><?= $status ? '&status=' . e($status) : '' ?>&export=csv" class="btn btn-sm btn-success" data-testid="export-csv">
        <i class="bi bi-download"></i> <?= t('Export CSV') ?>
    </a>
</div>

<!-- Filter -->
<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0"><?= t('Dari') ?></label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0"><?= t('Sampai') ?></label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0"><?= t('Vertikal') ?></label>
                <select name="type" class="form-select form-select-sm" data-testid="filter-type">
                    <option value=""><?= t('Semua Tipe') ?></option>
                    <?php foreach ($verticalLabels as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0"><?= t('Status') ?></label>
                <select name="status" class="form-select form-select-sm" data-testid="filter-status">
                    <option value=""><?= t('Semua') ?></option>
                    <?php foreach (['pending', 'confirmed', 'cancelled', 'refunded'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(t($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary"><?= t('Filter') ?></button>
                <a href="sales-report.php" class="btn btn-sm btn-outline-secondary"><?= t('Reset') ?></a>
            </div>
        </div>
    </div>
</form>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <?php
    $summary = [
        [t('Total Transaksi'), number_format($totalTx), 'bi-receipt', 'primary'],
        [t('Total Pendapatan'), formatRupiah($totalRevenue), 'bi-cash-stack', 'success'],
        [t('Rata-rata per Transaksi'), formatRupiah($avgTx), 'bi-calculator', 'info'],
        [t('Vertikal Teratas'), $topVertical ? $verticalLabels[$topVertical] : '-', 'bi-trophy', 'warning'],
    ];
    foreach ($summary as [$label, $val, $icon, $color]): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" data-testid="summary-<?= preg_replace('/[^a-z]/', '', strtolower($label)) ?>">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small mb-1"><?= $label ?></div>
                    <div class="fs-5 fw-bold"><?= $val ?></div>
                </div>
                <div class="rounded-circle bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                    <i class="bi <?= $icon ?> fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Revenue Breakdown Pie -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><?= t('Breakdown Pendapatan') ?></h6>
        <div style="height:260px;"><canvas id="revenuePieChart" data-testid="chart-revenue-pie"></canvas></div>
    </div>
</div>

<!-- Detail Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0"><?= t('Detail Transaksi') ?></h6>
        <span class="badge bg-secondary" data-testid="row-count"><?= $totalTx ?> <?= t('transaksi') ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 admin-table">
                <thead class="table-light">
                    <tr>
                        <th><?= t('Tanggal') ?></th>
                        <th><?= t('Kode') ?></th>
                        <th><?= t('Pelanggan') ?></th>
                        <th><?= t('Item') ?></th>
                        <th><?= t('Tipe') ?></th>
                        <th><?= t('Qty') ?></th>
                        <th><?= t('Total') ?></th>
                        <th><?= t('Status') ?></th>
                        <th><?= t('Metode') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($rows, 0, 200) as $r): ?>
                    <tr data-testid="sales-row">
                        <td><small><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small></td>
                        <td><strong class="small" style="font-size:11px;"><?= e($r['booking_code'] ?: ('#' . $r['id'])) ?></strong></td>
                        <td><small><?= e($r['name']) ?><br><span class="text-muted"><?= e($r['email']) ?></span></small></td>
                        <td><small><?= e($r['item_title']) ?></small></td>
                        <td><span class="badge bg-<?= $typeBadge[$r['btype']] ?>"><?= $verticalLabels[$r['btype']] ?></span></td>
                        <td><small><?= e($r['qty_label']) ?></small></td>
                        <td class="fw-semibold"><?= formatRupiah($r['total_price']) ?></td>
                        <td>
                            <span class="badge bg-<?= in_array($r['status'], ['confirmed'], true) ? 'success' : ($r['status'] === 'pending' ? 'warning text-dark' : ($r['status'] === 'refunded' ? 'info' : 'danger')) ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td><small><?= e($payMap[$r['booking_code']] ?? '-') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4"><?= t('Belum ada transaksi pada periode ini') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalTx > 200): ?>
    <div class="card-footer bg-white text-center text-muted small">
        <?= t('Menampilkan 200 dari') ?> <?= $totalTx ?> <?= t('transaksi') ?> — <?= t('gunakan filter atau Export CSV untuk data lengkap') ?>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    var pieLabels = <?= json_encode(array_values(array_map(fn($k) => $verticalLabels[$k], array_keys($perType))), JSON_UNESCAPED_UNICODE) ?>;
    var pieData = <?= json_encode(array_values($perType)) ?>;
    if (!pieData.length) { pieLabels = ['<?= t('Belum ada data') ?>']; pieData = [1]; }

    new Chart(document.getElementById('revenuePieChart'), {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: ['#0d6efd', '#dc3545', '#212529', '#0dcaf0', '#ffc107', '#198754', '#6c757d', '#6610f2']
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } }
        }
    });
})();
</script>

<?php require_once 'includes/admin-footer.php'; ?>

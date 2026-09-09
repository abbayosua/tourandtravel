<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/analytics.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Accounting');

// ============================================================
// Expense CRUD (PRD 3.3) + CSRF protection
// ============================================================
const EXPENSE_CATEGORIES = ['Marketing', 'Operasional', 'Gaji', 'Sewa', 'Utilitas', 'Lainnya'];

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, is_string($postedToken) ? $postedToken : '')) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
    $action = $_POST['expense_action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $category = in_array($_POST['category'] ?? '', EXPENSE_CATEGORIES, true) ? $_POST['category'] : 'Lainnya';
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate)) $expenseDate = date('Y-m-d');
        if ($description !== '' && $amount > 0) {
            if ($id > 0) {
                db()->prepare("UPDATE expenses SET category=?, description=?, amount=?, created_at=? WHERE id=?")
                    ->execute([$category, $description, $amount, $expenseDate . ' 12:00:00', $id]);
                $flash = ['success', t('Pengeluaran berhasil diperbarui')];
            } else {
                db()->prepare("INSERT INTO expenses (category, description, amount, created_at) VALUES (?,?,?,?)")
                    ->execute([$category, $description, $amount, $expenseDate . ' 12:00:00']);
                $flash = ['success', t('Pengeluaran berhasil ditambahkan')];
            }
        } else {
            $flash = ['danger', t('Deskripsi dan jumlah wajib diisi (jumlah > 0)')];
        }
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM expenses WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
        $flash = ['success', t('Pengeluaran berhasil dihapus')];
    }
    header('Location: accounting.php?from=' . urlencode($from) . '&to=' . urlencode($to) . '&msg=1');
    exit;
}

[$from, $to] = analyticsRange($_GET['from'] ?? null, $_GET['to'] ?? null);
$pnl = accountingPnL($from, $to);
$verticalLabels = ['tour' => t('Tour'), 'hotel' => t('Hotel'), 'flight' => t('Pesawat'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('Kereta'), 'esim' => 'eSIM', 'ferry' => t('Ferry')];

$grossMargin = $pnl['total_revenue'] > 0 ? round($pnl['gross_profit'] / $pnl['total_revenue'] * 100, 1) : 0.0;
$netMargin = $pnl['total_revenue'] > 0 ? round($pnl['net_profit'] / $pnl['total_revenue'] * 100, 1) : 0.0;

// Monthly comparison (6 bulan) + expense breakdown untuk chart
$monthly = accountingMonthlyComparison($to, 6);

// Daftar expense periode ini (untuk tabel CRUD)
$expenseRows = [];
try {
    $st = db()->prepare("SELECT * FROM expenses WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY ORDER BY created_at DESC, id DESC");
    $st->execute([$from, $to]);
    $expenseRows = $st->fetchAll();
} catch (Throwable $e) {}

// Export CSV P&L
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pnl-' . $from . '-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, [t('Profit & Loss'), $from . ' — ' . $to]);
    fputcsv($out, []);
    fputcsv($out, [t('REVENUE')]);
    foreach ($pnl['revenue'] as $vk => $v) fputcsv($out, [$verticalLabels[$vk], number_format($v['total'], 2, '.', '')]);
    fputcsv($out, [t('Total Pendapatan'), number_format($pnl['total_revenue'], 2, '.', '')]);
    fputcsv($out, []);
    fputcsv($out, [t('COGS')]);
    foreach ($pnl['revenue'] as $vk => $v) fputcsv($out, [$verticalLabels[$vk], number_format($v['cogs'], 2, '.', '')]);
    fputcsv($out, [t('Total HPP'), number_format($pnl['total_cogs'], 2, '.', '')]);
    fputcsv($out, []);
    fputcsv($out, [t('Laba Kotor'), number_format($pnl['gross_profit'], 2, '.', ''), $grossMargin . '%']);
    fputcsv($out, []);
    fputcsv($out, [t('EXPENSES')]);
    foreach ($pnl['expenses'] as $cat => $amt) fputcsv($out, [$cat, number_format($amt, 2, '.', '')]);
    fputcsv($out, [t('Total Pengeluaran'), number_format($pnl['total_expenses'], 2, '.', '')]);
    fputcsv($out, []);
    fputcsv($out, [t('Laba Bersih'), number_format($pnl['net_profit'], 2, '.', ''), $netMargin . '%']);
    fclose($out);
    exit;
}

require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><?= t('Accounting') ?> — <?= t('Laba Rugi') ?></h4>
    <a href="accounting.php?from=<?= e($from) ?>&to=<?= e($to) ?>&export=csv" class="btn btn-sm btn-success" data-testid="export-pnl-csv">
        <i class="bi bi-download"></i> <?= t('Export CSV') ?>
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible py-2 small" data-testid="expense-flash"><?= t('Berhasil diperbarui') ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

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
                <button class="btn btn-sm btn-primary"><?= t('Filter') ?></button>
            </div>
            <div class="col-auto small text-muted py-1">
                <?= t('Periode') ?>: <?= tglIndonesia($from) ?> — <?= tglIndonesia($to) ?>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#expenseModal" data-testid="add-expense-btn" onclick="openExpenseModal(0)">
                    <i class="bi bi-plus-lg"></i> <?= t('Tambah Pengeluaran') ?>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Summary top cards -->
<div class="row g-3 mb-4">
    <?php
    $topCards = [
        [t('Total Pendapatan'), formatRupiah($pnl['total_revenue']), 'bi-cash-stack', 'primary', 100.0],
        [t('Laba Kotor'), formatRupiah($pnl['gross_profit']), 'bi-graph-up-arrow', 'info', $grossMargin],
        [t('Total Pengeluaran'), formatRupiah($pnl['total_expenses']), 'bi-wallet2', 'warning', null],
        [t('Laba Bersih'), formatRupiah($pnl['net_profit']), 'bi-trophy', $pnl['net_profit'] >= 0 ? 'success' : 'danger', $netMargin],
    ];
    foreach ($topCards as [$label, $val, $icon, $color, $pct]): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" data-testid="pnl-<?= preg_replace('/[^a-z]/', '', strtolower($label)) ?>">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small mb-1"><?= $label ?></div>
                    <div class="fs-5 fw-bold"><?= $val ?></div>
                    <?php if ($pct !== null): ?><small class="text-muted"><?= $pct ?>% <?= t('dari pendapatan') ?></small><?php endif; ?>
                </div>
                <div class="rounded-circle bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                    <i class="bi <?= $icon ?> fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- P&L Statement -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><?= t('Profit & Loss') ?> — <?= t('Periode') ?> <?= date('d/m/y', strtotime($from)) ?>–<?= date('d/m/y', strtotime($to)) ?></h6></div>
            <div class="card-body p-4" data-testid="pnl-statement">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td colspan="2" class="fw-bold text-uppercase small text-muted border-0 pb-1"><?= t('Pendapatan') ?></td></tr>
                        <?php foreach ($pnl['revenue'] as $vk => $v): ?>
                        <tr data-testid="pnl-revenue-row">
                            <td class="ps-3"><?= $verticalLabels[$vk] ?> <small class="text-muted">(<?= $v['n'] ?>)</small></td>
                            <td class="text-end"><?= formatRupiah($v['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$pnl['revenue']): ?><tr><td colspan="2" class="ps-3 text-muted">—</td></tr><?php endif; ?>
                        <tr class="table-light fw-bold">
                            <td><?= t('Total Pendapatan') ?></td>
                            <td class="text-end"><?= formatRupiah($pnl['total_revenue']) ?></td>
                        </tr>

                        <tr><td colspan="2" class="fw-bold text-uppercase small text-muted pt-3 pb-1 border-0"><?= t('Harga Pokok Penjualan') ?></td></tr>
                        <?php foreach ($pnl['revenue'] as $vk => $v): if ($v['cogs'] <= 0 && $v['total'] <= 0) continue; ?>
                        <tr data-testid="pnl-cogs-row">
                            <td class="ps-3"><?= $verticalLabels[$vk] ?></td>
                            <td class="text-end">(<?= formatRupiah($v['cogs']) ?>)</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td><?= t('Total HPP') ?></td>
                            <td class="text-end">(<?= formatRupiah($pnl['total_cogs']) ?>)</td>
                        </tr>
                        <tr class="fw-bold border-top border-2">
                            <td><?= t('Laba Kotor') ?> <small class="text-muted"><?= $grossMargin ?>%</small></td>
                            <td class="text-end <?= $pnl['gross_profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatRupiah($pnl['gross_profit']) ?></td>
                        </tr>

                        <tr><td colspan="2" class="fw-bold text-uppercase small text-muted pt-3 pb-1 border-0"><?= t('Pengeluaran') ?></td></tr>
                        <?php foreach ($pnl['expenses'] as $cat => $amt): ?>
                        <tr data-testid="pnl-expense-row">
                            <td class="ps-3"><?= e($cat) ?></td>
                            <td class="text-end">(<?= formatRupiah($amt) ?>)</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$pnl['expenses']): ?><tr><td colspan="2" class="ps-3 text-muted">—</td></tr><?php endif; ?>
                        <tr class="table-light fw-bold">
                            <td><?= t('Total Pengeluaran') ?></td>
                            <td class="text-end">(<?= formatRupiah($pnl['total_expenses']) ?>)</td>
                        </tr>
                        <tr class="fw-bold border-top border-2 table-warning">
                            <td><?= t('Laba Bersih') ?> <small><?= $netMargin ?>%</small></td>
                            <td class="text-end fs-5 <?= $pnl['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatRupiah($pnl['net_profit']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3"><?= t('Perbandingan Bulanan') ?> (6 <?= t('bulan') ?>)</h6>
                <div style="height:240px;"><canvas id="monthlyChart" data-testid="chart-monthly"></canvas></div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3"><?= t('Breakdown Pengeluaran') ?></h6>
                <div style="height:200px;"><canvas id="expensePieChart" data-testid="chart-expense-pie"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Expense Management -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0"><?= t('Daftar Pengeluaran') ?> (<?= count($expenseRows) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 admin-table" data-testid="expense-table">
                <thead class="table-light">
                    <tr>
                        <th><?= t('Tanggal') ?></th>
                        <th><?= t('Kategori') ?></th>
                        <th><?= t('Deskripsi') ?></th>
                        <th><?= t('Jumlah') ?></th>
                        <th class="text-end"><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenseRows as $er): ?>
                    <tr data-testid="expense-row" data-id="<?= (int)$er['id'] ?>" data-category="<?= e($er['category']) ?>" data-description="<?= e($er['description']) ?>" data-amount="<?= e($er['amount']) ?>" data-date="<?= e(substr($er['created_at'], 0, 10)) ?>">
                        <td><small><?= date('d/m/Y', strtotime($er['created_at'])) ?></small></td>
                        <td><span class="badge bg-secondary"><?= e($er['category']) ?></span></td>
                        <td><small><?= e($er['description']) ?></small></td>
                        <td class="fw-semibold"><?= formatRupiah($er['amount']) ?></td>
                        <td class="text-end table-action">
                            <button class="btn btn-sm btn-outline-primary" onclick="openExpenseModal(<?= (int)$er['id'] ?>)" data-testid="edit-expense"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('<?= t('Hapus pengeluaran ini?') ?>')">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="expense_action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$er['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-testid="delete-expense"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$expenseRows): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4"><?= t('Belum ada pengeluaran pada periode ini') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Expense (add/edit) -->
<div class="modal fade" id="expenseModal" tabindex="-1" data-testid="expense-modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="expenseForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="expense_action" value="save">
        <input type="hidden" name="id" id="expenseId" value="0">
        <div class="modal-header py-2">
          <h6 class="modal-title" id="expenseModalTitle"><?= t('Tambah Pengeluaran') ?></h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold"><?= t('Kategori') ?></label>
            <select name="category" id="expenseCategory" class="form-select" required data-testid="expense-category">
              <?php foreach (EXPENSE_CATEGORIES as $cat): ?>
              <option value="<?= $cat ?>"><?= t($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold"><?= t('Deskripsi') ?></label>
            <input type="text" name="description" id="expenseDescription" class="form-control" required data-testid="expense-description">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold"><?= t('Jumlah') ?></label>
            <input type="number" name="amount" id="expenseAmount" class="form-control" min="1" step="0.01" required data-testid="expense-amount">
          </div>
          <div class="mb-1">
            <label class="form-label small fw-semibold"><?= t('Tanggal') ?></label>
            <input type="date" name="expense_date" id="expenseDate" class="form-control" value="<?= e(date('Y-m-d')) ?>">
          </div>
        </div>
        <div class="modal-footer py-1">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><?= t('Batal') ?></button>
          <button type="submit" class="btn btn-sm btn-primary" data-testid="expense-submit"><?= t('Simpan') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
function openExpenseModal(id) {
    const row = document.querySelector('tr[data-id="' + id + '"]');
    document.getElementById('expenseId').value = id;
    document.getElementById('expenseModalTitle').textContent = id > 0 ? '<?= t('Edit Pengeluaran') ?>' : '<?= t('Tambah Pengeluaran') ?>';
    document.getElementById('expenseCategory').value = row ? row.dataset.category : 'Marketing';
    document.getElementById('expenseDescription').value = row ? row.dataset.description : '';
    document.getElementById('expenseAmount').value = row ? parseFloat(row.dataset.amount) : '';
    document.getElementById('expenseDate').value = row ? row.dataset.date : new Date().toISOString().slice(0, 10);
    new bootstrap.Modal(document.getElementById('expenseModal')).show();
}
</script>
<script>
(function () {
    var mLabels = <?= json_encode(array_map(fn($m) => date('M y', strtotime($m['month'] . '-01')), $monthly)) ?>;
    var revData = <?= json_encode(array_map(fn($m) => (float)$m['revenue'], $monthly)) ?>;
    var expData = <?= json_encode(array_map(fn($m) => (float)($m['cogs'] + $m['expenses']), $monthly)) ?>;
    var profData = <?= json_encode(array_map(fn($m) => (float)$m['profit'], $monthly)) ?>;

    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: mLabels,
            datasets: [
                { label: '<?= t('Pendapatan') ?>', data: revData, backgroundColor: '#0d6efd' },
                { label: '<?= t('Biaya') ?> (COGS+Expense)', data: expData, backgroundColor: '#dc3545' },
                { label: '<?= t('Laba Bersih') ?>', data: profData, backgroundColor: '#198754' }
            ]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    var expLabels = <?= json_encode(array_keys($pnl['expenses']), JSON_UNESCAPED_UNICODE) ?>;
    var expData2 = <?= json_encode(array_values($pnl['expenses'])) ?>;
    if (!expData2.length) { expLabels = ['<?= t('Belum ada data') ?>']; expData2 = [1]; }
    new Chart(document.getElementById('expensePieChart'), {
        type: 'doughnut',
        data: { labels: expLabels, datasets: [{ data: expData2, backgroundColor: ['#0d6efd', '#dc3545', '#ffc107', '#198754', '#6c757d', '#6610f2'] }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
    });
})();
</script>

<?php require_once 'includes/admin-footer.php'; ?>

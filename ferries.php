<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Ferry';
$route = $_GET['route'] ?? '';
$routes = db()->query("SELECT DISTINCT CONCAT(route_from, ' - ', route_to) as route FROM ferries WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM ferries WHERE is_active = 1";
$params = [];
if ($route) { $sql .= " AND CONCAT(route_from, ' - ', route_to) LIKE ?"; $params[] = "%$route%"; }
$sql .= " ORDER BY price ASC";
$ferries = db()->prepare($sql);
$ferries->execute($params);
$ferries = $ferries->fetchAll();

require_once 'includes/header.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3">Ferry</h4>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <select name="route" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Rute</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= e($r) ?>" <?= $route === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <div class="row g-2">
            <?php foreach ($ferries as $f): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="fw-semibold mb-0"><?= e($f['company']) ?></h6>
                                <small class="text-muted"><?= e($f['vessel_name']) ?></small>
                            </div>
                            <span class="fw-bold text-primary"><?= formatRupiah($f['price']) ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div class="text-center">
                                <div class="fw-bold"><?= date('H:i', strtotime($f['departure_time'])) ?></div>
                                <small class="text-muted"><?= e($f['route_from']) ?></small>
                            </div>
                            <div class="text-muted small">→</div>
                            <div class="text-center">
                                <div class="fw-bold"><?= date('H:i', strtotime($f['arrival_time'])) ?></div>
                                <small class="text-muted"><?= e($f['route_to']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($ferries)): ?><div class="col-12 text-center py-5 text-muted">Tidak ada jadwal ferry.</div><?php endif; ?>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

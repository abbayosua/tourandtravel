<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Pesawat';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$class = $_GET['class'] ?? '';

$cities = db()->query("SELECT DISTINCT from_city FROM flights WHERE is_active = 1 ORDER BY from_city")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM flights WHERE is_active = 1";
$params = [];
if ($from) { $sql .= " AND from_city LIKE ?"; $params[] = "%$from%"; }
if ($to) { $sql .= " AND to_city LIKE ?"; $params[] = "%$to%"; }
if ($class) { $sql .= " AND class = ?"; $params[] = $class; }
$sql .= " ORDER BY price ASC";
$flights = db()->prepare($sql);
$flights->execute($params);
$flights = $flights->fetchAll();

require_once 'includes/header.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3">Pesawat</h4>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3"><input type="text" name="from" class="form-control form-control-sm" placeholder="Dari..." value="<?= e($from) ?>"></div>
            <div class="col-md-3"><input type="text" name="to" class="form-control form-control-sm" placeholder="Ke..." value="<?= e($to) ?>"></div>
            <div class="col-md-2">
                <select name="class" class="form-select form-select-sm">
                    <option value="">Semua Kelas</option>
                    <option value="economy" <?= $class === 'economy' ? 'selected' : '' ?>>Ekonomi</option>
                    <option value="business" <?= $class === 'business' ? 'selected' : '' ?>>Bisnis</option>
                    <option value="first" <?= $class === 'first' ? 'selected' : '' ?>>First</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Cari</button></div>
        </form>
        <div class="row g-2">
            <?php foreach ($flights as $f): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-semibold mb-0"><?= e($f['airline']) ?></h6>
                                <small class="text-muted"><?= e($f['flight_number']) ?> · <?= e($f['class']) ?></small>
                            </div>
                            <span class="badge bg-primary"><?= e($f['duration']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-center">
                                    <div class="fw-bold"><?= date('H:i', strtotime($f['departure_time'])) ?></div>
                                    <small class="text-muted"><?= e(substr($f['from_city'], 0, 20)) ?></small>
                                </div>
                                <div class="text-muted small">→</div>
                                <div class="text-center">
                                    <div class="fw-bold"><?= date('H:i', strtotime($f['arrival_time'])) ?></div>
                                    <small class="text-muted"><?= e(substr($f['to_city'], 0, 20)) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary"><?= formatRupiah($f['price']) ?></div>
                                <a href="flight-detail.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 mt-1">Pilih</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($flights)): ?><div class="col-12 text-center py-5 text-muted">Tidak ada penerbangan.</div><?php endif; ?>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

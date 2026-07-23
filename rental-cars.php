<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Rental Mobil';
$city = $_GET['city'] ?? '';
$type = $_GET['type'] ?? '';
$cities = db()->query("SELECT DISTINCT city FROM rental_cars WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
$types = db()->query("SELECT DISTINCT car_type FROM rental_cars WHERE is_active = 1 ORDER BY car_type")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM rental_cars WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city = ?"; $params[] = $city; }
if ($type) { $sql .= " AND car_type = ?"; $params[] = $type; }
$sql .= " ORDER BY price_per_day ASC";
$cars = db()->prepare($sql);
$cars->execute($params);
$cars = $cars->fetchAll();

require_once 'includes/header.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3">Rental Mobil</h4>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="city" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kota</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Tipe</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <div class="row g-3">
            <?php foreach ($cars as $car): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card tour-card-klook border-0 shadow-sm h-100">
                    <div class="position-relative overflow-hidden rounded-top" style="height: 180px;">
                        <img src="https://picsum.photos/seed/<?= urlencode($car['slug']) ?>/640/480" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($car['name']) ?>">
                        <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm"><?= e($car['car_type']) ?></span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column">
                        <h6 class="fw-semibold mb-1"><?= e($car['name']) ?></h6>
                        <div class="d-flex gap-2 small text-muted mb-2">
                            <span><i class="bi bi-geo-alt me-1"></i><?= e($car['city']) ?></span>
                            <span><i class="bi bi-people me-1"></i><?= $car['passenger_capacity'] ?> kursi</span>
                            <span><i class="bi bi-gear me-1"></i><?= ucfirst($car['transmission']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                            <span class="fw-bold text-primary"><?= formatRupiah($car['price_per_day']) ?><small class="fw-normal text-muted">/hari</small></span>
                            <a href="rental-car-detail.php?slug=<?= e($car['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">Sewa</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($cars)): ?><div class="col-12 text-center py-5 text-muted">Tidak ada mobil.</div><?php endif; ?>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

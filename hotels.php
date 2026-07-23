<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Hotel';
$city = $_GET['city'] ?? '';
$cities = db()->query("SELECT DISTINCT city FROM hotels WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM hotels WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city = ?"; $params[] = $city; }
$sql .= " ORDER BY star_rating DESC, price_per_night ASC";
$hotels = db()->prepare($sql);
$hotels->execute($params);
$hotels = $hotels->fetchAll();

require_once 'includes/header.php';
?>
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Hotel</h4>
            <small class="text-muted"><?= count($hotels) ?> hotel</small>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="hotels.php" class="btn btn-sm <?= !$city ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill">Semua Kota</a>
            <?php foreach ($cities as $c): ?>
                <a href="hotels.php?city=<?= urlencode($c) ?>" class="btn btn-sm <?= $city === $c ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill"><?= e($c) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="row g-3">
            <?php foreach ($hotels as $h): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card tour-card-klook border-0 shadow-sm h-100">
                    <div class="position-relative overflow-hidden rounded-top" style="height: 200px;">
                        <img src="https://picsum.photos/seed/<?= urlencode($h['slug']) ?>/640/480" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($h['name']) ?>">
                        <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2 shadow-sm">★ <?= $h['star_rating'] ?></span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column">
                        <h6 class="fw-semibold mb-1"><?= e($h['name']) ?></h6>
                        <small class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i><?= e($h['city']) ?></small>
                        <p class="small text-muted flex-grow-1 mb-2"><?= substr(e($h['description']), 0, 100) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="fw-bold text-primary"><?= formatRupiah($h['price_per_night']) ?><small class="fw-normal text-muted">/malam</small></span>
                            <a href="hotel-detail.php?slug=<?= e($h['slug']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">Pesan</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($hotels)): ?><div class="col-12 text-center py-5 text-muted">Belum ada hotel.</div><?php endif; ?>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

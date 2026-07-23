<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Hotel';
$city = $_GET['city'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = (int)($_GET['guests'] ?? 2);
$stars = $_GET['stars'] ?? '';
$sort = $_GET['sort'] ?? 'price';

$cities = db()->query("SELECT DISTINCT city FROM hotels WHERE is_active = 1 ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM hotels WHERE is_active = 1";
$params = [];
if ($city) { $sql .= " AND city LIKE ?"; $params[] = "%$city%"; }
if ($stars) { $sql .= " AND star_rating = ?"; $params[] = (int)$stars; }
$sql .= match($sort) {
    'price' => " ORDER BY price_per_night ASC",
    'price_desc' => " ORDER BY price_per_night DESC",
    'stars' => " ORDER BY star_rating DESC, price_per_night ASC",
    default => " ORDER BY price_per_night ASC"
};
$hotels = db()->prepare($sql);
$hotels->execute($params);
$hotels = $hotels->fetchAll();

require_once 'includes/header.php';
?>
<section class="py-4 bg-light">
    <div class="container">
        <!-- Search -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-building me-2"></i>Cari Hotel</h5>
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Kota</label>
                        <input type="text" name="city" class="form-control" placeholder="Cari kota..." value="<?= e($city) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Check-in</label>
                        <input type="date" name="checkin" class="form-control" value="<?= e($checkin ?: date('Y-m-d')) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Check-out</label>
                        <input type="date" name="checkout" class="form-control" value="<?= e($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Tamu</label>
                        <select name="guests" class="form-select">
                            <?php for ($g=1; $g<=6; $g++): ?>
                            <option value="<?= $g ?>" <?= $guests === $g ? 'selected' : '' ?>><?= $g ?> Tamu</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-semibold mb-2">Bintang Hotel</h6>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="?<?= http_build_query(array_merge($_GET, ['stars' => ''])) ?>" class="btn btn-sm <?= !$stars ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill">Semua</a>
                            <?php for ($s=5; $s>=3; $s--): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['stars' => $s])) ?>" class="btn btn-sm <?= $stars == $s ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= str_repeat('★', $s) ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <h6 class="fw-semibold mb-2">Urutkan</h6>
                        <div class="d-flex flex-column gap-1">
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'price'])) ?>" class="small text-decoration-none <?= $sort === 'price' ? 'fw-bold text-primary' : 'text-muted' ?>">💲 Harga Termurah</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'price_desc'])) ?>" class="small text-decoration-none <?= $sort === 'price_desc' ? 'fw-bold text-primary' : 'text-muted' ?>">💲 Harga Termahal</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'stars'])) ?>" class="small text-decoration-none <?= $sort === 'stars' ? 'fw-bold text-primary' : 'text-muted' ?>">⭐ Bintang Tertinggi</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="col-md-9">
                <?php if (count($hotels) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"><?= count($hotels) ?> hotel ditemukan</small>
                </div>
                <div class="row g-3">
                    <?php foreach ($hotels as $h): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card-klook border-0 shadow-sm h-100">
                            <div class="position-relative overflow-hidden rounded-top" style="height: 180px;">
                                <img src="https://picsum.photos/seed/<?= urlencode($h['slug']) ?>/640/480" class="w-100 h-100" style="object-fit: cover;" alt="">
                                <span class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark shadow-sm"><?= str_repeat('★', $h['star_rating']) ?></span>
                                <span class="position-absolute bottom-0 end-0 m-2 badge bg-white text-dark shadow-sm"><i class="bi bi-geo-alt me-1"></i><?= e($h['city']) ?></span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-semibold mb-1"><?= e($h['name']) ?></h6>
                                <p class="small text-muted flex-grow-1 mb-2"><?= substr(e($h['description']), 0, 80) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div>
                                        <span class="fw-bold text-primary fs-5"><?= formatRupiah($h['price_per_night']) ?></span>
                                        <small class="text-muted">/malam</small>
                                    </div>
                                    <a href="hotel-detail.php?slug=<?= e($h['slug']) ?>&checkin=<?= urlencode($checkin ?: date('Y-m-d')) ?>&checkout=<?= urlencode($checkout ?: date('Y-m-d', strtotime('+2 days'))) ?>&guests=<?= $guests ?>" class="btn btn-sm btn-primary rounded-pill px-3">Pesan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Tidak ada hotel ditemukan.</p>
                    <a href="hotels.php" class="btn btn-primary rounded-pill px-4">Reset</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

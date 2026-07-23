<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Pesawat';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';
$class = $_GET['class'] ?? '';
$passengers = (int)($_GET['passengers'] ?? 1);

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
<section class="py-4 bg-light" style="min-height: 80vh;">
    <div class="container">
        <!-- Search Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-airplane me-2"></i>Cari Penerbangan</h5>
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Dari</label>
                        <div class="search-wrapper">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-geo-alt text-primary"></i></span>
                                <input type="text" name="from" class="form-control city-search" placeholder="Kota asal..." value="<?= e($from) ?>" autocomplete="off" data-target="fromDropdown" id="fromInput">
                            </div>
                            <div class="search-dropdown" id="fromDropdown"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Ke</label>
                        <div class="search-wrapper">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-geo-alt text-danger"></i></span>
                                <input type="text" name="to" class="form-control city-search" placeholder="Kota tujuan..." value="<?= e($to) ?>" autocomplete="off" data-target="toDropdown" id="toInput">
                            </div>
                            <div class="search-dropdown" id="toDropdown"></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Tanggal</label>
                        <input type="date" name="date" class="form-control" value="<?= e($date ?: date('Y-m-d')) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Kelas</label>
                        <select name="class" class="form-select">
                            <option value="">Semua</option>
                            <option value="economy" <?= $class === 'economy' ? 'selected' : '' ?>>Ekonomi</option>
                            <option value="business" <?= $class === 'business' ? 'selected' : '' ?>>Bisnis</option>
                            <option value="first" <?= $class === 'first' ? 'selected' : '' ?>>First</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <?php if (count($flights) > 0): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= count($flights) ?> Penerbangan Ditemukan</h5>
                <small class="text-muted"><?= $from ?: 'Semua kota' ?> → <?= $to ?: 'Semua tujuan' ?></small>
            </div>
            <small class="text-muted">Urut: Termurah</small>
        </div>

        <div class="row g-3">
            <?php foreach ($flights as $f): 
                $dep = date('H:i', strtotime($f['departure_time']));
                $arr = date('H:i', strtotime($f['arrival_time']));
                $airlineCode = substr($f['airline'], 0, 2);
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm flight-card">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-center g-3">
                            <!-- Airline -->
                            <div class="col-md-2 d-flex align-items-center gap-2">
                                <div class="flight-logo d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width: 44px; height: 44px;">
                                    <?= $airlineCode ?>
                                </div>
                                <div>
                                    <div class="fw-semibold small"><?= e($f['airline']) ?></div>
                                    <small class="text-muted" style="font-size: 11px;"><?= e($f['flight_number']) ?></small>
                                </div>
                            </div>

                            <!-- Route -->
                            <div class="col-md-5">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="text-center" style="min-width: 70px;">
                                        <div class="fs-5 fw-bold"><?= $dep ?></div>
                                        <small class="text-muted"><?= e(explode('(', $f['from_city'])[0]) ?></small>
                                    </div>
                                    <div class="flex-grow-1 text-center position-relative">
                                        <div class="border-top border-2 border-primary position-relative" style="height: 0;">
                                            <i class="bi bi-airplane-fill text-primary position-absolute top-0 start-50 translate-middle" style="font-size: 12px;"></i>
                                        </div>
                                        <small class="text-muted d-block mt-1"><?= e($f['duration']) ?></small>
                                    </div>
                                    <div class="text-center" style="min-width: 70px;">
                                        <div class="fs-5 fw-bold"><?= $arr ?></div>
                                        <small class="text-muted"><?= e(explode('(', $f['to_city'])[0]) ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Class & Info -->
                            <div class="col-md-2 text-center">
                                <span class="badge bg-<?= $f['class'] === 'economy' ? 'success' : ($f['class'] === 'business' ? 'warning text-dark' : 'danger') ?> rounded-pill">
                                    <?= ucfirst($f['class']) ?>
                                </span>
                                <small class="d-block text-muted mt-1">Langsung</small>
                            </div>

                            <!-- Price & CTA -->
                            <div class="col-md-3 text-md-end">
                                <div class="fs-5 fw-bold text-primary"><?= formatRupiah($f['price']) ?></div>
                                <small class="text-muted">/orang</small>
                                <div class="mt-2">
                                    <a href="flight-detail.php?id=<?= $f['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold w-100 w-md-auto">Pilih</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-airplane fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Tidak ada penerbangan ditemukan.</p>
            <a href="flights.php" class="btn btn-primary rounded-pill px-4">Reset Pencarian</a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

<script>
document.querySelectorAll('.city-search').forEach(function(input) {
    var dropdownId = input.getAttribute('data-target');
    var dropdown = document.getElementById(dropdownId);
    if (!dropdown) return;

    var debounce;

    input.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < 1) {
            dropdown.classList.remove('show');
            return;
        }
        debounce = setTimeout(function() {
            fetch('city-search-ajax.php?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.length) { dropdown.classList.remove('show'); return; }
                    var html = '';
                    data.forEach(function(item) {
                        html += '<div class="search-item" onclick="selectCity(\'' + input.id + '\',\'' + dropdownId + '\',\'' + item.label.replace(/'/g, "\\'") + '\')">' +
                            '<div class="search-icon bg-light text-primary"><i class="bi bi-geo-alt"></i></div>' +
                            '<div class="fw-semibold small">' + item.label + '</div></div>';
                    });
                    dropdown.innerHTML = html;
                    dropdown.classList.add('show');
                });
        }, 200);
    });

    document.addEventListener('click', function(e) {
        if (!input.parentElement.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
});

function selectCity(inputId, dropdownId, city) {
    document.getElementById(inputId).value = city;
    document.getElementById(dropdownId).classList.remove('show');
}
</script>

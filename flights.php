<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Pesawat';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$class = $_GET['class'] ?? '';

// Join schedules with flights
$sql = "SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, 
               f.departure_time, f.arrival_time, f.duration, f.class
        FROM flight_schedules fs 
        JOIN flights f ON fs.flight_id = f.id 
        WHERE fs.is_active = 1 AND fs.departure_date = ?";
$params = [$date];

if ($from) { $sql .= " AND f.from_city LIKE ?"; $params[] = "%$from%"; }
if ($to) { $sql .= " AND f.to_city LIKE ?"; $params[] = "%$to%"; }
if ($class) { $sql .= " AND f.class = ?"; $params[] = $class; }
$sql .= " ORDER BY fs.price ASC";

$schedules = db()->prepare($sql);
$schedules->execute($params);
$schedules = $schedules->fetchAll();

// Get unique dates for quick pick
$allDates = db()->query("SELECT DISTINCT departure_date FROM flight_schedules WHERE is_active = 1 AND departure_date >= CURDATE() ORDER BY departure_date LIMIT 14")->fetchAll(PDO::FETCH_COLUMN);

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
                        <input type="date" name="date" class="form-control" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>">
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

                <!-- Quick date picker -->
                <?php if (count($allDates) > 0): ?>
                <div class="d-flex gap-1 overflow-auto mt-3 pb-1">
                    <?php foreach (array_slice($allDates, 0, 7) as $d): 
                        $dayName = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][(int)date('w', strtotime($d))];
                    ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['date' => $d])) ?>" 
                       class="btn btn-sm <?= $date === $d ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill flex-shrink-0">
                        <?= $dayName ?><br><strong><?= date('d', strtotime($d)) ?></strong>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Results -->
        <?php if (count($schedules) > 0): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= count($schedules) ?> Penerbangan</h5>
                <small class="text-muted"><?= tglIndonesia($date) ?></small>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($schedules as $s): 
                $dep = date('H:i', strtotime($s['departure_time']));
                $arr = date('H:i', strtotime($s['arrival_time']));
                $airlineCode = substr($s['airline'], 0, 2);
                $fromShort = explode('(', $s['from_city'])[0];
                $toShort = explode('(', $s['to_city'])[0];
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm flight-card">
                    <div class="card-body p-3 p-md-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-2 d-flex align-items-center gap-2">
                                <div class="flight-logo d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width: 44px; height: 44px;">
                                    <?= $airlineCode ?>
                                </div>
                                <div>
                                    <div class="fw-semibold small"><?= e($s['airline']) ?></div>
                                    <small class="text-muted" style="font-size: 11px;"><?= e($s['flight_number']) ?></small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="text-center" style="min-width: 70px;">
                                        <div class="fs-5 fw-bold"><?= $dep ?></div>
                                        <small class="text-muted"><?= e(trim($fromShort)) ?></small>
                                    </div>
                                    <div class="flex-grow-1 text-center position-relative px-2">
                                        <div class="border-top border-2 border-primary position-relative">
                                            <i class="bi bi-airplane-fill text-primary position-absolute top-0 start-50 translate-middle" style="font-size: 12px;"></i>
                                        </div>
                                        <small class="text-muted d-block mt-1"><?= e($s['duration']) ?></small>
                                    </div>
                                    <div class="text-center" style="min-width: 70px;">
                                        <div class="fs-5 fw-bold"><?= $arr ?></div>
                                        <small class="text-muted"><?= e(trim($toShort)) ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2 text-center">
                                <span class="badge bg-<?= $s['class'] === 'economy' ? 'success' : ($s['class'] === 'business' ? 'warning text-dark' : 'danger') ?> rounded-pill">
                                    <?= ucfirst($s['class']) ?>
                                </span>
                                <small class="d-block text-muted mt-1">Sisa <?= $s['available_seats'] ?> kursi</small>
                            </div>

                            <div class="col-md-2 text-center">
                                <div class="fs-5 fw-bold text-primary"><?= formatRupiah($s['price']) ?></div>
                                <small class="text-muted">/orang</small>
                            </div>

                            <div class="col-md-2 text-md-end">
                                <?php if ($s['available_seats'] > 0): ?>
                                <a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-primary rounded-pill px-4 fw-semibold w-100 w-md-auto">Pilih</a>
                                <?php else: ?>
                                <button class="btn btn-secondary rounded-pill px-4 w-100 w-md-auto" disabled>Penuh</button>
                                <?php endif; ?>
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
            <p class="mt-2 text-muted">Tidak ada penerbangan untuk tanggal ini.</p>
            <a href="flights.php" class="btn btn-primary rounded-pill px-4">Reset</a>
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
        if (q.length < 1) { dropdown.classList.remove('show'); return; }
        debounce = setTimeout(function() {
            fetch('city-search-ajax.php?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.length) { dropdown.classList.remove('show'); return; }
                    var html = '';
                    data.forEach(function(item) {
                        html += '<div class="search-item" onclick="document.getElementById(\'' + input.id + '\').value=\'' + item.label.replace(/'/g,"\\'") + '\';document.getElementById(\'' + dropdownId + '\').classList.remove(\'show\')">' +
                            '<div class="search-icon bg-light text-primary"><i class="bi bi-geo-alt"></i></div>' +
                            '<div class="fw-semibold small">' + item.label + '</div></div>';
                    });
                    dropdown.innerHTML = html;
                    dropdown.classList.add('show');
                });
        }, 200);
    });
    document.addEventListener('click', function(e) {
        if (!input.parentElement.contains(e.target)) dropdown.classList.remove('show');
    });
});
</script>

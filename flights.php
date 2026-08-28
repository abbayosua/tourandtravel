<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/duffel.php';

$pageTitle = 'Pesawat';
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$class = $_GET['class'] ?? '';
$passengers = max(1, min(9, (int)($_GET['passengers'] ?? 1)));
$doSearch = isset($_GET['search']);

// Keep past dates when searching so Duffel validation shows
if (!$doSearch && (!strtotime($date) || $date < date('Y-m-d'))) $date = date('Y-m-d', strtotime('+3 days'));

$duffelOffers = [];
$duffelError = null;
$localSchedules = [];

if ($doSearch && $from && $to) {
    $cabinMap = ['economy'=>'economy','business'=>'business','first'=>'first','premium_economy'=>'premium_economy'];
    $cabin = $cabinMap[$class] ?? 'economy';
    if ($class === '') $cabin = 'economy';
    $result = duffelSearchOffers($from, $to, $date, $cabin, $passengers);
    if (isset($result['error'])) {
        $duffelError = $result['error'];
    } else {
        $all = $result['offers'] ?? [];
        if ($class) {
            $filtered = array_values(array_filter($all, function($o) use ($class) {
                $seg = $o['slices'][0]['segments'][0] ?? null;
                if (!$seg) return true;
                $cc = strtolower($seg['passengers'][0]['cabin_class'] ?? '');
                return $cc === $class;
            }));
            $duffelOffers = $filtered;
            if (empty($duffelOffers)) $duffelOffers = $all;
        } else {
            $duffelOffers = $all;
        }
    }
    if ($duffelError || empty($duffelOffers)) {
        $sql = "SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date=?";
        $params=[$date];
        if ($from) {$sql.=" AND f.from_city LIKE ?";$params[]="%$from%";}
        if ($to) {$sql.=" AND f.to_city LIKE ?";$params[]="%$to%";}
        if ($class) {$sql.=" AND f.class=?";$params[]=$class;}
        $sql.=" ORDER BY fs.price ASC LIMIT 20";
        $st=db()->prepare($sql);
        $st->execute($params);
        $localSchedules=$st->fetchAll();
    }
} elseif ($doSearch && (!$from || !$to)) {
    $duffelError = 'Silakan isi kota asal dan tujuan.';
} else {
    $st=db()->prepare("SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date>=CURDATE() ORDER BY fs.departure_date ASC, fs.price ASC LIMIT 10");
    $st->execute([]);
    $localSchedules=$st->fetchAll();
}
$allDates = db()->query("SELECT DISTINCT departure_date FROM flight_schedules WHERE is_active=1 AND departure_date>=CURDATE() ORDER BY departure_date LIMIT 14")->fetchAll(PDO::FETCH_COLUMN);
require_once 'includes/header.php';
?>
<section class="py-4 bg-light" style="min-height: 80vh;">
    <div class="container">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-airplane me-2"></i>Cari Penerbangan <small class="text-muted fw-normal" style="font-size:12px">(Live via Duffel - Test Mode)</small></h5>
                <?php if ($duffelError): ?>
                    <div class="alert alert-warning py-2 small"><?= e($duffelError) ?></div>
                <?php endif; ?>
                <form method="GET" class="row g-2 align-items-end" id="flightSearchForm">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Dari</label>
                        <div class="search-wrapper">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-geo-alt text-primary"></i></span>
                                <input type="text" name="from" class="form-control city-search" placeholder="Kota asal (CGK)..." value="<?= e($from) ?>" autocomplete="off" data-target="fromDropdown" id="fromInput">
                            </div>
                            <div class="search-dropdown" id="fromDropdown"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Ke</label>
                        <div class="search-wrapper">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-geo-alt text-danger"></i></span>
                                <input type="text" name="to" class="form-control city-search" placeholder="Kota tujuan (DPS)..." value="<?= e($to) ?>" autocomplete="off" data-target="toDropdown" id="toInput">
                            </div>
                            <div class="search-dropdown" id="toDropdown"></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-muted">Tanggal</label>
                        <input type="date" name="date" class="form-control" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                    </div>
                    <div class="col-3 col-md-1">
                        <label class="form-label small fw-semibold text-muted">Penumpang</label>
                        <select name="passengers" class="form-select">
                            <?php for($p=1;$p<=9;$p++): ?><option value="<?= $p ?>" <?= $passengers===$p?'selected':'' ?>><?= $p ?></option><?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-3 col-md-1">
                        <label class="form-label small fw-semibold text-muted">Kelas</label>
                        <select name="class" class="form-select">
                            <option value="">Semua</option>
                            <option value="economy" <?= $class === 'economy' ? 'selected' : '' ?>>Ekonomi</option>
                            <option value="business" <?= $class === 'business' ? 'selected' : '' ?>>Bisnis</option>
                            <option value="first" <?= $class === 'first' ? 'selected' : '' ?>>First</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit" name="search" value="1"><i class="bi bi-search me-1"></i>Cari</button>
                    </div>
                </form>
                <?php if (count($allDates) > 0): ?>
                <div class="d-flex gap-1 overflow-auto mt-3 pb-1">
                    <?php foreach (array_slice($allDates, 0, 7) as $d):
                        $dayName = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][(int)date('w', strtotime($d))];
                    ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['date' => $d, 'search'=>1])) ?>" class="btn btn-sm <?= $date === $d ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill flex-shrink-0"><?= $dayName ?><br><strong><?= date('d', strtotime($d)) ?></strong></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($doSearch): ?>
            <?php if (!empty($duffelOffers)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div><h5 class="fw-bold mb-0"><?= count($duffelOffers) ?> Penerbangan <span class="badge bg-success ms-1" style="font-size:11px">Live Duffel</span></h5><small class="text-muted"><?= tglIndonesia($date) ?> · <?= e($from) ?> → <?= e($to) ?> · <?= $passengers ?> pax</small></div>
            </div>
            <div class="row g-3" id="duffelResults">
                <?php foreach ($duffelOffers as $o):
                    $slice = $o['slices'][0]; $seg = $slice['segments'][0];
                    $dep = date('H:i', strtotime($seg['departing_at'])); $arr = date('H:i', strtotime($seg['arriving_at']));
                    $carrier = $seg['marketing_carrier'] ?? $seg['operating_carrier'];
                    $duration = duffelFormatDuration($slice['duration'] ?? $seg['duration']);
                    $cc = $seg['passengers'][0]['cabin_class'] ?? 'economy';
                    $baggages = $seg['passengers'][0]['baggages'] ?? [];
                    $offerId = $o['id'];
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm flight-card">
                        <div class="card-body p-3 p-md-4">
                            <div class="row align-items-center g-3">
                                <div class="col-md-2 d-flex align-items-center gap-2">
                                    <?php if (!empty($carrier['logo_symbol_url'])): ?><img src="<?= e($carrier['logo_symbol_url']) ?>" alt="" style="width:44px;height:44px;object-fit:contain" class="bg-white rounded-2 border"><?php else: ?><div class="flight-logo d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width:44px;height:44px;"><?= e(substr($carrier['name']??'ZZ',0,2)) ?></div><?php endif; ?>
                                    <div><div class="fw-semibold small"><?= e($carrier['name'] ?? 'Duffel Airways') ?></div><small class="text-muted" style="font-size:11px;"><?= e($carrier['iata_code'] ?? 'ZZ') ?> <?= e($seg['marketing_carrier_flight_number'] ?? '') ?></small></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e($seg['origin']['iata_code'] ?? '') ?></small></div>
                                        <div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-primary position-relative"><i class="bi bi-airplane-fill text-primary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-muted d-block mt-1"><?= e($duration) ?></small><?php if (count($slice['segments'])>1): ?><small class="text-warning" style="font-size:11px"><?= count($slice['segments'])-1 ?> transit</small><?php else: ?><small class="text-success" style="font-size:11px">Langsung</small><?php endif; ?></div>
                                        <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e($seg['destination']['iata_code'] ?? '') ?></small></div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center"><span class="badge bg-<?= $cc==='economy'?'success':($cc==='business'?'warning text-dark':'danger') ?> rounded-pill"><?= ucfirst($cc) ?></span><small class="d-block text-muted mt-1" style="font-size:11px"><?php foreach($baggages as $bg) echo e($bg['quantity'].' '.($bg['type']==='checked'?'bagasi':'kabin')).' '; ?></small></div>
                                <div class="col-md-2 text-center"><div class="fs-6 fw-bold text-primary"><?= duffelFormatPrice($o['total_amount'], $o['total_currency']) ?></div><small class="text-muted">/orang</small></div>
                                <div class="col-md-2 text-md-end"><a href="flight-detail.php?offer_id=<?= e($offerId) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold w-100">Pilih</a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif (!empty($localSchedules)): ?>
            <div class="alert alert-info py-2 small">Hasil live tidak tersedia, menampilkan jadwal lokal.</div>
            <div class="row g-3">
                <?php foreach ($localSchedules as $s): $dep = date('H:i', strtotime($s['departure_time'])); $arr = date('H:i', strtotime($s['arrival_time'])); $airlineCode = substr($s['airline'], 0, 2); $fromShort = explode('(', $s['from_city'])[0]; $toShort = explode('(', $s['to_city'])[0]; ?>
                <div class="col-12"><div class="card border-0 shadow-sm flight-card"><div class="card-body p-3 p-md-4"><div class="row align-items-center g-3">
                    <div class="col-md-2 d-flex align-items-center gap-2"><div class="flight-logo d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary fw-bold rounded-2" style="width:44px;height:44px;"><?= $airlineCode ?></div><div><div class="fw-semibold small"><?= e($s['airline']) ?></div><small class="text-muted" style="font-size:11px;"><?= e($s['flight_number']) ?></small></div></div>
                    <div class="col-md-4"><div class="d-flex align-items-center justify-content-center gap-2"><div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e(trim($fromShort)) ?></small></div><div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-secondary position-relative"><i class="bi bi-airplane-fill text-secondary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-muted d-block mt-1"><?= e($s['duration']) ?></small></div><div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e(trim($toShort)) ?></small></div></div></div>
                    <div class="col-md-2 text-center"><span class="badge bg-secondary rounded-pill"><?= ucfirst($s['class']) ?></span><small class="d-block text-muted mt-1">Sisa <?= $s['available_seats'] ?> kursi</small></div>
                    <div class="col-md-2 text-center"><div class="fs-5 fw-bold text-primary"><?= formatRupiah($s['price']) ?></div><small class="text-muted">/orang</small></div>
                    <div class="col-md-2 text-md-end"><a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-outline-primary rounded-pill px-4 w-100">Pilih (Lokal)</a></div>
                </div></div></div></div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5" id="noResults"><i class="bi bi-airplane fs-1 text-muted"></i><p class="mt-2 text-muted">Tidak ada penerbangan untuk rute/tanggal tersebut.</p><p class="small text-muted">Coba: CGK → DPS, SIN → CGK, atau ubah tanggal. Test mode hanya mendukung rute Duffel Airways.</p><a href="flights.php" class="btn btn-primary rounded-pill px-4">Reset</a></div>
            <?php endif; ?>
        <?php else: ?>
            <?php if (count($localSchedules) > 0): ?><p class="small text-muted mb-2">Jadwal lokal (contoh). Gunakan pencarian di atas untuk hasil live Duffel.</p><div class="row g-3"><?php foreach ($localSchedules as $s): $dep = date('H:i', strtotime($s['departure_time'])); $arr = date('H:i', strtotime($s['arrival_time'])); $airlineCode = substr($s['airline'], 0, 2); ?>
                <div class="col-12"><div class="card border-0 shadow-sm flight-card"><div class="card-body p-3 d-flex justify-content-between align-items-center"><div class="d-flex align-items-center gap-2"><div class="flight-logo bg-light border rounded-2 d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:12px"><?= $airlineCode ?></div><div><div class="fw-semibold small"><?= e($s['airline']) ?> <?= e($s['flight_number']) ?></div><small class="text-muted"><?= e($s['from_city']) ?> → <?= e($s['to_city']) ?> · <?= e($s['duration']) ?></small></div></div><div class="text-end"><div class="fw-bold text-primary small"><?= formatRupiah($s['price']) ?></div><a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill mt-1">Lihat</a></div></div></div></div>
                <?php endforeach; ?></div><?php endif; ?>
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
                        html += '<div class="search-item" data-label="' + item.label.replace(/"/g,'&quot;') + '"><div class="search-icon bg-light text-primary"><i class="bi bi-geo-alt"></i></div><div class="fw-semibold small">' + item.label + '</div></div>';
                    });
                    dropdown.innerHTML = html;
                    dropdown.classList.add('show');
                    dropdown.querySelectorAll('.search-item').forEach(function(el){
                        el.addEventListener('click', function(){
                            document.getElementById(input.id).value = this.getAttribute('data-label');
                            dropdown.classList.remove('show');
                        });
                    });
                });
        }, 200);
    });
    document.addEventListener('click', function(e) {
        if (!input.closest('.search-wrapper').contains(e.target)) dropdown.classList.remove('show');
    });
});
</script>

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/duffel.php';
require_once 'includes/flightlist.php';

$pageTitle = t('Pesawat');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$class = $_GET['class'] ?? '';
$passengers = max(1, min(9, (int)($_GET['passengers'] ?? 1)));
$tripType = ($_GET['trip_type'] ?? 'oneway') === 'roundtrip' ? 'roundtrip' : 'oneway';
$returnDate = $_GET['return_date'] ?? '';
$doSearch = isset($_GET['search']);

// Traveloka-style filters
$airlineFilter = $_GET['airline'] ?? [];
$airlineFilter = is_array($airlineFilter) ? array_values(array_filter(array_map('trim', $airlineFilter))) : (trim((string)$airlineFilter) !== '' ? [trim((string)$airlineFilter)] : []);
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$depFilter = trim($_GET['dep'] ?? '');
$stopsFilter = trim($_GET['stops'] ?? '');
$sortRaw = trim((string)($_GET['sort'] ?? ''));
$sort = in_array($sortRaw, ['price', 'duration', 'rating']) ? $sortRaw : 'price';

// Keep past dates when searching so Duffel validation shows
if (!$doSearch && (!strtotime($date) || $date < date('Y-m-d'))) $date = date('Y-m-d', strtotime('+3 days'));

$duffelOffers = [];
$duffelError = null;
$localSchedules = [];

if ($doSearch && $from && $to) {
    // Primary: FlightList (gratis, real airlines)
    $flightlistResult = flightlistSearchOffers($from, $to, $date, $class ?: 'economy', $passengers);
    if (isset($flightlistResult['offers']) && count($flightlistResult['offers']) > 0) {
        $duffelOffers = $flightlistResult['offers'];
        $flightlistCurrency = $flightlistResult['currency'] ?? 'USD';
        // Mark as FlightList source via session flag
        $offerSource = 'flightlist';
    } elseif (!empty($flightlistResult['unreachable'])) {
        // FlightList unreachable -> fallback Duffel
        $cabinMap = ['economy'=>'economy','business'=>'business','first'=>'first','premium_economy'=>'premium_economy'];
        $cabin = $cabinMap[$class] ?? 'economy';
        if ($class === '') $cabin = 'economy';
        $result = duffelSearchOffers($from, $to, $date, $cabin, $passengers);
        if (isset($result['error'])) {
            $duffelError = $result['error'] . ' (FlightList juga tidak terjangkau)';
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
            $offerSource = 'duffel';
        }
        if (!empty($result['error']) || empty($duffelOffers)) {
            // Final fallback: DB seed
            $sql = "SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date=?";
            $params=[$date];
            if ($from) {$sql.=" AND f.from_city LIKE ?";$params[]="%$from%";}
            if ($to) {$sql.=" AND f.to_city LIKE ?";$params[]="%$to%";}
            if ($class) {$sql.=" AND f.class=?";$params[]=$class;}
            $sql.=" ORDER BY fs.price ASC LIMIT 20";
            $st=db()->prepare($sql);
            $st->execute($params);
            $localSchedules=$st->fetchAll();
            if (empty($localSchedules) && empty($duffelOffers)) {
                $duffelError = $flightlistResult['error'] ?? ($result['error'] ?? 'Tidak ada penerbangan untuk rute/tanggal ini.');
            }
        }
    } else {
        // FlightList reachable but 0 results -> show FlightList 0 (no fallback to avoid confusing mix), with DB fallback if desired
        $offerSource = 'flightlist';
        if (empty($flightlistResult['offers'])) {
            // Fallback to DB so demo not empty
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
    }
} elseif ($doSearch && (!$from || !$to)) {
    $duffelError = 'Silakan isi kota asal dan tujuan.';
} else {
    $st=db()->prepare("SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date>=CURDATE() ORDER BY fs.departure_date ASC, fs.price ASC LIMIT 10");
    $st->execute([]);
    $localSchedules=$st->fetchAll();
}
$allDates = db()->query("SELECT DISTINCT departure_date FROM flight_schedules WHERE is_active=1 AND departure_date>=CURDATE() ORDER BY departure_date LIMIT 14")->fetchAll(PDO::FETCH_COLUMN);
$allAirlines = db()->query("SELECT DISTINCT airline FROM flights WHERE is_active=1 AND airline IS NOT NULL AND airline != '' ORDER BY airline")->fetchAll(PDO::FETCH_COLUMN);
// Filter live/local schedules by airline/min/max price/departure time/stops
if (!empty($duffelOffers) && (!empty($airlineFilter) || $minPrice !== '' || $maxPrice !== '' || $depFilter !== '' || $stopsFilter !== '')) {
    $duffelOffers = array_values(array_filter($duffelOffers, function ($o) use ($airlineFilter, $minPrice, $maxPrice, $depFilter, $stopsFilter) {
        $isFL = isset($o['route']) && isset($o['flyFrom']);
        $airline = $isFL ? ($o['airlines'][0] ?? '') : (($o['slices'][0]['segments'][0]['marketing_carrier']['name'] ?? '') ?: ($o['slices'][0]['segments'][0]['marketing_carrier']['iata_code'] ?? ''));
        if (!empty($airlineFilter)) {
            $matched = false;
            foreach ($airlineFilter as $af) {
                if (stripos($airline, $af) !== false) { $matched = true; break; }
            }
            if (!$matched) return false;
        }
        $price = (float)($isFL ? ($o['price'] ?? 0) : ($o['total_amount'] ?? 0));
        if ($minPrice !== '' && $price < (float)$minPrice) return false;
        if ($maxPrice !== '' && $price > (float)$maxPrice) return false;
        if ($depFilter !== '') {
            $depStr = $isFL ? ($o['local_departure'] ?? '') : ($o['slices'][0]['segments'][0]['departing_at'] ?? '');
            $hour = (int)date('G', strtotime($depStr));
            $inRange = match ($depFilter) { 'morning' => $hour >= 5 && $hour < 12, 'afternoon' => $hour >= 12 && $hour < 17, 'evening' => $hour >= 17 && $hour < 22, 'night' => $hour >= 22 || $hour < 5, default => true };
            if (!$inRange) return false;
        }
        if ($stopsFilter !== '') {
            $stops = $isFL ? (count($o['route'] ?? []) > 1 ? count($o['route']) - 1 : 0) : (count($o['slices'][0]['segments'] ?? []) > 1 ? count($o['slices'][0]['segments']) - 1 : 0);
            if ($stopsFilter === 'direct' && $stops > 0) return false;
            if ($stopsFilter === 'transit' && $stops === 0) return false;
        }
        return true;
    }));
}
if (!empty($localSchedules) && (!empty($airlineFilter) || $minPrice !== '' || $maxPrice !== '')) {
    $localSchedules = array_values(array_filter($localSchedules, function ($s) use ($airlineFilter, $minPrice, $maxPrice) {
        if (!empty($airlineFilter)) {
            $matched = false;
            foreach ($airlineFilter as $af) {
                if (stripos($s['airline'] ?? '', $af) !== false) { $matched = true; break; }
            }
            if (!$matched) return false;
        }
        $price = (float)($s['price'] ?? 0);
        if ($minPrice !== '' && $price < (float)$minPrice) return false;
        if ($maxPrice !== '' && $price > (float)$maxPrice) return false;
        return true;
    }));
}

// Sort results (Traveloka: Termurah / Tercepat / Terpopuler)
function flightDurationMinutes($dur) {
    if (is_numeric($dur)) return (int)$dur;
    $s = (string)$dur; $m = 0;
    if (preg_match('/PT(\d+)H(\d*)M/', $s, $iso)) return (int)$iso[1] * 60 + (int)($iso[2] ?? 0);
    if (preg_match('/(\d+)\s*h/i', $s, $mh)) $m += (int)$mh[1] * 60;
    if (preg_match('/(\d+)\s*m/i', $s, $mm)) $m += (int)$mm[1];
    return $m;
}
$sortPriceOf = function ($o) { return isset($o['route']) && isset($o['flyFrom']) ? (float)($o['price'] ?? 0) : (float)($o['total_amount'] ?? 0); };
$sortDurationOf = function ($o) {
    if (isset($o['route']) && isset($o['flyFrom'])) return flightDurationMinutes($o['duration']['departure'] ?? $o['duration']['total'] ?? 0);
    return flightDurationMinutes($o['slices'][0]['duration'] ?? ($o['slices'][0]['segments'][0]['duration'] ?? 0));
};
if ($sort === 'price' || $sort === 'rating') {
    usort($duffelOffers, function ($a, $b) use ($sortPriceOf) { return $sortPriceOf($a) <=> $sortPriceOf($b); });
    usort($localSchedules, function ($a, $b) { return (float)($a['price'] ?? 0) <=> (float)($b['price'] ?? 0); });
} elseif ($sort === 'duration') {
    usort($duffelOffers, function ($a, $b) use ($sortDurationOf) { return $sortDurationOf($a) <=> $sortDurationOf($b); });
}
require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4 bg-light" style="min-height: 80vh;">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('Pesawat'), 'url' => null]]); ?>
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-3 p-md-4">
                <?php if ($duffelError): ?>
                    <div class="alert alert-warning py-2 small"><?= e($duffelError) ?></div>
                <?php endif; ?>
                <!-- Transport tabs ala Traveloka -->
                <div class="d-flex gap-3 gap-md-4 mb-3 pb-2 border-bottom overflow-auto">
                    <a href="flights.php" class="traveloka-tab active"><i class="bi bi-airplane"></i><?= t('Pesawat') ?></a>
                    <a href="trains.php" class="traveloka-tab"><i class="bi bi-train-front"></i><?= t('Kereta') ?></a>
                    <a href="ferries.php" class="traveloka-tab"><i class="bi bi-water"></i><?= t('Ferry') ?></a>
                    <a href="rental-cars.php" class="traveloka-tab"><i class="bi bi-car-front"></i><?= t('Rental') ?></a>
                </div>
                <form method="GET" id="flightSearchForm">
                    <div class="d-flex gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="trip_type" value="oneway" id="tripOneway" <?= $tripType === 'oneway' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold small" for="tripOneway"><i class="bi bi-arrow-right me-1"></i><?= t('One Way') ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="trip_type" value="roundtrip" id="tripRoundtrip" <?= $tripType === 'roundtrip' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold small" for="tripRoundtrip"><i class="bi bi-arrow-left-right me-1"></i><?= t('Round Trip') ?></label>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3">
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Dari') ?></div>
                            <div class="search-wrapper">
                                <input type="text" name="from" class="form-control city-search" placeholder="Kota asal (CGK)..." value="<?= e($from) ?>" autocomplete="off" data-target="fromDropdown" id="fromInput">
                                <div class="search-dropdown" id="fromDropdown"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Ke') ?></div>
                            <div class="search-wrapper">
                                <input type="text" name="to" class="form-control city-search" placeholder="Kota tujuan (DPS)..." value="<?= e($to) ?>" autocomplete="off" data-target="toDropdown" id="toInput">
                                <div class="search-dropdown" id="toDropdown"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md return-date-col" style="<?= $tripType === 'roundtrip' ? '' : 'display:none' ?>">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Tanggal Pulang') ?></div>
                            <input type="date" name="return_date" class="form-control" value="<?= e($returnDate) ?>" min="<?= $date ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= $tripType === 'roundtrip' ? t('Pergi') : t('Tanggal') ?></div>
                            <input type="date" name="date" class="form-control" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Penumpang') ?></div>
                            <select name="passengers" class="form-select">
                                <?php for($p=1;$p<=9;$p++): ?><option value="<?= $p ?>" <?= $passengers===$p?'selected':'' ?>><?= $p ?></option><?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="traveloka-search-field">
                            <div class="form-label"><?= t('Kelas') ?></div>
                            <select name="class" class="form-select">
                                <option value=""><?= t('Semua') ?></option>
                                <option value="economy" <?= $class === 'economy' ? 'selected' : '' ?>><?= t('Ekonomi') ?></option>
                                <option value="business" <?= $class === 'business' ? 'selected' : '' ?>><?= t('Bisnis') ?></option>
                                <option value="first" <?= $class === 'first' ? 'selected' : '' ?>><?= t('First') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-auto d-grid">
                        <button class="btn btn-primary traveloka-search-btn px-4" type="submit" name="search" value="1"><i class="bi bi-search me-1"></i><?= t('Cari') ?></button>
                    </div>
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

        <div class="row">
            <!-- Sidebar Filter Traveloka -->
            <div class="col-lg-3 mb-3">
                <div class="card border-0 shadow-sm klook-filter-sidebar sticky-lg-top" style="top: 80px;">
                    <div class="card-body p-3">
                        <button class="btn btn-outline-primary btn-sm w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#flightFilterCollapse">
                            <i class="bi bi-funnel me-1"></i><?= t('Filter') ?>
                        </button>
                        <div class="collapse d-lg-block" id="flightFilterCollapse">
                            <form method="GET" id="flightFilterForm">
                                <?php foreach (['from','to','date','return_date','trip_type','passengers','class','search'] as $hf): if (!isset($_GET[$hf])) continue; ?>
                                <input type="hidden" name="<?= $hf ?>" value="<?= e(is_array($_GET[$hf]) ? implode(',', $_GET[$hf]) : $_GET[$hf]) ?>">
                                <?php endforeach; ?>

                                <?php if (count($allAirlines) > 0): ?>
                                <h6 class="fw-semibold mb-2"><?= t('Maskapai') ?></h6>
                                <div class="mb-3">
                                    <?php foreach (array_slice($allAirlines, 0, 8) as $al): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="airline[]" value="<?= e($al) ?>" id="al_<?= e(buatSlug($al)) ?>" <?= in_array($al, $airlineFilter) ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="al_<?= e(buatSlug($al)) ?>"><?= e($al) ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <h6 class="fw-semibold mb-2"><?= t('Jam Berangkat') ?></h6>
                                <div class="mb-3">
                                    <?php foreach (['morning' => t('Pagi (05-12)'), 'afternoon' => t('Siang (12-17)'), 'evening' => t('Sore (17-22)'), 'night' => t('Malam (22-05)')] as $dk => $dl): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="dep" value="<?= $dk ?>" id="dep_<?= $dk ?>" <?= $depFilter === $dk ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="dep_<?= $dk ?>"><?= $dl ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="dep" value="" id="dep_all" <?= $depFilter === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="dep_all"><?= t('Semua') ?></label>
                                    </div>
                                </div>

                                <h6 class="fw-semibold mb-2"><?= t('Transit') ?></h6>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="stops" value="direct" id="stops_direct" <?= $stopsFilter === 'direct' ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="stops_direct"><?= t('Langsung') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="stops" value="transit" id="stops_transit" <?= $stopsFilter === 'transit' ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="stops_transit"><?= t('Transit') ?></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="stops" value="" id="stops_all" <?= $stopsFilter === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label small" for="stops_all"><?= t('Semua') ?></label>
                                    </div>
                                </div>

                                <h6 class="fw-semibold mb-2"><?= t('Harga') ?></h6>
                                <div class="d-flex gap-2 mb-3">
                                    <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= e($minPrice) ?>" min="0">
                                    <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= e($maxPrice) ?>" min="0">
                                </div>
                                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i><?= t('Terapkan') ?></button>
                                <a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&date=<?= urlencode($date) ?>&class=<?= urlencode($class) ?>&passengers=<?= $passengers ?>&trip_type=<?= $tripType ?>&search=1" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?= t('Reset') ?></a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
        <?php if ($doSearch): ?>
            <?php if (!empty($duffelOffers)): ?>
            <?php
                $badge = 'Live Duffel'; $badgeClass='bg-success';
                if (($offerSource ?? '') === 'flightlist') { $badge='FlightList (Real)'; $badgeClass='bg-primary'; }
                elseif (($offerSource ?? '') === 'duffel') { $badge='Live Duffel'; $badgeClass='bg-success'; }
            ?>
            <!-- Sort bar ala Traveloka -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div><h5 class="fw-bold mb-0"><?= count($duffelOffers) ?> Penerbangan <span class="badge <?= $badgeClass ?> ms-1" style="font-size:11px"><?= $badge ?></span></h5><small class="text-muted"><?= tglIndonesia($date) ?> · <?= e($from) ?> → <?= e($to) ?> · <?= $passengers ?> pax</small></div>
                <div class="d-flex gap-1">
                    <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price']))) ?>" class="btn btn-sm <?= $sort === 'price' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Termurah') ?></a>
                    <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'duration']))) ?>" class="btn btn-sm <?= $sort === 'duration' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Tercepat') ?></a>
                    <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'rating']))) ?>" class="btn btn-sm <?= $sort === 'rating' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Terpopuler') ?></a>
                </div>
            </div>
            </div>
            <div class="row g-3" id="duffelResults">
                <?php foreach ($duffelOffers as $o):
                    $isFlightList = isset($o['route']) && isset($o['flyFrom']);
                    if ($isFlightList) {
                        $route0 = $o['route'][0] ?? $o;
                        $airlineCode = $o['airlines'][0] ?? ($route0['airline'] ?? 'ZZ');
                        $carrier = ['name'=>$airlineCode, 'iata_code'=>$airlineCode, 'logo_symbol_url'=>null];
                        $dep = date('H:i', strtotime($o['local_departure'] ?? $route0['local_departure'] ?? ''));
                        $arr = date('H:i', strtotime($o['local_arrival'] ?? $route0['local_arrival'] ?? ''));
                        $duration = flightlistFormatDuration($o['duration']['departure'] ?? $o['duration']['total'] ?? 0);
                        $stops = count($o['route']) > 1 ? count($o['route'])-1 : 0;
                        $cc = 'economy';
                        $offerId = $o['id'];
                        $fromCode = $o['flyFrom'] ?? $route0['flyFrom'] ?? '';
                        $toCode = $o['flyTo'] ?? $route0['flyTo'] ?? '';
                        $isFL = true;
                    } else {
                        $slice = $o['slices'][0]; $seg = $slice['segments'][0];
                        $dep = date('H:i', strtotime($seg['departing_at'])); $arr = date('H:i', strtotime($seg['arriving_at']));
                        $carrier = $seg['marketing_carrier'] ?? $seg['operating_carrier'];
                        $duration = duffelFormatDuration($slice['duration'] ?? $seg['duration']);
                        $cc = $seg['passengers'][0]['cabin_class'] ?? 'economy';
                        $offerId = $o['id'];
                        $isFL = false;
                        $fromCode = $seg['origin']['iata_code'] ?? '';
                        $toCode = $seg['destination']['iata_code'] ?? '';
                        $stops = count($slice['segments']) > 1 ? count($slice['segments'])-1 : 0;
                    }
                    $baggages = $isFL ? [] : ($seg['passengers'][0]['baggages'] ?? []);
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
                                        <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e($isFL ? $fromCode : ($seg['origin']['iata_code'] ?? '')) ?></small></div>
                                        <div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-primary position-relative"><i class="bi bi-airplane-fill text-primary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-muted d-block mt-1"><?= e($duration) ?></small><?php if ($stops>0): ?><small class="text-warning" style="font-size:11px"><?= $stops ?> <?= t('transit') ?></small><?php else: ?><small class="text-success" style="font-size:11px"><?= t('Langsung') ?></small><?php endif; ?></div>
                                        <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e($isFL ? $toCode : ($seg['destination']['iata_code'] ?? '')) ?></small></div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center"><span class="badge bg-<?= $cc==='economy'?'success':($cc==='business'?'warning text-dark':'danger') ?> rounded-pill"><?= ucfirst($cc) ?></span><small class="d-block text-muted mt-1" style="font-size:11px"><?php
$baggageInfo = '';
if ($isFL) {
    $baggageInfo = t('Bagasi') . ' ' . ($o['baggage'] ?? '-');
} else {
    $baggageText = '';
    foreach($baggages as $bg) $baggageText .= $bg['quantity'] . ' ' . ($bg['type']==='checked'?t('bagasi'):t('kabin')) . ' ';
    $baggageInfo = $baggageText ?: ($s['baggage_allowance'] ?? '');
    if (!empty($o['refundable'])) echo '<span class="badge bg-success-subtle text-success border border-success-subtle d-block mt-1" style="font-size:10px;"><i class="bi bi-arrow-repeat me-1"></i>' . t('Refundable') . '</span>';
}
?><?= $baggageInfo ? '<span class="d-block" style="font-size:10px;"><i class="bi bi-briefcase me-1"></i>' . e($baggageInfo) . '</span>' : '' ?></small></div>
                                <div class="col-md-2 text-center"><div class="fs-6 fw-bold text-primary"><?= $isFL ? flightlistFormatPrice($o['price'] ?? $o['conversion']['USD'] ?? 0) : duffelFormatPrice($o['total_amount'], $o['total_currency']) ?></div><small class="text-muted">/ <?= t('orang') ?></small></div>
                                <div class="col-md-2 text-md-end"><a href="flight-detail.php?<?= $isFL ? "fl_offer_id=".e($offerId) : "offer_id=".e($offerId) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold w-100"><?= t('Pilih') ?></a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif (!empty($localSchedules)): ?>
            <div class="alert alert-info py-2 small"><?= t('Hasil live tidak tersedia, menampilkan jadwal lokal.') ?></div>
            <div class="row g-3">
                <?php foreach ($localSchedules as $s): $dep = date('H:i', strtotime($s['departure_time'])); $arr = date('H:i', strtotime($s['arrival_time'])); $airlineCode = substr($s['airline'], 0, 2); $fromShort = explode('(', $s['from_city'])[0]; $toShort = explode('(', $s['to_city'])[0]; ?>
                <div class="col-12"><div class="card border-0 shadow-sm flight-card"><div class="card-body p-3 p-md-4"><div class="row align-items-center g-3">
                    <div class="col-md-2 d-flex align-items-center gap-2"><div class="flight-logo d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary fw-bold rounded-2" style="width:44px;height:44px;"><?= $airlineCode ?></div><div><div class="fw-semibold small"><?= e($s['airline']) ?></div><small class="text-muted" style="font-size:11px;"><?= e($s['flight_number']) ?></small></div></div>
                    <div class="col-md-4"><div class="d-flex align-items-center justify-content-center gap-2"><div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e(trim($fromShort)) ?></small></div><div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-secondary position-relative"><i class="bi bi-airplane-fill text-secondary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-muted d-block mt-1"><?= e($s['duration']) ?></small></div><div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e(trim($toShort)) ?></small></div></div></div>
                    <div class="col-md-2 text-center"><span class="badge bg-secondary rounded-pill"><?= ucfirst($s['class']) ?></span><small class="d-block text-muted mt-1"><?= t('Sisa') ?> <?= $s['available_seats'] ?> <?= t('kursi') ?></small></div>
                    <div class="col-md-2 text-center"><div class="fs-5 fw-bold text-primary"><?= formatCurrencySpan($s['price']) ?></div><small class="text-muted">/ <?= t('orang') ?></small></div>
                    <div class="col-md-2 text-md-end"><a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-outline-primary rounded-pill px-4 w-100"><?= t('Pilih (Lokal)') ?></a></div>
                </div></div></div></div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5" id="noResults"><i class="bi bi-airplane fs-1 text-muted"></i><p class="mt-2 text-muted"><?= t('Tidak ada penerbangan untuk rute/tanggal tersebut.') ?></p><p class="small text-muted"><?= t('Coba: CGK → DPS, SIN → CGK, atau ubah tanggal.') ?></p><a href="flights.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a></div>
            <?php endif; ?>
        <?php else: ?>
            <?php if (count($localSchedules) > 0): ?><p class="small text-muted mb-2">Jadwal lokal (contoh). Gunakan pencarian di atas untuk hasil live Duffel.</p><div class="row g-3"><?php foreach ($localSchedules as $s): $dep = date('H:i', strtotime($s['departure_time'])); $arr = date('H:i', strtotime($s['arrival_time'])); $airlineCode = substr($s['airline'], 0, 2); ?>
                <div class="col-12"><div class="card border-0 shadow-sm flight-card"><div class="card-body p-3 d-flex justify-content-between align-items-center"><div class="d-flex align-items-center gap-2"><div class="flight-logo bg-light border rounded-2 d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:12px"><?= $airlineCode ?></div><div><div class="fw-semibold small"><?= e($s['airline']) ?> <?= e($s['flight_number']) ?></div><small class="text-muted"><?= e($s['from_city']) ?> → <?= e($s['to_city']) ?> · <?= e($s['duration']) ?></small></div></div><div class="text-end"><div class="fw-bold text-primary small"><?= formatCurrencySpan($s['price']) ?></div><a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill mt-1">Lihat</a></div></div></div></div>
                <?php endforeach; ?></div><?php endif; ?>
        <?php endif; ?>
        </div><!-- /.col-lg-9 -->
        </div><!-- /.row -->
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
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
document.querySelectorAll('input[name="trip_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var returnCol = document.querySelector('.return-date-col');
        var dateLabel = document.querySelector('input[name="date"]').closest('.traveloka-search-field').querySelector('.form-label');
        if (document.getElementById('tripRoundtrip').checked) {
            returnCol.style.display = '';
            dateLabel.textContent = '<?= t('Pergi') ?>';
        } else {
            returnCol.style.display = 'none';
            dateLabel.textContent = '<?= t('Tanggal') ?>';
        }
    });
});
</script>

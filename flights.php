<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/nusatrip.php';
// FlightList & Duffel dinonaktifkan — pencarian & booking pakai NusaTrip (lihat nusatrip-flight-book.php).
require_once 'includes/duffel.php';
require_once 'includes/flightlist.php';

$pageTitle = t('Pesawat');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$class = $_GET['class'] ?? '';
$passengers = max(1, min(9, (int)($_GET['passengers'] ?? 1)));
$tripType = in_array($_GET['trip_type'] ?? 'oneway', ['oneway', 'roundtrip', 'multicity'], true) ? ($_GET['trip_type'] ?? 'oneway') : 'oneway';
$returnDate = $_GET['return_date'] ?? '';

// Multi-city legs: leg_from[], leg_to[], leg_date[]
$legs = [];
if ($tripType === 'multicity') {
    $legFrom = is_array($_GET['leg_from'] ?? null) ? $_GET['leg_from'] : [];
    $legTo = is_array($_GET['leg_to'] ?? null) ? $_GET['leg_to'] : [];
    $legDate = is_array($_GET['leg_date'] ?? null) ? $_GET['leg_date'] : [];
    $n = max(count($legFrom), count($legTo), count($legDate));
    for ($i = 0; $i < min($n, 6); $i++) {
        $lf = trim((string)($legFrom[$i] ?? ''));
        $lt = trim((string)($legTo[$i] ?? ''));
        $ld = trim((string)($legDate[$i] ?? ''));
        if ($lf !== '' && $lt !== '' && $ld !== '') {
            $legs[] = ['origin' => $lf, 'destination' => $lt, 'departure_date' => $ld];
        }
    }
}
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

// Kalender harga pasar per tanggal (price_calendar item_type=flight, item_id=0)
$flightCal = [];
foreach (db()->query("SELECT date, price FROM price_calendar WHERE item_type = 'flight' AND item_id = 0 AND date >= CURDATE() AND date <= CURDATE() + INTERVAL 90 DAY ORDER BY date")->fetchAll() as $fcRow) {
    $flightCal[] = ['date' => $fcRow['date'], 'price' => (float)$fcRow['price']];
}

// Keep past dates when searching so Duffel validation shows
if (!$doSearch && (!strtotime($date) || $date < date('Y-m-d'))) $date = date('Y-m-d', strtotime('+3 days'));

$duffelOffers = [];
$duffelError = null;
$nusaOffers = [];
$nusaError = null;
$localSchedules = [];
$localTotal = 0;
$localPage = max(1, (int)($_GET['page'] ?? 1));
$localPerPage = 10;

$loadLocalFlights = function (string $from, string $to, string $date, string $class, int $page) use ($localPerPage, $airlineFilter, $minPrice, $maxPrice, $depFilter, $stopsFilter): array {
    $sql = "SELECT SQL_CALC_FOUND_ROWS fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date=?";
    $params = [$date];
    if ($from) { $sql .= " AND f.from_city LIKE ?"; $params[] = "%$from%"; }
    if ($to) { $sql .= " AND f.to_city LIKE ?"; $params[] = "%$to%"; }
    if ($class) { $sql .= " AND f.class=?"; $params[] = $class; }
    if (!empty($airlineFilter)) {
        $ors = [];
        foreach ($airlineFilter as $af) { $ors[] = "f.airline LIKE ?"; $params[] = "%$af%"; }
        $sql .= " AND (" . implode(' OR ', $ors) . ")";
    }
    if ($minPrice !== '') { $sql .= " AND fs.price >= ?"; $params[] = (float)$minPrice; }
    if ($maxPrice !== '') { $sql .= " AND fs.price <= ?"; $params[] = (float)$maxPrice; }
    if ($depFilter !== '') {
        $hour = "HOUR(fs.departure_time)";
        $depSql = match ($depFilter) {
            'morning' => "$hour >= 5 AND $hour < 12",
            'afternoon' => "$hour >= 12 AND $hour < 17",
            'evening' => "$hour >= 17 AND $hour < 22",
            'night' => "($hour >= 22 OR $hour < 5)",
            default => '',
        };
        if ($depSql !== '') $sql .= " AND ($depSql)";
    }
    if ($stopsFilter === 'transit') { $sql .= " AND 1=0"; }
    $sql .= " ORDER BY fs.price ASC LIMIT $localPerPage OFFSET " . (($page - 1) * $localPerPage);
    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    $total = (int)db()->query("SELECT FOUND_ROWS()")->fetchColumn();
    return [$rows, $total];
};

if ($doSearch && $tripType === 'multicity' && count($legs) >= 2) {
    // Multi-kota tak didukung flight_search NusaTrip (single leg) → fallback jadwal lokal.
    [$localSchedules, $localTotal] = $loadLocalFlights($from, $to, $date, $class, $localPage);
} elseif ($doSearch && $tripType === 'multicity') {
    $nusaError = t('Minimal 2 leg untuk perjalanan multi-kota.');
} elseif ($doSearch && $from && $to) {
    // Primary: NusaTrip (flightlist & duffel dinonaktifkan).
    if (!nusaModuleEnabled()) {
        $nusaError = t('Modul NusaTrip nonaktif.');
    } else {
        $fromCode = nusaParseIata($from);
        $toCode = nusaParseIata($to);
        if (!$fromCode || !$toCode) {
            $nusaError = t('Kode bandara tidak valid. Contoh: CGK, DPS, atau pilih dari daftar.');
        } else {
            $nusaRes = nusaFlightSearch($fromCode, $toCode, $date, $passengers);
            $nusaData = $nusaRes['data'] ?? null;
            if (($nusaRes['http'] ?? 0) === 200 && is_array($nusaData) && !empty($nusaData['outbounds'])) {
                $nusaAirlines = (array)($nusaData['airlines'] ?? []);
                $flightRoute = (string)($nusaData['flightRoute'] ?? 'domestic');
                $_SESSION['nusa_flight_offers'] = [];
                foreach ($nusaData['outbounds'] as $o) {
                    if (empty($o['param'])) continue;
                    $norm = nusaNormalizeFlight($o, $nusaAirlines, $fromCode, $toCode);
                    $norm['flight_route'] = $flightRoute;
                    $norm['pax'] = $passengers;
                    $norm['key'] = substr(md5($norm['param']), 0, 16);
                    $nusaOffers[] = $norm;
                    $_SESSION['nusa_flight_offers'][$norm['key']] = $norm;
                }
                $offerSource = 'nusatrip';
            } else {
                $nusaError = (string)($nusaData['messages'][0]['message'] ?? '') ?: t('NusaTrip tidak terjangkau');
            }
        }
    }
    if (empty($nusaOffers)) {
        [$localSchedules, $localTotal] = $loadLocalFlights($from, $to, $date, $class, $localPage);
    }
} elseif ($doSearch && (!$from || !$to)) {
    $nusaError = t('Silakan isi kota asal dan tujuan.');
} else {
    $st=db()->prepare("SELECT SQL_CALC_FOUND_ROWS fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date>=CURDATE() ORDER BY fs.departure_date ASC, fs.price ASC LIMIT 10 OFFSET " . ((max(1, (int)($_GET['page'] ?? 1)) - 1) * 10));
    $st->execute([]);
    $localSchedules=$st->fetchAll();
    $totalSchedules = (int)db()->query("SELECT FOUND_ROWS()")->fetchColumn();
    $lastPage = max(1, (int)ceil($totalSchedules / 10));
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
}
if ($doSearch && !empty($localSchedules)) {
    $currentPage = $localPage;
    $lastPage = max(1, (int)ceil($localTotal / $localPerPage));
}
$allDates = db()->query("SELECT DISTINCT departure_date FROM flight_schedules WHERE is_active=1 AND departure_date>=CURDATE() ORDER BY departure_date LIMIT 14")->fetchAll(PDO::FETCH_COLUMN);
// Extract airlines from actual search results (not hardcoded from DB)
$allAirlines = [];
if (!empty($duffelOffers)) {
    foreach ($duffelOffers as $o) {
        $isFL = isset($o['route']) && isset($o['flyFrom']);
        $airline = $isFL ? ($o['airlines'][0] ?? '') : (($o['slices'][0]['segments'][0]['marketing_carrier']['name'] ?? '') ?: ($o['slices'][0]['segments'][0]['marketing_carrier']['iata_code'] ?? ''));
        if ($airline && !in_array($airline, $allAirlines)) $allAirlines[] = $airline;
    }
}
if (!empty($nusaOffers)) {
    foreach ($nusaOffers as $o) {
        $airline = $o['airline_name'] ?? '';
        if ($airline && !in_array($airline, $allAirlines)) $allAirlines[] = $airline;
    }
}
if (!empty($localSchedules)) {
    foreach ($localSchedules as $s) {
        $airline = $s['airline'] ?? '';
        if ($airline && !in_array($airline, $allAirlines)) $allAirlines[] = $airline;
    }
}
sort($allAirlines);
// Filter NusaTrip offers by airline/min/max price/departure time/stops
if (!empty($nusaOffers) && (!empty($airlineFilter) || $minPrice !== '' || $maxPrice !== '' || $depFilter !== '' || $stopsFilter !== '')) {
    $nusaOffers = array_values(array_filter($nusaOffers, function ($o) use ($airlineFilter, $minPrice, $maxPrice, $depFilter, $stopsFilter) {
        if (!empty($airlineFilter)) {
            $matched = false;
            foreach ($airlineFilter as $af) {
                if (stripos($o['airline_name'] ?? '', $af) !== false || stripos($o['airline_code'] ?? '', $af) !== false) { $matched = true; break; }
            }
            if (!$matched) return false;
        }
        $price = (float)($o['price'] ?? 0);
        if ($minPrice !== '' && $price < (float)$minPrice) return false;
        if ($maxPrice !== '' && $price > (float)$maxPrice) return false;
        if ($depFilter !== '') {
            $hour = (int)date('G', strtotime($o['dep'] ?? ''));
            $inRange = match ($depFilter) { 'morning' => $hour >= 5 && $hour < 12, 'afternoon' => $hour >= 12 && $hour < 17, 'evening' => $hour >= 17 && $hour < 22, 'night' => $hour >= 22 || $hour < 5, default => true };
            if (!$inRange) return false;
        }
        if ($stopsFilter === 'direct' && (int)($o['stops'] ?? 0) > 0) return false;
        if ($stopsFilter === 'transit' && (int)($o['stops'] ?? 0) === 0) return false;
        return true;
    }));
}
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
if (!empty($localSchedules) && (!empty($airlineFilter) || $minPrice !== '' || $maxPrice !== '' || $depFilter !== '' || $stopsFilter !== '')) {
    $localSchedules = array_values(array_filter($localSchedules, function ($s) use ($airlineFilter, $minPrice, $maxPrice, $depFilter, $stopsFilter) {
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
        if ($depFilter !== '') {
            $hour = (int)date('G', strtotime($s['departure_time'] ?? ''));
            $inRange = match ($depFilter) { 'morning' => $hour >= 5 && $hour < 12, 'afternoon' => $hour >= 12 && $hour < 17, 'evening' => $hour >= 17 && $hour < 22, 'night' => $hour >= 22 || $hour < 5, default => true };
            if (!$inRange) return false;
        }
        if ($stopsFilter === 'direct' && !empty($s['stops']) && (int)$s['stops'] > 0) return false;
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
    usort($nusaOffers, function ($a, $b) { return (float)($a['price'] ?? 0) <=> (float)($b['price'] ?? 0); });
    usort($localSchedules, function ($a, $b) { return (float)($a['price'] ?? 0) <=> (float)($b['price'] ?? 0); });
} elseif ($sort === 'duration') {
    usort($duffelOffers, function ($a, $b) use ($sortDurationOf) { return $sortDurationOf($a) <=> $sortDurationOf($b); });
    usort($nusaOffers, function ($a, $b) { return (int)($a['duration'] ?? 0) <=> (int)($b['duration'] ?? 0); });
}
require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-shared.php';
?>
<?php if (!$doSearch): require __DIR__ . '/includes/homepage/flight-hero.php'; endif; ?>

<?php if ($doSearch): ?>
<div class="voyage-spreader"></div>
<section class="py-4 bg-light" style="min-height:60vh;">
    <div class="container">
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
                                    <input type="number" name="min_price" class="form-control form-control-sm" placeholder="<?= t('Min') ?>" value="<?= e($minPrice) ?>" min="0">
                                    <input type="number" name="max_price" class="form-control form-control-sm" placeholder="<?= t('Max') ?>" value="<?= e($maxPrice) ?>" min="0">
                                </div>
                                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i><?= t('Terapkan') ?></button>
                                <a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&date=<?= urlencode($date) ?>&class=<?= urlencode($class) ?>&passengers=<?= $passengers ?>&trip_type=<?= $tripType ?>&search=1" class="btn btn-outline-secondary btn-sm w-100 mt-2"><?= t('Reset') ?></a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <!-- Skeleton Loading (shown initially, hidden after content loads) -->
                <div id="flightSkeleton">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="col-12 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-2 d-flex align-items-center gap-2">
                                        <div class="skeleton" style="width:44px;height:44px;border-radius:8px;"></div>
                                        <div class="flex-grow-1">
                                            <div class="skeleton skeleton-text" style="width:80%;"></div>
                                            <div class="skeleton skeleton-text" style="width:50%;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="text-center">
                                                <div class="skeleton skeleton-text" style="width:50px;height:20px;margin:0 auto;"></div>
                                                <div class="skeleton skeleton-text" style="width:30px;margin:0 auto;"></div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="skeleton" style="height:2px;width:100%;"></div>
                                                <div class="skeleton skeleton-text" style="width:40px;margin:4px auto 0;"></div>
                                            </div>
                                            <div class="text-center">
                                                <div class="skeleton skeleton-text" style="width:50px;height:20px;margin:0 auto;"></div>
                                                <div class="skeleton skeleton-text" style="width:30px;margin:0 auto;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="skeleton skeleton-text" style="width:60px;height:18px;margin:0 auto;"></div>
                                        <div class="skeleton skeleton-text" style="width:40px;margin:4px auto 0;"></div>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="skeleton skeleton-text" style="width:80px;height:24px;margin:0 auto;"></div>
                                        <div class="skeleton skeleton-text" style="width:40px;margin:4px auto 0;"></div>
                                    </div>
                                    <div class="col-md-2 text-md-end">
                                        <div class="skeleton skeleton-btn" style="width:100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Actual Content (hidden initially, shown after load) -->
                <div id="flightContent" style="display: none;">
        <?php if ($doSearch): ?>
            <?php if (!empty($nusaOffers)): ?>
            <?php $badge = 'NusaTrip'; $badgeClass = 'bg-primary'; ?>
            <!-- Sort bar ala Traveloka -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div><h5 class="fw-bold mb-0"><?= count($nusaOffers) ?> <?= t('Penerbangan') ?> <span class="badge <?= $badgeClass ?> ms-1" style="font-size:11px"><?= $badge ?></span></h5><small class="text-muted"><?= formatDate($date) ?> · <?= e($from) ?> → <?= e($to) ?> · <?= $passengers ?> <?= t('pax') ?></small></div>
                <div class="d-flex gap-1">
                    <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'price']))) ?>" class="btn btn-sm <?= $sort === 'price' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Termurah') ?></a>
                    <a href="?<?= e(http_build_query(array_merge($_GET, ['sort' => 'duration']))) ?>" class="btn btn-sm <?= $sort === 'duration' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill"><?= t('Tercepat') ?></a>
                </div>
            </div>
            <div class="row g-3" id="flightGrid">
                <?php foreach ($nusaOffers as $o):
                    $dep = !empty($o['dep']) ? date('H:i', strtotime(substr($o['dep'], 0, 8))) : '--:--';
                    $arr = !empty($o['arr']) ? date('H:i', strtotime(substr($o['arr'], 0, 8))) : '--:--';
                    $durMin = (int)($o['duration'] ?? 0);
                    $duration = $durMin > 0 ? floor($durMin / 60) . 'j ' . ($durMin % 60) . 'm' : '-';
                    $stops = (int)($o['stops'] ?? 0);
                    $cc = strtolower((string)($o['class_type'] ?? 'economy'));
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm flight-card">
                        <div class="card-body p-3 p-md-4">
                            <div class="row align-items-center g-3">
                                <div class="col-md-2 d-flex align-items-center gap-2">
                                    <?php $logoUrl = 'https://images.kiwi.com/airlines/64/' . e($o['airline_code'] ?: 'ZZ') . '.png'; ?>
                                    <img src="<?= $logoUrl ?>" alt="<?= e($o['airline_name']) ?>" style="width:44px;height:44px;object-fit:contain" class="bg-white rounded-2 border" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                    <div class="flight-logo d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width:44px;height:44px;display:none;"><?= e(substr($o['airline_name'] ?: 'ZZ', 0, 2)) ?></div>
                                    <div><div class="fw-semibold small"><?= e($o['airline_name']) ?></div><small class="text-muted" style="font-size:11px;"><?= e($o['flight_number']) ?></small></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e($o['from']) ?></small></div>
                                        <div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-primary position-relative"><i class="bi bi-airplane-fill text-primary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-muted d-block mt-1"><?= e($duration) ?></small><?php if ($stops>0): ?><small class="text-warning" style="font-size:11px"><?= $stops ?> <?= t('transit') ?></small><?php else: ?><small class="text-success" style="font-size:11px"><?= t('Langsung') ?></small><?php endif; ?></div>
                                        <div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e($o['to']) ?></small></div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center"><span class="badge bg-<?= $cc==='economy'?'success':($cc==='business'?'warning text-dark':'danger') ?> rounded-pill"><?= e(ucfirst($cc)) ?></span><small class="d-block text-muted mt-1" style="font-size:11px"><?php if (!empty($o['baggage'])): ?><span class="d-block" style="font-size:10px;"><i class="bi bi-briefcase me-1"></i><?= t('Bagasi') ?> <?= e((string)$o['baggage']) ?> kg</span><?php endif; ?><?php if (!empty($o['seat'])): ?><span class="d-block" style="font-size:10px;"><?= t('Sisa') ?> <?= (int)$o['seat'] ?></span><?php endif; ?></small></div>
                                <div class="col-md-2 text-center"><div class="fs-6 fw-bold text-primary"><?= formatCurrencySpan((float)$o['price'], 'IDR') ?></div><small class="text-muted">/ <?= t('orang') ?></small></div>
                                <div class="col-md-2 text-md-end"><a href="nusatrip-flight-book.php?of=<?= e($o['key']) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold w-100"><?= t('Pilih') ?></a></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif (!empty($localSchedules)): ?>
            <div class="alert alert-info py-2 small"><?= t('Hasil live tidak tersedia, menampilkan jadwal lokal.') ?></div>
            <div class="row g-3" id="flightGrid">
                <?php foreach ($localSchedules as $s): $dep = date('H:i', strtotime($s['departure_time'])); $arr = date('H:i', strtotime($s['arrival_time'])); $airlineCode = substr($s['airline'], 0, 2); $fromShort = explode('(', $s['from_city'])[0]; $toShort = explode('(', $s['to_city'])[0]; ?>
                <div class="col-12"><div class="card border-0 shadow-sm flight-card"><div class="card-body p-3 p-md-4"><div class="row align-items-center g-3">
                    <div class="col-md-2 d-flex align-items-center gap-2"><img src="https://images.kiwi.com/airlines/64/<?= $airlineCode ?>.png" alt="<?= e($s['airline']) ?>" style="width:44px;height:44px;object-fit:contain" class="bg-white rounded-2 border" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div class="flight-logo d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary fw-bold rounded-2" style="width:44px;height:44px;display:none;"><?= $airlineCode ?></div><div><div class="fw-semibold small"><?= e($s['airline']) ?></div><small class="text-muted" style="font-size:11px;"><?= e($s['flight_number']) ?></small></div></div>
                    <div class="col-md-4"><div class="d-flex align-items-center justify-content-center gap-2"><div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e(trim($fromShort)) ?></small></div><div class="flex-grow-1 text-center px-2"><div class="border-top border-2 border-secondary position-relative"><i class="bi bi-airplane-fill text-secondary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i></div><small class="text-muted d-block mt-1"><?= e($s['duration']) ?></small></div><div class="text-center" style="min-width:70px;"><div class="fs-5 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e(trim($toShort)) ?></small></div></div></div>
                    <div class="col-md-2 text-center"><span class="badge bg-secondary rounded-pill"><?= ucfirst($s['class']) ?></span><small class="d-block text-muted mt-1"><?= t('Sisa') ?> <?= $s['available_seats'] ?> <?= t('kursi') ?></small></div>
                    <div class="col-md-2 text-center"><div class="fs-5 fw-bold text-primary"><?= formatCurrencySpan($s['price']) ?></div><small class="text-muted">/ <?= t('orang') ?></small></div>
                    <div class="col-md-2 text-md-end"><a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-outline-primary rounded-pill px-4 w-100"><?= t('Pilih (Lokal)') ?></a></div>
                </div></div></div></div>
                <?php endforeach; ?>
            </div>
            <?php if (isset($lastPage) && $lastPage > $currentPage): ?>
            <div class="load-more-trigger text-center py-4" data-page="<?= $currentPage ?>" data-last-page="<?= $lastPage ?>" data-testid="flight-load-more">
                <div class="load-more-spinner spinner-border text-primary" role="status">
                    <span class="visually-hidden"><?= t('Loading...') ?></span>
                </div>
                <div class="load-more-error d-none" data-load-error="true">
                    <i class="bi bi-wifi-off fs-3 text-muted"></i>
                    <p class="mt-2 mb-2 text-muted small"><?= t('Gagal memuat penerbangan. Periksa koneksi Anda.') ?></p>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-load-retry><?= t('Coba Lagi') ?></button>
                </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="text-center py-5" id="noResults"><i class="bi bi-airplane fs-1 text-muted"></i><p class="mt-2 text-muted"><?= t('Tidak ada penerbangan untuk rute/tanggal tersebut.') ?></p><?php if (!empty($nusaError)): ?><p class="small text-danger"><?= e($nusaError) ?></p><?php endif; ?><p class="small text-muted"><?= t('Coba: CGK → DPS, SIN → CGK, atau ubah tanggal.') ?></p><a href="flights.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a></div>
            <?php endif; ?>
        <?php endif; ?>
        </div><!-- /.col-lg-9 -->
        </div><!-- /.row -->
    </div>
</section>
<?php endif; ?>
<?php require_once 'includes/footer-shared.php'; ?>
<script>
// Show skeleton initially, then reveal content
document.addEventListener('DOMContentLoaded', function() {
    var skeleton = document.getElementById('flightSkeleton');
    var content = document.getElementById('flightContent');
    if (skeleton && content) {
        // Small delay to show skeleton effect
        setTimeout(function() {
            skeleton.style.display = 'none';
            content.style.display = 'block';
        }, 300);
    }

    // Infinite Scroll with IntersectionObserver (port dari tours.php)
    var loadMoreTrigger = document.querySelector('.load-more-trigger');
    if (loadMoreTrigger) {
        var currentPage = parseInt(loadMoreTrigger.dataset.page);
        var lastPage = parseInt(loadMoreTrigger.dataset.lastPage);
        var spinner = loadMoreTrigger.querySelector('.load-more-spinner');
        var errorBox = loadMoreTrigger.querySelector('.load-more-error');
        var retryBtn = loadMoreTrigger.querySelector('[data-load-retry]');
        var loading = false;
        var failed = false;

        function loadNextPage() {
            if (loading || failed || currentPage >= lastPage) return;
            loading = true;
            if (spinner) spinner.classList.remove('d-none');
            if (errorBox) errorBox.classList.add('d-none');
            currentPage++;

            var params = new URLSearchParams(window.location.search);
            params.set('page', currentPage);
            var ajaxUrl = 'flights-ajax.php?' + params.toString();

            fetch(ajaxUrl)
                .then(function(response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.text();
                })
                .then(function(html) {
                    var temp = document.createElement('div');
                    temp.innerHTML = html;
                    if (temp.querySelector('[data-empty]')) {
                        loadMoreTrigger.remove();
                        loading = false;
                        return;
                    }
                    var grid = document.getElementById('flightGrid');
                    if (grid) {
                        grid.insertAdjacentHTML('beforeend', temp.innerHTML);
                    }
                    loadMoreTrigger.dataset.page = currentPage;
                    if (currentPage >= lastPage) {
                        loadMoreTrigger.remove();
                    }
                    loading = false;
                })
                .catch(function() {
                    currentPage--;
                    loading = false;
                    failed = true;
                    if (spinner) spinner.classList.add('d-none');
                    if (errorBox) errorBox.classList.remove('d-none');
                });
        }

        if (retryBtn) {
            retryBtn.addEventListener('click', function() {
                failed = false;
                loadNextPage();
            });
        }

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) loadNextPage();
            });
        }, { rootMargin: '200px' });

        observer.observe(loadMoreTrigger);
    }
});
</script>
<script>
// ===== Harga per tanggal (price_calendar) =====
var FLIGHT_CAL = <?= json_encode($flightCal) ?>;
function showFlightCalHint() {
    var hint = document.getElementById('flightCalHint');
    if (!hint) return;
    var d = document.querySelector('input[name="date"]').value;
    var hit = FLIGHT_CAL.find(function(r) { return r.date === d; });
    if (hit) {
        hint.textContent = '<?= t('Harga termurah') ?>: Rp ' + hit.price.toLocaleString(window.I18N && String(window.I18N.locale).indexOf('en') === 0 ? 'en-US' : 'id-ID');
        hint.classList.remove('d-none');
    } else {
        hint.textContent = '';
        hint.classList.add('d-none');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    showFlightCalHint();
    var dateInput = document.querySelector('input[name="date"]');
    if (dateInput) dateInput.addEventListener('change', showFlightCalHint);
});
</script>

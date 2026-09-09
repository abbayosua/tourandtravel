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
$localSchedules = [];

if ($doSearch && $tripType === 'multicity' && count($legs) >= 2) {
    // Multi-city: langsung Duffel multi-slice (FlightList tak dukung multi-leg)
    $cabinMap = ['economy'=>'economy','business'=>'business','first'=>'first','premium_economy'=>'premium_economy'];
    $cabin = $cabinMap[$class] ?? 'economy';
    $result = duffelSearchOffers(null, null, null, $cabin, $passengers, $legs);
    if (isset($result['error'])) {
        $duffelError = $result['error'];
    } else {
        $duffelOffers = $result['offers'] ?? [];
        $offerSource = 'duffel';
        $flightlistCurrency = $duffelOffers[0]['total_currency'] ?? 'USD';
    }
} elseif ($doSearch && $tripType === 'multicity') {
    $duffelError = 'Minimal 2 leg untuk perjalanan multi-kota.';
} elseif ($doSearch && $from && $to) {
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
    $st=db()->prepare("SELECT SQL_CALC_FOUND_ROWS fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date>=CURDATE() ORDER BY fs.departure_date ASC, fs.price ASC LIMIT 10 OFFSET " . ((max(1, (int)($_GET['page'] ?? 1)) - 1) * 10));
    $st->execute([]);
    $localSchedules=$st->fetchAll();
    $totalSchedules = (int)db()->query("SELECT FOUND_ROWS()")->fetchColumn();
    $lastPage = max(1, (int)ceil($totalSchedules / 10));
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
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
if (!empty($localSchedules)) {
    foreach ($localSchedules as $s) {
        $airline = $s['airline'] ?? '';
        if ($airline && !in_array($airline, $allAirlines)) $allAirlines[] = $airline;
    }
}
sort($allAirlines);
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
    usort($localSchedules, function ($a, $b) { return (float)($a['price'] ?? 0) <=> (float)($b['price'] ?? 0); });
} elseif ($sort === 'duration') {
    usort($duffelOffers, function ($a, $b) use ($sortDurationOf) { return $sortDurationOf($a) <=> $sortDurationOf($b); });
}
require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<?php if (!$doSearch): ?>
<section class="hero-uifactory">
  <div class="hero-bg-shape hero-bg-shape--1"></div>
  <div class="hero-bg-shape hero-bg-shape--2"></div>
  <div class="hero-content">
    <h1 class="hero-headline">Jelajahi Lebih Banyak,<br><span>Nikmati Perjalanannya.</span></h1>
    <p class="hero-sub">Pesan tiket pesawat, ferry, dan kereta api dalam satu tempat.</p>
  </div>
  <div class="booking-card">
    <div class="booking-card-inner">
      <div class="booking-tabs" role="tablist">
        <a href="flights.php" class="booking-tab active" role="tab"><i class="bi bi-airplane"></i> <?= t('Pesawat') ?></a>
        <a href="ferries.php" class="booking-tab" role="tab"><i class="bi bi-water"></i> <?= t('Ferry') ?></a>
        <a href="trains.php" class="booking-tab" role="tab"><i class="bi bi-train-front"></i> <?= t('Kereta') ?></a>
        <a href="rental-cars.php" class="booking-tab" role="tab"><i class="bi bi-car-front"></i> <?= t('Rental') ?></a>
      </div>
      <div class="booking-form">
        <?php if ($duffelError): ?>
          <div class="alert alert-warning py-2 small mb-3"><?= e($duffelError) ?></div>
        <?php endif; ?>
        <form method="GET" id="flightSearchForm">
          <div class="form-row-options">
            <div class="trip-type-group">
              <button type="button" class="trip-type-btn <?= $tripType === 'roundtrip' ? 'active' : '' ?>" onclick="document.getElementById('tripTypeHidden').value='roundtrip';document.querySelectorAll('.trip-type-btn').forEach(function(b){b.classList.remove('active')});this.classList.add('active');document.querySelectorAll('.return-date-col').forEach(function(c){c.style.display=''});" data-type="roundtrip"><?= t('Pulang Pergi') ?></button>
              <button type="button" class="trip-type-btn <?= $tripType === 'oneway' ? 'active' : '' ?>" onclick="document.getElementById('tripTypeHidden').value='oneway';document.querySelectorAll('.trip-type-btn').forEach(function(b){b.classList.remove('active')});this.classList.add('active');document.querySelectorAll('.return-date-col').forEach(function(c){c.style.display='none'});" data-type="oneway"><?= t('Sekali Jalan') ?></button>
              <button type="button" class="trip-type-btn <?= $tripType === 'multicity' ? 'active' : '' ?>" onclick="document.getElementById('tripTypeHidden').value='multicity';document.querySelectorAll('.trip-type-btn').forEach(function(b){b.classList.remove('active')});this.classList.add('active');" data-type="multicity"><?= t('Multi-Kota') ?></button>
            </div>
            <input type="hidden" name="trip_type" value="<?= e($tripType) ?>" id="tripTypeHidden">
            <div class="custom-select">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              <select name="passengers">
                <?php for($p=1;$p<=9;$p++): ?><option value="<?= $p ?>" <?= $passengers===$p?'selected':'' ?>><?= $p ?> <?= t('orang') ?></option><?php endfor; ?>
              </select>
            </div>
            <div class="custom-select">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <select name="class">
                <option value=""><?= t('Semua Kelas') ?></option>
                <option value="economy" <?= $class === 'economy' ? 'selected' : '' ?>><?= t('Ekonomi') ?></option>
                <option value="business" <?= $class === 'business' ? 'selected' : '' ?>><?= t('Bisnis') ?></option>
                <option value="first" <?= $class === 'first' ? 'selected' : '' ?>><?= t('First Class') ?></option>
              </select>
            </div>
          </div>
          <div id="multiCityLegs" class="mb-3 d-none" data-testid="multicity-legs">
            <div id="legContainer"></div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addLegBtn" data-testid="add-leg"><i class="bi bi-plus-lg me-1"></i><?= t('Tambah leg') ?></button>
            <div class="form-text"><?= t('Maksimal 6 leg.') ?></div>
          </div>
          <div class="form-search-row">
            <div class="search-field">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Dari') ?></span>
              <input type="text" name="from" class="city-search" placeholder="<?= t('Kota atau bandara') ?>" value="<?= e($from) ?>" autocomplete="off" data-target="fromDropdown" id="fromInput">
              <div class="search-dropdown" id="fromDropdown"></div>
            </div>
            <button type="button" class="swap-btn" onclick="var f=document.querySelector('[name=from]'),t=document.querySelector('[name=to]'),tmp=f.value;f.value=t.value;t.value=tmp;" aria-label="Tukar"><div class="swap-btn-inner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16l-4-4 4-4"/><path d="M17 8l4 4-4 4"/><line x1="3" y1="12" x2="21" y2="12"/></svg></div></button>
            <div class="search-field">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Ke') ?></span>
              <input type="text" name="to" class="city-search" placeholder="<?= t('Kota atau bandara') ?>" value="<?= e($to) ?>" autocomplete="off" data-target="toDropdown" id="toInput">
              <div class="search-dropdown" id="toDropdown"></div>
            </div>
            <div class="search-field return-date-col" style="<?= $tripType === 'roundtrip' ? '' : 'display:none' ?>">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= t('Tanggal Pulang') ?></span>
              <input type="date" name="return_date" value="<?= e($returnDate) ?>" min="<?= $date ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
            </div>
            <div class="search-field">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= $tripType === 'roundtrip' ? t('Tanggal Pergi') : t('Tanggal') ?></span>
              <input type="date" name="date" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
              <?php if (!empty($flightCal)): ?>
              <div class="small text-primary fw-semibold mt-1 d-none" id="flightCalHint" data-testid="flight-cal-hint"></div>
              <?php endif; ?>
            </div>
            <button class="search-btn" type="submit" name="search" value="1">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <?= t('Cari') ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($doSearch): ?>
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
            <?php if (!empty($duffelOffers)): ?>
            <?php
                $badge = 'Live Duffel'; $badgeClass='bg-success';
                if (($offerSource ?? '') === 'flightlist') { $badge='FlightList (Real)'; $badgeClass='bg-primary'; }
                elseif (($offerSource ?? '') === 'duffel') { $badge='Live Duffel'; $badgeClass='bg-success'; }
            ?>
            <!-- Sort bar ala Traveloka -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div><h5 class="fw-bold mb-0"><?= count($duffelOffers) ?> <?= t('Penerbangan') ?> <span class="badge <?= $badgeClass ?> ms-1" style="font-size:11px"><?= $badge ?></span></h5><small class="text-muted"><?= formatDate($date) ?> · <?= e($from) ?> → <?= e($to) ?> · <?= $passengers ?> <?= t('pax') ?></small></div>
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
                                    <?php $logoUrl = 'https://images.kiwi.com/airlines/64/' . e($carrier['iata_code'] ?? 'ZZ') . '.png'; ?>
                                    <img src="<?= $logoUrl ?>" alt="<?= e($carrier['name'] ?? '') ?>" style="width:44px;height:44px;object-fit:contain" class="bg-white rounded-2 border" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                    <div class="flight-logo d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold rounded-2" style="width:44px;height:44px;display:none;"><?= e(substr($carrier['name']??'ZZ',0,2)) ?></div>
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
                    <div class="col-md-2 d-flex align-items-center gap-2"><img src="https://images.kiwi.com/airlines/64/<?= $airlineCode ?>.png" alt="<?= e($s['airline']) ?>" style="width:44px;height:44px;object-fit:contain" class="bg-white rounded-2 border" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div class="flight-logo d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary fw-bold rounded-2" style="width:44px;height:44px;display:none;"><?= $airlineCode ?></div><div><div class="fw-semibold small"><?= e($s['airline']) ?></div><small class="text-muted" style="font-size:11px;"><?= e($s['flight_number']) ?></small></div></div>
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
            <?php if (count($localSchedules) > 0): ?><p class="small text-muted mb-2"></p><div class="row g-3" id="flightGrid"><?php foreach ($localSchedules as $s): $dep = date('H:i', strtotime($s['departure_time'])); $arr = date('H:i', strtotime($s['arrival_time'])); $airlineCode = substr($s['airline'], 0, 2); ?>
                <div class="col-12"><div class="card border-0 shadow-sm flight-card"><div class="card-body p-3 d-flex justify-content-between align-items-center"><div class="d-flex align-items-center gap-2"><img src="https://images.kiwi.com/airlines/64/<?= $airlineCode ?>.png" alt="<?= e($s['airline']) ?>" style="width:36px;height:36px;object-fit:contain" class="bg-white rounded-2 border" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div class="flight-logo bg-light border rounded-2 d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:12px;display:none;"><?= $airlineCode ?></div><div><div class="fw-semibold small"><?= e($s['airline']) ?> <?= e($s['flight_number']) ?></div><small class="text-muted"><?= e($s['from_city']) ?> → <?= e($s['to_city']) ?> · <?= e($s['duration']) ?></small></div></div><div class="text-end"><div class="fw-bold text-primary small"><?= formatCurrencySpan($s['price']) ?></div><a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill mt-1"><?= t('Lihat') ?></a></div></div></div></div>
                <?php endforeach; ?></div>
                <?php if (isset($lastPage) && $lastPage > $currentPage): ?>
                <div class="load-more-trigger text-center py-4" data-page="<?= $currentPage ?>" data-last-page="<?= $lastPage ?>" data-testid="flight-load-more">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-center py-5" id="noLocalResults"><i class="bi bi-airplane fs-1 text-muted"></i><p class="mt-2 text-muted"><?= t('Tidak ada jadwal lokal yang cocok dengan filter.') ?></p><a href="flights.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a></div>
                <?php endif; ?>
        <?php endif; ?>
        </div><!-- /.col-lg-9 -->
        </div><!-- /.row -->
    </div>
</section>
<?php endif; ?>
<?php require_once 'includes/footer-klook.php'; ?>
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
        var loading = false;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && !loading && currentPage < lastPage) {
                    loading = true;
                    currentPage++;

                    var params = new URLSearchParams(window.location.search);
                    params.set('page', currentPage);
                    var ajaxUrl = 'flights-ajax.php?' + params.toString();

                    fetch(ajaxUrl)
                        .then(function(response) { return response.text(); })
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
                            loading = false;
                        });
                }
            });
        }, { rootMargin: '200px' });

        observer.observe(loadMoreTrigger);
    }
});
</script>
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
        var wrapper = input.closest('.search-field') || input.closest('.search-wrapper');
        if (wrapper && !wrapper.contains(e.target)) dropdown.classList.remove('show');
    });
});
document.querySelectorAll('.trip-type-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('tripTypeHidden').value = btn.dataset.type;
        initTripUi();
    });
});
function initTripUi() {
    (function() {
        var tripType = document.getElementById('tripTypeHidden').value;
        var returnCol = document.querySelector('.return-date-col');
        var searchField = document.querySelector('input[name="date"]').closest('.search-field');
        var dateLabel = searchField ? searchField.querySelector('.search-field-label') : null;
        var isMc = tripType === 'multicity';
        document.getElementById('multiCityLegs').classList.toggle('d-none', !isMc);
        if (returnCol) returnCol.style.display = tripType === 'roundtrip' ? '' : 'none';
        if (dateLabel) dateLabel.textContent = tripType === 'roundtrip' ? '<?= t('Tanggal Pergi') ?>' : '<?= t('Tanggal') ?>';
    })();
    if (document.getElementById('tripTypeHidden').value === 'multicity') {
        document.getElementById('multiCityLegs').classList.remove('d-none');
    }
}
initTripUi();
// ===== Leg editor multi-city =====
    var legContainer = document.getElementById('legContainer');
    var legIdx = 0;
    function addLeg(fromVal, toVal, dateVal) {
        legIdx++;
        var row = document.createElement('div');
        row.className = 'row g-2 mb-2 leg-row';
        row.innerHTML = ''
            + '<div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_from[]" placeholder="<?= t('Dari (CGK)...') ?>" value="' + (fromVal || '') + '" autocomplete="off"></div>'
            + '<div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_to[]" placeholder="<?= t('Ke (DPS)...') ?>" value="' + (toVal || '') + '" autocomplete="off"></div>'
            + '<div class="col-md-3"><input type="date" class="form-control form-control-sm" name="leg_date[]" value="' + (dateVal || '') + '" min="<?= date('Y-m-d') ?>"></div>'
            + '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 leg-del" title="<?= t('Hapus') ?>">×</button></div>';
        row.querySelector('.leg-del').addEventListener('click', function() {
            row.remove();
        });
        legContainer.appendChild(row);
    }
    var addLegBtn = document.getElementById('addLegBtn');
    if (addLegBtn) {
        addLegBtn.addEventListener('click', function() {
            var rows = legContainer.querySelectorAll('.leg-row');
            if (rows.length >= 6) return;
            addLeg();
        });
    }
    <?php if ($tripType === 'multicity' && count($legs)): ?>
    (function() {
        <?php foreach ($legs as $lg): ?>
        addLeg(<?= json_encode($lg['origin']) ?>, <?= json_encode($lg['destination']) ?>, <?= json_encode($lg['departure_date']) ?>);
        <?php endforeach; ?>
    })();
    <?php else: ?>
    addLeg('', '', '');
    addLeg('', '', '');
    <?php endif; ?>
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

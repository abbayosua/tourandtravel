<?php
/**
 * Transport Search Hero — SATU hero+form reusable untuk Pesawat/Ferry/Kereta.
 * Mode via $tsMode ('flight'|'ferry'|'train'). Dipakai via:
 *   $tsMode = 'flight'; require 'includes/homepage/transport-search.php';
 *   $tsMode = 'ferry';  require 'includes/homepage/transport-search.php';
 * flight-hero.php kini shim ke file ini (mode flight) agar ID E2E tetap sama.
 * Field legacy dipertahankan: from/to/date/return_date/passengers/class/search,
 * tripTypeHidden + trip-type-btn (flight), from_pid/from_spid/to_pid/to_spid (ferry).
 */
require_once __DIR__ . '/../components/date-picker.php';
$tsMode = $tsMode ?? 'flight';
if (!in_array($tsMode, ['flight', 'ferry', 'train'], true)) $tsMode = 'flight';
$isFlight = $tsMode === 'flight';
$isFerry = $tsMode === 'ferry';
$isTrain = $tsMode === 'train';

$tsAction = $tsAction ?? ($isFerry ? 'ferries.php' : ($isTrain ? 'trains.php' : 'flights.php'));
$tsFormId = $tsFormId ?? ($isFerry ? 'ferrySearchForm' : ($isTrain ? 'trainSearchForm' : 'flightSearchForm'));
$tsAutocomplete = $tsAutocomplete ?? ($isFerry ? 'ajax/ferry-place-search.php' : 'city-search-ajax.php');
$tsSearchClass = $tsSearchClass ?? ($isFerry ? 'ferry-search' : 'city-search');
$tsShowTrip = $tsShowTrip ?? $isFlight;
$tsShowClass = $tsShowClass ?? ($isFlight || $isTrain);
$tsShowMulti = $tsShowMulti ?? $isFlight;
$tsShowCal = $tsShowCal ?? $isFlight;

$tsTrip = $tsTrip ?? $tripType ?? ($_GET['trip_type'] ?? 'oneway');
if (!in_array($tsTrip, ['oneway', 'roundtrip', 'multicity'], true)) $tsTrip = 'oneway';
$tsFrom = $tsFrom ?? $from ?? $routeFrom ?? ($_GET['from'] ?? '');
$tsTo = $tsTo ?? $to ?? $routeTo ?? ($_GET['to'] ?? '');
$tsDate = $tsDate ?? $date ?? ($_GET['date'] ?? date('Y-m-d', strtotime('+3 days')));
$tsReturn = $tsReturn ?? $returnDate ?? ($_GET['return_date'] ?? '');
$tsPax = $tsPax ?? $passengers ?? max(1, min(9, (int)($_GET['passengers'] ?? 1)));
$tsClass = $tsClass ?? $class ?? ($_GET['class'] ?? '');
$tsLegs = $tsLegs ?? $legs ?? [];
$tsError = $tsError ?? $duffelError ?? $easybookError ?? null;
$tsCal = $tsCal ?? $flightCal ?? [];
$tsFromPid = $tsFromPid ?? $fromPlaceId ?? (int)($_GET['from_pid'] ?? 0);
$tsFromSpid = $tsFromSpid ?? $fromSubPlace ?? (int)($_GET['from_spid'] ?? 0);
$tsToPid = $tsToPid ?? $toPlaceId ?? (int)($_GET['to_pid'] ?? 0);
$tsToSpid = $tsToSpid ?? $toSubPlace ?? (int)($_GET['to_spid'] ?? 0);

$tsDefaultBg = $isFerry
    ? 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=2070&q=80'
    : ($isTrain
        ? 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&w=2070&q=80'
        : 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2070&q=80');
$tsBg = $tsBg ?? (($heroSlides[0]['image'] ?? '') ?: $tsDefaultBg);
$tsTitleB = $tsTitleB ?? ($isFerry ? 'Ferry' : ($isTrain ? 'Train' : 'Flight'));
$tsSub = $tsSub ?? ($isFerry ? t('Pesan tiket ferry — booking instan, harga terbaik.') : ($isTrain ? t('Tiket kereta pilihan — booking instan, harga terbaik.') : t('Tiket pesawat pilihan — booking instan, harga terbaik.')));
$tsProof = $tsProof ?? ($isFerry ? t('ferries') : ($isTrain ? t('trains') : t('flights')));
$tsChips = $tsChips ?? ($isFerry
    ? [['l' => 'Batam → Singapore', 'u' => 'ferries.php?from=Batam&to=Singapore&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1'], ['l' => 'Johor → Batam', 'u' => 'ferries.php?from=Johor&to=Batam&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1'], ['l' => 'Merak → Bakauheni', 'u' => 'ferries.php?from=Merak&to=Bakauheni&date=' . date('Y-m-d', strtotime('+7 days')) . '&search=1']]
    : ($isTrain
        ? [['l' => 'Jakarta → Bandung', 'u' => 'trains.php?from=Jakarta&to=Bandung'], ['l' => 'Jakarta → Surabaya', 'u' => 'trains.php?from=Jakarta&to=Surabaya'], ['l' => 'Yogyakarta → Jakarta', 'u' => 'trains.php?from=Yogyakarta&to=Jakarta']]
        : [['l' => 'Jakarta → Denpasar', 'u' => 'flights.php?from=' . urlencode('Jakarta (CGK)') . '&to=' . urlencode('Denpasar (DPS)') . '&date=' . date('Y-m-d') . '&search=1'], ['l' => 'Jakarta → Singapore', 'u' => 'flights.php?from=' . urlencode('Jakarta (CGK)') . '&to=' . urlencode('Singapore (SIN)') . '&date=' . date('Y-m-d') . '&search=1'], ['l' => 'Jakarta → Tokyo', 'u' => 'flights.php?from=' . urlencode('Jakarta (CGK)') . '&to=' . urlencode('Tokyo (NRT)') . '&date=' . date('Y-m-d') . '&search=1']]));
$tsPhFrom = $tsPhFrom ?? ($isFerry ? t('Kota atau terminal') : t('Kota atau bandara'));
$tsPhTo = $tsPhTo ?? $tsPhFrom;
?>
<style>
.voyage-transport-hero{overflow:clip}
.voyage-transport-hero .ts-aurora{position:absolute;border-radius:50%;filter:blur(90px);pointer-events:none}
.voyage-transport-hero .ts-aurora-a{top:-30%;left:-20%;width:80%;height:80%;opacity:.25;background:radial-gradient(60% 60% at 50% 50%,#7DD3FC 0%,#38BDF8 20%,transparent 70%);animation:tsAurora 18s ease-in-out infinite}
.voyage-transport-hero .ts-aurora-b{top:-20%;right:-10%;width:70%;height:70%;opacity:.2;background:radial-gradient(60% 60% at 50% 50%,#A78BFA 0%,#8B5CF6 25%,transparent 70%);animation:tsAurora 22s ease-in-out infinite reverse}
.voyage-transport-hero .ts-aurora-c{top:30%;left:10%;width:60%;height:50%;opacity:.14;background:radial-gradient(60% 60% at 50% 50%,#7DD3FC 0%,#A78BFA 40%,transparent 70%)}
@keyframes tsAurora{0%,100%{transform:translate(-10%,-10%) scale(1)}50%{transform:translate(5%,5%) scale(1.1)}}
.voyage-transport-hero .voyage-inner{padding-top:150px;padding-bottom:8px}
.voyage-transport-hero .voyage-sub{max-width:560px}
.voyage-transport-hero .flight-glass{position:relative;max-width:1000px;background:rgba(255,255,255,.72);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(13,110,253,.18);border-radius:24px;box-shadow:0 12px 40px rgba(13,110,253,.14),inset 0 1px 0 rgba(255,255,255,.9);padding:0;overflow:hidden}
.voyage-transport-hero .flight-glass::before{content:'';position:absolute;top:0;left:1px;right:1px;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.9) 20%,#fff 50%,rgba(255,255,255,.9) 80%,transparent);z-index:3;pointer-events:none}
.voyage-transport-hero .booking-tabs{display:flex;gap:4px;padding:18px 22px 0;border-bottom:1px solid #E4E7EE;overflow-x:auto;scrollbar-width:none}
.voyage-transport-hero .booking-tabs::-webkit-scrollbar{display:none}
.voyage-transport-hero .booking-tab{display:flex;align-items:center;gap:7px;padding:10px 18px 12px;font-size:14px;font-weight:600;color:#8B90A0;text-decoration:none;white-space:nowrap;border-bottom:2.5px solid transparent}
.voyage-transport-hero .booking-tab:hover{color:#1A1A2E}
.voyage-transport-hero .booking-tab.active{color:#0064D2;border-bottom-color:#0064D2}
.voyage-transport-hero .booking-form{padding:20px 22px 22px}
.voyage-transport-hero .form-row-options{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.voyage-transport-hero .trip-type-group{display:flex;background:#F5F7FA;border:1px solid #E4E7EE;border-radius:100px;padding:3px;gap:2px}
.voyage-transport-hero .trip-type-btn{padding:7px 16px;border:0;background:transparent;font-size:13px;font-weight:600;color:#5A6178;border-radius:100px;cursor:pointer;white-space:nowrap}
.voyage-transport-hero .trip-type-btn.active{background:#fff;color:#0064D2;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.voyage-transport-hero .custom-select{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#F5F7FA;border:1px solid #E4E7EE;border-radius:100px;font-size:13px;color:#5A6178;cursor:pointer}
.voyage-transport-hero .custom-select svg{width:14px;height:14px;color:#8B90A0}
.voyage-transport-hero .custom-select select{appearance:none;-webkit-appearance:none;border:0;background:transparent;font-size:13px;color:#1A1A2E;cursor:pointer;outline:0}
.voyage-transport-hero .form-search-row{display:flex;align-items:stretch;gap:0;background:#F5F7FA;border:1.5px solid #E4E7EE;border-radius:16px;transition:border-color .2s}
.voyage-transport-hero .form-search-row:focus-within{border-color:#0064D2;box-shadow:0 0 0 3px rgba(0,100,210,.08)}
.voyage-transport-hero .search-field{flex:1;display:flex;flex-direction:column;justify-content:center;padding:12px 18px;border-right:1px solid #E4E7EE;min-width:0;position:relative}
.voyage-transport-hero .search-field:last-of-type{border-right:0}
.voyage-transport-hero .search-field-label{font-size:11px;font-weight:600;color:#8B90A0;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;display:flex;align-items:center;gap:5px}
.voyage-transport-hero .search-field-label svg{width:13px;height:13px;color:#0064D2}
.voyage-transport-hero .search-field input{border:0;background:transparent;font-size:14px;font-weight:600;color:#1A1A2E;width:100%;outline:0}
.voyage-transport-hero .search-field input::placeholder{color:#5A6178;font-weight:500}
.voyage-transport-hero .search-field select{border:0;background:transparent;font-size:14px;font-weight:600;color:#1A1A2E;width:100%;outline:0;appearance:none;-webkit-appearance:none;cursor:pointer}
.voyage-transport-hero .search-dropdown{position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid #E4E7EE;border-radius:14px;z-index:300;display:none;overflow:hidden;box-shadow:0 20px 60px rgba(13,110,253,.18)}
.voyage-transport-hero .search-dropdown.show{display:block}
.voyage-transport-hero .search-dropdown .search-item{display:flex;align-items:center;gap:10px;padding:10px 12px;cursor:pointer;color:#1A1A2E;font-size:13px}
.voyage-transport-hero .search-dropdown .search-item:hover{background:rgba(13,110,253,.08)}
.voyage-transport-hero .swap-btn{flex:none;align-self:center;background:#fff;border:1px solid #E4E7EE;border-radius:50%;width:38px;height:38px;margin:0 4px;cursor:pointer;color:#0064D2;display:flex;align-items:center;justify-content:center}
.voyage-transport-hero .swap-btn-inner svg{width:16px;height:16px}
.voyage-transport-hero .search-btn{flex:none;align-self:stretch;margin:8px;border:0;border-radius:12px;padding:0 26px;font-size:14px;font-weight:700;color:#fff;background:#0d6efd;cursor:pointer;display:flex;align-items:center;gap:8px;justify-content:center;box-shadow:0 8px 24px rgba(13,110,253,.35)}
.voyage-transport-hero .search-btn svg{width:16px;height:16px}
.voyage-transport-hero .search-btn:hover{background:#0b5ed7}
.voyage-transport-hero .voyage-chips{display:flex;gap:8px;margin-top:18px;flex-wrap:wrap}
.voyage-transport-hero .voyage-chips a{padding:8px 18px;border-radius:999px;font-size:13px;font-weight:500;text-decoration:none;background:rgba(255,255,255,.7);border:1px solid rgba(13,110,253,.18);color:#33465f;backdrop-filter:blur(12px)}
.voyage-transport-hero .voyage-chips a:hover{background:#fff;color:#0d1b33}
[data-theme="dark"] .voyage-transport-hero .flight-glass{background:linear-gradient(180deg,rgba(255,255,255,.12) 0%,rgba(255,255,255,.04) 100%);border-color:rgba(255,255,255,.16);box-shadow:inset 0 1px 0 0 rgba(255,255,255,.18),inset 0 -1px 0 0 rgba(255,255,255,.04),0 20px 60px -20px rgba(125,211,252,.3),0 0 80px rgba(167,139,250,.15)}
[data-theme="dark"] .voyage-transport-hero .booking-tabs{border-color:rgba(255,255,255,.1)}
[data-theme="dark"] .voyage-transport-hero .booking-tab{color:rgba(255,255,255,.55)}
[data-theme="dark"] .voyage-transport-hero .booking-tab:hover{color:#fff}
[data-theme="dark"] .voyage-transport-hero .booking-tab.active{color:#7DD3FC;border-bottom-color:#7DD3FC}
[data-theme="dark"] .voyage-transport-hero .trip-type-group{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
[data-theme="dark"] .voyage-transport-hero .trip-type-btn{color:rgba(255,255,255,.6)}
[data-theme="dark"] .voyage-transport-hero .trip-type-btn.active{background:rgba(255,255,255,.14);color:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3)}
[data-theme="dark"] .voyage-transport-hero .custom-select{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:rgba(255,255,255,.75)}
[data-theme="dark"] .voyage-transport-hero .custom-select select{color:#fff}
[data-theme="dark"] .voyage-transport-hero .custom-select select option{color:#111}
[data-theme="dark"] .voyage-transport-hero .form-search-row{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1)}
[data-theme="dark"] .voyage-transport-hero .form-search-row:focus-within{border-color:rgba(125,211,252,.5);box-shadow:0 0 0 3px rgba(125,211,252,.15)}
[data-theme="dark"] .voyage-transport-hero .search-field{border-color:rgba(255,255,255,.1)}
[data-theme="dark"] .voyage-transport-hero .search-field-label{color:rgba(255,255,255,.5)}
[data-theme="dark"] .voyage-transport-hero .search-field-label svg{color:#7DD3FC}
[data-theme="dark"] .voyage-transport-hero .search-field input,[data-theme="dark"] .voyage-transport-hero .search-field select{color:#fff}
[data-theme="dark"] .voyage-transport-hero .search-field input::placeholder{color:rgba(255,255,255,.4)}
[data-theme="dark"] .voyage-transport-hero .search-field input[type=date]{color-scheme:dark}
[data-theme="dark"] .voyage-transport-hero .search-dropdown{background:rgba(13,18,32,.97);border-color:rgba(255,255,255,.14);box-shadow:0 20px 60px rgba(0,0,0,.5)}
[data-theme="dark"] .voyage-transport-hero .search-dropdown .search-item{color:#fff}
[data-theme="dark"] .voyage-transport-hero .search-dropdown .search-item:hover{background:rgba(125,211,252,.12)}
[data-theme="dark"] .voyage-transport-hero .swap-btn{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.14);color:#7DD3FC}
[data-theme="dark"] .voyage-transport-hero .search-btn{color:#05070D;background:linear-gradient(135deg,#7DD3FC,#A78BFA);box-shadow:0 8px 24px rgba(125,211,252,.35)}
[data-theme="dark"] .voyage-transport-hero .voyage-chips a{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.12);color:rgba(255,255,255,.75)}
[data-theme="dark"] .voyage-transport-hero .voyage-chips a:hover{background:rgba(255,255,255,.12);color:#fff}
@media(max-width:768px){.voyage-transport-hero .form-search-row{flex-direction:column}.voyage-transport-hero .search-field{border-right:0;border-bottom:1px solid #E4E7EE}.voyage-transport-hero .search-btn{margin:8px;min-height:48px}.voyage-transport-hero .swap-btn{transform:rotate(90deg)}}
</style>
<section class="voyage-hero voyage-transport-hero voyage-flight-hero">
    <div class="voyage-bg">
        <div class="voyage-bg-img" style="background-image:url('<?= e($tsBg) ?>')"></div>
        <div class="voyage-bg-grad"></div>
        <div class="voyage-bg-glow"></div>
        <div class="ts-aurora ts-aurora-a"></div>
        <div class="ts-aurora ts-aurora-b"></div>
        <div class="ts-aurora ts-aurora-c"></div>
    </div>
    <div class="voyage-inner">
        <h1 class="voyage-title"><?= t('Find Your') ?><br><span class="serif voyage-title-accent">Perfect</span> <?= t($tsTitleB) ?></h1>
        <p class="voyage-sub"><?= e($tsSub) ?></p>
        <div class="flight-glass">
            <div class="booking-tabs" role="tablist">
                <a href="flights.php" class="booking-tab <?= $isFlight ? 'active' : '' ?>" role="tab"><i class="bi bi-airplane"></i> <?= t('Pesawat') ?></a>
                <a href="ferries.php" class="booking-tab <?= $isFerry ? 'active' : '' ?>" role="tab"><i class="bi bi-water"></i> <?= t('Ferry') ?></a>
                <a href="trains.php" class="booking-tab <?= $isTrain ? 'active' : '' ?>" role="tab"><i class="bi bi-train-front"></i> <?= t('Kereta') ?></a>
                <a href="rental-cars.php" class="booking-tab" role="tab"><i class="bi bi-car-front"></i> <?= t('Rental') ?></a>
            </div>
            <div class="booking-form">
                <?php if ($tsError): ?>
                <div class="alert alert-warning py-2 small mb-3"><?= e($tsError) ?></div>
                <?php endif; ?>
                <form method="GET" action="<?= e($tsAction) ?>" id="<?= e($tsFormId) ?>" data-autocomplete="<?= e($tsAutocomplete) ?>">
                    <?php if ($tsShowTrip): ?>
                    <div class="form-row-options">
                        <div class="trip-type-group">
                            <button type="button" class="trip-type-btn <?= $tsTrip === 'roundtrip' ? 'active' : '' ?>" data-type="roundtrip"><?= t('Pulang Pergi') ?></button>
                            <button type="button" class="trip-type-btn <?= $tsTrip === 'oneway' ? 'active' : '' ?>" data-type="oneway"><?= t('Sekali Jalan') ?></button>
                            <button type="button" class="trip-type-btn <?= $tsTrip === 'multicity' ? 'active' : '' ?>" data-type="multicity"><?= t('Multi-Kota') ?></button>
                        </div>
                        <input type="hidden" name="trip_type" value="<?= e($tsTrip) ?>" id="<?= $isFlight ? 'tripTypeHidden' : 'tsTripTypeHidden' ?>">
                        <div class="custom-select">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <select name="passengers">
                                <?php for ($p = 1; $p <= 9; $p++): ?><option value="<?= $p ?>" <?= (int)$tsPax === $p ? 'selected' : '' ?>><?= $p ?> <?= t('orang') ?></option><?php endfor; ?>
                            </select>
                        </div>
                        <?php if ($tsShowClass): ?>
                        <div class="custom-select">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <select name="class">
                                <option value=""><?= t('Semua Kelas') ?></option>
                                <option value="economy" <?= $tsClass === 'economy' ? 'selected' : '' ?>><?= t('Ekonomi') ?></option>
                                <option value="business" <?= $tsClass === 'business' ? 'selected' : '' ?>><?= t('Bisnis') ?></option>
                                <option value="first" <?= $tsClass === 'first' ? 'selected' : '' ?>><?= t('First Class') ?></option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($tsShowMulti): ?>
                    <div id="tsMultiCityLegs" class="mb-3 <?= $tsTrip === 'multicity' ? '' : 'd-none' ?>" data-testid="multicity-legs">
                        <div id="tsLegContainer">
                            <?php if (!empty($tsLegs)): foreach ($tsLegs as $lg): ?>
                            <div class="row g-2 mb-2 leg-row">
                                <div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_from[]" placeholder="<?= t('Dari (CGK)...') ?>" value="<?= e($lg['origin'] ?? '') ?>" autocomplete="off"></div>
                                <div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_to[]" placeholder="<?= t('Ke (DPS)...') ?>" value="<?= e($lg['destination'] ?? '') ?>" autocomplete="off"></div>
                                <div class="col-md-3"><?php renderDatePicker(['name' => 'leg_date[]', 'value' => $lg['departure_date'] ?? '', 'cls' => 'form-control form-control-sm', 'min' => 'today', 'bare' => true]); ?></div>
                                <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 leg-del">×</button></div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="tsAddLegBtn" data-testid="add-leg"><i class="bi bi-plus-lg me-1"></i><?= t('Tambah leg') ?></button>
                        <div class="form-text"><?= t('Maksimal 6 leg.') ?></div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <div class="form-search-row">
                        <div class="search-field voyage-field">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Dari') ?></span>
                            <input type="text" name="from" class="ts-search <?= e($tsSearchClass) ?>" placeholder="<?= e($tsPhFrom) ?>" value="<?= e($tsFrom) ?>" autocomplete="off" data-target="tsFromDropdown" id="<?= $isFlight ? 'fromInput' : 'tsFromInput' ?>">
                            <div class="search-dropdown" id="<?= $isFlight ? 'fromDropdown' : 'tsFromDropdown' ?>"></div>
                            <?php if ($isFerry): ?>
                            <input type="hidden" name="from_pid" value="<?= (int)$tsFromPid ?>">
                            <input type="hidden" name="from_spid" value="<?= (int)$tsFromSpid ?>">
                            <?php endif; ?>
                        </div>
                        <button type="button" class="swap-btn" aria-label="Tukar"><span class="swap-btn-inner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16l-4-4 4-4"/><path d="M17 8l4 4-4 4"/><line x1="3" y1="12" x2="21" y2="12"/></svg></span></button>
                        <div class="search-field voyage-field">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Ke') ?></span>
                            <input type="text" name="to" class="ts-search <?= e($tsSearchClass) ?>" placeholder="<?= e($tsPhTo) ?>" value="<?= e($tsTo) ?>" autocomplete="off" data-target="tsToDropdown" id="<?= $isFlight ? 'toInput' : 'tsToInput' ?>">
                            <div class="search-dropdown" id="<?= $isFlight ? 'toDropdown' : 'tsToDropdown' ?>"></div>
                            <?php if ($isFerry): ?>
                            <input type="hidden" name="to_pid" value="<?= (int)$tsToPid ?>">
                            <input type="hidden" name="to_spid" value="<?= (int)$tsToSpid ?>">
                            <?php endif; ?>
                        </div>
                        <?php if ($tsShowTrip): ?>
                        <div class="search-field voyage-field return-date-col" style="<?= $tsTrip === 'roundtrip' ? '' : 'display:none' ?>">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= t('Tanggal Pulang') ?></span>
                            <?php renderDatePicker(['name' => 'return_date', 'value' => $tsReturn, 'min' => $tsDate, 'max' => date('Y-m-d', strtotime('+360 days')), 'bare' => true]); ?>
                        </div>
                        <?php endif; ?>
                        <div class="search-field voyage-field">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= ($tsShowTrip && $tsTrip === 'roundtrip') ? t('Tanggal Pergi') : t('Tanggal') ?></span>
                            <?php renderDatePicker(['name' => 'date', 'value' => $tsDate, 'min' => 'today', 'max' => date('Y-m-d', strtotime('+360 days')), 'bare' => true, 'prices' => $tsCal, 'priceBase' => 'avg', 'resultId' => 'tsCalHint', 'resultBaseLabel' => t('Harga termurah')]); ?>
                            <?php if ($tsShowCal && !empty($tsCal)): ?>
                            <div class="small fw-semibold mt-1" id="tsCalHint" data-testid="flight-cal-hint"></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!$tsShowTrip): ?>
                        <div class="search-field voyage-field">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> <?= t('Penumpang') ?></span>
                            <select name="passengers">
                                <?php for ($p = 1; $p <= 9; $p++): ?><option value="<?= $p ?>" <?= (int)$tsPax === $p ? 'selected' : '' ?>><?= $p ?> <?= t('orang') ?></option><?php endfor; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <button class="search-btn" type="submit" name="search" value="1">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <?= t('Cari') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="voyage-proof"><span class="voyage-proof-rate"><i>★</i> 4.9 • 2M+ <?= e($tsProof) ?></span><span class="voyage-proof-sub"><?= t('Dipercaya traveler') ?></span></div>
        <div class="voyage-chips">
            <?php foreach ($tsChips as $chip): ?>
            <a href="<?= e($chip['u']) ?>"><?= e($chip['l']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('<?= $tsFormId ?>');
    if (!form || form.dataset.tsInit) return;
    form.dataset.tsInit = '1';
    var hidden = form.querySelector('input[name=trip_type]');
    var legsBox = form.querySelector('#tsMultiCityLegs');
    var legBox = form.querySelector('#tsLegContainer');
    function syncTrip() {
        if (!hidden) return;
        var tt = hidden.value || 'oneway';
        form.querySelectorAll('.return-date-col').forEach(function (c) { c.style.display = tt === 'roundtrip' ? '' : 'none'; });
        if (legsBox) legsBox.classList.toggle('d-none', tt !== 'multicity');
        if (legBox) legBox.querySelectorAll('input').forEach(function (i) { i.disabled = tt !== 'multicity'; });
    }
    form.querySelectorAll('.trip-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (hidden) hidden.value = btn.dataset.type;
            form.querySelectorAll('.trip-type-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            syncTrip();
        });
    });
    var swap = form.querySelector('.swap-btn');
    if (swap) swap.addEventListener('click', function () {
        var f = form.querySelector('[name=from]'), t = form.querySelector('[name=to]');
        if (f && t) { var tmp = f.value; f.value = t.value; t.value = tmp; }
        var fp = form.querySelector('[name=from_pid]'), tp = form.querySelector('[name=to_pid]');
        var fs = form.querySelector('[name=from_spid]'), ts = form.querySelector('[name=to_spid]');
        if (fp && tp) { var ptmp = fp.value; fp.value = tp.value; tp.value = ptmp; }
        if (fs && ts) { var stmp = fs.value; fs.value = ts.value; ts.value = stmp; }
    });
    function bindDel(scope) {
        scope.querySelectorAll('.leg-del').forEach(function (b) {
            if (b.dataset.tsBind) return; b.dataset.tsBind = '1';
            b.addEventListener('click', function () { b.closest('.leg-row').remove(); });
        });
    }
    function addLeg(fv, tv, dv) {
        if (!legBox) return;
        if (legBox.querySelectorAll('.leg-row').length >= 6) return;
        var row = document.createElement('div');
        row.className = 'row g-2 mb-2 leg-row';
        row.innerHTML = '<div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_from[]" placeholder="<?= t('Dari (CGK)...') ?>" value="' + (fv || '').replace(/"/g, '&quot;') + '" autocomplete="off"></div>'
            + '<div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_to[]" placeholder="<?= t('Ke (DPS)...') ?>" value="' + (tv || '').replace(/"/g, '&quot;') + '" autocomplete="off"></div>'
            + '<div class="col-md-3"><input type="text" class="form-control form-control-sm dp-flat" name="leg_date[]" value="' + (dv || '') + '" placeholder="YYYY-MM-DD" autocomplete="off" data-dp-mode="single" data-dp-months="1" data-dp-min="<?= date('Y-m-d') ?>"></div>'
            + '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 leg-del">×</button></div>';
        legBox.appendChild(row);
        bindDel(row);
    }
    if (legBox) {
        bindDel(legBox);
        if (legBox.querySelectorAll('.leg-row').length === 0 && hidden && hidden.value === 'multicity') { addLeg('', '', ''); addLeg('', '', ''); }
        var addBtn = form.querySelector('#tsAddLegBtn');
        if (addBtn && !addBtn.dataset.tsBind) { addBtn.dataset.tsBind = '1'; addBtn.addEventListener('click', function () { addLeg('', '', ''); }); }
    }
    syncTrip();
    var endpoint = form.getAttribute('data-autocomplete') || 'city-search-ajax.php';
    form.querySelectorAll('.ts-search').forEach(function (input) {
        var dropdownId = input.getAttribute('data-target');
        var dropdown = dropdownId ? document.getElementById(dropdownId) : null;
        if (!dropdown) return;
        var debounce;
        input.addEventListener('input', function () {
            clearTimeout(debounce);
            var q = this.value.trim();
            var field = input.closest('.search-field');
            var pidH = field ? field.querySelector('input[name$="_pid"]') : null;
            var spidH = field ? field.querySelector('input[name$="_spid"]') : null;
            if (pidH) pidH.value = '0';
            if (spidH) spidH.value = '0';
            if (q.length < 1) { dropdown.classList.remove('show'); return; }
            debounce = setTimeout(function () {
                fetch(endpoint + '?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.length) { dropdown.classList.remove('show'); return; }
                        var html = '';
                        data.forEach(function (item) {
                            var pid = item.pid || 0, spid = item.spid || 0;
                            html += '<div class="search-item" data-label="' + String(item.label).replace(/"/g, '&quot;') + '" data-pid="' + pid + '" data-spid="' + spid + '"><div class="search-icon bg-light text-primary"><i class="bi bi-geo-alt"></i></div><div class="fw-semibold small">' + String(item.label).replace(/</g, '&lt;') + '</div></div>';
                        });
                        dropdown.innerHTML = html;
                        dropdown.classList.add('show');
                        dropdown.querySelectorAll('.search-item').forEach(function (el) {
                            el.addEventListener('click', function () {
                                input.value = this.getAttribute('data-label');
                                if (pidH) pidH.value = this.getAttribute('data-pid') || '0';
                                if (spidH) spidH.value = this.getAttribute('data-spid') || '0';
                                dropdown.classList.remove('show');
                            });
                        });
                    });
            }, 200);
        });
        document.addEventListener('click', function (e) {
            var wrapper = input.closest('.search-field') || input.closest('.search-wrapper');
            if (wrapper && !wrapper.contains(e.target)) dropdown.classList.remove('show');
        });
    });
    // Hint harga ditangani komponen date-picker via resultId tsCalHint.
});
</script>

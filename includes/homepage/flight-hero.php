<?php
/**
 * Flight Hero - Voyage dark liquid-glass ala referensi Liquid-Glass-Hero-Tiket.
 * Header tetap (header-shared). Hero text ikut gaya hotels/landing:
 * voyage-title serif accent + voyage-sub + voyage-search pill.
 * Form GET kompatibel flights.php: trip_type/from/to/date/return_date/
 * passengers/class/legs + IDs untuk E2E (flightSearchForm, tripTypeHidden...).
 * Dipakai flights.php (!\$doSearch) & index.php (site_focus=flight).
 */
$fhTripType = $tripType ?? ($_GET['trip_type'] ?? 'oneway');
if (!in_array($fhTripType, ['oneway', 'roundtrip', 'multicity'], true)) $fhTripType = 'oneway';
$fhFrom = $from ?? ($_GET['from'] ?? '');
$fhTo = $to ?? ($_GET['to'] ?? '');
$fhDate = $date ?? ($_GET['date'] ?? date('Y-m-d', strtotime('+3 days')));
$fhReturn = $returnDate ?? ($_GET['return_date'] ?? '');
$fhPax = $passengers ?? max(1, min(9, (int)($_GET['passengers'] ?? 1)));
$fhClass = $class ?? ($_GET['class'] ?? '');
$fhError = $duffelError ?? null;
$fhCal = $flightCal ?? [];
$fhLegs = $legs ?? [];
$fhBg = ($heroSlides[0]['image'] ?? '') ?: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2070&q=80';

?>
<style>
.voyage-flight-hero{background:#05070D;color:#fff;overflow:clip}
.voyage-flight-hero .voyage-bg{position:absolute;inset:0}
.voyage-flight-hero .voyage-bg-img{opacity:.5}
.voyage-flight-hero .voyage-bg-grad{background:linear-gradient(180deg,rgba(5,7,13,.55) 0%,rgba(5,7,13,.35) 40%,rgba(5,7,13,.88) 100%)}
.voyage-flight-hero .voyage-bg-glow{background:none;opacity:1}
.voyage-flight-hero .fh-aurora{position:absolute;border-radius:50%;filter:blur(90px);pointer-events:none}
.voyage-flight-hero .fh-aurora-a{top:-30%;left:-20%;width:80%;height:80%;opacity:.35;background:radial-gradient(60% 60% at 50% 50%,#7DD3FC 0%,#38BDF8 20%,transparent 70%);animation:fhAurora 18s ease-in-out infinite}
.voyage-flight-hero .fh-aurora-b{top:-20%;right:-10%;width:70%;height:70%;opacity:.3;background:radial-gradient(60% 60% at 50% 50%,#A78BFA 0%,#8B5CF6 25%,transparent 70%);animation:fhAurora 22s ease-in-out infinite reverse}
.voyage-flight-hero .fh-aurora-c{top:30%;left:10%;width:60%;height:50%;opacity:.2;background:radial-gradient(60% 60% at 50% 50%,#7DD3FC 0%,#A78BFA 40%,transparent 70%)}
@keyframes fhAurora{0%,100%{transform:translate(-10%,-10%) scale(1)}50%{transform:translate(5%,5%) scale(1.1)}}
.voyage-flight-hero .voyage-inner{padding-top:150px;padding-bottom:8px}
.voyage-flight-hero .voyage-title{color:#fff}
.voyage-flight-hero .voyage-title-accent{color:#fff}
.voyage-flight-hero .voyage-sub{color:rgba(255,255,255,.65);max-width:560px}
.voyage-flight-hero .voyage-proof-rate{color:#fff}
.voyage-flight-hero .voyage-proof-sub{color:rgba(255,255,255,.5)}
.voyage-flight-hero .flight-glass{position:relative;max-width:1000px;background:linear-gradient(180deg,rgba(255,255,255,.12) 0%,rgba(255,255,255,.04) 100%);backdrop-filter:blur(40px) saturate(160%);-webkit-backdrop-filter:blur(40px) saturate(160%);border:1px solid rgba(255,255,255,.16);border-radius:24px;box-shadow:inset 0 1px 0 0 rgba(255,255,255,.18),inset 0 -1px 0 0 rgba(255,255,255,.04),0 20px 60px -20px rgba(125,211,252,.3),0 0 80px rgba(167,139,250,.15);padding:0;overflow:hidden}
.voyage-flight-hero .flight-glass::before{content:'';position:absolute;top:0;left:1px;right:1px;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.5) 20%,rgba(255,255,255,.8) 50%,rgba(255,255,255,.5) 80%,transparent);z-index:3;pointer-events:none}
.voyage-flight-hero .booking-tabs{display:flex;gap:4px;padding:18px 22px 0;border-bottom:1px solid rgba(255,255,255,.1);overflow-x:auto;scrollbar-width:none}
.voyage-flight-hero .booking-tabs::-webkit-scrollbar{display:none}
.voyage-flight-hero .booking-tab{display:flex;align-items:center;gap:7px;padding:10px 18px 12px;font-size:14px;font-weight:600;color:rgba(255,255,255,.55);text-decoration:none;white-space:nowrap;border-bottom:2.5px solid transparent}
.voyage-flight-hero .booking-tab:hover{color:#fff}
.voyage-flight-hero .booking-tab.active{color:#7DD3FC;border-bottom-color:#7DD3FC}
.voyage-flight-hero .booking-form{padding:20px 22px 22px}
.voyage-flight-hero .form-row-options{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.voyage-flight-hero .trip-type-group{display:flex;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:100px;padding:3px;gap:2px}
.voyage-flight-hero .trip-type-btn{padding:7px 16px;border:0;background:transparent;font-size:13px;font-weight:600;color:rgba(255,255,255,.6);border-radius:100px;cursor:pointer;white-space:nowrap}
.voyage-flight-hero .trip-type-btn.active{background:rgba(255,255,255,.14);color:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3)}
.voyage-flight-hero .custom-select{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:100px;font-size:13px;color:rgba(255,255,255,.75);cursor:pointer}
.voyage-flight-hero .custom-select svg{width:14px;height:14px;opacity:.6}
.voyage-flight-hero .custom-select select{appearance:none;-webkit-appearance:none;border:0;background:transparent;font-size:13px;color:#fff;cursor:pointer;outline:0}
.voyage-flight-hero .custom-select select option{color:#111}
.voyage-flight-hero .form-search-row{display:flex;align-items:stretch;gap:0;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:16px;transition:border-color .2s}
.voyage-flight-hero .form-search-row:focus-within{border-color:rgba(125,211,252,.5);box-shadow:0 0 0 3px rgba(125,211,252,.15)}
.voyage-flight-hero .search-field{flex:1;display:flex;flex-direction:column;justify-content:center;padding:12px 18px;border-right:1px solid rgba(255,255,255,.1);min-width:0;position:relative}
.voyage-flight-hero .search-field:last-of-type{border-right:0}
.voyage-flight-hero .search-field-label{font-size:11px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;display:flex;align-items:center;gap:5px}
.voyage-flight-hero .search-field-label svg{width:13px;height:13px;color:#7DD3FC}
.voyage-flight-hero .search-field input{border:0;background:transparent;font-size:14px;font-weight:600;color:#fff;width:100%;outline:0}
.voyage-flight-hero .search-field input::placeholder{color:rgba(255,255,255,.4);font-weight:500}
.voyage-flight-hero .search-field input[type=date]{color-scheme:dark}
.voyage-flight-hero .search-dropdown{position:absolute;top:calc(100% + 6px);left:0;right:0;background:rgba(13,18,32,.97);border:1px solid rgba(255,255,255,.14);border-radius:14px;z-index:300;display:none;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.voyage-flight-hero .search-dropdown.show{display:block}
.voyage-flight-hero .search-dropdown .search-item{display:flex;align-items:center;gap:10px;padding:10px 12px;cursor:pointer;color:#fff;font-size:13px}
.voyage-flight-hero .search-dropdown .search-item:hover{background:rgba(125,211,252,.12)}
.voyage-flight-hero .swap-btn{flex:none;align-self:center;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:50%;width:38px;height:38px;margin:0 4px;cursor:pointer;color:#7DD3FC;display:flex;align-items:center;justify-content:center}
.voyage-flight-hero .swap-btn-inner svg{width:16px;height:16px}
.voyage-flight-hero .search-btn{flex:none;align-self:stretch;margin:8px;border:0;border-radius:12px;padding:0 26px;font-size:14px;font-weight:700;color:#05070D;background:linear-gradient(135deg,#7DD3FC,#A78BFA);cursor:pointer;display:flex;align-items:center;gap:8px;justify-content:center;box-shadow:0 8px 24px rgba(125,211,252,.35)}
.voyage-flight-hero .search-btn svg{width:16px;height:16px}
.voyage-flight-hero .search-btn:hover{filter:brightness(1.08)}
.voyage-flight-hero #multiCityLegs .form-text{color:rgba(255,255,255,.45)}
.voyage-flight-hero #multiCityLegs input{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);color:#fff}
.voyage-flight-hero #multiCityLegs input::placeholder{color:rgba(255,255,255,.4)}
.voyage-flight-hero .voyage-chips{display:flex;gap:8px;margin-top:18px;flex-wrap:wrap}
.voyage-flight-hero .voyage-chips a{padding:8px 18px;border-radius:999px;font-size:13px;font-weight:500;text-decoration:none;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.75);backdrop-filter:blur(12px)}
.voyage-flight-hero .voyage-chips a:hover{background:rgba(255,255,255,.12);color:#fff}
@media(max-width:768px){.voyage-flight-hero .form-search-row{flex-direction:column}.voyage-flight-hero .search-field{border-right:0;border-bottom:1px solid rgba(255,255,255,.1)}.voyage-flight-hero .search-btn{margin:8px;min-height:48px}.voyage-flight-hero .swap-btn{transform:rotate(90deg)}}
</style>
<section class="voyage-hero voyage-flight-hero">
    <div class="voyage-bg">
        <div class="voyage-bg-img" style="background-image:url('<?= e($fhBg) ?>')"></div>
        <div class="voyage-bg-grad"></div>
        <div class="voyage-bg-glow"></div>
        <div class="fh-aurora fh-aurora-a"></div>
        <div class="fh-aurora fh-aurora-b"></div>
        <div class="fh-aurora fh-aurora-c"></div>
    </div>
    <div class="voyage-inner">
        <h1 class="voyage-title"><?= t('Find Your') ?><br><span class="serif voyage-title-accent">Perfect</span> <?= t('Flight') ?></h1>
        <p class="voyage-sub"><?= t('Tiket pesawat pilihan — booking instan, harga terbaik.') ?></p>
        <div class="flight-glass">
            <div class="booking-tabs" role="tablist">
                <a href="flights.php" class="booking-tab active" role="tab"><i class="bi bi-airplane"></i> <?= t('Pesawat') ?></a>
                <a href="ferries.php" class="booking-tab" role="tab"><i class="bi bi-water"></i> <?= t('Ferry') ?></a>
                <a href="trains.php" class="booking-tab" role="tab"><i class="bi bi-train-front"></i> <?= t('Kereta') ?></a>
                <a href="rental-cars.php" class="booking-tab" role="tab"><i class="bi bi-car-front"></i> <?= t('Rental') ?></a>
            </div>
            <div class="booking-form">
                <?php if ($fhError): ?>
                <div class="alert alert-warning py-2 small mb-3"><?= e($fhError) ?></div>
                <?php endif; ?>
                <form method="GET" action="flights.php" id="flightSearchForm">
                    <div class="form-row-options">
                        <div class="trip-type-group">
                            <button type="button" class="trip-type-btn <?= $fhTripType === 'roundtrip' ? 'active' : '' ?>" data-type="roundtrip"><?= t('Pulang Pergi') ?></button>
                            <button type="button" class="trip-type-btn <?= $fhTripType === 'oneway' ? 'active' : '' ?>" data-type="oneway"><?= t('Sekali Jalan') ?></button>
                            <button type="button" class="trip-type-btn <?= $fhTripType === 'multicity' ? 'active' : '' ?>" data-type="multicity"><?= t('Multi-Kota') ?></button>
                        </div>
                        <input type="hidden" name="trip_type" value="<?= e($fhTripType) ?>" id="tripTypeHidden">
                        <div class="custom-select">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <select name="passengers">
                                <?php for ($p = 1; $p <= 9; $p++): ?><option value="<?= $p ?>" <?= $fhPax === $p ? 'selected' : '' ?>><?= $p ?> <?= t('orang') ?></option><?php endfor; ?>
                            </select>
                        </div>
                        <div class="custom-select">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <select name="class">
                                <option value=""><?= t('Semua Kelas') ?></option>
                                <option value="economy" <?= $fhClass === 'economy' ? 'selected' : '' ?>><?= t('Ekonomi') ?></option>
                                <option value="business" <?= $fhClass === 'business' ? 'selected' : '' ?>><?= t('Bisnis') ?></option>
                                <option value="first" <?= $fhClass === 'first' ? 'selected' : '' ?>><?= t('First Class') ?></option>
                            </select>
                        </div>
                    </div>
                    <div id="multiCityLegs" class="mb-3 <?= $fhTripType === 'multicity' ? '' : 'd-none' ?>" data-testid="multicity-legs">
                        <div id="legContainer">
                            <?php if (!empty($fhLegs)): foreach ($fhLegs as $lg): ?>
                            <div class="row g-2 mb-2 leg-row">
                                <div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_from[]" placeholder="<?= t('Dari (CGK)...') ?>" value="<?= e($lg['origin'] ?? '') ?>" autocomplete="off"></div>
                                <div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_to[]" placeholder="<?= t('Ke (DPS)...') ?>" value="<?= e($lg['destination'] ?? '') ?>" autocomplete="off"></div>
                                <div class="col-md-3"><input type="date" class="form-control form-control-sm" name="leg_date[]" value="<?= e($lg['departure_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>"></div>
                                <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 leg-del">×</button></div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-light" id="addLegBtn" data-testid="add-leg"><i class="bi bi-plus-lg me-1"></i><?= t('Tambah leg') ?></button>
                        <div class="form-text"><?= t('Maksimal 6 leg.') ?></div>
                    </div>
                    <div class="form-search-row">
                        <div class="search-field voyage-field">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Dari') ?></span>
                            <input type="text" name="from" class="city-search" placeholder="<?= t('Kota atau bandara') ?>" value="<?= e($fhFrom) ?>" autocomplete="off" data-target="fromDropdown" id="fromInput">
                            <div class="search-dropdown" id="fromDropdown"></div>
                        </div>
                        <button type="button" class="swap-btn" aria-label="Tukar"><span class="swap-btn-inner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16l-4-4 4-4"/><path d="M17 8l4 4-4 4"/><line x1="3" y1="12" x2="21" y2="12"/></svg></span></button>
                        <div class="search-field voyage-field">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Ke') ?></span>
                            <input type="text" name="to" class="city-search" placeholder="<?= t('Kota atau bandara') ?>" value="<?= e($fhTo) ?>" autocomplete="off" data-target="toDropdown" id="toInput">
                            <div class="search-dropdown" id="toDropdown"></div>
                        </div>
                        <div class="search-field voyage-field return-date-col" style="<?= $fhTripType === 'roundtrip' ? '' : 'display:none' ?>">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= t('Tanggal Pulang') ?></span>
                            <input type="date" name="return_date" value="<?= e($fhReturn) ?>" min="<?= e($fhDate) ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                        </div>
                        <div class="search-field voyage-field">
                            <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= $fhTripType === 'roundtrip' ? t('Tanggal Pergi') : t('Tanggal') ?></span>
                            <input type="date" name="date" value="<?= e($fhDate) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                            <?php if (!empty($fhCal)): ?>
                            <div class="small fw-semibold mt-1 d-none" id="flightCalHint" data-testid="flight-cal-hint" style="color:#7DD3FC"></div>
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
        <div class="voyage-proof"><span class="voyage-proof-rate"><i>★</i> 4.9 • 2M+ <?= t('flights') ?></span><span class="voyage-proof-sub"><?= t('Dipercaya traveler') ?></span></div>
        <div class="voyage-chips">
            <a href="flights.php?from=Jakarta&to=Denpasar&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&search=1">Jakarta → Denpasar</a>
            <a href="flights.php?from=Jakarta&to=Singapore&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&search=1">Jakarta → Singapore</a>
            <a href="flights.php?from=Jakarta&to=Tokyo&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&search=1">Jakarta → Tokyo</a>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('flightSearchForm');
    if (!form || form.dataset.fhInit) return;
    form.dataset.fhInit = '1';
    var hidden = document.getElementById('tripTypeHidden');
    var legsBox = document.getElementById('multiCityLegs');
    var legBox = document.getElementById('legContainer');
    function syncTrip() {
        var tt = hidden ? hidden.value : 'oneway';
        document.querySelectorAll('#flightSearchForm .return-date-col').forEach(function (c) { c.style.display = tt === 'roundtrip' ? '' : 'none'; });
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
    });
    function bindDel(scope) {
        scope.querySelectorAll('.leg-del').forEach(function (b) {
            if (b.dataset.fhBind) return; b.dataset.fhBind = '1';
            b.addEventListener('click', function () { b.closest('.leg-row').remove(); });
        });
    }
    var FH_LEG_FROM = <?= json_encode(t('Dari (CGK)...')) ?>;
    var FH_LEG_TO = <?= json_encode(t('Ke (DPS)...')) ?>;
    function addLeg(fv, tv, dv) {
        if (!legBox) return;
        if (legBox.querySelectorAll('.leg-row').length >= 6) return;
        var row = document.createElement('div');
        row.className = 'row g-2 mb-2 leg-row';
        row.innerHTML = '<div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_from[]" placeholder="' + FH_LEG_FROM + '" value="' + (fv || '').replace(/"/g, '&quot;') + '" autocomplete="off"></div>'
            + '<div class="col-md-4"><input type="text" class="form-control form-control-sm city-search" name="leg_to[]" placeholder="' + FH_LEG_TO + '" value="' + (tv || '').replace(/"/g, '&quot;') + '" autocomplete="off"></div>'
            + '<div class="col-md-3"><input type="date" class="form-control form-control-sm" name="leg_date[]" value="' + (dv || '') + '" min="<?= date('Y-m-d') ?>"></div>'
            + '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 leg-del">×</button></div>';
        legBox.appendChild(row);
        bindDel(row);
    }
    if (legBox) {
        bindDel(legBox);
        if (legBox.querySelectorAll('.leg-row').length === 0) { addLeg('', '', ''); addLeg('', '', ''); }
        var addBtn = document.getElementById('addLegBtn');
        if (addBtn && !addBtn.dataset.fhBind) { addBtn.dataset.fhBind = '1'; addBtn.addEventListener('click', function () { addLeg('', '', ''); }); }
    }
    syncTrip();
});
</script>

<?php function hhImg($city, $name) { $map = ['Bali' => '1537991586-4ad0008cc2d9', 'Jakarta' => '1555899436-51d7141a21b6', 'Bandung' => '1566073771259-6a8506099945', 'Surabaya' => '1566073771259-6a8506099945', 'Yogyakarta' => '1566073771259-6a8506099945', 'Batam' => '1571003126331-e1e05b35c9d7']; $id = $map[$city] ?? '1566073771259-6a8506099945'; return 'https://images.unsplash.com/photo-' . $id . '?auto=format&fit=crop&w=640&q=70'; } ?>
<?php
/**
 * Hotel Hero - Voyage style ala Hero-2 (Find Your Perfect Stay).
 * Search pill kaca: Kota + Check-in/out (kalender popup) + Tamu & Kamar -> GET ke hotels.php.
 */
$hotelBg = $heroSlides[0]['image'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2070&q=80';
$hotelToday = date('Y-m-d');
$hotelCheckinD = $_GET['checkin'] ?? $hotelToday;
$hotelCheckoutD = $_GET['checkout'] ?? date('Y-m-d', strtotime('+2 days'));
$hotelGuestsN = max(1, (int)($_GET['guests'] ?? 2));
$hotelRoomsN = max(1, (int)($_GET['rooms'] ?? 1));
$hotelCityV = $_GET['city'] ?? '';
$hotelCities = [];
try { $hotelCities = db()->query("SELECT DISTINCT city FROM hotels WHERE is_active = 1 ORDER BY city ASC LIMIT 8")->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) {}
$hotelCards = array_slice($hotels ?? [], 0, 6);
require_once __DIR__ . '/../components/date-picker.php';
?>
<section class="voyage-hero voyage-hotel-hero<?= !empty($hotelSearched) ? ' is-searched' : '' ?>">
    <div class="voyage-bg">
        <div class="voyage-bg-img" style="background-image:url('<?= e($hotelBg) ?>')"></div>
        <div class="voyage-bg-grad"></div>
        <div class="voyage-bg-glow"></div>
    </div>

    <div class="voyage-inner">
        <h1 class="voyage-title"><?= t('Find Your') ?><br><span class="serif voyage-title-accent">Perfect</span> <?= t('Stay') ?></h1>
        <p class="voyage-sub"><?= t('Hotel, vila & resor pilihan — booking instan, harga terbaik.') ?></p>

        <form class="voyage-search" method="GET" action="hotels.php" id="voyageHotelForm">
            <div class="voyage-field">
                <label><?= t('Kota / Hotel') ?></label>
                <input type="text" name="city" id="voyageHotelCity" placeholder="<?= t('Mau menginap di mana?') ?>" value="<?= e($hotelCityV) ?>" autocomplete="off" list="voyageHotelCityList">
                <datalist id="voyageHotelCityList">
                    <?php foreach ($hotelCities as $hc): ?><option value="<?= e($hc) ?>"></option><?php endforeach; ?>
                </datalist>
            </div>
            <div class="voyage-div"></div>
            <div class="voyage-field voyage-click" id="voyageInBtn" tabindex="0">
                <label><?= t('Check-in') ?></label>
                <span class="voyage-val" id="voyageInVal"><?= t('Tambah tanggal') ?></span>
            </div>
            <div class="voyage-div"></div>
            <div class="voyage-field voyage-click" id="voyageOutBtn" tabindex="0">
                <label><?= t('Check-out') ?></label>
                <span class="voyage-val" id="voyageOutVal"><?= t('Tambah tanggal') ?></span>
            </div>
            <?php renderDatePicker(['mode' => 'range', 'bare' => true, 'startName' => 'checkin', 'startId' => 'voyageCheckin', 'startValue' => $hotelCheckinD, 'endName' => 'checkout', 'endId' => 'voyageCheckout', 'endValue' => $hotelCheckoutD, 'id' => 'voyageRange', 'cls' => 'd-none', 'min' => $hotelToday, 'months' => 2]); ?>
            <div class="voyage-div"></div>
            <div class="voyage-field voyage-click" id="voyageRoomBtn" tabindex="0">
                <label><?= t('Tamu & Kamar') ?></label>
                <span class="voyage-val" id="voyageRoomVal">2 <?= t('Tamu') ?>, 1 <?= t('Kamar') ?></span>
                <input type="hidden" name="guests" id="voyageGuests" value="<?= $hotelGuestsN ?>">
                <input type="hidden" name="rooms" id="voyageRooms" value="<?= $hotelRoomsN ?>">
            </div>
            <button type="submit" class="voyage-go" aria-label="<?= t('Cari') ?>"><i class="bi bi-search"></i></button>

            <div class="voyage-pop voyage-pop-right" id="voyageRoomPop">
                <?php $roomRows = [['k' => 'adults', 'l' => t('Dewasa'), 's' => t('Usia 13+')], ['k' => 'children', 'l' => t('Anak'), 's' => t('Usia 2-12')], ['k' => 'rooms', 'l' => t('Kamar'), 's' => t('Jumlah kamar')]]; ?>
                <?php foreach ($roomRows as $wr): ?>
                <div class="voyage-who-row">
                    <div><div class="voyage-who-l"><?= e($wr['l']) ?></div><div class="voyage-who-s"><?= e($wr['s']) ?></div></div>
                    <div class="voyage-step">
                        <button type="button" data-room="<?= $wr['k'] ?>" data-d="-1">−</button>
                        <span id="voyage-<?= $wr['k'] ?>"> <?= $wr['k'] === 'adults' ? $hotelGuestsN : ($wr['k'] === 'rooms' ? $hotelRoomsN : 0) ?></span>
                        <button type="button" data-room="<?= $wr['k'] ?>" data-d="1">+</button>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="voyage-pop-foot">
                    <span><?= t('Total tamu & kamar') ?></span>
                    <button type="button" id="voyageRoomOk"><?= t('Pilih') ?></button>
                </div>
            </div>
        </form>
        <div class="voyage-proof"><span class="voyage-proof-rate"><i>★</i> 4.9 • 2M+ <?= t('stays') ?></span><span class="voyage-proof-sub"><?= t('Dipercaya traveler') ?></span></div>
        <div class="voyage-list">
            <div class="voyage-list-head">
                <div class="voyage-list-title"><h2><?= $hotelCityV !== '' ? t('Top stays in') . ' ' . e($hotelCityV) : t('Top stays') ?></h2><span class="voyage-count"><?= count($hotelCards) ?> <?= t('hotel') ?></span></div>
                <div class="voyage-sort"><span><?= t('Urut:') ?></span><a href="hotels.php?city=<?= urlencode($hotelCityV) ?>" class="on"><?= t('Rekomendasi') ?></a><a href="hotels.php?city=<?= urlencode($hotelCityV) ?>&sort=price_desc"><?= t('Harga') ?></a><a href="hotels.php?city=<?= urlencode($hotelCityV) ?>&sort=stars"><?= t('Bintang') ?></a></div>
            </div>
            <div class="voyage-tgrid">
                <?php if (!empty($hotelCards)): foreach ($hotelCards as $hc): $hSlug = $hc['slug'] ?? $hc['id']; $hName = $hc['name'] ?? ''; $hCity = $hc['city'] ?? ''; $hStars = (int)($hc['star_rating'] ?? 4); $hAm = array_values(array_filter(array_map('trim', explode(',', (string)($hc['amenities'] ?? ''))))); $hLink = 'hotel-detail.php?slug=' . urlencode($hSlug) . '&checkin=' . urlencode($hotelCheckinD) . '&checkout=' . urlencode($hotelCheckoutD) . '&guests=' . (int)$hotelGuestsN; ?>
                <div class="voyage-tcard">
                    <a class="voyage-tcard-media" href="<?= $hLink ?>" aria-label="<?= e($hName) ?>">
                        <img src="<?= hhImg($hCity, $hName) ?>" alt="<?= e($hName) ?>" loading="lazy">
                        <span class="voyage-tcard-shade"></span>
                        <span class="voyage-tcard-badges">
                            <span class="voyage-badge voyage-badge-best"><?= $hStars ?>★</span>
                            <?php if (!empty($hc['instant_confirmation'])): ?><span class="voyage-badge voyage-badge-instant">⚡ <?= t('Instan') ?></span><?php endif; ?>
                        </span>
                        <span class="voyage-tcard-pills">
                            <span class="voyage-pill">★ <?= e(number_format((float)($hc['rating'] ?? 4.8), 1)) ?></span>
                            <span class="voyage-pill"><?= e($hCity) ?></span>
                        </span>
                    </a>
                    <div class="voyage-tcard-b">
                        <a class="voyage-tcard-t" href="<?= $hLink ?>"><?= e(mb_strimwidth($hName, 0, 60, '...')) ?></a>
                        <div class="voyage-tcard-loc"><i class="bi bi-geo-alt"></i> <?= e($hCity) ?> • <?= str_repeat('★', min(5, max(1, $hStars))) ?></div>
                        <div class="voyage-tcard-foot">
                            <div class="voyage-tcard-price">
                                <span class="voyage-tcard-now"><?= formatRupiah((float)($hc['price_per_night'] ?? 0)) ?></span>
                                <span class="voyage-tcard-per">/<?= t('malam') ?> • <?= !empty($hc['free_cancellation']) ? t('Batal gratis') : t('Konfirmasi instan') ?></span>
                            </div>
                            <a class="voyage-tcard-add" href="<?= $hLink ?>"><?= t('Pesan') ?> <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</section>


<script>
(function(){
var inBtn=document.getElementById('voyageInBtn');
var outBtn=document.getElementById('voyageOutBtn'),roomBtn=document.getElementById('voyageRoomBtn'),roomPop=document.getElementById('voyageRoomPop');
if(!inBtn||!roomPop)return;
var inH=document.getElementById('voyageCheckin'),outH=document.getElementById('voyageCheckout'),inV=document.getElementById('voyageInVal'),outV=document.getElementById('voyageOutVal'),rangeEl=document.getElementById('voyageRange');
var gH=document.getElementById('voyageGuests'),rH=document.getElementById('voyageRooms'),roomV=document.getElementById('voyageRoomVal');
function fmtS(s){try{return new Date(s+'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric'});}catch(e){return s;}}
function syncLabel(){inV.textContent=inH&&inH.value?fmtS(inH.value):'Add date';outV.textContent=outH&&outH.value?fmtS(outH.value):'Add date';}
function closeAll(){roomPop.classList.remove('show');}
function openRange(){if(rangeEl&&rangeEl._flatpickr){rangeEl._flatpickr.open();}else if(rangeEl){rangeEl.focus();}}
if(rangeEl){rangeEl.addEventListener('dp:change',syncLabel);}
inBtn.addEventListener('click',function(e){e.stopPropagation();roomPop.classList.remove('show');openRange();});
outBtn.addEventListener('click',function(e){e.stopPropagation();roomPop.classList.remove('show');openRange();});
roomBtn.addEventListener('click',function(e){e.stopPropagation();roomPop.classList.toggle('show');});
document.addEventListener('click',function(e){if(!e.target.closest('#voyageHotelForm'))closeAll();});
var adults=parseInt((document.getElementById('voyage-adults').textContent||'2'),10)||2,children=parseInt((document.getElementById('voyage-children').textContent||'0'),10)||0,rooms=parseInt((document.getElementById('voyage-rooms').textContent||'1'),10)||1;
function syncRoom(){var g=adults+children;gH.value=g;rH.value=rooms;roomV.textContent=g+' Guests, '+rooms+' Rooms';document.getElementById('voyage-adults').textContent=adults;document.getElementById('voyage-children').textContent=children;document.getElementById('voyage-rooms').textContent=rooms;}
roomPop.querySelectorAll('button[data-room]').forEach(function(b){b.addEventListener('click',function(){var k=b.getAttribute('data-room'),d=parseInt(b.getAttribute('data-d'),10);if(k==='adults')adults=Math.max(1,adults+d);else if(k==='children')children=Math.max(0,children+d);else rooms=Math.max(1,Math.min(8,rooms+d));syncRoom();});});
document.getElementById('voyageRoomOk').addEventListener('click',closeAll);syncLabel();syncRoom();

})();
</script>

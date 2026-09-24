<?php
/**
 * Tour Hero — Voyage style (dark premium, serif headline, pill search).
 * Fokus TOUR: cari destinasi + tanggal keberangkatan + peserta → submit GET ke tours.php.
 * Data: $heroSlides, $heroHeadline, $heroSub, $categories, $featuredTours (dari index.php).
 */
$voyageBg = $heroSlides[0]['image'] ?? 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=2070&q=80';
$voyageTitle = !empty($heroSlides[0]['title']) ? $heroSlides[0]['title'] : $heroHeadline;
$voyageSub = !empty($heroSlides[0]['subtitle']) ? $heroSlides[0]['subtitle'] : $heroSub;
$voyageCats = array_slice($categories ?? [], 0, 6);
$voyageRecent = array_slice($featuredTours ?? [], 0, 3);
$voyageCards = array_slice($featuredTours ?? [], 0, 6);
// Trending: flatten getCityDestinations → 6 kartu
$voyageTrend = [];
try {
    foreach (getCityDestinations() as $catGroup) {
        foreach ($catGroup as $d) {
            $voyageTrend[] = $d;
            if (count($voyageTrend) >= 6) break 2;
        }
    }
} catch (Throwable $e) {}
$voyageToday = date('Y-m-d');
?>
<section class="voyage-hero">
    <div class="voyage-bg">
        <div class="voyage-bg-img" style="background-image:url('<?= e($voyageBg) ?>')"></div>
        <div class="voyage-bg-grad"></div>
        <div class="voyage-bg-glow"></div>
    </div>

    <div class="voyage-inner">
        <h1 class="voyage-title"><?= t('Temukan') ?><br><span class="serif voyage-title-accent">Perfect</span> <?= t('Trip') ?></h1>
        <p class="voyage-sub"><?= t('Paket tour pilihan, villa & pengalaman — booking instan, harga terbaik.') ?></p>

        <form class="voyage-search" method="GET" action="tours.php" id="voyageSearchForm">
            <div class="voyage-field">
                <label><?= t('Destinasi') ?></label>
                <input type="text" name="search" id="voyageWhere" placeholder="<?= t('Cari destinasi atau aktivitas...') ?>" autocomplete="off">
            </div>
            <div class="voyage-div"></div>
            <div class="voyage-field voyage-click" id="voyageWhenBtn" tabindex="0">
                <label><?= t('Tanggal') ?></label>
                <span class="voyage-val" id="voyageWhenVal"><?= t('Pilih tanggal') ?></span>
                <input type="date" name="departure" id="voyageDate" min="<?= $voyageToday ?>" hidden>
            </div>
            <div class="voyage-div"></div>
            <div class="voyage-field voyage-click" id="voyageWhoBtn" tabindex="0">
                <label><?= t('Peserta') ?></label>
                <span class="voyage-val" id="voyageWhoVal">2 <?= t('Peserta') ?></span>
            </div>
            <button type="submit" class="voyage-go" aria-label="<?= t('Cari') ?>">
                <i class="bi bi-search"></i>
            </button>

            <div class="voyage-pop" id="voyageDatePop">
                <input type="date" id="voyageDatePick" min="<?= $voyageToday ?>" value="<?= $voyageToday ?>">
                <div class="voyage-pop-foot">
                    <span><?= t('Tanggal keberangkatan') ?></span>
                    <button type="button" id="voyageDateOk"><?= t('Pilih') ?></button>
                </div>
            </div>

            <div class="voyage-pop voyage-pop-right" id="voyageWhoPop">
                <?php $whoRows = [['k' => 'adults', 'l' => t('Dewasa'), 's' => '13+'], ['k' => 'children', 'l' => t('Anak'), 's' => '2-12']]; ?>
                <?php foreach ($whoRows as $wr): ?>
                <div class="voyage-who-row">
                    <div><div class="voyage-who-l"><?= e($wr['l']) ?></div><div class="voyage-who-s"><?= e($wr['s']) ?></div></div>
                    <div class="voyage-step">
                        <button type="button" data-who="<?= $wr['k'] ?>" data-d="-1">−</button>
                        <span id="voyage-<?= $wr['k'] ?>"> <?= $wr['k'] === 'adults' ? '2' : '0' ?></span>
                        <button type="button" data-who="<?= $wr['k'] ?>" data-d="1">+</button>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="voyage-pop-foot">
                    <span><?= t('Total peserta') ?></span>
                    <button type="button" id="voyageWhoOk"><?= t('Pilih') ?></button>
                </div>
            </div>
        </form>
        <div class="voyage-proof"><span class="voyage-proof-rate"><i>★</i> 4.9 &bull; 2M+ <?= t('tour') ?></span><span class="voyage-proof-sub"><?= t('Dipercaya traveler') ?></span></div>

        <?php if (!empty($voyageCats)): ?>
        <div class="voyage-chips">
            <a href="tours.php" class="on"><?= t('Semua') ?></a>
            <?php foreach ($voyageCats as $vc): ?>
            <a href="tours.php?category=<?= urlencode($vc) ?>"><?= e($vc) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="voyage-list">
            <?php if (!empty($voyageCards)): ?>
            <div class="voyage-list-head">
                <div class="voyage-list-title"><h2><?= t('Top experiences') ?></h2><span class="voyage-count"><?= count($voyageCards) ?> <?= t('tour') ?></span></div>
                <div class="voyage-sort"><span><?= t('Urut:') ?></span><a href="tours.php" class="on"><?= t('Rekomendasi') ?></a><a href="tours.php?sort=termurah"><?= t('Harga') ?></a><a href="tours.php?sort=rating"><?= t('Rating') ?></a></div>
            </div>
            <div class="voyage-tgrid">
                <?php foreach ($voyageCards as $vc):
                    $vSlug = $vc['slug'] ?? $vc['id'];
                    $vTitle = $vc['title'] ?? '';
                    $vLoc = trim(($vc['location_city'] ?? '') ?: ($vc['category'] ?? ''));
                    $vRating = number_format((float)($vc['rating'] ?? 5), 1);
                    $vRev = (int)($vc['total_reviews'] ?? 0);
                    $vDays = (int)($vc['duration_days'] ?? 0);
                    $vDur = $vDays > 0 ? $vDays . 'D' . (!empty($vc['duration_nights']) ? (int)$vc['duration_nights'] . 'N' : '') : t('Tour');
                    try { $vDisc = function_exists('getDiskonPersen') ? (int)getDiskonPersen($vc) : 0; } catch (Throwable $e) { $vDisc = 0; }
                    $vHi = [];
                    if (!empty($vc['highlights'])) {
                        foreach (preg_split("/[\r\n]+/", (string)$vc['highlights']) as $hx) {
                            $hx = trim($hx);
                            $hx = trim($hx, "-\xe2\x80\xa2\xe2\x80\xa3*0123456789. ");
                            if ($hx !== '') $vHi[] = $hx;
                            if (count($vHi) >= 2) break;
                        }
                    }
                ?>
                <div class="voyage-tcard">
                    <a class="voyage-tcard-media" href="tour-detail.php?slug=<?= urlencode($vSlug) ?>" aria-label="<?= e($vTitle) ?>">
                        <img src="<?= e(getTourImage($vc, 'medium')) ?>" alt="<?= e($vTitle) ?>" loading="lazy" onerror="this.src='<?= e(getTourImageFallback($vc, 'medium')) ?>'">
                        <span class="voyage-tcard-shade"></span>
                        <span class="voyage-tcard-badges">
                            <?php if ($vDisc > 0): ?><span class="voyage-badge voyage-badge-disc">-<?= $vDisc ?>%</span><?php endif; ?>
                            <?php if (!empty($vc['best_seller'])): ?><span class="voyage-badge voyage-badge-best"><?= t('Bestseller') ?></span><?php endif; ?>
                            <?php if (!empty($vc['instant_confirmation'])): ?><span class="voyage-badge voyage-badge-instant">&#9889; <?= t('Instan') ?></span><?php endif; ?>
                        </span>
                        <?php if (function_exists('isLoggedIn')): ?>
                        <button type="button" class="voyage-wish wishlist-btn <?= (!empty($wishlistIds) && in_array($vc['id'] ?? 0, $wishlistIds)) ? 'on' : '' ?>" data-tour-id="<?= (int)($vc['id'] ?? 0) ?>" onclick="if(typeof toggleWishlist==='function')toggleWishlist(this, <?= (int)($vc['id'] ?? 0) ?>)" aria-label="wishlist"><i class="bi bi-heart<?= (!empty($wishlistIds) && in_array($vc['id'] ?? 0, $wishlistIds)) ? '-fill' : '' ?>"></i></button>
                        <?php endif; ?>
                        <span class="voyage-tcard-pills">
                            <span class="voyage-pill">&#9733; <?= e($vRating) ?> <em><?php if ($vRev > 0): ?>&bull; <?= number_format($vRev) ?><?php endif; ?></em></span>
                            <span class="voyage-pill"><?= e($vDur) ?></span>
                        </span>
                    </a>
                    <div class="voyage-tcard-b">
                        <a class="voyage-tcard-t" href="tour-detail.php?slug=<?= urlencode($vSlug) ?>"><?= e(mb_strimwidth($vTitle, 0, 64, '...')) ?></a>
                        <?php if ($vLoc !== ''): ?><div class="voyage-tcard-loc"><i class="bi bi-geo-alt"></i> <?= e(mb_strimwidth($vLoc, 0, 40, '...')) ?></div><?php endif; ?>
                        <?php if (!empty($vHi)): ?>
                        <div class="voyage-tcard-hi"><?php foreach ($vHi as $hx): ?><span><i class="bi bi-check"></i><?= e(mb_strimwidth($hx, 0, 52, '...')) ?></span><?php endforeach; ?></div>
                        <?php endif; ?>
                        <div class="voyage-tcard-foot">
                            <div class="voyage-tcard-price">
                                <span class="voyage-tcard-now"><?= formatCurrencySpan($vc['price'], $vc['price_currency'] ?? 'IDR') ?></span>
                                <?php if (!empty($vc['original_price']) && $vc['original_price'] > $vc['price']): ?><span class="voyage-tcard-was"><?= formatCurrencySpan($vc['original_price'], $vc['price_currency'] ?? 'IDR') ?></span><?php endif; ?>
                                <span class="voyage-tcard-per">/<?= t('orang') ?> &bull; <?= !empty($vc['free_cancellation']) ? t('Batal gratis') : t('Konfirmasi instan') ?></span>
                            </div>
                            <a class="voyage-tcard-add" href="tour-detail.php?slug=<?= urlencode($vSlug) ?>"><?= t('Detail') ?> <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="voyage-trustrow"><span><i class="dot g"></i><?= t('Konfirmasi instan') ?></span><span><i class="dot o"></i><?= t('Harga terbaik') ?></span><span class="hide-m"><i class="dot w"></i><?= t('Batal gratis') ?></span></div>
            <?php endif; ?>
        </div>
    </div>
</section>



<script>
(function(){
var dateBtn=document.getElementById('voyageWhenBtn'),datePop=document.getElementById('voyageDatePop'),
datePick=document.getElementById('voyageDatePick'),dateHidden=document.getElementById('voyageDate'),
dateVal=document.getElementById('voyageWhenVal'),dateOk=document.getElementById('voyageDateOk'),
whoBtn=document.getElementById('voyageWhoBtn'),whoPop=document.getElementById('voyageWhoPop'),
whoVal=document.getElementById('voyageWhoVal'),whoOk=document.getElementById('voyageWhoOk');
var adults=2,children=0;
function fmt(d){try{return new Date(d+'T00:00:00').toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'})}catch(e){return d}}
function closeAll(){datePop.classList.remove('show');whoPop.classList.remove('show')}
dateBtn.addEventListener('click',function(e){e.stopPropagation();whoPop.classList.remove('show');datePop.classList.toggle('show')});
whoBtn.addEventListener('click',function(e){e.stopPropagation();datePop.classList.remove('show');whoPop.classList.toggle('show')});
document.addEventListener('click',function(e){if(!e.target.closest('.voyage-search'))closeAll()});
dateOk.addEventListener('click',function(){if(datePick.value){dateHidden.value=datePick.value;dateVal.textContent=fmt(datePick.value)}closeAll()});
datePick.addEventListener('change',function(){if(datePick.value){dateHidden.value=datePick.value;dateVal.textContent=fmt(datePick.value)}});
whoPop.querySelectorAll('button[data-who]').forEach(function(b){b.addEventListener('click',function(){
var k=b.getAttribute('data-who'),d=parseInt(b.getAttribute('data-d'),10);
if(k==='adults')adults=Math.max(1,adults+d);else children=Math.max(0,children+d);
document.getElementById('voyage-adults').textContent=adults;
document.getElementById('voyage-children').textContent=children;
whoVal.textContent=(adults+children)+' <?= t('Peserta') ?>';
})});
whoOk.addEventListener('click',closeAll);
})();
</script>

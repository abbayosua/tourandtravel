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
        <p class="voyage-eyebrow"><?= t('Jelajahi Dunia Bersama Kami') ?></p>
        <h1 class="voyage-title serif"><?= e($voyageTitle) ?></h1>
        <p class="voyage-sub"><?= e($voyageSub) ?></p>

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

        <?php if (!empty($voyageCats)): ?>
        <div class="voyage-chips">
            <a href="tours.php" class="on"><?= t('Semua') ?></a>
            <?php foreach ($voyageCats as $vc): ?>
            <a href="tours.php?category=<?= urlencode($vc) ?>"><?= e($vc) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="voyage-grid">
            <?php if (!empty($voyageRecent)): ?>
            <div>
                <div class="voyage-sec-head"><span><?= t('Populer saat ini') ?></span><a href="tours.php?sort=popular"><?= t('Lihat semua') ?> →</a></div>
                <div class="voyage-recent">
                    <?php foreach ($voyageRecent as $rt): ?>
                    <a class="voyage-rec" href="tour-detail.php?slug=<?= urlencode($rt['slug'] ?? $rt['id']) ?>">
                        <img src="<?= e(getTourImage($rt, 'small')) ?>" alt="" loading="lazy">
                        <div><div class="voyage-rec-t"><?= e(mb_strimwidth($rt['title'] ?? '', 0, 34, '…')) ?></div>
                        <div class="voyage-rec-s">★ <?= e(number_format((float)($rt['rating'] ?? 5), 1)) ?> • <?= (int)($rt['duration_days'] ?? 0) > 0 ? (int)$rt['duration_days'] . 'D' : t('Tour') ?></div></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($voyageTrend)): ?>
            <div>
                <div class="voyage-sec-head"><span><?= t('Destinasi trending') ?></span><a href="tours.php"><?= t('Lihat semua') ?> →</a></div>
                <div class="voyage-trend">
                    <?php foreach ($voyageTrend as $td):
                        $tc = $td['city'] ?? '';
                        try { $cnt = countToursByCity($tc); } catch (Throwable $e) { $cnt = 0; }
                    ?>
                    <a class="voyage-card" href="tours.php?search=<?= urlencode($tc) ?>">
                        <img src="<?= e(getDestinasiImage($tc)) ?>" alt="<?= e($tc) ?>" loading="lazy">
                        <div class="voyage-card-b"><div class="voyage-card-t"><?= e($tc) ?></div><div class="voyage-card-s"><?= (int)$cnt ?> <?= t('paket') ?></div></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
@import url('https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap');
.voyage-hero{position:relative;overflow:hidden;background:linear-gradient(180deg,#e7f0ff 0%,#f5f9ff 50%,#ffffff 100%);color:#0d1b33;padding:64px 0 48px}
.voyage-hero .serif{font-family:'Instrument Serif',serif}
.voyage-bg{position:absolute;inset:0}
.voyage-bg-img{position:absolute;inset:0;opacity:.38;background-size:cover;background-position:center 35%}
.voyage-bg-grad{position:absolute;inset:0;background:linear-gradient(180deg,rgba(231,240,255,.35) 0%,rgba(245,249,255,.72) 55%,#ffffff 100%),linear-gradient(90deg,rgba(13,110,253,.10),transparent 55%,rgba(102,16,242,.07))}
.voyage-bg-glow{position:absolute;inset:0;opacity:.5;background:radial-gradient(900px circle at 15% 15%,rgba(13,110,253,.14),transparent 60%),radial-gradient(700px circle at 85% 25%,rgba(255,122,26,.10),transparent 60%)}
.voyage-inner{position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:0 20px}
.voyage-eyebrow{font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:#0d6efd;font-weight:600;margin:0 0 10px}
.voyage-title{font-size:clamp(38px,6vw,68px);line-height:1.02;letter-spacing:-.02em;margin:0 0 10px;font-weight:400;color:#0b1e3f}
.voyage-sub{color:#4d5f7a;font-size:15px;margin:0 0 26px;max-width:560px}
.voyage-search{position:relative;display:flex;align-items:stretch;gap:4px;max-width:860px;background:rgba(255,255,255,.72);border:1px solid rgba(13,110,253,.18);border-radius:999px;padding:8px;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);box-shadow:0 12px 40px rgba(13,110,253,.14),inset 0 1px 0 rgba(255,255,255,.9)}
.voyage-field{flex:1;display:flex;flex-direction:column;justify-content:center;padding:6px 20px;min-width:0;border-radius:999px}
.voyage-field label{font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#6d7d99;font-weight:600}
.voyage-field input[type=text]{background:transparent;border:0;outline:0;color:#0d1b33;font-size:14px;width:100%}
.voyage-field input[type=text]::placeholder{color:#93a1b8}
.voyage-click{cursor:pointer}
.voyage-click:hover{background:rgba(13,110,253,.06)}
.voyage-val{font-size:14px;color:#0d1b33;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.voyage-div{width:1px;background:rgba(13,110,253,.14);margin:10px 0}
.voyage-go{width:52px;height:52px;flex:none;border:0;border-radius:50%;background:#0d6efd;color:#fff;font-size:18px;cursor:pointer;align-self:center;box-shadow:0 4px 14px rgba(13,110,253,.35)}
.voyage-go:hover{background:#0b5ed7}
.voyage-pop{display:none;position:absolute;top:calc(100% + 12px);left:8px;background:rgba(255,255,255,.94);border:1px solid rgba(13,110,253,.16);border-radius:20px;padding:18px;z-index:200;backdrop-filter:blur(20px);box-shadow:0 20px 60px rgba(13,110,253,.18);min-width:300px;color:#0d1b33}
.voyage-pop.show{display:block}
.voyage-pop-right{left:auto;right:8px}
.voyage-pop input[type=date]{width:100%;background:#eef4ff;border:1px solid rgba(13,110,253,.2);color:#0d1b33;border-radius:12px;padding:10px 12px;color-scheme:light}
.voyage-pop-foot{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:14px;border-top:1px solid rgba(13,110,253,.12);font-size:12px;color:#6d7d99}
.voyage-pop-foot button{border:0;background:#0d6efd;color:#fff;border-radius:999px;padding:8px 18px;font-size:12px;font-weight:600;cursor:pointer}
.voyage-who-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid rgba(13,110,253,.1)}
.voyage-who-l{font-size:14px;font-weight:500}
.voyage-who-s{font-size:12px;color:#6d7d99}
.voyage-step{display:flex;align-items:center;gap:12px}
.voyage-step button{width:32px;height:32px;border-radius:50%;border:1px solid rgba(13,110,253,.25);background:#fff;color:#0d1b33;cursor:pointer;font-size:16px;line-height:1}
.voyage-step span{min-width:20px;text-align:center;font-size:14px;font-weight:600}
.voyage-chips{display:flex;gap:8px;margin-top:18px;flex-wrap:wrap}
.voyage-chips a{padding:8px 18px;border-radius:999px;font-size:13px;font-weight:500;text-decoration:none;background:rgba(255,255,255,.7);border:1px solid rgba(13,110,253,.18);color:#33465f;backdrop-filter:blur(12px)}
.voyage-chips a.on{background:#0d6efd;color:#fff;border-color:#0d6efd}
.voyage-chips a:hover{background:#fff;color:#0d1b33}
.voyage-chips a.on:hover{background:#0b5ed7;color:#fff}
.voyage-grid{display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:32px}
.voyage-sec-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#7a8aa5;font-weight:600}
.voyage-sec-head a{font-size:11px;color:#0d6efd;text-decoration:none;letter-spacing:0;text-transform:none}
.voyage-sec-head a:hover{text-decoration:underline}
.voyage-recent{display:flex;flex-direction:column;gap:8px}
.voyage-rec{display:flex;align-items:center;gap:12px;padding:8px 14px 8px 8px;border-radius:999px;background:rgba(255,255,255,.75);border:1px solid rgba(13,110,253,.14);text-decoration:none;color:#0d1b33;backdrop-filter:blur(12px)}
.voyage-rec:hover{background:#fff}
.voyage-rec img{width:34px;height:34px;border-radius:50%;object-fit:cover;flex:none}
.voyage-rec-t{font-size:12px;font-weight:500;line-height:1.3}
.voyage-rec-s{font-size:11px;color:#6d7d99}
.voyage-trend{display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none}
.voyage-trend::-webkit-scrollbar{display:none}
.voyage-card{position:relative;flex:none;width:150px;border-radius:20px;overflow:hidden;background:rgba(255,255,255,.8);border:1px solid rgba(13,110,253,.14);text-decoration:none;color:#0d1b33}
.voyage-card img{width:100%;height:110px;object-fit:cover;display:block}
.voyage-card-b{padding:10px 12px}
.voyage-card-t{font-size:13px;font-weight:600}
.voyage-card-s{font-size:11px;color:#6d7d99}
@media(max-width:768px){
.voyage-hero{padding:40px 0 32px}
.voyage-search{flex-direction:column;border-radius:24px}
.voyage-div{width:auto;height:1px;margin:0 10px}
.voyage-go{width:100%;border-radius:16px;height:46px}
.voyage-grid{grid-template-columns:1fr;gap:24px}
.voyage-pop{left:0;right:0;min-width:0}
}
[data-theme="dark"] .voyage-hero{background:#08080a;color:#fff}
[data-theme="dark"] .voyage-bg-img{opacity:.55}
[data-theme="dark"] .voyage-bg-grad{background:linear-gradient(180deg,rgba(10,10,11,.4),rgba(10,10,11,.55) 60%,#08080a),linear-gradient(90deg,rgba(10,10,11,.8),transparent 60%,rgba(10,10,11,.6))}
[data-theme="dark"] .voyage-bg-glow{background:radial-gradient(900px circle at 15% 15%,rgba(255,122,26,.18),transparent 60%),radial-gradient(700px circle at 85% 25%,rgba(140,130,255,.12),transparent 60%)}
[data-theme="dark"] .voyage-eyebrow{color:rgba(255,255,255,.5)}
[data-theme="dark"] .voyage-title{color:#fff}
[data-theme="dark"] .voyage-sub{color:rgba(255,255,255,.65)}
[data-theme="dark"] .voyage-search{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.14);box-shadow:0 8px 40px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.14)}
[data-theme="dark"] .voyage-field label{color:rgba(255,255,255,.45)}
[data-theme="dark"] .voyage-field input[type=text]{color:#fff}
[data-theme="dark"] .voyage-field input[type=text]::placeholder{color:rgba(255,255,255,.4)}
[data-theme="dark"] .voyage-click:hover{background:rgba(255,255,255,.06)}
[data-theme="dark"] .voyage-val{color:#fff}
[data-theme="dark"] .voyage-div{background:rgba(255,255,255,.12)}
[data-theme="dark"] .voyage-go{background:#fff;color:#000;box-shadow:none}
[data-theme="dark"] .voyage-go:hover{background:rgba(255,255,255,.9)}
[data-theme="dark"] .voyage-pop{background:rgba(17,17,19,.95);border-color:rgba(255,255,255,.12);color:#fff;box-shadow:0 20px 80px rgba(0,0,0,.6)}
[data-theme="dark"] .voyage-pop input[type=date]{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.14);color:#fff;color-scheme:dark}
[data-theme="dark"] .voyage-pop-foot{border-color:rgba(255,255,255,.1);color:rgba(255,255,255,.5)}
[data-theme="dark"] .voyage-pop-foot button{background:#fff;color:#000}
[data-theme="dark"] .voyage-who-row{border-color:rgba(255,255,255,.07)}
[data-theme="dark"] .voyage-who-s{color:rgba(255,255,255,.5)}
[data-theme="dark"] .voyage-step button{border-color:rgba(255,255,255,.15);background:rgba(255,255,255,.08);color:#fff}
[data-theme="dark"] .voyage-chips a{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:rgba(255,255,255,.7)}
[data-theme="dark"] .voyage-chips a.on{background:#fff;color:#000;border-color:#fff}
[data-theme="dark"] .voyage-chips a:hover{background:rgba(255,255,255,.12);color:#fff}
[data-theme="dark"] .voyage-sec-head{color:rgba(255,255,255,.35)}
[data-theme="dark"] .voyage-sec-head a{color:rgba(255,255,255,.5)}
[data-theme="dark"] .voyage-sec-head a:hover{color:#fff}
[data-theme="dark"] .voyage-rec{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:#fff}
[data-theme="dark"] .voyage-rec:hover{background:rgba(255,255,255,.09)}
[data-theme="dark"] .voyage-rec-s{color:rgba(255,255,255,.5)}
[data-theme="dark"] .voyage-card{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:#fff}
[data-theme="dark"] .voyage-card-s{color:rgba(255,255,255,.5)}
</style>

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

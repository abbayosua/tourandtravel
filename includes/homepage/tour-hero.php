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
.voyage-list{margin-top:32px}
.voyage-list-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.voyage-list-title{display:flex;align-items:center;gap:10px}
.voyage-list-title h2{font-size:18px;font-weight:600;letter-spacing:-.01em;margin:0;color:#0b1e3f}
.voyage-count{padding:4px 10px;border-radius:999px;background:rgba(13,110,253,.1);border:1px solid rgba(13,110,253,.16);font-size:11px;color:#0d6efd;font-weight:600;white-space:nowrap}
.voyage-sort{display:flex;align-items:center;gap:6px;font-size:12px;color:#6d7d99;flex-wrap:wrap}
.voyage-sort a{padding:6px 12px;border-radius:999px;font-size:12px;font-weight:500;text-decoration:none;background:rgba(255,255,255,.7);border:1px solid rgba(13,110,253,.16);color:#33465f}
.voyage-sort a.on{background:#0d6efd;color:#fff;border-color:#0d6efd}
.voyage-tgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.voyage-tcard{position:relative;border-radius:26px;background:rgba(255,255,255,.8);border:1px solid rgba(13,110,253,.14);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);overflow:hidden;transition:transform .4s,box-shadow .4s,border-color .4s;box-shadow:0 8px 32px rgba(13,110,253,.08)}
.voyage-tcard:hover{transform:translateY(-4px);border-color:rgba(13,110,253,.3);box-shadow:0 20px 60px rgba(13,110,253,.18)}
.voyage-tcard-media{position:relative;display:block;aspect-ratio:16/10;overflow:hidden;background:#dbe7ff}
.voyage-tcard-media img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .8s ease}
.voyage-tcard:hover .voyage-tcard-media img{transform:scale(1.06)}
.voyage-tcard-shade{position:absolute;inset:0;background:linear-gradient(180deg,rgba(5,15,35,.02) 40%,rgba(5,15,35,.55) 100%)}
.voyage-tcard-badges{position:absolute;top:10px;left:10px;right:52px;display:flex;flex-wrap:wrap;gap:6px}
.voyage-badge{padding:5px 10px;border-radius:999px;font-size:10px;font-weight:700;letter-spacing:.02em;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid;display:inline-flex;align-items:center}
.voyage-badge-disc{background:rgba(13,110,253,.94);color:#fff;border-color:rgba(13,110,253,.5)}
.voyage-badge-best{background:#fff;color:#0b1e3f;border-color:#fff}
.voyage-badge-instant{background:rgba(255,255,255,.94);color:#0b1e3f;border-color:rgba(255,255,255,.85)}
.voyage-wish{position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.88);border:1px solid rgba(13,110,253,.2);color:#0b1e3f;display:flex;align-items:center;justify-content:center;cursor:pointer;backdrop-filter:blur(12px);font-size:14px;padding:0}
.voyage-wish.on{background:#0d6efd;color:#fff;border-color:#0d6efd}
.voyage-tcard-pills{position:absolute;left:10px;right:10px;bottom:10px;display:flex;align-items:center;justify-content:space-between;gap:8px}
.voyage-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:999px;background:rgba(5,15,35,.52);border:1px solid rgba(255,255,255,.28);color:#fff;font-size:11px;font-weight:600;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)}
.voyage-pill em{font-style:normal;color:rgba(255,255,255,.65);font-weight:400}
.voyage-tcard-b{padding:14px}
.voyage-tcard-t{display:block;font-size:14px;font-weight:600;line-height:1.35;color:#0b1e3f;text-decoration:none;min-height:38px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.voyage-tcard-t:hover{color:#0d6efd}
.voyage-tcard-loc{margin-top:6px;font-size:12px;color:#6d7d99;display:flex;align-items:center;gap:5px}
.voyage-tcard-hi{margin-top:8px;display:flex;flex-direction:column;gap:5px}
.voyage-tcard-hi span{display:flex;align-items:center;gap:7px;font-size:11px;color:#5b6b86;line-height:1.4}
.voyage-tcard-hi i{width:16px;height:16px;border-radius:50%;background:rgba(13,110,253,.1);border:1px solid rgba(13,110,253,.18);display:inline-flex;align-items:center;justify-content:center;font-size:9px;color:#0d6efd;flex:none;font-style:normal}
.voyage-tcard-foot{margin-top:12px;padding-top:12px;border-top:1px solid rgba(13,110,253,.1);display:flex;align-items:flex-end;justify-content:space-between;gap:10px}
.voyage-tcard-price{display:flex;flex-direction:column;gap:1px;min-width:0}
.voyage-tcard-now{font-size:15px;font-weight:700;color:#0b1e3f;letter-spacing:-.01em}
.voyage-tcard-was{font-size:11px;color:#93a1b8;text-decoration:line-through}
.voyage-tcard-per{font-size:10px;color:#7a8aa5}
.voyage-tcard-add{flex:none;height:36px;padding:0 16px;border-radius:999px;background:#0d6efd;color:#fff;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 6px 16px rgba(13,110,253,.3)}
.voyage-tcard-add:hover{background:#0b5ed7;color:#fff}
.voyage-trustrow{margin-top:16px;display:flex;align-items:center;justify-content:center;gap:22px;font-size:11px;color:#7a8aa5;flex-wrap:wrap}
.voyage-trustrow .dot{width:6px;height:6px;border-radius:50%;display:inline-block;margin-right:6px}
.voyage-trustrow .dot.g{background:#22c55e}.voyage-trustrow .dot.o{background:#0d6efd}.voyage-trustrow .dot.w{background:#cbd5e1}
@media(max-width:1024px){.voyage-tgrid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){
.voyage-hero{padding:40px 0 32px}
.voyage-search{flex-direction:column;border-radius:24px}
.voyage-div{width:auto;height:1px;margin:0 10px}
.voyage-go{width:100%;border-radius:16px;height:46px}
.voyage-grid{grid-template-columns:1fr;gap:24px}.voyage-tgrid{grid-template-columns:1fr}.voyage-list-head{align-items:flex-start;flex-direction:column}.voyage-trustrow .hide-m{display:none}
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
[data-theme="dark"] .voyage-list-title h2{color:#fff}
[data-theme="dark"] .voyage-count{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);color:#fff}
[data-theme="dark"] .voyage-sort{color:rgba(255,255,255,.5)}
[data-theme="dark"] .voyage-sort a{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:rgba(255,255,255,.7)}
[data-theme="dark"] .voyage-sort a.on{background:#fff;color:#000;border-color:#fff}
[data-theme="dark"] .voyage-tcard{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);box-shadow:none;color:#fff}
[data-theme="dark"] .voyage-tcard:hover{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.16);box-shadow:0 20px 60px rgba(0,0,0,.5)}
[data-theme="dark"] .voyage-tcard-media{background:#141417}
[data-theme="dark"] .voyage-tcard-t{color:#fff}
[data-theme="dark"] .voyage-tcard-loc{color:rgba(255,255,255,.55)}
[data-theme="dark"] .voyage-tcard-hi span{color:rgba(255,255,255,.6)}
[data-theme="dark"] .voyage-tcard-hi i{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);color:#fff}
[data-theme="dark"] .voyage-tcard-foot{border-color:rgba(255,255,255,.08)}
[data-theme="dark"] .voyage-tcard-now{color:#fff}
[data-theme="dark"] .voyage-tcard-was{color:rgba(255,255,255,.4)}
[data-theme="dark"] .voyage-tcard-per{color:rgba(255,255,255,.45)}
[data-theme="dark"] .voyage-tcard-add{background:#fff;color:#000;box-shadow:none}
[data-theme="dark"] .voyage-badge-best{background:#fff;color:#000;border-color:#fff}
[data-theme="dark"] .voyage-badge-instant{background:rgba(255,255,255,.92);color:#000}
[data-theme="dark"] .voyage-wish{background:rgba(0,0,0,.4);border-color:rgba(255,255,255,.2);color:#fff}
[data-theme="dark"] .voyage-wish.on{background:#fff;color:#000;border-color:#fff}
[data-theme="dark"] .voyage-trustrow{color:rgba(255,255,255,.4)}
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

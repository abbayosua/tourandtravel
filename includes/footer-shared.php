<?php
/**
 * includes/footer-shared.php — SATU footer untuk semua halaman publik (reusable).
 * Pengganti footer.php + footer-klook.php (keduanya kini shim ke file ini).
 * Kolom Layanan memakai getNavMenus() — dinamis mengikuti admin/nav-menus.php.
 */
if (!function_exists('getNavMenus')) require_once __DIR__ . '/nav-menus.php';
$footMenus = getNavMenus();
?>
</main>
<!-- Footer Klook-style -->
<footer id="kontak" class="bg-dark text-light pt-5 pb-3 mt-5 voyage-footer">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7 text-center" data-testid="newsletter-block">
                <h5 class="fw-bold mb-2"><?= t('Dapatkan Penawaran Terbaik') ?></h5>
                <p class="text-secondary small mb-3"><?= t('Berlangganan newsletter kami untuk promo eksklusif & tips perjalanan.') ?></p>
                <form class="d-flex gap-2 klook-newsletter-form" id="newsletterForm" style="max-width: 460px; margin: 0 auto;">
                    <div class="input-group" style="border-radius: var(--radius-full); overflow: hidden;">
                        <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control border-0 shadow-none" id="newsletterEmail" placeholder="<?= t('Alamat email Anda') ?>" required>
                    </div>
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold flex-shrink-0 klook-newsletter-btn" type="submit"><?= t('Berlangganan') ?></button>
                </form>
                <div class="klook-newsletter-msg small mt-2"></div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <?php $footLogo = function_exists('siteLogoUrl') ? siteLogoUrl() : ''; ?>
                <h5 class="fw-bold mb-3"><?php if ($footLogo): ?><img src="<?= e($footLogo) ?>" alt="<?= e(siteName()) ?>" style="height:30px;width:auto" data-testid="brand-logo"><?php else: ?><i class="bi bi-airplane-engines-fill"></i><?php endif; ?> <?= siteName() ?></h5>
                <p class="text-secondary small"><?= t('Partner perjalanan terpercaya Anda. Kami menyediakan paket wisata domestik & internasional dengan harga terbaik.') ?></p>
                <div class="d-flex gap-3 mt-3">
                    <?php foreach (['instagram' => 'bi-instagram', 'facebook' => 'bi-facebook', 'youtube' => 'bi-youtube', 'tiktok' => 'bi-tiktok'] as $sn => $snIcon): $snUrl = (string)getSetting('social_' . $sn, ''); if (!filter_var($snUrl, FILTER_VALIDATE_URL)) continue; ?>
                    <a href="<?= e($snUrl) ?>" target="_blank" rel="noopener" class="text-light fs-5" aria-label="<?= e(ucfirst($sn)) ?>" data-testid="social-<?= $sn ?>"><i class="bi <?= $snIcon ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-3">
                <h6 class="fw-bold mb-3"><?= t('Layanan') ?></h6>
                <ul class="list-unstyled small">
                    <?php foreach (array_slice($footMenus, 0, 5) as $fm): ?>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/<?= e($fm['url']) ?>" class="text-secondary text-decoration-none hover-light"><?= t($fm['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="col-md-3">
                <h6 class="fw-bold mb-3"><?= t('Bantuan') ?></h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/track.php" class="text-secondary text-decoration-none hover-light"><?= t('Lacak Booking') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/destinasi.php" class="text-secondary text-decoration-none hover-light"><?= t('Destinasi') ?></a></li>
                    <?php if (isLoggedIn()): ?>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/profile.php" class="text-secondary text-decoration-none hover-light"><?= t('Profil') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/my-bookings.php" class="text-secondary text-decoration-none hover-light"><?= t('Booking Saya') ?></a></li>
                    <?php else: ?>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/login.php" class="text-secondary text-decoration-none hover-light"><?= t('Login') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/register.php" class="text-secondary text-decoration-none hover-light"><?= t('Daftar') ?></a></li>
                    <?php endif; ?>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/wishlist.php" class="text-secondary text-decoration-none hover-light"><?= t('Wishlist') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/about.php" class="text-secondary text-decoration-none hover-light"><?= t('Tentang Kami') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/terms.php" class="text-secondary text-decoration-none hover-light"><?= t('Ketentuan Layanan') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/privacy.php" class="text-secondary text-decoration-none hover-light"><?= t('Kebijakan Privasi') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/refund-policy.php" class="text-secondary text-decoration-none hover-light"><?= t('Kebijakan Refund') ?></a></li>
                </ul>
            </div>

            <div class="col-md-3">
                <h6 class="fw-bold mb-3"><?= t('Kontak') ?></h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-geo-alt-fill me-2"></i> <?= t('Taman Mediterania Blok JJ3 no 19, Batam, Kepulauan Riau, Indonesia') ?></li>
                    <li class="mb-2"><i class="bi bi-telephone-fill me-2"></i> 08117774884</li>
                    <li class="mb-2"><i class="bi bi-whatsapp me-2"></i> 08117774884</li>
                    <li class="mb-2"><i class="bi bi-envelope-fill me-2"></i> hello@tourandtravel.web.id</li>
                </ul>
                <h6 class="fw-bold mt-3"><?= t('Jam Operasional') ?></h6>
                <p class="mb-0 text-secondary small"><?= t('Senin - Sabtu: 08:00 - 20:00') ?></p>
                <p class="text-secondary small"><?= t('Minggu: 09:00 - 15:00') ?></p>
            </div>
        </div>

        <hr class="border-secondary my-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3 text-secondary small">
                <span><?= t('Metode Pembayaran') ?>:</span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold"><?= t('Visa') ?></span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold"><?= t('Mastercard') ?></span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold"><?= t('PayPal') ?></span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold"><?= t('Bank Transfer') ?></span>
            </div>
            <div class="text-secondary small">
                <i class="bi bi-shield-check me-1"></i><?= t('Pembayaran aman & terenkripsi') ?>
            </div>
        </div>

        <hr class="border-secondary my-3">
        <p class="text-center text-secondary mb-0 small">&copy; <?= date('Y') ?> <?= siteName() ?>. <?= t('All rights reserved.') ?></p>
    </div>
</footer>

<!-- Wishlist toast -->
<div id="wlToast" class="position-fixed top-0 start-50 translate-middle-x mt-3 d-none" style="z-index: 1090;" data-testid="wishlist-toast" role="status" aria-live="polite">
    <div id="wlToastInner" class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow text-white bg-dark">
        <i id="wlToastIcon" class="bi bi-heart-fill text-danger"></i>
        <span id="wlToastMsg" class="small fw-semibold"></span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script src="<?= BASE_URL ?>/assets/js/currency.js?v=<?= filemtime(__DIR__ . '/../assets/js/currency.js') ?>"></script>
<script>
// Anti double-submit: form bertanda data-submit-once menonaktifkan tombolnya
// dan menampilkan spinner saat dikirim. Nilai tombol (jika ada) dipindah ke
// hidden input agar tetap terkirim meski tombolnya dinonaktifkan.
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM' || !form.hasAttribute('data-submit-once')) return;
    if (e.defaultPrevented) return;
    var btn = (e.submitter && e.submitter.form === form) ? e.submitter : form.querySelector('button[type="submit"], input[type="submit"]');
    if (!btn || btn.disabled) return;
    if (btn.name) {
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = btn.name;
        h.value = btn.value;
        form.appendChild(h);
    }
    btn.disabled = true;
    if (btn.tagName === 'BUTTON') {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + (window.I18N ? window.I18N.t('Memproses...') : '');
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var current = localStorage.getItem('currency') || 'IDR';
    var label = document.getElementById('currencyLabel');
    if (label) label.textContent = current;
    document.querySelectorAll('.currency-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTimeout(function() {
                var c = localStorage.getItem('currency') || 'IDR';
                if (label) label.textContent = c;
            }, 50);
        });
    });
});
</script>
<script>
function wlTr(key) { return (window.I18N && typeof window.I18N.t === 'function') ? window.I18N.t(key) : key; }
var _wlToastTimer = null;
function showWlToast(msg, type, duration) {
    var wrap = document.getElementById('wlToast');
    var txt = document.getElementById('wlToastMsg');
    var ico = document.getElementById('wlToastIcon');
    if (!wrap || !txt) return;
    txt.textContent = msg;
    if (ico) ico.className = 'bi ' + (type === 'removed' ? 'bi-heart text-white-50' : type === 'error' ? 'bi-exclamation-triangle-fill text-warning' : type === 'login' ? 'bi-box-arrow-in-right text-info' : 'bi-heart-fill text-danger');
    if (type === 'login') {
        wrap.style.cursor = 'pointer';
        wrap.onclick = function() { window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search); };
    } else {
        wrap.style.cursor = 'default';
        wrap.onclick = null;
    }
    wrap.classList.remove('d-none');
    if (_wlToastTimer) clearTimeout(_wlToastTimer);
    _wlToastTimer = setTimeout(function() { wrap.classList.add('d-none'); }, duration || (type === 'login' ? 3500 : 2500));
}
function toggleWishlist(btn, tourId, itemType, ev) {
    try {
        ev = ev || (typeof window !== 'undefined' && window.event ? window.event : null);
        if (ev) {
            if (ev.preventDefault) ev.preventDefault();
            if (ev.stopPropagation) ev.stopPropagation();
            ev.cancelBubble = true;
        }
    } catch (e) {}
    itemType = itemType || 'tour';
    <?php if (!isLoggedIn()): ?>
    showWlToast(wlTr('Klik Login untuk menambahkan wishlist'), 'login');
    return false;
    <?php endif; ?>
    var icon = btn.querySelector('i');
    if (btn.dataset.wlBusy === '1') return false;
    btn.dataset.wlBusy = '1';
    var prevIcon = icon ? icon.className : '';
    var prevCls = btn.className;
    btn.classList.add('opacity-50');
    fetch('wishlist-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'tour_id=' + encodeURIComponent(tourId) + '&item_type=' + encodeURIComponent(itemType) + '&action=toggle'
    })
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(d) {
            if (d.status === 'added') {
                if (icon) icon.className = 'bi bi-heart-fill';
                if (btn.classList.contains('voyage-wish')) {
                    btn.classList.add('on');
                } else {
                    btn.className = btn.className.replace(/text-\w+/g, '').trim() + ' text-danger';
                }
                showWlToast(wlTr('Ditambahkan ke wishlist'), 'added');
            } else if (d.status === 'removed') {
                if (icon) icon.className = 'bi bi-heart';
                if (btn.classList.contains('voyage-wish')) {
                    btn.classList.remove('on');
                } else {
                    btn.className = btn.className.replace(/text-\w+/g, '').trim() + ' text-white';
                }
                showWlToast(wlTr('Dihapus dari wishlist'), 'removed');
            }
        })
        .catch(function() {
            // Feedback error singkat, lalu kembalikan tampilan semula.
            if (icon) icon.className = 'bi bi-exclamation-triangle-fill';
            btn.classList.add('text-warning');
            btn.title = '<?= t('Gagal menyimpan wishlist. Coba lagi.') ?>';
            showWlToast(wlTr('Gagal menyimpan wishlist. Coba lagi.'), 'error');
            setTimeout(function() {
                if (icon) icon.className = prevIcon;
                btn.className = prevCls;
            }, 1500);
        })
        .finally(function() {
            btn.dataset.wlBusy = '0';
            btn.classList.remove('opacity-50');
        });
    return false;
}
</script>
<?php require_once __DIR__ . '/components/social-proof.php'; ?>
<?php require_once __DIR__ . '/components/live-chat.php'; ?>
<?php require_once __DIR__ . '/components/bottom-nav.php'; ?>

<script>
(function() {
    function tick() {
        document.querySelectorAll('.flash-countdown[data-deadline]').forEach(function(el) {
            var end = new Date(el.dataset.deadline).getTime();
            var diff = Math.floor((end - Date.now()) / 1000);
            if (diff <= 0) { el.textContent = ''; return; }
            var d = Math.floor(diff / 86400), h = Math.floor(diff % 86400 / 3600), m = Math.floor(diff % 3600 / 60), s = diff % 60;
            el.textContent = (d > 0 ? d + 'h ' : '') + h + 'j ' + String(m).padStart(2, '0') + 'm ' + String(s).padStart(2, '0') + 'd';
        });
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css">
<script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>

</body>
</html><script>
(function () {
    var match = location.href.match(/[?&]slug=([^&]+)/);
    var title = document.title.split(' - ')[0];
    if (match && /detail\.php$/.test(location.pathname)) {
        var list = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
        list = list.filter(function (x) { return x.slug !== match[1]; });
        list.unshift({ slug: match[1], title: title, url: location.pathname + '?slug=' + match[1] });
        localStorage.setItem('recentlyViewed', JSON.stringify(list.slice(0, 6)));
    }
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var lazyImages = document.querySelectorAll('.lazy-image');
    lazyImages.forEach(function(img) {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', function() {
                this.classList.add('loaded');
            });
            img.addEventListener('error', function() {
                this.classList.add('loaded');
            });
        }
    });
});
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Footer Klook-style -->
<footer id="kontak" class="bg-dark text-light pt-5 pb-3 mt-5">
    <!-- Newsletter signup -->
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7 text-center">
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
            <!-- Kolom 1: Brand -->
            <div class="col-md-3">
                <h5 class="fw-bold mb-3"><i class="bi bi-airplane-engines-fill"></i> <?= SITE_NAME ?></h5>
                <p class="text-secondary small"><?= t('Partner perjalanan terpercaya Anda. Kami menyediakan paket wisata domestik & internasional dengan harga terbaik.') ?></p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-light fs-5"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-light fs-5"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-light fs-5"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="text-light fs-5"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>

            <!-- Kolom 2: Layanan -->
            <div class="col-md-3">
                <h6 class="fw-bold mb-3"><?= t('Layanan') ?></h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/tours.php" class="text-secondary text-decoration-none hover-light"><?= t('Paket Tour') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/hotels.php" class="text-secondary text-decoration-none hover-light"><?= t('Hotel') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/flights.php" class="text-secondary text-decoration-none hover-light"><?= t('Pesawat') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/ferries.php" class="text-secondary text-decoration-none hover-light"><?= t('Ferry') ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/rental-cars.php" class="text-secondary text-decoration-none hover-light"><?= t('Rental Mobil') ?></a></li>
                </ul>
            </div>

            <!-- Kolom 3: Bantuan -->
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
                </ul>
            </div>

            <!-- Kolom 4: Kontak -->
            <div class="col-md-3">
                <h6 class="fw-bold mb-3"><?= t('Kontak') ?></h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-geo-alt-fill me-2"></i> Jl. Merdeka No. 123, Jakarta</li>
                    <li class="mb-2"><i class="bi bi-telephone-fill me-2"></i> 021-12345678</li>
                    <li class="mb-2"><i class="bi bi-whatsapp me-2"></i> 0812-3456-7890</li>
                    <li class="mb-2"><i class="bi bi-envelope-fill me-2"></i> info@wanderlusttravel.com</li>
                </ul>
                <h6 class="fw-bold mt-3"><?= t('Jam Operasional') ?></h6>
                <p class="mb-0 text-secondary small">Senin - Sabtu: 08:00 - 20:00</p>
                <p class="text-secondary small">Minggu: 09:00 - 15:00</p>
            </div>
        </div>

        <!-- Payment icons -->
        <hr class="border-secondary my-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 text-secondary small">
                <span><?= t('Metode Pembayaran') ?>:</span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold">Visa</span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold">Mastercard</span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold">PayPal</span>
                <span class="badge bg-white bg-opacity-10 text-light px-3 py-2 rounded-pill fw-semibold">Bank Transfer</span>
            </div>
            <div class="text-secondary small">
                <i class="bi bi-shield-check me-1"></i><?= t('Pembayaran aman & terenkripsi') ?>
            </div>
        </div>

        <!-- Copyright -->
        <hr class="border-secondary my-3">
        <p class="text-center text-secondary mb-0 small">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. <?= t('All rights reserved.') ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script src="<?= BASE_URL ?>/assets/js/currency.js?v=<?= filemtime(__DIR__ . '/../assets/js/currency.js') ?>"></script>
<script>
// Update currency label in navbar
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
function toggleWishlist(btn, tourId) {
    <?php if (!isLoggedIn()): ?>
    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
    <?php endif; ?>
    var icon = btn.querySelector('i');
    fetch('wishlist-ajax.php?tour_id=' + tourId + '&action=toggle')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'added') {
                icon.className = 'bi bi-heart-fill';
                btn.className = btn.className.replace(/text-\w+/g, '').trim() + ' text-danger';
            } else if (d.status === 'removed') {
                icon.className = 'bi bi-heart';
                btn.className = btn.className.replace(/text-\w+/g, '').trim() + ' text-white';
            }
        });
}
</script>
<script>
// Newsletter submit handler (uses newsletter-ajax.php)
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('newsletterForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var email = document.getElementById('newsletterEmail').value.trim();
        var msg = document.querySelector('.klook-newsletter-msg');
        if (!msg) return;
        fetch('newsletter-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            msg.textContent = d.message || (d.success ? 'OK' : 'Gagal');
            msg.className = 'klook-newsletter-msg small mt-2 ' + (d.success ? 'text-success' : 'text-danger');
            if (d.success) form.reset();
        })
        .catch(function() {
            msg.textContent = 'Terjadi kesalahan. Coba lagi.';
            msg.className = 'klook-newsletter-msg small mt-2 text-danger';
        });
    });
});
</script>
</body>
</html>
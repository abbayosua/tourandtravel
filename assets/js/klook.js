/**
 * klook.js — TourAndTravel Klook-style interactions
 * Hero carousel, navbar scroll shadow, newsletter AJAX, confetti, price calculator
 */

(function () {
    'use strict';

    /* ============================================================
       1. Hero Carousel Auto-Slide
       ============================================================ */
    function initHeroCarousel() {
        var carousel = document.getElementById('heroCarousel');
        if (!carousel) return;
        // Bootstrap carousel auto-slide via data-bs-ride="carousel" (handled by Bootstrap)
        // Pause on hover untuk UX
        carousel.addEventListener('mouseenter', function () {
            var bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) bsCarousel.pause();
        });
        carousel.addEventListener('mouseleave', function () {
            var bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) bsCarousel.cycle();
        });
    }

    /* ============================================================
       2. Navbar Scroll Shadow
       ============================================================ */
    function initNavbarScroll() {
        var navbar = document.querySelector('.klook-navbar-wrap');
        if (!navbar) return;
        var checkScroll = function () {
            if (window.scrollY > 10) {
                navbar.classList.add('klook-navbar-shadow');
            } else {
                navbar.classList.remove('klook-navbar-shadow');
            }
        };
        window.addEventListener('scroll', checkScroll, { passive: true });
        checkScroll();
    }

    /* ============================================================
       3. Newsletter AJAX
       ============================================================ */
    function initNewsletter() {
        var form = document.getElementById('newsletterForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var email = document.getElementById('newsletterEmail');
            if (!email || !email.value.trim()) return;
            var msg = document.querySelector('.klook-newsletter-msg');
            if (!msg) return;

            fetch('newsletter-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email.value.trim())
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                msg.textContent = d.message || (d.success ? 'Berhasil! Cek email Anda.' : 'Gagal. Coba lagi.');
                msg.className = 'klook-newsletter-msg small mt-2 ' + (d.success ? 'text-success' : 'text-danger');
                if (d.success) email.value = '';
            })
            .catch(function () {
                msg.textContent = 'Terjadi kesalahan. Coba lagi nanti.';
                msg.className = 'klook-newsletter-msg small mt-2 text-danger';
            });
        });
    }

    /* ============================================================
       4. Confetti Animation (Booking Success)
       ============================================================ */
    function initConfetti() {
        var container = document.getElementById('confetti-container');
        if (!container) return;

        var colors = ['#0d6efd', '#6610f2', '#198754', '#ffc107', '#dc3545', '#0dcaf0'];
        var confettiCount = 80;

        for (var i = 0; i < confettiCount; i++) {
            var confetti = document.createElement('div');
            confetti.className = 'confetti-piece';
            confetti.style.cssText = [
                'position: absolute',
                'width: ' + (Math.random() * 8 + 4) + 'px',
                'height: ' + (Math.random() * 8 + 4) + 'px',
                'background: ' + colors[Math.floor(Math.random() * colors.length)],
                'left: ' + Math.random() * 100 + '%',
                'top: -10px',
                'opacity: ' + (Math.random() * 0.5 + 0.3),
                'border-radius: ' + (Math.random() > 0.5 ? '50%' : '2px'),
                'animation: confetti-fall ' + (Math.random() * 2 + 1.5) + 's ease-out forwards',
                'animation-delay: ' + (Math.random() * 0.8) + 's',
                'transform: rotate(' + (Math.random() * 360) + 'deg)',
                'z-index: 1050',
                'pointer-events: none'
            ].join('; ');
            container.appendChild(confetti);
        }

        // Clean up after animation
        setTimeout(function () {
            container.innerHTML = '';
        }, 4000);
    }

    /* ============================================================
       5. Price Calculator (Hotel: total per night × rooms)
       ============================================================ */
    function initPriceCalculator() {
        var totalDisplay = document.getElementById('totalDisplay');
        if (!totalDisplay) return;

        var checkinInput = document.getElementById('checkin');
        var checkoutInput = document.getElementById('checkout');
        var roomsSelect = document.getElementById('rooms');
        var pricePerNight = parseFloat(totalDisplay.getAttribute('data-price') || '0');
        var nightsDisplay = document.getElementById('nightsDisplay');

        function updateTotal() {
            if (!checkinInput || !checkoutInput || !roomsSelect) return;
            var ci = checkinInput.value;
            var co = checkoutInput.value;
            if (!ci || !co) return;

            var diff = Math.floor((new Date(co) - new Date(ci)) / (1000 * 60 * 60 * 24));
            if (diff < 1) {
                if (nightsDisplay) nightsDisplay.textContent = '0';
                totalDisplay.textContent = 'Rp 0';
                return;
            }

            var rooms = parseInt(roomsSelect.value) || 1;
            var total = pricePerNight * diff * rooms;

            if (nightsDisplay) nightsDisplay.textContent = diff + ' malam' + (rooms > 1 ? ' x ' + rooms + ' kamar' : '');

            // Format Rupiah
            totalDisplay.textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
        }

        if (checkinInput) checkinInput.addEventListener('change', updateTotal);
        if (checkoutInput) checkoutInput.addEventListener('change', updateTotal);
        if (roomsSelect) roomsSelect.addEventListener('change', updateTotal);

        // Run once on load
        updateTotal();
    }

    /* ============================================================
       6. Promo Code AJAX
       ============================================================ */
    function applyPromo(inputId, resultId, subtotal) {
        var input = document.getElementById(inputId);
        var result = document.getElementById(resultId);
        if (!input || !result) return;

        var code = input.value.trim();
        if (!code) {
            result.textContent = 'Masukkan kode promo';
            result.className = 'klook-promo-result small mt-1 text-danger';
            return;
        }

        fetch('apply-promo-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'code=' + encodeURIComponent(code) + '&subtotal=' + encodeURIComponent(subtotal)
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                result.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>' + d.message +
                    ' Diskon: <strong>Rp ' + Number(d.discount).toLocaleString('id-ID') + '</strong>';
                result.className = 'klook-promo-result small mt-1 text-success';
            } else {
                result.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i>' + (d.message || 'Kode promo tidak valid');
                result.className = 'klook-promo-result small mt-1 text-danger';
            }
        })
        .catch(function () {
            result.textContent = 'Terjadi kesalahan. Coba lagi nanti.';
            result.className = 'klook-promo-result small mt-1 text-danger';
        });
    }

    /* ============================================================
       7. Flight Search Sticky (Traveloka) — scroll spy
       ============================================================ */
    function initFlightSticky() {
        var form = document.getElementById('flightSearchForm');
        if (!form) return;
        var wrap = form.closest('.card');
        if (!wrap) return;
        var offset = wrap.offsetTop;
        var stuck = false;
        var onScroll = function () {
            var scrolled = window.scrollY > offset + 40;
            if (scrolled && !stuck) {
                stuck = true;
                wrap.classList.add('traveloka-search-sticky');
            } else if (!scrolled && stuck) {
                stuck = false;
                wrap.classList.remove('traveloka-search-sticky');
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ============================================================
       8. Flight Card Hover (Traveloka) — border primary
       ============================================================ */
    function initFlightCardHover() {
        document.querySelectorAll('.flight-card').forEach(function (card) {
            card.classList.add('klook-hover-card');
        });
    }

    /* ============================================================
       9. Init on DOMContentLoaded
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initHeroCarousel();
        initNavbarScroll();
        initNewsletter();
        initConfetti();
        initPriceCalculator();
        initFlightSticky();
        initFlightCardHover();
    });

})();
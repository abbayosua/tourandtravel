<?php
/**
 * Preset Flight — Hero Tiket.com-style: search form penerbangan dominan
 * di atas gradasi warna khas (merah-oranye). Submit GET ke flights.php dengan
 * param yang sama: trip_type, from, to, date, return_date, pax.
 * Autocomplete kota memakai endpoint existing city-search-ajax.php.
 */
$flightHeroImg = ($heroSlides[0]['image'] ?? '');
$flightDate = date('Y-m-d', strtotime('+7 days'));
?>
<section class="position-relative overflow-hidden" style="min-height: 72vh; background: linear-gradient(160deg, #e33d2e 0%, #f26522 55%, #ffb347 100%);">
    <?php if ($flightHeroImg): ?>
        <img src="<?= e($flightHeroImg) ?>" class="position-absolute w-100 h-100" style="object-fit: cover; inset: 0; opacity: .28;" alt="">
    <?php endif; ?>

    <div class="container position-relative py-5" style="z-index: 2;">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8 text-white">
                <h1 class="display-5 fw-bold mb-2">
                    <?= !empty($heroSlides[0]['title']) ? e(t($heroSlides[0]['title'])) : t('Terbang ke Kota Impian') ?>
                </h1>
                <p class="lead mb-4 text-white-50">
                    <?= !empty($heroSlides[0]['subtitle']) ? e(t($heroSlides[0]['subtitle'])) : t('Tiket pesawat murah ke ratusan kota, setiap hari.') ?>
                </p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="bg-white rounded-4 shadow-lg p-3 p-md-4">
                    <form method="GET" action="flights.php" id="homeFlightSearchForm">
                        <!-- Trip type -->
                        <div class="d-flex gap-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="trip_type" value="oneway" id="homeTripOneway" checked>
                                <label class="form-check-label small fw-semibold" for="homeTripOneway"><?= t('One Way') ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="trip_type" value="roundtrip" id="homeTripRoundtrip">
                                <label class="form-check-label small fw-semibold" for="homeTripRoundtrip"><?= t('Round Trip') ?></label>
                            </div>
                        </div>

                        <div class="row g-2 g-md-3 align-items-end">
                            <div class="col-md position-relative search-wrapper">
                                <label class="form-label small fw-semibold text-muted"><?= t('Dari') ?></label>
                                <input type="text" name="from" class="form-control city-search" placeholder="<?= t('Kota asal (CGK)...') ?>" autocomplete="off" data-target="homeFromDropdown" id="homeFromInput" required>
                                <div class="search-dropdown" id="homeFromDropdown"></div>
                            </div>
                            <div class="col-md-auto text-center d-none d-md-block" style="padding-bottom: .5rem;">
                                <i class="bi bi-arrow-left-right text-muted"></i>
                            </div>
                            <div class="col-md position-relative search-wrapper">
                                <label class="form-label small fw-semibold text-muted"><?= t('Ke') ?></label>
                                <input type="text" name="to" class="form-control city-search" placeholder="<?= t('Kota tujuan (DPS)...') ?>" autocomplete="off" data-target="homeToDropdown" id="homeToInput" required>
                                <div class="search-dropdown" id="homeToDropdown"></div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted" id="homeDateLabel"><?= t('Pergi') ?></label>
                                <input type="date" name="date" class="form-control" value="<?= e($flightDate) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                            </div>
                            <div class="col-md-2 home-return-date-col" style="display:none;">
                                <label class="form-label small fw-semibold text-muted"><?= t('Pulang') ?></label>
                                <input type="date" name="return_date" class="form-control" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted"><?= t('Penumpang') ?></label>
                                <select name="pax" class="form-select">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> <?= t('pax') ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-auto col-12">
                                <button type="submit" class="btn btn-danger w-100 px-4 py-2 fw-semibold">
                                    <i class="bi bi-search me-1"></i><?= t('Cari Penerbangan') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            <a href="flights.php" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold"><i class="bi bi-airplane me-1"></i><?= t('Semua Penerbangan') ?></a>
            <a href="flights.php?from=Jakarta&to=Denpasar" class="btn btn-sm btn-outline-light rounded-pill px-3">Jakarta → Denpasar</a>
            <a href="flights.php?from=Jakarta&to=Singapore" class="btn btn-sm btn-outline-light rounded-pill px-3">Jakarta → Singapore</a>
            <a href="flights.php?from=Jakarta&to=Kuala Lumpur" class="btn btn-sm btn-outline-light rounded-pill px-3">Jakarta → Kuala Lumpur</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Autocomplete kota (endpoint existing)
    document.querySelectorAll('#homeFlightSearchForm .city-search').forEach(function (input) {
        var dropdown = document.getElementById(input.getAttribute('data-target'));
        if (!dropdown) return;
        var debounce;
        input.addEventListener('input', function () {
            clearTimeout(debounce);
            var q = this.value.trim();
            if (q.length < 1) { dropdown.classList.remove('show'); return; }
            debounce = setTimeout(function () {
                fetch('city-search-ajax.php?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.length) { dropdown.classList.remove('show'); return; }
                        var html = '';
                        data.forEach(function (item) {
                            html += '<div class="search-item" data-label="' + item.label.replace(/"/g, '&quot;') + '"><div class="search-icon bg-light text-primary"><i class="bi bi-geo-alt"></i></div><div class="fw-semibold small">' + item.label + '</div></div>';
                        });
                        dropdown.innerHTML = html;
                        dropdown.classList.add('show');
                        dropdown.querySelectorAll('.search-item').forEach(function (el) {
                            el.addEventListener('click', function () {
                                input.value = this.getAttribute('data-label');
                                dropdown.classList.remove('show');
                            });
                        });
                    });
            }, 200);
        });
        document.addEventListener('click', function (e) {
            if (!input.closest('.search-wrapper').contains(e.target)) dropdown.classList.remove('show');
        });
    });

    // Toggle return date
    document.querySelectorAll('#homeFlightSearchForm input[name="trip_type"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var returnCol = document.querySelector('.home-return-date-col');
            var dateLabel = document.getElementById('homeDateLabel');
            if (document.getElementById('homeTripRoundtrip').checked) {
                returnCol.style.display = '';
                dateLabel.textContent = '<?= t('Pergi') ?>';
            } else {
                returnCol.style.display = 'none';
                dateLabel.textContent = '<?= t('Pergi') ?>';
            }
        });
    });
});
</script>

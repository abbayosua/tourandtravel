<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/easybook.php';

$pageTitle = t('Ferry');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$search = isset($_GET['search']);

// Default route: Batam → Singapore
if (!$from && !$to && !$search) {
    $from = 'Batam';
    $to = 'Singapore';
}

// Place IDs for Easybook
$fromPlaceId = 0;
$toPlaceId = 0;
$fromSubPlace = 0;
$toSubPlace = 0;

$fromPlaceId = (int)($_GET['from_pid'] ?? 0);
$fromSubPlace = (int)($_GET['from_spid'] ?? 0);
$toPlaceId = (int)($_GET['to_pid'] ?? 0);
$toSubPlace = (int)($_GET['to_spid'] ?? 0);

if ($from && !$fromPlaceId) {
    $places = easybookSearchPlace($from);
    if (!empty($places)) {
        foreach ($places as $p) {
            if ($p['spid'] == 0 && $p['pn']) {
                $fromPlaceId = $p['pid'];
                break;
            }
        }
        if (!$fromPlaceId && !empty($places[0])) {
            $fromPlaceId = $places[0]['pid'];
        }
    }
}

if ($to && !$toPlaceId) {
    $places = easybookSearchPlace($to);
    if (!empty($places)) {
        foreach ($places as $p) {
            if ($p['spid'] == 0 && $p['pn']) {
                $toPlaceId = $p['pid'];
                break;
            }
        }
        if (!$toPlaceId && !empty($places[0])) {
            $toPlaceId = $places[0]['pid'];
        }
    }
}

$ferries = [];
$easybookError = null;

if ($search && $fromPlaceId && $toPlaceId) {
    $ferries = easybookSearchTrips($fromPlaceId, $toPlaceId, $date, $fromSubPlace, $toSubPlace);
    if (empty($ferries)) {
        $easybookError = 'Tidak ada jadwal ferry ditemukan untuk rute/tanggal ini.';
    }
} elseif ($search && (!$fromPlaceId || !$toPlaceId)) {
    $easybookError = 'Kota asal/tujuan tidak ditemukan. Coba: Batam, Singapore, Johor.';
}

// Also get local DB ferries as fallback
if (empty($ferries)) {
    $sql = "SELECT * FROM ferries WHERE is_active = 1";
    $params = [];
    if ($from && $to) {
        $sql .= " AND (route_from LIKE ? OR route_to LIKE ?)";
        $params[] = "%$from%";
        $params[] = "%$to%";
    }
    $sql .= " ORDER BY price ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $dbFerries = $stmt->fetchAll();
    
    // Convert DB format to match Easybook format
    foreach ($dbFerries as $f) {
        $ferries[] = [
            'company' => $f['company'],
            'departure_time' => date('H:i', strtotime($f['departure_time'])),
            'available_seats' => 0,
            'price' => $f['price'],
            'from_terminal' => $f['route_from'],
            'to_terminal' => $f['route_to'],
            'date' => date('Y-m-d'),
        ];
    }
}

require_once 'includes/header.php';
?>
<section class="py-4 bg-light" style="min-height: 80vh;">
    <div class="container">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-water me-2"></i><?= t('Cari Ferry') ?></h5>
                <?php if ($easybookError): ?>
                    <div class="alert alert-warning py-2 small"><?= e($easybookError) ?></div>
                <?php endif; ?>
                <form method="GET" class="row g-2 align-items-end" id="ferrySearchForm">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted"><?= t('Dari') ?></label>
                        <div class="search-wrapper">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-geo-alt text-primary"></i></span>
                                <input type="text" name="from" class="form-control ferry-search" placeholder="Kota atau terminal..." value="<?= e($from) ?>" autocomplete="off" data-target="fromDropdown" id="fromInput">
                            </div>
                            <div class="search-dropdown" id="fromDropdown"></div>
                        </div>
                        <input type="hidden" name="from_pid" value="<?= $fromPlaceId ?>">
                        <input type="hidden" name="from_spid" value="<?= $fromSubPlace ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted"><?= t('Ke') ?></label>
                        <div class="search-wrapper">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-geo-alt text-danger"></i></span>
                                <input type="text" name="to" class="form-control ferry-search" placeholder="Kota atau terminal..." value="<?= e($to) ?>" autocomplete="off" data-target="toDropdown" id="toInput">
                            </div>
                            <div class="search-dropdown" id="toDropdown"></div>
                        </div>
                        <input type="hidden" name="to_pid" value="<?= $toPlaceId ?>">
                        <input type="hidden" name="to_spid" value="<?= $toSubPlace ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted"><?= t('Tanggal') ?></label>
                        <input type="date" name="date" class="form-control" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit" name="search" value="1"><i class="bi bi-search me-1"></i><?= t('Cari') ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($search && !empty($ferries)): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= count($ferries) ?> <?= t('Jadwal Ferry') ?></h5>
                <small class="text-muted"><?= tglIndonesia($date) ?> · <?= e($from) ?> → <?= e($to) ?></small>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($ferries as $f): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($f['company']) ?></h6>
                                <?php if ($f['available_seats'] > 0): ?>
                                    <small class="text-muted"><?= $f['available_seats'] ?> <?= t('kursi tersedia') ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary fs-5"><?= formatCurrencySpan($f['price']) ?></div>
                                <small class="text-muted">/ <?= t('orang') ?></small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-3 p-3 bg-light rounded">
                            <div class="text-center flex-grow-1">
                                <div class="fs-4 fw-bold"><?= e($f['departure_time']) ?></div>
                                <small class="text-muted fw-semibold"><?= e($f['from_terminal'] ?: $from) ?></small>
                            </div>
                            <div class="text-center">
                                <i class="bi bi-arrow-right fs-4 text-primary"></i>
                            </div>
                            <div class="text-center flex-grow-1">
                                <div class="fs-4 fw-bold">-</div>
                                <small class="text-muted fw-semibold"><?= e($f['to_terminal'] ?: $to) ?></small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.easybook.com/id-id/ferry?fromplace=<?= $fromPlaceId ?>&toplace=<?= $toPlaceId ?>&departtime=<?= e($date) ?>" 
                               class="btn btn-primary w-100 rounded-pill fw-semibold" target="_blank">
                                <i class="bi bi-ticket-detailed me-1"></i><?= t('Pilih & Booking') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif ($search && empty($ferries)): ?>
        <div class="text-center py-5">
            <i class="bi bi-water fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Tidak ada jadwal ferry untuk rute/tanggal ini.') ?></p>
            <p class="small text-muted"><?= t('Coba: Batam → Singapore, atau ubah tanggal.') ?></p>
            <a href="ferries.php" class="btn btn-primary rounded-pill px-4"><?= t('Reset') ?></a>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-water fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Masukkan kota asal dan tujuan untuk mencari jadwal ferry.') ?></p>
            <p class="small text-muted"><?= t('Contoh: Batam → Singapore, Johor → Batam.') ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
<script>
document.querySelectorAll('.ferry-search').forEach(function(input) {
    var dropdownId = input.getAttribute('data-target');
    var dropdown = document.getElementById(dropdownId);
    var hiddenPid = input.closest('.col-md-3').querySelector('input[name*="_pid"]');
    var hiddenSpid = input.closest('.col-md-3').querySelector('input[name*="_spid"]');
    if (!dropdown) return;
    var debounce;
    input.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < 1) { dropdown.classList.remove('show'); return; }
        debounce = setTimeout(function() {
            fetch('ajax/ferry-place-search.php?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.length) { dropdown.classList.remove('show'); return; }
                    var html = '';
                    data.forEach(function(item) {
                        html += '<div class="search-item" data-label="' + item.label.replace(/"/g,'&quot;') + '" data-pid="' + item.pid + '" data-spid="' + (item.spid||0) + '"><div class="search-icon bg-light text-primary"><i class="bi bi-geo-alt"></i></div><div><div class="fw-semibold small">' + item.label + '</div></div></div>';
                    });
                    dropdown.innerHTML = html;
                    dropdown.classList.add('show');
                    dropdown.querySelectorAll('.search-item').forEach(function(el){
                        el.addEventListener('click', function(){
                            input.value = this.getAttribute('data-label');
                            if (hiddenPid) hiddenPid.value = this.getAttribute('data-pid');
                            if (hiddenSpid) hiddenSpid.value = this.getAttribute('data-spid');
                            dropdown.classList.remove('show');
                        });
                    });
                });
        }, 200);
    });
    document.addEventListener('click', function(e) {
        if (!input.closest('.search-wrapper').contains(e.target)) dropdown.classList.remove('show');
    });
});
</script>
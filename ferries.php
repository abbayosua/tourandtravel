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
            'vessel_name' => $f['vessel_name'] ?? '',
            'departure_time' => date('H:i', strtotime($f['departure_time'])),
            'arrival_time' => $f['arrival_time'] ? date('H:i', strtotime($f['arrival_time'])) : '-',
            'available_seats' => 0,
            'price' => $f['price'],
            'from_terminal' => $f['route_from'],
            'to_terminal' => $f['route_to'],
            'date' => date('Y-m-d'),
        ];
    }
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<?php if (!$search): ?>
<section class="hero-uifactory">
  <div class="hero-bg-shape hero-bg-shape--1"></div>
  <div class="hero-bg-shape hero-bg-shape--2"></div>
  <div class="hero-content">
    <h1 class="hero-headline">Jelajahi Lebih Banyak,<br><span>Nikmati Perjalanannya.</span></h1>
    <p class="hero-sub">Pesan tiket ferry, pesawat, dan kereta api dalam satu tempat.</p>
  </div>
  <div class="booking-card">
    <div class="booking-card-inner">
      <div class="booking-tabs" role="tablist">
        <a href="ferries.php" class="booking-tab active" role="tab"><i class="bi bi-water"></i> <?= t('Ferry') ?></a>
        <a href="flights.php" class="booking-tab" role="tab"><i class="bi bi-airplane"></i> <?= t('Pesawat') ?></a>
        <a href="trains.php" class="booking-tab" role="tab"><i class="bi bi-train-front"></i> <?= t('Kereta') ?></a>
        <a href="rental-cars.php" class="booking-tab" role="tab"><i class="bi bi-car-front"></i> <?= t('Rental') ?></a>
      </div>
      <div class="booking-form">
        <?php if ($easybookError): ?>
          <div class="alert alert-warning py-2 small mb-3"><?= e($easybookError) ?></div>
        <?php endif; ?>
        <form method="GET" id="ferrySearchForm">
          <div class="form-search-row">
            <div class="search-field">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Dari') ?></span>
              <input type="text" name="from" class="ferry-search" placeholder="<?= t('Kota atau terminal') ?>" value="<?= e($from) ?>" autocomplete="off" data-target="fromDropdown" id="fromInput">
              <div class="search-dropdown" id="fromDropdown"></div>
              <input type="hidden" name="from_pid" value="<?= $fromPlaceId ?>">
              <input type="hidden" name="from_spid" value="<?= $fromSubPlace ?>">
            </div>
            <button type="button" class="swap-btn" onclick="var f=document.querySelector('[name=from]'),t=document.querySelector('[name=to]'),tmp=f.value;f.value=t.value;t.value=tmp;" aria-label="Tukar"><div class="swap-btn-inner"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16l-4-4 4-4"/><path d="M17 8l4 4-4 4"/><line x1="3" y1="12" x2="21" y2="12"/></svg></div></button>
            <div class="search-field">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7 11.5 7.3 11.8a1 1 0 0 0 1.4 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg> <?= t('Ke') ?></span>
              <input type="text" name="to" class="ferry-search" placeholder="<?= t('Kota atau terminal') ?>" value="<?= e($to) ?>" autocomplete="off" data-target="toDropdown" id="toInput">
              <div class="search-dropdown" id="toDropdown"></div>
              <input type="hidden" name="to_pid" value="<?= $toPlaceId ?>">
              <input type="hidden" name="to_spid" value="<?= $toSubPlace ?>">
            </div>
            <div class="search-field">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= t('Tanggal') ?></span>
              <input type="date" name="date" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+360 days')) ?>">
            </div>
            <div class="search-field">
              <span class="search-field-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> <?= t('Penumpang') ?></span>
              <select name="passengers">
                <?php for ($p=1; $p<=9; $p++): ?>
                <option value="<?= $p ?>" <?= ((int)($_GET['passengers'] ?? 1)) === $p ? 'selected' : '' ?>><?= $p ?> <?= t('orang') ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <button class="search-btn" type="submit" name="search" value="1">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <?= t('Cari') ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($search): ?>
<section class="py-4 bg-light" style="min-height:60vh;">
    <div class="container">
        <?php if ($search && !empty($ferries)): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= count($ferries) ?> <?= t('Jadwal Ferry') ?></h5>
                <small class="text-muted"><?= formatDate($date) ?> · <?= e($from) ?> → <?= e($to) ?></small>
            </div>
        </div>
        <?php
        $minPrice = min(array_column($ferries, 'price'));
        ?>
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle easybook-table">
                    <thead class="table-light">
                        <tr>
                            <th><?= t('Perusahaan') ?></th>
                            <th><?= t('Kapal') ?></th>
                            <th><?= t('Berangkat') ?></th>
                            <th><?= t('Tiba') ?></th>
                            <th class="text-end"><?= t('Harga') ?></th>
                            <th class="text-center"><?= t('Aksi') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ferries as $f): 
                            $isCheapest = (float)$f['price'] === (float)$minPrice;
                        ?>
                        <tr class="<?= $isCheapest ? 'table-success' : '' ?>">
                            <td>
                                <?php $logo = easybookCompanyLogo($f['company']); ?>
                                <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="<?= e($f['company']) ?>" class="me-2" style="height:20px;vertical-align:middle;"><?php endif; ?>
                                <strong><?= e($f['company']) ?></strong>
                            </td>
                            <td><?= e($f['vessel_name'] ?? '-') ?></td>
                            <td><span class="fw-semibold"><?= e($f['departure_time']) ?></span><br><small class="text-muted"><?= e($f['from_terminal'] ?: $from) ?></small></td>
                            <td><span class="fw-semibold"><?= e($f['arrival_time'] ?? '-') ?></span><br><small class="text-muted"><?= e($f['to_terminal'] ?: $to) ?></small></td>
                            <td class="text-end">
                                <span class="fw-bold text-primary<?= $isCheapest ? ' fs-5' : '' ?>"><?= formatRupiah($f['price']) ?></span>
                                <?php if ($isCheapest): ?><span class="badge bg-success ms-1" style="font-size:10px;"><?= t('Hemat') ?></span><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="ferry-booking.php?company=<?= urlencode($f['company']) ?>&from=<?= urlencode($f['from_terminal'] ?: $from) ?>&to=<?= urlencode($f['to_terminal'] ?: $to) ?>&date=<?= e($f['date'] ?? $date) ?>&time=<?= urlencode($f['departure_time']) ?>&price=<?= (float)$f['price'] ?>&passengers=<?= $passengers ?? 1 ?>&vessel=<?= urlencode($f['vessel_name'] ?? '') ?>&from_terminal=<?= urlencode($f['from_terminal'] ?? '') ?>&to_terminal=<?= urlencode($f['to_terminal'] ?? '') ?>&arrival_time=<?= urlencode($f['arrival_time'] ?? '') ?>"
                                   class="btn btn-sm btn-primary rounded-pill px-3" data-testid="btn-book-ferry">
                                    <?= t('Pesan') ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="alert alert-info py-2 mt-3 small" style="border-left: 3px solid var(--primary);">
            <i class="bi bi-info-circle me-1"></i><?= t('Sesampainya di pelabuhan, tunjukkan e-ticket ke petugas.') ?>
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
<?php endif; ?>
<?php require_once 'includes/footer-klook.php'; ?>
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
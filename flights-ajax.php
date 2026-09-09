<?php
/**
 * flights-ajax.php — AJAX pagination for flights listing.
 * Returns HTML fragment of flight cards for infinite scroll.
 *
 * GET page=2&from=&to=&date=&... → HTML fragment
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/duffel.php';
require_once 'includes/flightlist.php';

header('Content-Type: text/html; charset=utf-8');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$date = $_GET['date'] ?? date('Y-m-d', strtotime('+3 days'));
$class = $_GET['class'] ?? '';
$passengers = max(1, min(9, (int)($_GET['passengers'] ?? 1)));
$sort = $_GET['sort'] ?? 'price';

// Get local schedules
$sql = "SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.is_active=1 AND fs.departure_date=?";
$params = [$date];
if ($from) { $sql .= " AND f.from_city LIKE ?"; $params[] = "%$from%"; }
if ($to) { $sql .= " AND f.to_city LIKE ?"; $params[] = "%$to%"; }
if ($class) { $sql .= " AND f.class=?"; $params[] = $class; }
$sql .= " ORDER BY fs.price ASC LIMIT $limit OFFSET $offset";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$flights = $stmt->fetchAll();

ob_start();
if (count($flights) > 0):
foreach ($flights as $s):
    $dep = date('H:i', strtotime($s['departure_time']));
    $arr = date('H:i', strtotime($s['arrival_time']));
    $airlineCode = substr($s['airline'], 0, 2);
    $fromShort = explode('(', $s['from_city'])[0];
    $toShort = explode('(', $s['to_city'])[0];
?>
<div class="col-12" data-page="<?= $page ?>">
    <div class="card border-0 shadow-sm flight-card">
        <div class="card-body p-3 p-md-4">
            <div class="row align-items-center g-3">
                <div class="col-md-2 d-flex align-items-center gap-2">
                    <div class="flight-logo d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary fw-bold rounded-2" style="width:44px;height:44px;"><?= $airlineCode ?></div>
                    <div>
                        <div class="fw-semibold small"><?= e($s['airline']) ?></div>
                        <small class="text-muted" style="font-size:11px;"><?= e($s['flight_number']) ?></small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <div class="text-center" style="min-width:70px;">
                            <div class="fs-5 fw-bold"><?= $dep ?></div>
                            <small class="text-muted"><?= e(trim($fromShort)) ?></small>
                        </div>
                        <div class="flex-grow-1 text-center px-2">
                            <div class="border-top border-2 border-secondary position-relative">
                                <i class="bi bi-airplane-fill text-secondary position-absolute top-0 start-50 translate-middle" style="font-size:12px;"></i>
                            </div>
                            <small class="text-muted d-block mt-1"><?= e($s['duration']) ?></small>
                        </div>
                        <div class="text-center" style="min-width:70px;">
                            <div class="fs-5 fw-bold"><?= $arr ?></div>
                            <small class="text-muted"><?= e(trim($toShort)) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <span class="badge bg-secondary rounded-pill"><?= ucfirst($s['class']) ?></span>
                    <small class="d-block text-muted mt-1"><?= t('Sisa') ?> <?= $s['available_seats'] ?> <?= t('kursi') ?></small>
                </div>
                <div class="col-md-2 text-center">
                    <div class="fs-5 fw-bold text-primary"><?= formatCurrencySpan($s['price']) ?></div>
                    <small class="text-muted">/ <?= t('orang') ?></small>
                </div>
                <div class="col-md-2 text-md-end">
                    <a href="flight-detail.php?schedule_id=<?= $s['id'] ?>" class="btn btn-outline-primary rounded-pill px-4 w-100"><?= t('Pilih (Lokal)') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
endforeach;
else:
?>
<div class="text-center py-4 text-muted" data-empty="true">
    <i class="bi bi-airplane fs-1"></i>
    <p class="mt-2"><?= t('Semua penerbangan sudah dimuat.') ?></p>
</div>
<?php
endif;

echo ob_get_clean();

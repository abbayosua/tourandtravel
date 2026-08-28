<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/duffel.php';

$offerId = trim($_GET['offer_id'] ?? '');
$scheduleId = (int)($_GET['schedule_id'] ?? 0);

$mode = null;
$offer = null;
$schedule = null;
$offerError = null;

if ($offerId) {
    $res = duffelGetOffer($offerId);
    if (isset($res['error'])) {
        $offerError = $res['error'];
    } else {
        $offer = $res['offer'];
        $mode = 'duffel';
    }
} elseif ($scheduleId) {
    $stmt = db()->prepare("SELECT fs.*, f.airline, f.flight_number, f.from_city, f.to_city, f.departure_time, f.arrival_time, f.duration, f.class FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE fs.id=? AND fs.is_active=1");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch();
    if ($schedule) $mode='local';
}

if (!$mode) {
    if ($offerError) {
        $pageTitle='Penerbangan Tidak Tersedia';
        require_once 'includes/header.php';
        echo '<div class="container py-5 text-center"><h5>Penerbangan tidak tersedia</h5><p class="text-muted small">'.e($offerError).'</p><a href="flights.php" class="btn btn-primary mt-3">Kembali Cari</a></div>';
        require_once 'includes/footer.php'; exit;
    }
    header('Location: flights.php'); exit;
}

if ($mode==='duffel') $pageTitle = ($offer['slices'][0]['segments'][0]['marketing_carrier']['name']??'Penerbangan').' - Live';
else $pageTitle = $schedule['airline'].' '.$schedule['flight_number'];

$bookingSuccess = '';
$bookingError = '';
$bookingResult = null;

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['book_duffel'])) {
    if ($mode!=='duffel' || !$offer) {
        $bookingError='Sesi penerbangan tidak valid. Silakan cari ulang.';
    } elseif (!isLoggedIn()) {
        $bookingError='Silakan login terlebih dahulu.';
    } else {
        $name = trim($_POST['name']??'');
        $phone = trim($_POST['phone']??'');
        $email = trim($_POST['email']?? (getUser()['email']??''));
        $passengersCount = (int)($_POST['passengers']??1);
        if (!$name || !$phone) $bookingError='Nama dan telepon wajib diisi.';
        else {
            $offerPassengers = $offer['passengers'] ?? [];
            if (empty($offerPassengers)) $bookingError='Data penumpang tidak tersedia.';
            else {
                $parts = preg_split('/\s+/', trim($name), 2);
                $given = $parts[0]; $family = $parts[1] ?? $given;
                $passengersData = [];
                foreach($offerPassengers as $op) {
                    $passengersData[] = ['id'=>$op['id'],'title'=>'mr','given_name'=>$given,'family_name'=>$family,'born_on'=>'1990-01-01','email'=>$email ?: 'guest@example.com','phone_number'=>preg_match('/^\+/', $phone)?$phone:'+62'.ltrim(preg_replace('/[^0-9]/','',$phone),'0'),'gender'=>'m'];
                }
                if ($passengersCount !== count($passengersData)) {
                    if ($passengersCount!==1) $bookingError='Untuk multi-penumpang, silakan cari dengan jumlah penumpang yang sesuai.';
                }
                if (!$bookingError) {
                    $orderRes = duffelCreateOrder($offerId, $passengersData);
                    if (isset($orderRes['error'])) $bookingError = 'Gagal memesan: '.$orderRes['error'];
                    else {
                        $order = $orderRes['order'];
                        $bookingResult = $order;
                        $bookingSuccess = 'Penerbangan berhasil dipesan! Booking ref: '.($order['booking_reference']??$order['id']);
                    }
                }
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD']==='POST' && $mode==='local') {
    $name = trim($_POST['name']??'');
    $phone = trim($_POST['phone']??'');
    $passengers = (int)($_POST['passengers']??1);
    if ($name && $phone && $passengers>0) {
        $total = $schedule['price'] * $passengers;
        $bookingSuccess = "Penerbangan berhasil dipesan! Total: ".formatRupiah($total);
    }
}

require_once 'includes/header.php';
?>
<section class="py-4 bg-light" style="min-height: 80vh;">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="flights.php">Pesawat</a></li>
            <li class="breadcrumb-item active"><?= $mode==='duffel' ? e($offer['slices'][0]['segments'][0]['marketing_carrier']['iata_code']??'Live').' '.e($offer['slices'][0]['segments'][0]['marketing_carrier_flight_number']??'') : e($schedule['flight_number']); ?></li>
        </ol></nav>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($mode==='duffel' && $offer):
                    $slice = $offer['slices'][0]; $seg = $slice['segments'][0]; $carrier = $seg['marketing_carrier'] ?? $seg['operating_carrier'];
                    $dep = date('H:i', strtotime($seg['departing_at'])); $arr = date('H:i', strtotime($seg['arriving_at']));
                    $duration = duffelFormatDuration($slice['duration'] ?? $seg['duration']);
                    $cc = $seg['passengers'][0]['cabin_class'] ?? 'economy';
                ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 text-center">
                        <span class="badge bg-success mb-2">Live via Duffel (Test Mode)</span>
                        <h4 class="fw-bold"><?= e($carrier['name'] ?? 'Duffel Airways') ?></h4>
                        <span class="badge bg-primary"><?= e(($carrier['iata_code']??'ZZ').' '.($seg['marketing_carrier_flight_number']??'')) ?></span>
                        <span class="badge bg-<?= $cc==='economy'?'success':($cc==='business'?'warning text-dark':'danger') ?> ms-1"><?= ucfirst($cc) ?></span>
                        <div class="d-flex justify-content-center align-items-center gap-4 my-4">
                            <div class="text-center"><div class="fs-3 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e($seg['origin']['iata_code'] ?? '') ?></small><div class="small text-muted"><?= e($seg['origin']['name'] ?? '') ?></div></div>
                            <div class="text-center"><i class="bi bi-airplane-fill fs-3 text-primary d-block mb-1"></i><span class="text-muted small"><?= e($duration) ?></span><div class="text-muted small"><?= e(date('d M Y', strtotime($seg['departing_at']))) ?></div></div>
                            <div class="text-center"><div class="fs-3 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e($seg['destination']['iata_code'] ?? '') ?></small><div class="small text-muted"><?= e($seg['destination']['name'] ?? '') ?></div></div>
                        </div>
                        <div class="d-flex justify-content-center gap-4 text-muted small mb-3">
                            <span><?php if(count($slice['segments'])>1) echo (count($slice['segments'])-1).' transit'; else echo 'Langsung'; ?></span>
                            <span><?= e($offer['total_currency'].' '.number_format((float)$offer['total_amount'],2)) ?> total</span>
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Pesan Penerbangan</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <span class="fw-semibold"><?= e($carrier['name'] ?? '') ?> · <?= e($seg['marketing_carrier_flight_number'] ?? '') ?></span>
                            <span class="fw-bold text-primary fs-5"><?= duffelFormatPrice($offer['total_amount'], $offer['total_currency']) ?><small class="fw-normal fs-6 text-muted">/org</small></span>
                        </div>
                        <?php if ($bookingSuccess): ?><div class="alert alert-success py-2" id="bookingSuccess"><?= e($bookingSuccess) ?><?php if($bookingResult): ?><br><small>Order ID: <?= e($bookingResult['id']) ?> · Ref: <?= e($bookingResult['booking_reference'] ?? '-') ?></small><?php endif; ?></div><?php endif; ?>
                        <?php if ($bookingError): ?><div class="alert alert-danger py-2" id="bookingError"><?= e($bookingError) ?></div><?php endif; ?>
                        <?php if ($bookingSuccess && $bookingResult): ?>
                            <a href="flights.php" class="btn btn-primary w-100">Cari Penerbangan Lain</a>
                        <?php elseif (!isLoggedIn()): ?>
                            <div class="text-center py-3"><p class="fw-semibold mb-2">Login untuk Memesan</p><a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary w-100">Masuk / Daftar</a></div>
                        <?php else: ?>
                        <form method="POST" id="duffelBookingForm">
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" name="name" class="form-control" placeholder="Nama Lengkap" value="<?= e(getUser()['name'] ?? '') ?>" required></div>
                                <div class="col-md-6"><input type="text" name="phone" class="form-control" placeholder="No. Telepon" value="<?= e(getUser()['phone'] ?? '') ?>" required></div>
                                <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email" value="<?= e(getUser()['email'] ?? '') ?>" required></div>
                                <div class="col-md-3"><select name="passengers" class="form-select"><option value="1">1 Penumpang</option></select></div>
                                <div class="col-md-3 d-grid"><button type="submit" name="book_duffel" value="1" class="btn btn-primary fw-semibold">Pesan Sekarang</button></div>
                            </div>
                            <small class="text-muted d-block mt-2">Test mode: booking akan membuat order Duffel Airways dummy.</small>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 text-center">
                        <h4 class="fw-bold"><?= e($schedule['airline']) ?></h4>
                        <span class="badge bg-primary"><?= e($schedule['flight_number']) ?></span>
                        <span class="badge bg-<?= $schedule['class'] === 'economy' ? 'success' : ($schedule['class'] === 'business' ? 'warning text-dark' : 'danger') ?> ms-1"><?= ucfirst($schedule['class']) ?></span>
                        <div class="d-flex justify-content-center align-items-center gap-4 my-4">
                            <div class="text-center"><div class="fs-3 fw-bold"><?= date('H:i', strtotime($schedule['departure_time'])) ?></div><small class="text-muted"><?= e(explode('(', $schedule['from_city'])[0]) ?></small></div>
                            <div class="text-center"><i class="bi bi-airplane-fill fs-3 text-primary d-block mb-1"></i><span class="text-muted small"><?= e($schedule['duration']) ?></span><div class="text-muted small"><?= tglIndonesia($schedule['departure_date']) ?></div></div>
                            <div class="text-center"><div class="fs-3 fw-bold"><?= date('H:i', strtotime($schedule['arrival_time'])) ?></div><small class="text-muted"><?= e(explode('(', $schedule['to_city'])[0]) ?></small></div>
                        </div>
                        <div class="d-flex justify-content-center gap-4 text-muted small mb-3"><span>Langsung</span><span>Sisa <?= $schedule['available_seats'] ?> kursi</span></div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Pesan Penerbangan</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom"><span class="fw-semibold"><?= e($schedule['airline']) ?> · <?= e($schedule['flight_number']) ?></span><span class="fw-bold text-primary fs-5"><?= formatRupiah($schedule['price']) ?><small class="fw-normal fs-6 text-muted">/org</small></span></div>
                        <?php if ($bookingSuccess): ?><div class="alert alert-success py-2"><?= $bookingSuccess ?></div><?php endif; ?>
                        <?php if (!isLoggedIn()): ?><div class="text-center py-3"><p class="fw-semibold mb-2">Login untuk Memesan</p><a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary w-100">Masuk / Daftar</a></div><?php elseif ($schedule['available_seats'] < 1): ?><div class="alert alert-danger py-2">Kursi penuh untuk jadwal ini.</div><?php else: ?>
                        <form method="POST"><div class="row g-2"><div class="col-md-6"><input type="text" name="name" class="form-control" placeholder="Nama Lengkap" value="<?= e(getUser()['name'] ?? '') ?>" required></div><div class="col-md-6"><input type="text" name="phone" class="form-control" placeholder="No. Telepon" value="<?= e(getUser()['phone'] ?? '') ?>" required></div><div class="col-md-4"><select name="passengers" class="form-select"><?php for ($i=1; $i<=min(9, $schedule['available_seats']); $i++): ?><option value="<?= $i ?>"><?= $i ?> Penumpang</option><?php endfor; ?></select></div><div class="col-md-8 d-grid"><button type="submit" class="btn btn-primary fw-semibold">Pesan Sekarang</button></div></div></form><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>

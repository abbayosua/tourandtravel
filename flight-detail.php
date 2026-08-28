<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/duffel.php';
require_once 'includes/flightlist.php';

$offerId = trim($_GET['offer_id'] ?? '');
$flOfferId = trim($_GET['fl_offer_id'] ?? '');
$scheduleId = (int)($_GET['schedule_id'] ?? 0);

$mode = null;
$offer = null;
$schedule = null;
$offerError = null;

if ($flOfferId) {
    $res = flightlistGetOffer($flOfferId);
    if (isset($res['error'])) {
        $offerError = $res['error'];
    } else {
        $offer = $res['offer'];
        $mode = 'flightlist';
        $offerId = $flOfferId; // unify for booking
    }
} elseif ($offerId) {
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
elseif ($mode==='flightlist') $pageTitle = ($offer['airlines'][0] ?? 'Penerbangan').' - FlightList';
else $pageTitle = $schedule['airline'].' '.$schedule['flight_number'];

$bookingSuccess = '';
$bookingError = '';
$bookingResult = null;

// FlightList booking (demo: no real order, redirect to Kiwi deep_link if present)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['book_fl'])) {
    if ($mode!=='flightlist' || !$offer) {
        $bookingError='Sesi penerbangan tidak valid. Silakan cari ulang.';
    } elseif (!isLoggedIn()) {
        $bookingError='Silakan login terlebih dahulu.';
    } else {
        $name = trim($_POST['name']??''); $phone = trim($_POST['phone']??''); $email = trim($_POST['email']?? (getUser()['email']??''));
        if (!$name || !$phone) $bookingError='Nama dan telepon wajib diisi.';
        else {
            $deep = $offer['deep_link'] ?? null;
            $price = $offer['price'] ?? 0;
            $bookingSuccess = 'Penerbangan FlightList dipilih! ' . ($deep ? 'Lanjutkan di Kiwi: ' . $deep : 'Harga: $'.number_format($price,0));
            $bookingResult = ['id'=>$offer['id'], 'deep_link'=>$deep, 'price'=>$price];
        }
    }
} elseif ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['book_duffel'])) {
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
            <li class="breadcrumb-item active"><?= $mode==='duffel' ? e($offer['slices'][0]['segments'][0]['marketing_carrier']['iata_code']??'Live').' '.e($offer['slices'][0]['segments'][0]['marketing_carrier_flight_number']??'') : ($mode==='flightlist' ? e(($offer['airlines'][0]??'FL').' '.($offer['route'][0]['flight_no']??'')) : e($schedule['flight_number'])); ?></li>
        </ol></nav>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($mode==='flightlist' && $offer):
                    $route0 = $offer['route'][0] ?? $offer; $airlineCode = $offer['airlines'][0] ?? ($route0['airline'] ?? 'JT');
                    $dep = date('H:i', strtotime($offer['local_departure'] ?? $route0['local_departure'] ?? '')); $arr = date('H:i', strtotime($offer['local_arrival'] ?? $route0['local_arrival'] ?? ''));
                    $duration = flightlistFormatDuration($offer['duration']['departure'] ?? $offer['duration']['total'] ?? 0);
                    $cc = 'economy'; $stops = count($offer['route']) > 1 ? count($offer['route'])-1 : 0;
                ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 text-center">
                        <span class="badge bg-primary mb-2">FlightList (Real)</span>
                        <h4 class="fw-bold"><?= e($airlineCode) ?> <?= e($route0['flight_no'] ?? '') ?></h4>
                        <span class="badge bg-primary"><?= e($airlineCode.' '.($route0['flight_no'] ?? '')) ?></span>
                        <span class="badge bg-success ms-1">Ekonomi</span>
                        <div class="d-flex justify-content-center align-items-center gap-4 my-4">
                            <div class="text-center"><div class="fs-3 fw-bold"><?= $dep ?></div><small class="text-muted"><?= e($offer['flyFrom'] ?? $route0['flyFrom'] ?? '') ?></small><div class="small text-muted"><?= e($offer['cityFrom'] ?? '') ?></div></div>
                            <div class="text-center"><i class="bi bi-airplane-fill fs-3 text-primary d-block mb-1"></i><span class="text-muted small"><?= e($duration) ?></span><div class="text-muted small"><?= e(date('d M Y', strtotime($offer['local_departure'] ?? ''))) ?></div><small class="text-muted"><?= $stops>0 ? $stops.' transit' : 'Langsung' ?></small></div>
                            <div class="text-center"><div class="fs-3 fw-bold"><?= $arr ?></div><small class="text-muted"><?= e($offer['flyTo'] ?? $route0['flyTo'] ?? '') ?></small><div class="small text-muted"><?= e($offer['cityTo'] ?? '') ?></div></div>
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Pesan Penerbangan</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom"><span class="fw-semibold"><?= e($airlineCode.' '.($route0['flight_no'] ?? '')) ?></span><span class="fw-bold text-primary fs-5"><?= flightlistFormatPrice($offer['price']) ?></span></div>
                        <?php if ($bookingSuccess): ?><div class="alert alert-success py-2" id="bookingSuccess"><?= e($bookingSuccess) ?><?php if($bookingResult && !empty($bookingResult['deep_link'])): ?><br><a href="<?= e($bookingResult['deep_link']) ?>" target="_blank" class="btn btn-sm btn-success mt-2">Buka di Kiwi.com</a><?php endif; ?></div><?php endif; ?>
                        <?php if ($bookingError): ?><div class="alert alert-danger py-2" id="bookingError"><?= e($bookingError) ?></div><?php endif; ?>
                        <?php if ($bookingSuccess && $bookingResult): ?>
                            <a href="flights.php" class="btn btn-primary w-100">Cari Lagi</a>
                        <?php elseif (!isLoggedIn()): ?>
                            <div class="text-center py-3"><p class="fw-semibold mb-2">Login untuk Memesan</p><a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary w-100">Masuk / Daftar</a></div>
                        <?php else: ?>
                        <form method="POST" id="flBookingForm">
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" name="name" class="form-control" placeholder="Nama Lengkap" value="<?= e(getUser()['name'] ?? '') ?>" required></div>
                                <div class="col-md-6"><input type="text" name="phone" class="form-control" placeholder="No. Telepon" value="<?= e(getUser()['phone'] ?? '') ?>" required></div>
                                <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email" value="<?= e(getUser()['email'] ?? '') ?>" required></div>
                                <div class="col-md-3"><select name="passengers" class="form-select"><option value="1">1 Penumpang</option></select></div>
                                <div class="col-md-3 d-grid"><button type="submit" name="book_fl" value="1" class="btn btn-primary fw-semibold">Pesan Sekarang</button></div>
                            </div>
                            <small class="text-muted d-block mt-2">FlightList: klik Pesan menampilkan link Kiwi.com (affiliate).</small>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($mode==='duffel' && $offer):
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

<?php
/**
 * AJAX: mulai pembayaran untuk satu booking (gateway: midtrans|tripay|xendit).
 * POST: booking_type, booking_id, [method utk tripay: BRIVA|BCAVA|QRIS|...]
 * Response midtrans: {ok, redirect_url?, order_id?, error?}
 * Response tripay: {ok, gateway:'tripay', pay_code?, pay_url?, checkout_url?, reference?, error?}
 * Response xendit: {ok, gateway:'xendit', invoice_id?, invoice_url?, redirect_url?, error?}
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payments.php';
require_once __DIR__ . '/../includes/tripay.php';
require_once __DIR__ . '/../includes/singapay.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => t('Method not allowed')]);
    exit;
}

if (!tripayInstantEnabled()) {
    echo json_encode(['ok' => false, 'error' => 'payment_disabled']);
    exit;
}

$bookingType = $_POST['booking_type'] ?? 'tour';
$bookingId = (int)($_POST['booking_id'] ?? 0);

$typeMap = [
    'tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings',
    'train' => 'train_bookings', 'transfer' => 'transfer_bookings',
    'attraction' => 'attraction_bookings', 'esim' => 'connectivity_bookings',
    'ferry' => 'ferry_bookings', 'pelni' => 'pelni_bookings',
];
if (!isset($typeMap[$bookingType]) || $bookingId < 1) {
    echo json_encode(['ok' => false, 'error' => 'invalid_booking']);
    exit;
}

// Ambil booking (milik user login ATAU guest dengan code — untuk tour wajib cocok id+status pending)
$table = $typeMap[$bookingType];
$priceCol = ['tour' => 'total_price', 'hotel' => 'total_price', 'flight' => 'total_price',
             'train' => 'total_price', 'transfer' => 'total_price', 'attraction' => 'total_price', 'esim' => 'total_price',
             'ferry' => 'total_price', 'pelni' => 'total_price'];
$stmt = db()->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();
if (!$booking) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}
if (($booking['status'] ?? '') !== 'pending') {
    echo json_encode(['ok' => false, 'error' => 'not_payable']);
    exit;
}

// Ownership: booking milik user lain tidak boleh dipayarkan (kecuali guest booking)
if (!empty($booking['user_id']) && (int)$booking['user_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// Nominal yang ditagih: pakai snapshot charged_amount (IDR) bila ada,
// fallback ke total_price (produk yang memang IDR).
$gross = (isset($booking['charged_amount']) && $booking['charged_amount'] !== null && (float)$booking['charged_amount'] > 0)
    ? (float)$booking['charged_amount']
    : (float)$booking[$priceCol[$bookingType]];
$customer = [
    'name' => $booking['name'] ?? null,
    'email' => $booking['email'] ?? null,
    'phone' => $booking['phone'] ?? null,
];

if (tripayGateway() === 'tripay') {
    $tripayMethod = preg_replace('/[^A-Z0-9_]/', '', strtoupper((string)($_POST['method'] ?? 'BRIVA')));
    $result = createTripayTransaction($bookingId, $gross, $customer, $tripayMethod ?: 'BRIVA', $bookingType);
    $result['gateway'] = 'tripay';
} elseif (tripayGateway() === 'xendit') {
    require_once __DIR__ . '/../includes/xendit.php';
    $result = createXenditInvoice($bookingId, $gross, $customer, $bookingType);
    $result['gateway'] = 'xendit';
} elseif (singapayEnabled()) {
    if (($_POST['pay_kind'] ?? 'va') === 'card' && !empty($_POST['card_number'])) {
        $result = singapayCreateCard($bookingId, $gross, $customer, [
            'number' => (string)($_POST['card_number'] ?? ''),
            'expiry' => (string)($_POST['card_expiry'] ?? ''),
            'cvv' => (string)($_POST['card_cvv'] ?? ''),
            'holder_name' => (string)($_POST['card_holder'] ?? ''),
            'holder_email' => (string)($_POST['card_email'] ?? ''),
        ], $bookingType);
    } else {
        $bank = preg_replace('/[^A-Z]/', '', strtoupper((string)($_POST['bank'] ?? 'BRI')));
        $result = singapayCreateVa($bookingId, $gross, $customer, $bank ?: 'BRI', $bookingType);
    }
    $result['gateway'] = 'singapay';
} else {
    $result = createMidtransSnapTransaction($bookingType, $bookingId, $gross, $customer);
    $result['gateway'] = 'midtrans';
}
echo json_encode($result, JSON_UNESCAPED_SLASHES);

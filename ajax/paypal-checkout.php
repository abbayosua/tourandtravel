<?php
/**
 * ajax/paypal-checkout.php — Backlog #7: buat PayPal order untuk booking.
 * POST JSON: { booking_type, booking_id }
 * Response: { success, approve_url?, order_id?, error? }
 * Fail-soft: tanpa kredensial → { success: false, error: 'paypal_not_configured' }
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payments-paypal.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'unauthorized', 'message' => 'Login diperlukan']);
    exit;
}

$typeMap = ['tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings'];
$bookingType = $_POST['booking_type'] ?? (json_decode(file_get_contents('php://input'), true)['booking_type'] ?? '');
$bookingId = (int)($_POST['booking_id'] ?? (json_decode(file_get_contents('php://input'), true)['booking_id'] ?? 0));

if (!isset($typeMap[$bookingType]) || $bookingId < 1) {
    echo json_encode(['success' => false, 'error' => 'invalid_booking']);
    exit;
}

$table = $typeMap[$bookingType];
$stmt = db()->prepare("SELECT total_price, status FROM `$table` WHERE id = ? AND user_id = ?");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();
if (!$booking) {
    echo json_encode(['success' => false, 'error' => 'invalid_booking', 'message' => 'Booking tidak ditemukan']);
    exit;
}
if ($booking['status'] !== 'pending') {
    echo json_encode(['success' => false, 'error' => 'not_payable']);
    exit;
}

$result = paypalCreateOrder($bookingType, $bookingId, (string)$booking['total_price']);
if (isset($result['error'])) {
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}

// Catat payments row (status pending) supaya capture bisa idempotent
db()->prepare("INSERT IGNORE INTO payments (order_id, booking_type, booking_id, gross_amount, status, payment_type)
    VALUES (?, ?, ?, ?, 'pending', 'paypal')")
    ->execute([$result['id'], $bookingType, $bookingId, $booking['total_price']]);

echo json_encode([
    'success' => true,
    'order_id' => $result['id'],
    'approve_url' => $result['approve_url'],
    'amount_usd' => $result['amount_usd'] ?? null,
]);

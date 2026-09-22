<?php
/**
 * ajax/saved-payments.php — Backlog #8: kelola metode pembayaran tersimpan + 1-click charge.
 * GET               : list metode user
 * POST action=save  : simpan token (dari Midtrans getCardToken / SDK)
 * POST action=remove: soft-delete by id
 * POST action=default: set default by id
 * POST action=charge: 1-click pay (booking_type, booking_id, method_id)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/saved-payments.php';
require_once __DIR__ . '/../includes/payments.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'methods' => getSavedPaymentMethods($userId)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $token = trim($_POST['token'] ?? '');
    if (strlen($token) < 10 || strlen($token) > 255) {
        echo json_encode(['success' => false, 'error' => 'invalid_token']);
        exit;
    }
    $id = savePaymentMethod(
        $userId,
        $token,
        $_POST['brand'] ?? null,
        $_POST['masked_number'] ?? null,
        $_POST['expiry_month'] ? (int)$_POST['expiry_month'] : null,
        $_POST['expiry_year'] ? (int)$_POST['expiry_year'] : null
    );
    echo json_encode(['success' => $id > 0, 'id' => $id]);
    exit;
}

if ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    echo json_encode(['success' => removePaymentMethod($userId, $id)]);
    exit;
}

if ($action === 'default') {
    $id = (int)($_POST['id'] ?? 0);
    echo json_encode(['success' => setDefaultPaymentMethod($userId, $id)]);
    exit;
}

if ($action === 'charge') {
    if (!midtransEnabled()) {
        echo json_encode(['success' => false, 'error' => 'payment_disabled']);
        exit;
    }
    $typeMap = ['tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings'];
    $bookingType = $_POST['booking_type'] ?? 'tour';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $methodId = (int)($_POST['method_id'] ?? 0);
    if (!isset($typeMap[$bookingType]) || $bookingId < 1 || $methodId < 1) {
        echo json_encode(['success' => false, 'error' => 'invalid_request']);
        exit;
    }
    // booking milik user + pending
    $stmt = db()->prepare("SELECT total_price, status FROM `{$typeMap[$bookingType]}` WHERE id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();
    if (!$booking || $booking['status'] !== 'pending') {
        echo json_encode(['success' => false, 'error' => 'not_payable']);
        exit;
    }

    $orderId = $bookingType . '-1click-' . $bookingId . '-' . time();
    $res = chargeWithSavedToken($userId, $methodId, $orderId, (float)$booking['total_price']);
    if (isset($res['error'])) {
        echo json_encode(['success' => false, 'error' => $res['error']]);
        exit;
    }

    // Catat payments + sync booking (untuk status capture/settlement)
    $finalStatus = in_array($res['status'], ['capture', 'settlement'], true) ? 'paid' : 'pending';
    db()->prepare("INSERT INTO payments (order_id, booking_type, booking_id, gross_amount, status, payment_type, transaction_id, raw_payload, paid_at)
        VALUES (?, ?, ?, ?, ?, 'credit_card', ?, ?, IF(? IN ('capture','settlement'), NOW(), NULL))")
        ->execute([$orderId, $bookingType, $bookingId, $booking['total_price'], $finalStatus, $res['transaction_id'], json_encode($res['raw']), $res['status']]);
    db()->prepare("UPDATE `{$typeMap[$bookingType]}` SET payment_status = ? WHERE id = ?")
        ->execute([$finalStatus, $bookingId]);

    echo json_encode([
        'success' => $finalStatus === 'paid',
        'status' => $res['status'],
        'order_id' => $orderId,
        'paid' => $finalStatus === 'paid',
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'unknown_action']);

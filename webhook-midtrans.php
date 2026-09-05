<?php
/**
 * Webhook Midtrans (payment notification) — PUBLIK, tanpa session.
 *
 * Keamanan:
 *  - Verifikasi signature sha512(order_id+status_code+gross_amount+serverKey)
 *  - Payload tidak sah → 403; order tak dikenal → 404
 *  - Idempotent: notif berulang no-op (lihat handleMidtransNotification)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payments.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$notif = json_decode($raw, true);

if (!is_array($notif)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$orderKnown = false;
$stmt = db()->prepare("SELECT id FROM payments WHERE order_id = ? LIMIT 1");
$stmt->execute([(string)($notif['order_id'] ?? '')]);
$orderKnown = (bool)$stmt->fetch();

$ok = handleMidtransNotification($notif);

if (!$ok) {
    // signature salah ATAU order tak dikenal
    http_response_code($orderKnown ? 403 : 404);
    echo json_encode(['ok' => false, 'error' => $orderKnown ? 'invalid_signature' : 'order_not_found']);
    exit;
}

http_response_code(200);
echo json_encode(['ok' => true]);

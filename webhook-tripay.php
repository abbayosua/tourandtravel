<?php
/**
 * Webhook Tripay (callback payment_status) — PUBLIK, tanpa session.
 *
 * Keamanan:
 *  - Verifikasi HMAC-SHA256(raw body, privateKey) vs header X-Callback-Signature
 *  - Event harus payment_status; payload tak sah → success:false
 *  - Reference tak dikenal → success:false (Tripay retry 2 mnt x3)
 *  - Idempotent: callback berulang no-op (lihat handleTripayCallback)
 *  - Selalu balas JSON {success:bool} agar retry berhenti bila sudah diproses
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/tripay.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
$event = $_SERVER['HTTP_X_CALLBACK_EVENT'] ?? '';

if ($raw === '' || $raw === false) {
    echo json_encode(['success' => false, 'message' => 'Empty callback']);
    exit;
}

if (!hash_equals(tripayCallbackSignature((string)$raw), (string)$sigHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

$data = json_decode((string)$raw, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

if ($event !== '' && $event !== 'payment_status') {
    echo json_encode(['success' => false, 'message' => 'Unrecognized event']);
    exit;
}

$ok = handleTripayCallback($data);
if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Unknown reference']);
    exit;
}

echo json_encode(['success' => true]);

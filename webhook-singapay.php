<?php
/**
 * Webhook Singapay — PUBLIK, tanpa session.
 * Verifikasi HMAC-SHA512 (METHOD:ENDPOINT:TOKEN:HASH_BODY:TIMESTAMP),
 * lalu delegasi ke singapayHandleWebhook(). Selalu balas JSON {success:bool}.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/singapay.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
if ($raw === '' || $raw === false) {
    echo json_encode(['success' => false, 'message' => 'Empty callback']);
    exit;
}

$headers = [
    'X-Signature' => $_SERVER['HTTP_X_SIGNATURE'] ?? '',
    'X-Timestamp' => $_SERVER['HTTP_X_TIMESTAMP'] ?? '',
    'Authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
];
$endpoint = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (!singapayVerifySignature($raw, $headers, $endpoint)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

try {
    $ok = singapayHandleWebhook($data);
    echo json_encode(['success' => $ok]);
} catch (Throwable $e) {
    error_log('singapay webhook: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Processing error']);
}

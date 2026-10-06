<?php
/**
 * Webhook Xendit SANDBOX (callback invoice) — PUBLIK, tanpa session.
 *
 * Keamanan:
 *  - Verifikasi header x-callback-token vs settings xendit_callback_token
 *  - Payload tak sah → success:false
 *  - Invoice tak dikenal → success:false (Xendit retry otomatis)
 *  - Idempotent: callback berulang no-op (lihat handleXenditCallback)
 *
 * Daftarkan URL ini di dashboard Xendit → Settings → Developers → Webhooks
 * (Invoice callback): https://tourandtravel.web.id/webhook-xendit.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/xendit.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$tokenHeader = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';

if ($raw === '' || $raw === false) {
    echo json_encode(['success' => false, 'message' => 'Empty callback']);
    exit;
}

$expected = xenditCallbackToken();
if ($expected === '' || !hash_equals($expected, (string)$tokenHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid callback token']);
    exit;
}

$data = json_decode((string)$raw, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$ok = handleXenditCallback($data);
if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Unknown invoice']);
    exit;
}

echo json_encode(['success' => true]);

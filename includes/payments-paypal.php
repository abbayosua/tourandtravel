<?php
/**
 * includes/payments-paypal.php — Backlog #7: PayPal checkout (sandbox/production).
 * Alur: createOrder → user approve di PayPal → captureOrder → sync payments table (idempotent).
 *
 * Konfigurasi (config.php):
 *  - PAYPAL_CLIENT_ID, PAYPAL_SECRET   : kredensial app
 *  - PAYPAL_MODE                       : 'sandbox' | 'live' (default sandbox)
 *
 * Semua fungsi fail-soft: return ['error' => ...] tanpa throw.
 */

if (!defined('PAYPAL_MODE')) define('PAYPAL_MODE', 'sandbox');

function paypalEnabled(): bool {
    return defined('PAYPAL_CLIENT_ID') && PAYPAL_CLIENT_ID !== '' && defined('PAYPAL_SECRET') && PAYPAL_SECRET !== '';
}

function paypalApiBase(): string {
    return PAYPAL_MODE === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

/** OAuth2 token (cache per-request via static). */
function paypalAccessToken(): string {
    static $token = null;
    if ($token) return $token;
    if (!paypalEnabled()) return '';
    $ch = curl_init(paypalApiBase() . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string)$resp, true);
    $token = $data['access_token'] ?? '';
    return $token;
}

function paypalRequest(string $method, string $path, ?array $body = null): array {
    $token = paypalAccessToken();
    if ($token === '') return ['error' => 'paypal_not_configured', 'http_code' => 0];
    $ch = curl_init(paypalApiBase() . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$resp, true);
    if ($code >= 400) {
        $msg = $data['message'] ?? ($data['details'][0]['description'] ?? "HTTP $code");
        return ['error' => $msg, 'http_code' => $code, 'raw' => $data];
    }
    return ['data' => $data, 'http_code' => $code];
}

/**
 * Buat PayPal order. $amount decimal string (mis '100000.00'), $currency default USD (PayPal tidak support IDR).
 * Return ['id' => paypal_order_id, 'approve_url' => ...] atau error.
 */
function paypalCreateOrder(string $bookingType, int $bookingId, string $amount, string $currency = 'USD'): array {
    if (!paypalEnabled()) return ['error' => 'paypal_not_configured'];
    $amountUsd = paypalConvertToUsd((float)$amount);
    if ($amountUsd < 0.01) return ['error' => 'amount_too_small'];

    $res = paypalRequest('POST', '/v2/checkout/orders', [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $bookingType . '-' . $bookingId,
            'amount' => ['currency_code' => 'USD', 'value' => number_format($amountUsd, 2, '.', '')],
            'description' => ucfirst($bookingType) . ' booking #' . $bookingId,
        ]],
    ]);
    if (isset($res['error'])) return $res;
    $order = $res['data'];
    $approveUrl = '';
    foreach ($order['links'] ?? [] as $l) {
        if (($l['rel'] ?? '') === 'approve') { $approveUrl = $l['href']; break; }
    }
    return ['id' => $order['id'] ?? '', 'approve_url' => $approveUrl, 'amount_usd' => $amountUsd];
}

/** Konversi IDR → USD (rate sederhana, konsisten dengan duffelFormatPrice). */
function paypalConvertToUsd(float $amountIdr): float {
    $rate = 16200.0;
    return round($amountIdr / $rate, 2);
}

/**
 * Capture order setelah user approve. Idempotent: bila payments row sudah 'paid', no-op.
 * Return ['status' => 'paid'|'skipped', 'capture_id' => ...] atau error.
 */
function paypalCaptureOrder(string $bookingType, int $bookingId, string $paypalOrderId): array {
    if (!paypalEnabled()) return ['error' => 'paypal_not_configured'];

    // Idempotency: payments sudah paid → skip
    $chk = db()->prepare("SELECT status FROM payments WHERE order_id = ? LIMIT 1");
    $chk->execute([$paypalOrderId]);
    if (($chk->fetchColumn() ?: '') === 'paid') {
        return ['status' => 'skipped'];
    }

    $res = paypalRequest('POST', "/v2/checkout/orders/$paypalOrderId/capture");
    if (isset($res['error']) && ($res['raw']['details'][0]['issue'] ?? '') !== 'ORDER_ALREADY_CAPTURED') {
        return $res;
    }
    $order = $res['data'] ?? null;
    $status = $order['status'] ?? ($res['error'] ? 'COMPLETED' : ''); // already captured → COMPLETED
    if ($status !== 'COMPLETED') return ['error' => 'capture_not_completed', 'raw' => $order];

    $captureId = '';
    foreach ($order['purchase_units'][0]['payments']['captures'] ?? [] as $cap) {
        $captureId = $cap['id'] ?? '';
        break;
    }

    // Sync payments table (insert atau update status)
    $upd = db()->prepare("UPDATE payments SET status = 'paid', transaction_id = ?, payment_type = 'paypal', paid_at = NOW() WHERE order_id = ?");
    $upd->execute([$captureId, $paypalOrderId]);
    if ($upd->rowCount() === 0) {
        db()->prepare("INSERT INTO payments (order_id, booking_type, booking_id, gross_amount, status, payment_type, transaction_id, paid_at)
            VALUES (?, ?, ?, 0, 'paid', 'paypal', ?, NOW())")
            ->execute([$paypalOrderId, $bookingType, $bookingId, $captureId]);
    }

    // Sync booking payment_status
    $tableMap = ['tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings'];
    if (isset($tableMap[$bookingType])) {
        db()->prepare("UPDATE `{$tableMap[$bookingType]}` SET payment_status = 'paid' WHERE id = ?")
            ->execute([$bookingId]);
    }

    return ['status' => 'paid', 'capture_id' => $captureId];
}

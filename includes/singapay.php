<?php
/**
 * includes/singapay.php — Payment gateway Singapay (Virtual Account).
 *
 * Flow: access token (OAuth2 client credentials) → create VA per booking
 * → user transfer ke nomor VA → webhook va-transaction → paid.
 *
 * Sandbox: https://sandbox-payment-b2b.singapay.id
 * Production: https://payment-b2b.singapay.id
 */

function singapayBaseUrl(): string {
    return getSetting('singapay_env', 'sandbox') === 'production'
        ? 'https://payment-b2b.singapay.id'
        : 'https://sandbox-payment-b2b.singapay.id';
}

function singapayClientId(): string {
    return (string)getSetting('singapay_client_id', '');
}

function singapayClientSecret(): string {
    return (string)getSetting('singapay_client_secret', '');
}

function singapayApiKey(): string {
    return (string)getSetting('singapay_api_key', '');
}

function singapayAccountId(): string {
    return (string)getSetting('singapay_account_id', '');
}

function singapayConfigured(): bool {
    return singapayClientId() !== '' && singapayClientSecret() !== '' && singapayApiKey() !== '' && singapayAccountId() !== '';
}

/** Singapay aktif bila mode instant DAN gateway=singapay DAN kredensial terisi. */
function singapayEnabled(): bool {
    if (tripayMode() !== 'instant') return false;
    if (tripayGateway() !== 'singapay') return false;
    return singapayConfigured();
}

/** Access token (cached di settings sampai 5 menit sebelum expired). */
function singapayAccessToken(): string {
    $token = (string)getSetting('singapay_token', '');
    $expiry = (int)getSetting('singapay_token_expiry', 0);
    if ($token !== '' && $expiry > time() + 300) return $token;

    $ch = curl_init(singapayBaseUrl() . '/api/v1.1/access-token/b2b');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode(singapayClientId() . ':' . singapayClientSecret()),
            'X-PARTNER-ID: ' . singapayApiKey(),
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'client_credentials']),
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode((string)$res, true);
    if ($http !== 200 || empty($json['data']['access_token'])) {
        error_log('singapay token error: http=' . $http . ' ' . substr((string)$res, 0, 200));
        return '';
    }
    $token = (string)$json['data']['access_token'];
    $ttl = (int)($json['data']['expires_in'] ?? 3600);
    setSetting('singapay_token', $token);
    setSetting('singapay_token_expiry', time() + max(60, $ttl - 60));
    return $token;
}

/**
 * Create Virtual Account untuk satu booking. Return:
 *  ['ok'=>true, 'pay_code'=>VA number, 'va_id'=>ULID] atau ['ok'=>false,'error'=>..]
 */
function singapayCreateVa(int $bookingId, float $grossAmount, array $customer, string $bankCode = 'BRI', string $bookingType = 'tour'): array {
    if (!singapayConfigured()) return ['ok' => false, 'error' => 'singapay_not_configured'];
    if (singapayAccountId() === '') return ['ok' => false, 'error' => 'singapay_no_account'];

    $token = singapayAccessToken();
    if ($token === '') return ['ok' => false, 'error' => 'singapay_token_failed'];

    $codeTable = ['tour' => 'bookings', 'ferry' => 'ferry_bookings'][$bookingType] ?? 'bookings';
    $cb = db()->prepare("SELECT booking_code FROM `$codeTable` WHERE id = ?");
    $cb->execute([$bookingId]);
    $bookingCode = (string)($cb->fetchColumn() ?: ('BK-' . $bookingId));

    $amount = (int)round($grossAmount);
    $body = [
        'bank_code'       => $bankCode,
        'kind'            => 'temporary',
        'amount_type'      => 'closed',
        'name'             => mb_substr($customer['name'] ?? 'Pelanggan', 0, 200),
        'merchant_reff_no' => $bookingCode,
        'expired_at'       => (string)((time() + 24 * 3600) * 1000),
        'max_usage'        => 1,
        'amount'           => $amount,
    ];

    $ch = curl_init(singapayBaseUrl() . '/api/v1.0/virtual-accounts/' . singapayAccountId());
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'X-PARTNER-ID: ' . singapayApiKey(),
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode((string)$res, true);
    if ($http !== 200 || empty($json['data']['number'])) {
        error_log('singapay create VA error: http=' . $http . ' ' . substr((string)$res, 0, 300));
        return ['ok' => false, 'error' => 'singapay_va_error', 'http' => $http, 'detail' => $json['message'] ?? null];
    }
    $d = $json['data'];

    // Catat di payments (order_id = VA id, pay_code = nomor VA)
    db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, gateway, order_id, reference, pay_code, pay_url, checkout_url, gross_amount, status, payment_type)
        VALUES (?, ?, ?, 'singapay', ?, ?, ?, NULL, NULL, ?, 'pending', 'VA')")
        ->execute([$bookingType, $bookingId, $bookingCode, $d['id'], $d['id'], $d['number'], $grossAmount]);

    return ['ok' => true, 'pay_code' => $d['number'], 'va_id' => $d['id'], 'bank' => $d['bank']['short_name'] ?? $bankCode];
}

/**
 * Charge kartu kredit one-time (Visa/Mastercard/Amex + 3DS bila perlu).
 * $card = ['number','expiry' MMYY,'cvv','holder_name','holder_email'].
 * Return: ['ok'=>true,'status'=>'success|processing','payment_url'=>?,'transaction_id'=>?]
 * Bila 3DS (action=redirect + payment_url) -> user selesaikan di bank,
 * frontend polling payment-status sampai paid.
 */
function singapayCreateCard(int $bookingId, float $grossAmount, array $customer, array $card, string $bookingType = 'tour'): array {
    if (!singapayConfigured()) return ['ok' => false, 'error' => 'singapay_not_configured'];
    if (singapayAccountId() === '') return ['ok' => false, 'error' => 'singapay_no_account'];
    $token = singapayAccessToken();
    if ($token === '') return ['ok' => false, 'error' => 'singapay_token_failed'];

    $num = preg_replace('/\s+/', '', (string)($card['number'] ?? ''));
    $exp = (string)($card['expiry'] ?? '');
    $cvv = (string)($card['cvv'] ?? '');
    if (!preg_match('/^\d{13,19}$/', $num)) return ['ok' => false, 'error' => 'card_number_invalid'];
    if (!preg_match('/^\d{4}$/', $exp)) return ['ok' => false, 'error' => 'card_expiry_invalid'];
    if (!preg_match('/^\d{3,4}$/', $cvv)) return ['ok' => false, 'error' => 'card_cvv_invalid'];

    $codeTable = ['tour' => 'bookings', 'ferry' => 'ferry_bookings'][$bookingType] ?? 'bookings';
    $cb = db()->prepare("SELECT booking_code FROM `$codeTable` WHERE id = ?");
    $cb->execute([$bookingId]);
    $bookingCode = (string)($cb->fetchColumn() ?: ('BK-' . $bookingId));

    $body = [
        'amount' => (int)round($grossAmount),
        'reference_no' => $bookingCode,
        'goods_name' => mb_substr(($bookingType === 'ferry' ? 'Tiket Ferry ' : 'Paket Tour ') . $bookingCode, 0, 255),
        'customer_name' => mb_substr($customer['name'] ?? 'Pelanggan', 0, 255),
        'customer_email' => mb_substr($customer['email'] ?? 'noreply@tourandtravel.web.id', 0, 255),
        'customer_phone' => mb_substr($customer['phone'] ?? '', 0, 50),
        'customer_address' => mb_substr($customer['address'] ?? '-', 0, 500),
        'customer_city' => mb_substr($customer['city'] ?? 'Jakarta', 0, 100),
        'customer_state' => mb_substr($customer['state'] ?? 'DKI Jakarta', 0, 100),
        'customer_postal_code' => mb_substr($customer['postal'] ?? '10110', 0, 20),
        'customer_country' => 'ID',
        'card_number' => $num,
        'card_expiry' => $exp,
        'card_cvv' => $cvv,
        'card_holder_name' => mb_substr($card['holder_name'] ?? ($customer['name'] ?? 'Pelanggan'), 0, 255),
        'card_holder_email' => mb_substr($card['holder_email'] ?? ($customer['email'] ?? 'noreply@tourandtravel.web.id'), 0, 255),
    ];

    $ch = curl_init(singapayBaseUrl() . '/api/v2.0/card/' . singapayAccountId() . '/payment');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'X-PARTNER-ID: ' . singapayApiKey(),
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode((string)$res, true);
    if ($http !== 200 || ($json['response_code'] ?? '') !== 'SP000') {
        error_log('singapay card error: http=' . $http . ' ' . substr((string)$res, 0, 300));
        return ['ok' => false, 'error' => 'card_declined', 'detail' => $json['response_message'] ?? null];
    }
    $d = $json['data'];
    $txId = (string)($d['transaction_id'] ?? '');

    db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, gateway, order_id, reference, pay_code, pay_url, checkout_url, gross_amount, status, payment_type)
        VALUES (?, ?, ?, 'singapay', ?, ?, NULL, ?, ?, ?, 'pending', 'CARD')")
        ->execute([$bookingType, $bookingId, $bookingCode, $txId, $txId, $d['payment_url'] ?? null, $d['payment_url'] ?? null, $grossAmount]);

    return [
        'ok' => true,
        'status' => ($d['requires_3ds'] ?? false) ? 'processing' : (($d['status'] ?? '') === 'success' ? 'success' : 'processing'),
        'payment_url' => $d['payment_url'] ?? null,
        'transaction_id' => $txId,
        'card_masked' => $d['card_masked'] ?? null,
    ];
}

/**
 * Inquiry status transaksi kartu (untuk polling 3DS). Return: success|processing|failed.
 */
function singapayCardStatus(string $transactionId): string {
    if (!singapayConfigured()) return 'failed';
    $token = singapayAccessToken();
    if ($token === '') return 'processing';
    $ch = curl_init(singapayBaseUrl() . '/api/v2.0/card/' . singapayAccountId() . '/inquiry-status/' . urlencode($transactionId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'X-PARTNER-ID: ' . singapayApiKey(),
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode((string)$res, true);
    if ($http !== 200) return 'processing';
    return (string)($json['data']['status'] ?? 'processing');
}


/**
 * Handler webhook Singapay (va-transaction + card). Idempotent.
 * Return true bila sah & diproses (termasuk duplikat no-op).
 */
function singapayHandleWebhook(array $data): bool {
    $event = (string)($data['event'] ?? '');
    if ($event === 'card-transaction' || $event === 'card') {
        $cd = $data['data'] ?? $data;
        $ctx = $cd['transaction'] ?? $cd;
        $cref = (string)($ctx['reference_no'] ?? $ctx['reff_no'] ?? '');
        if ($cref === '') return false;
        $cs = db()->prepare("SELECT * FROM payments WHERE gateway='singapay' AND (order_id=? OR reference=? OR booking_code=?) LIMIT 1");
        $cs->execute([$cref, $cref, $cref]);
        $cpay = $cs->fetch();
        if (!$cpay) return false;
        $cst = strtolower((string)($ctx['status'] ?? ''));
        $cpaid = in_array($cst, ['success', 'paid', 'settlement', 'capture'], true);
        return singapayApplyPaid($cpay, (string)($ctx['transaction_id'] ?? $cref), $data, $cpaid);
    }
    if ($event !== 'va-transaction') return false;
    $tx = $data['data']['transaction'] ?? [];
    $reffNo = (string)($tx['reff_no'] ?? '');
    if ($reffNo === '') return false;

    // Cari payment by order_id (VA id) atau booking_code (merchant_reff_no)
    $stmt = db()->prepare("SELECT * FROM payments WHERE gateway='singapay' AND (order_id=? OR booking_code=?) LIMIT 1");
    $stmt->execute([$reffNo, $reffNo]);
    $payment = $stmt->fetch();
    if (!$payment) return false;

    $isPaid = ($tx['status'] ?? '') === 'paid';
    return singapayApplyPaid($payment, (string)($tx['transaction_id'] ?? ''), $data, $isPaid);
}

/**
 * Terapkan status paid/failed ke payment singapay + booking terkait (idempotent).
 */
function singapayApplyPaid(array $payment, string $txId, array $data, bool $isPaid): bool {
    $newStatus = $isPaid ? 'paid' : 'failed';
    $newStatus = $isPaid ? 'paid' : 'failed';

    // Idempotent: sudah final → no-op
    if (in_array($payment['status'], ['paid', 'expired', 'failed'], true)) return true;
    if ($payment['status'] === $newStatus) return true;

    db()->prepare("UPDATE payments SET status=?, transaction_id=?, raw_payload=?, paid_at=? WHERE id=?")
        ->execute([$newStatus, $txId, json_encode($data, JSON_UNESCAPED_UNICODE), $isPaid ? date('Y-m-d H:i:s') : null, $payment['id']]);

    // Sinkron booking (kolom payment_status) via typeMap
    $typeMap = [
        'tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings',
        'train' => 'train_bookings', 'transfer' => 'transfer_bookings',
        'attraction' => 'attraction_bookings', 'esim' => 'connectivity_bookings',
        'ferry' => 'ferry_bookings',
    ];
    $table = $typeMap[$payment['booking_type']] ?? null;
    if ($table) {
        db()->prepare("UPDATE `$table` SET payment_status = ? WHERE id = ?")
            ->execute([$isPaid ? 'paid' : 'unpaid', (int)$payment['booking_id']]);
    }

    if ($isPaid && $payment['booking_type'] === 'tour') {
        require_once __DIR__ . '/availability.php';
        deductTourSlotsOnPaid((int)$payment['booking_id']);
        db()->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
            ->execute([(int)$payment['booking_id']]);
    }
    if ($isPaid && $payment['booking_type'] === 'ferry') {
        db()->prepare("UPDATE ferry_bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
            ->execute([(int)$payment['booking_id']]);
    }
    return true;
}

/**
 * Validasi signature webhook Singapay.
 * StringToSign = METHOD:ENDPOINT:ACCESS_TOKEN:SHA256(sorted_body):TIMESTAMP
 * Signature = HMAC-SHA512(StringToSign, client_secret)
 */
function singapayVerifySignature(string $rawBody, array $headers, string $endpoint): bool {
    $sig = $headers['X-Signature'] ?? '';
    $ts = $headers['X-Timestamp'] ?? '';
    $auth = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    if ($sig === '' || $ts === '' || $auth === '') return false;

    $arr = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) return false;
    $sort = function (&$a) use (&$sort) {
        ksort($a, SORT_STRING);
        foreach ($a as &$v) if (is_array($v)) $sort($v);
    };
    $sort($arr);
    $bodyHash = hash('sha256', json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $stringToSign = implode(':', ['POST', $endpoint, $auth, $bodyHash, $ts]);
    $calc = hash_hmac('sha512', $stringToSign, singapayClientSecret());
    return hash_equals($calc, $sig);
}

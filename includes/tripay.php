<?php
/**
 * tripay.php — integrasi Tripay Closed Payment (tanpa composer).
 *
 * Fungsi inti (diuji unit):
 *  - tripayMode()            : manual|instant (default manual = approve admin)
 *  - tripayGateway()         : midtrans|tripay (gateway aktif saat instant)
 *  - tripayInstantEnabled()  : true bila mode=instant DAN gateway aktif + kredensial ada
 *  - tripaySignature()       : HMAC-SHA256(merchantCode.merchantRef.amount, privateKey)
 *  - tripayCallbackSignature(): HMAC-SHA256(rawJson, privateKey) utk verifikasi callback
 *  - handleTripayCallback()  : idempotent, update payments + bookings
 *  - generateTripayRef()     : TAT-T-{bookingId}-{random} sebagai merchant_ref
 *
 * Fungsi API (butuh network): createTripayTransaction()
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/** Mode pembayaran paket tour: manual (approve admin) | instant (payment gateway) */
function tripayMode(): string {
    $mode = getSetting('payment_mode', 'manual');
    return $mode === 'instant' ? 'instant' : 'manual';
}

/** Gateway aktif saat mode instant: midtrans | tripay */
function tripayGateway(): string {
    return getSetting('payment_gateway', 'midtrans') === 'tripay' ? 'tripay' : 'midtrans';
}

function tripayApiKey(): string {
    return (string)getSetting('tripay_api_key', '');
}

function tripayPrivateKey(): string {
    return (string)getSetting('tripay_private_key', '');
}

function tripayMerchantCode(): string {
    return (string)getSetting('tripay_merchant_code', '');
}

function tripayBaseUrl(): string {
    return getSetting('tripay_env', 'sandbox') === 'production'
        ? 'https://tripay.co.id/api'
        : 'https://tripay.co.id/api-sandbox';
}

function tripayConfigured(): bool {
    return tripayApiKey() !== '' && tripayPrivateKey() !== '' && tripayMerchantCode() !== '';
}

/**
 * Instant aktif bila: mode=instant DAN gateway terpilih siap.
 * Bila tidak → fallback manual approve (booking pending, admin konfirmasi).
 */
function tripayInstantEnabled(): bool {
    if (tripayMode() !== 'instant') return false;
    if (tripayGateway() === 'tripay') return tripayConfigured();
    return midtransEnabled();
}

/** Signature request transaksi: HMAC-SHA256(merchantCode.merchantRef.amount) */
function tripaySignature(string $merchantRef, $amount, ?string $privateKey = null, ?string $merchantCode = null): string {
    $pk = $privateKey ?? tripayPrivateKey();
    $mc = $merchantCode ?? tripayMerchantCode();
    return hash_hmac('sha256', $mc . $merchantRef . $amount, $pk);
}

/** Signature verifikasi callback: HMAC-SHA256(rawJson, privateKey) */
function tripayCallbackSignature(string $rawJson, ?string $privateKey = null): string {
    return hash_hmac('sha256', $rawJson, $privateKey ?? tripayPrivateKey());
}

/** merchant_ref unik: TAT-T-{bookingId}-{RANDOM} */
function generateTripayRef(int $bookingId): string {
    return 'TAT-T-' . $bookingId . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/** Map status Tripay → status internal */
function tripayMapStatus(string $status): string {
    switch (strtoupper($status)) {
        case 'PAID': return 'paid';
        case 'EXPIRED': return 'expired';
        case 'FAILED':
        case 'REFUND': return 'failed';
        default: return 'pending';
    }
}

/**
 * Buat transaksi Tripay untuk satu booking tour.
 * @return array ['ok'=>bool, 'pay_code'=>?, 'checkout_url'=>?, 'reference'=>?, 'merchant_ref'=>?, 'error'=>?]
 */
function createTripayTransaction(int $bookingId, float $grossAmount, array $customer = [], string $method = 'BRIVA'): array {
    if (!tripayConfigured()) {
        return ['ok' => false, 'error' => 'tripay_not_configured'];
    }

    $stmt = db()->prepare("SELECT * FROM payments WHERE gateway='tripay' AND booking_type='tour' AND booking_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$bookingId]);
    $existing = $stmt->fetch();
    $merchantRef = $existing['order_id'] ?? generateTripayRef($bookingId);

    $b = db()->prepare('SELECT booking_code FROM bookings WHERE id = ?');
    $b->execute([$bookingId]);
    $bookingCode = $b->fetchColumn() ?: null;

    $amount = (int)round($grossAmount);
    $data = [
        'method'        => $method,
        'merchant_ref'  => $merchantRef,
        'amount'        => $amount,
        'customer_name' => $customer['name'] ?? 'Pelanggan',
        'customer_email'=> $customer['email'] ?? 'noreply@' . preg_replace('#^https?://#', '', defined('BASE_URL') ? BASE_URL : 'tourandtravel.web.id'),
        'customer_phone'=> $customer['phone'] ?? '',
        'order_items'   => [[
            'name'     => 'Paket Tour' . ($bookingCode ? ' ' . $bookingCode : ''),
            'price'    => $amount,
            'quantity' => 1,
        ]],
        'return_url'    => BASE_URL . '/booking-success.php?code=' . urlencode((string)$bookingCode),
        'expired_time'  => (time() + (24 * 60 * 60)),
        'signature'     => tripaySignature($merchantRef, $amount),
    ];

    $ch = curl_init(tripayBaseUrl() . '/transaction/create');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . tripayApiKey()],
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $json = json_decode((string)$res, true);
    if ($http !== 200 || empty($json['success']) || empty($json['data']['reference'])) {
        return ['ok' => false, 'error' => 'tripay_error', 'http' => $http, 'detail' => $json['message'] ?? $curlErr];
    }
    $d = $json['data'];

    if (!$existing) {
        db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, gateway, order_id, reference, pay_code, pay_url, checkout_url, gross_amount, status) VALUES ('tour', ?, ?, 'tripay', ?, ?, ?, ?, ?, ?, 'pending')")
            ->execute([$bookingId, $bookingCode, $merchantRef, $d['reference'], $d['pay_code'] ?? null, $d['pay_url'] ?? null, $d['checkout_url'] ?? null, $grossAmount]);
    } else {
        db()->prepare('UPDATE payments SET reference=?, pay_code=?, pay_url=?, checkout_url=? WHERE id=?')
            ->execute([$d['reference'], $d['pay_code'] ?? null, $d['pay_url'] ?? null, $d['checkout_url'] ?? null, $existing['id']]);
    }

    return [
        'ok' => true,
        'reference' => $d['reference'],
        'merchant_ref' => $merchantRef,
        'pay_code' => $d['pay_code'] ?? null,
        'pay_url' => $d['pay_url'] ?? null,
        'checkout_url' => $d['checkout_url'] ?? null,
        'expired_time' => $d['expired_time'] ?? null,
    ];
}

/**
 * Handler callback Tripay — IDEMPOTEN (pola handleMidtransNotification).
 * @param array $data body JSON callback (sudah decode)
 * @return bool true bila sah & diproses (termasuk duplikat no-op)
 */
function handleTripayCallback(array $data): bool {
    $reference = (string)($data['reference'] ?? '');
    $merchantRef = (string)($data['merchant_ref'] ?? '');
    $status = strtoupper((string)($data['status'] ?? ''));
    if ($reference === '' || $merchantRef === '' || $status === '') return false;
    if (!in_array($status, ['PAID', 'FAILED', 'EXPIRED', 'REFUND'], true)) return false;

    $stmt = db()->prepare("SELECT * FROM payments WHERE gateway='tripay' AND (reference=? OR order_id=?) LIMIT 1");
    $stmt->execute([$reference, $merchantRef]);
    $payment = $stmt->fetch();
    if (!$payment) return false;

    $newStatus = tripayMapStatus($status);

    // IDEMPOTEN: sudah final → no-op
    if (in_array($payment['status'], ['paid', 'expired', 'failed'], true)) return true;
    if ($payment['status'] === $newStatus) return true;

    $paidAt = $newStatus === 'paid' ? date('Y-m-d H:i:s') : null;
    $upd = db()->prepare('UPDATE payments SET status=?, payment_type=?, raw_payload=?, paid_at=? WHERE id=?');
    $upd->execute([$newStatus, $data['payment_method_code'] ?? $data['payment_method'] ?? null, json_encode($data, JSON_UNESCAPED_UNICODE), $paidAt, $payment['id']]);

    // Sinkron booking tour (kolom payment_status)
    db()->prepare('UPDATE bookings SET payment_status = ? WHERE id = ?')
        ->execute([$newStatus === 'paid' ? 'paid' : 'unpaid', (int)$payment['booking_id']]);

    if ($newStatus === 'paid') {
        require_once __DIR__ . '/availability.php';
        deductTourSlotsOnPaid((int)$payment['booking_id']);
        // Poin loyalty: reuse pola midtrans
        $b2 = db()->prepare('SELECT total_price, booking_code, user_id FROM bookings WHERE id = ?');
        $b2->execute([(int)$payment['booking_id']]);
        if (($bk2 = $b2->fetch()) && (int)($bk2['user_id'] ?? 0) > 0) {
            require_once __DIR__ . '/points.php';
            awardPointsForPaidBooking('tour', (int)$payment['booking_id'], (int)$bk2['user_id'], (float)$bk2['total_price'], $bk2['booking_code'] ?? null);
            autoAssignTier((int)$bk2['user_id']);
        }
    }
    if (in_array($newStatus, ['failed', 'expired'], true)) {
        require_once __DIR__ . '/availability.php';
        releaseTourSlotsOnCancel((int)$payment['booking_id']);
    }

    return true;
}

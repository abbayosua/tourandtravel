<?php
/**
 * xendit.php — integrasi Xendit SANDBOX (tanpa composer, curl murni).
 *
 * Cakupan: invoice serbaguna (Virtual Account semua bank + kartu kredit 3DS
 * di halaman checkout Xendit) + callback invoice.
 *
 * Konfigurasi (settings DB, JANGAN hardcode — lihat admin/payments.php):
 *  - xendit_secret_key     : xnd_development_* (sandbox) / xnd_production_* (live)
 *  - xendit_public_key     : xnd_public_* (dipakai bila perlu tokenisasi langsung)
 *  - xendit_callback_token : token verifikasi header x-callback-token
 *  - xendit_env            : sandbox|production (default sandbox)
 *
 * Fungsi inti (pola sama seperti tripay.php):
 *  - xenditConfigured()        : secret + callback token terisi
 *  - createXenditInvoice()     : buat invoice utk satu booking (VA + kartu)
 *  - handleXenditCallback()    : IDEMPOTEN, update payments + bookings
 *  - xenditMapStatus()         : PAID/EXPIRED/… → paid/expired/…
 *
 * Webhook: webhook-xendit.php (verifikasi x-callback-token).
 * URL didaftarkan di dashboard Xendit → Settings → Developers → Webhooks:
 *   https://tourandtravel.web.id/webhook-xendit.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function xenditSecretKey(): string {
    return (string)getSetting('xendit_secret_key', '');
}

function xenditPublicKey(): string {
    return (string)getSetting('xendit_public_key', '');
}

function xenditCallbackToken(): string {
    return (string)getSetting('xendit_callback_token', '');
}

function xenditEnv(): string {
    return getSetting('xendit_env', 'sandbox') === 'production' ? 'production' : 'sandbox';
}

/** Base URL sama untuk sandbox & production — key yang menentukan environment. */
function xenditBaseUrl(): string {
    return 'https://api.xendit.co';
}

function xenditConfigured(): bool {
    return xenditSecretKey() !== '' && xenditCallbackToken() !== '';
}

/** external_id unik: TAT-X-{bookingId}-{RANDOM} */
function generateXenditRef(int $bookingId): string {
    return 'TAT-X-' . $bookingId . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Panggil REST API Xendit (Basic auth: secret key sebagai username).
 * @return array ['http'=>int, 'json'=>mixed, 'error'=>?string]
 */
function xenditApi(string $method, string $path, ?array $body = null): array {
    $ch = curl_init(xenditBaseUrl() . $path);
    $headers = ['Content-Type: application/json'];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERPWD => xenditSecretKey() . ':',
        CURLOPT_TIMEOUT => 25,
    ];
    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES);
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    return ['http' => $http, 'json' => json_decode((string)$res, true), 'error' => $curlErr !== '' ? $curlErr : null];
}

/** Map status invoice Xendit → status internal */
function xenditMapStatus(string $status): string {
    switch (strtoupper($status)) {
        case 'PAID':
        case 'SETTLED': return 'paid';
        case 'EXPIRED': return 'expired';
        case 'FAILED': return 'failed';
        default: return 'pending';
    }
}

/**
 * Buat invoice Xendit SANDBOX untuk satu booking tour (VA + kartu kredit).
 * Halaman checkout Xendit otomatis menampilkan channel aktif akun:
 * VA (BCA/BRI/BNI/Mandiri/Permata/…), kartu kredit 3DS, QRIS, retail.
 * @return array ['ok'=>bool, 'invoice_id'=>?, 'invoice_url'=>?, 'external_id'=>?, 'redirect_url'=>?, 'error'=>?]
 */
function createXenditInvoice(int $bookingId, float $grossAmount, array $customer = [], string $bookingType = 'tour'): array {
    if (!xenditConfigured()) {
        return ['ok' => false, 'error' => 'xendit_not_configured'];
    }

    $stmt = db()->prepare("SELECT * FROM payments WHERE gateway='xendit' AND booking_type=? AND booking_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$bookingType, $bookingId]);
    $existing = $stmt->fetch();
    $externalId = (is_array($existing) && !empty($existing['order_id'])) ? (string)$existing['order_id'] : generateXenditRef($bookingId);

    $codeTable = ['tour' => 'bookings', 'ferry' => 'ferry_bookings', 'pelni' => 'pelni_bookings'][$bookingType] ?? 'bookings';
    $b = db()->prepare("SELECT booking_code FROM `$codeTable` WHERE id = ?");
    $b->execute([$bookingId]);
    $bookingCode = $b->fetchColumn() ?: null;

    $itemName = ['tour' => 'Paket Tour', 'ferry' => 'Tiket Ferry', 'pelni' => 'Tiket Kapal PELNI'][$bookingType] ?? 'Paket';
    $amount = (int)round($grossAmount);
    if ($amount < 10000) {
        return ['ok' => false, 'error' => 'amount_too_small'];
    }
    $email = (string)($customer['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = 'noreply@' . preg_replace('#^https?://#', '', defined('BASE_URL') ? (string)BASE_URL : 'tourandtravel.web.id');
    }
    $payload = [
        'external_id' => $externalId,
        'amount' => $amount,
        'currency' => 'IDR',
        'description' => trim($itemName . ($bookingCode ? ' ' . $bookingCode : '')),
        'payer_email' => $email,
        'invoice_duration' => 24 * 60 * 60,
        'success_redirect_url' => (defined('BASE_URL') ? (string)BASE_URL : '') . '/booking-success.php?code=' . urlencode((string)$bookingCode),
        'failure_redirect_url' => (defined('BASE_URL') ? (string)BASE_URL : '') . '/booking-success.php?code=' . urlencode((string)$bookingCode),
    ];

    $api = xenditApi('POST', '/v2/invoices', $payload);
    $json = $api['json'];
    if ($api['http'] !== 200 && $api['http'] !== 201) {
        $msg = is_array($json) ? ($json['message'] ?? null) : null;
        return ['ok' => false, 'error' => 'xendit_error', 'http' => $api['http'], 'detail' => $msg ?? $api['error']];
    }
    if (!is_array($json) || empty($json['id']) || empty($json['invoice_url'])) {
        return ['ok' => false, 'error' => 'xendit_bad_response', 'http' => $api['http']];
    }

    if (!$existing) {
        db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, gateway, order_id, reference, pay_url, checkout_url, gross_amount, status) VALUES (?, ?, ?, 'xendit', ?, ?, ?, ?, ?, 'pending')")
            ->execute([$bookingType, $bookingId, $bookingCode, $externalId, (string)$json['id'], (string)$json['invoice_url'], (string)$json['invoice_url'], $grossAmount]);
    } else {
        db()->prepare('UPDATE payments SET reference=?, pay_url=?, checkout_url=? WHERE id=?')
            ->execute([(string)$json['id'], (string)$json['invoice_url'], (string)$json['invoice_url'], (int)$existing['id']]);
    }

    return [
        'ok' => true,
        'gateway' => 'xendit',
        'invoice_id' => (string)$json['id'],
        'invoice_url' => (string)$json['invoice_url'],
        'external_id' => $externalId,
        'redirect_url' => (string)$json['invoice_url'],
    ];
}

/**
 * Handler callback invoice Xendit — IDEMPOTEN (pola handleTripayCallback).
 * @param array $data body JSON callback (sudah decode)
 * @return bool true bila sah & diproses (termasuk duplikat no-op)
 */
function handleXenditCallback(array $data): bool {
    $invoiceId = (string)($data['id'] ?? '');
    $externalId = (string)($data['external_id'] ?? '');
    $status = strtoupper((string)($data['status'] ?? ''));
    if ($invoiceId === '' || $externalId === '' || $status === '') return false;
    if (!in_array($status, ['PAID', 'SETTLED', 'EXPIRED', 'FAILED'], true)) return false;

    $stmt = db()->prepare("SELECT * FROM payments WHERE gateway='xendit' AND (reference=? OR order_id=?) LIMIT 1");
    $stmt->execute([$invoiceId, $externalId]);
    $payment = $stmt->fetch();
    if (!$payment) return false;

    $newStatus = xenditMapStatus($status);

    // IDEMPOTEN: sudah final → no-op
    if (in_array($payment['status'], ['paid', 'expired', 'failed'], true)) return true;
    if ($payment['status'] === $newStatus) return true;

    $paidAt = $newStatus === 'paid' ? date('Y-m-d H:i:s') : null;
    $payType = null;
    if (!empty($data['payment_method'])) $payType = (string)$data['payment_method'];
    elseif (!empty($data['payment_channel'])) $payType = (string)$data['payment_channel'];
    elseif (!empty($data['payment_destination'])) $payType = (string)$data['payment_destination'];
    $upd = db()->prepare('UPDATE payments SET status=?, payment_type=?, raw_payload=?, paid_at=? WHERE id=?');
    $upd->execute([$newStatus, $payType, json_encode($data, JSON_UNESCAPED_UNICODE), $paidAt, $payment['id']]);

    // Sinkron booking (kolom payment_status) sesuai tipe — pola sama dgn tripay
    $typeMap = [
        'tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings',
        'train' => 'train_bookings', 'transfer' => 'transfer_bookings',
        'attraction' => 'attraction_bookings', 'esim' => 'connectivity_bookings',
        'ferry' => 'ferry_bookings', 'pelni' => 'pelni_bookings',
    ];
    $table = $typeMap[$payment['booking_type']] ?? null;
    if ($table) {
        db()->prepare("UPDATE `$table` SET payment_status = ? WHERE id = ?")
            ->execute([$newStatus === 'paid' ? 'paid' : 'unpaid', (int)$payment['booking_id']]);
    }

    if ($newStatus === 'paid' && $payment['booking_type'] === 'tour') {
        require_once __DIR__ . '/availability.php';
        deductTourSlotsOnPaid((int)$payment['booking_id']);
        // Booking paid → status confirmed (membuka akses refund & review)
        db()->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
            ->execute([(int)$payment['booking_id']]);
        // Poin loyalty + TravelPoints: reuse pola midtrans
        require_once __DIR__ . '/points.php';
        awardLoyaltyOnPaid('tour', (int)$payment['booking_id']);
    }
    if ($newStatus === 'paid' && $payment['booking_type'] === 'ferry') {
        // Ferry paid → confirmed
        db()->prepare("UPDATE ferry_bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
            ->execute([(int)$payment['booking_id']]);
    }
    if ($newStatus === 'paid' && $payment['booking_type'] === 'pelni') {
        // PELNI paid → confirmed
        db()->prepare("UPDATE pelni_bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
            ->execute([(int)$payment['booking_id']]);
    }
    if (in_array($newStatus, ['failed', 'expired'], true) && $payment['booking_type'] === 'tour') {
        require_once __DIR__ . '/availability.php';
        releaseTourSlotsOnCancel((int)$payment['booking_id']);
    }

    return true;
}

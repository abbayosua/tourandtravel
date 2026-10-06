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

/** Gateway aktif saat mode instant: midtrans | tripay | singapay | xendit */
function tripayGateway(): string {
    $gw = (string)getSetting('payment_gateway', 'midtrans');
    return in_array($gw, ['tripay', 'singapay', 'xendit'], true) ? $gw : 'midtrans';
}

/**
 * tripayChannelMeta — metadata channel Tripay untuk UI picker ber-icon.
 * Icon dari API /merchant/payment-channel (field icon_url); bila offline
 * atau tidak terdaftar, fallback icon bootstrap + tanpa fee.
 */
function tripayChannelMeta(?string $code = null): ?array {
    static $meta = null;
    if ($meta === null) {
        $meta = [
            'BRIVA' => ['label' => 'BRI Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'BNIVA' => ['label' => 'BNI Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'BCAVA' => ['label' => 'BCA Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 5.500'],
            'MANDIRIVA' => ['label' => 'Mandiri Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'PERMATAVA' => ['label' => 'Permata Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'MUAMALATVA' => ['label' => 'Muamalat Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'CIMBVA' => ['label' => 'CIMB Niaga Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'BSIVA' => ['label' => 'BSI Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'OCBCVA' => ['label' => 'OCBC Virtual Account', 'group' => 'Virtual Account', 'icon' => 'bi-bank', 'fee' => 'Rp 4.250'],
            'QRIS' => ['label' => 'QRIS', 'group' => 'E-Wallet / QR', 'icon' => 'bi-qr-code', 'fee' => 'Rp 750 + 0,7%'],
            'QRIS2' => ['label' => 'QRIS', 'group' => 'E-Wallet / QR', 'icon' => 'bi-qr-code', 'fee' => 'Rp 750 + 0,7%'],
            'ALFAMART' => ['label' => 'Alfamart', 'group' => 'Gerai Retail', 'icon' => 'bi-shop', 'fee' => 'Rp 3.500'],
            'INDOMARET' => ['label' => 'Indomaret', 'group' => 'Gerai Retail', 'icon' => 'bi-shop', 'fee' => 'Rp 3.500'],
            'ALFAMIDI' => ['label' => 'Alfamidi', 'group' => 'Gerai Retail', 'icon' => 'bi-shop', 'fee' => 'Rp 3.500'],
        ];
        try {
            if (tripayConfigured()) {
                $ch = curl_init(tripayBaseUrl() . '/merchant/payment-channel');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . tripayApiKey()],
                    CURLOPT_TIMEOUT => 8,
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ]);
                $res = curl_exec($ch);
                curl_close($ch);
                $j = json_decode((string)$res, true);
                if (!empty($j['success']) && is_array($j['data'] ?? null)) {
                    foreach ($j['data'] as $c) {
                        $ccode = strtoupper((string)($c['code'] ?? ''));
                        if ($ccode === '' || empty($c['active'])) continue;
                        $feeFlat = (int)($c['fee_merchant']['flat'] ?? $c['total_fee']['flat'] ?? 0);
                        $feePct = (float)($c['fee_merchant']['percent'] ?? $c['total_fee']['percent'] ?? 0);
                        $feeTxt = $feeFlat > 0 ? 'Rp ' . number_format($feeFlat, 0, ',', '.') : '';
                        if ($feePct > 0) $feeTxt .= ($feeTxt !== '' ? ' + ' : '') . rtrim(rtrim(number_format($feePct, 2, ',', '.'), '0'), ',') . '%';
                        $meta[$ccode] = [
                            'label' => (string)($c['name'] ?? $ccode),
                            'group' => (string)($c['group'] ?? ''),
                            'icon' => !empty($c['icon_url']) ? (string)$c['icon_url'] : ($meta[$ccode]['icon'] ?? 'bi-credit-card'),
                            'fee' => $feeTxt !== '' ? $feeTxt : ($meta[$ccode]['fee'] ?? ''),
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}
    }
    if ($code === null) return $meta;
    return $meta[strtoupper($code)] ?? null;
}

/** Channel yang ditawarkan di UI (urutan tampil). */
/** Localize a Tripay channel label (e.g. "BRI Virtual Account" -> "BRI 虚拟账户"). */
function tripayLocalizedLabel(string $label): string {
    return str_ireplace('Virtual Account', t('Virtual Account'), $label);
}

function tripayUiChannels(): array {
    return ['BRIVA', 'BCAVA', 'BNIVA', 'MANDIRIVA', 'PERMATAVA', 'QRIS', 'ALFAMART', 'INDOMARET'];
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
    if (tripayGateway() === 'xendit') {
        require_once __DIR__ . '/xendit.php';
        return xenditConfigured();
    }
    if (tripayGateway() === 'singapay') {
        require_once __DIR__ . '/singapay.php';
        return singapayConfigured();
    }
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
function createTripayTransaction(int $bookingId, float $grossAmount, array $customer = [], string $method = 'BRIVA', string $bookingType = 'tour'): array {
    if (!tripayConfigured()) {
        return ['ok' => false, 'error' => 'tripay_not_configured'];
    }

    $stmt = db()->prepare("SELECT * FROM payments WHERE gateway='tripay' AND booking_type=? AND booking_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$bookingType, $bookingId]);
    $existing = $stmt->fetch();
    $merchantRef = $existing['order_id'] ?? generateTripayRef($bookingId);

    $codeTable = ['tour' => 'bookings', 'ferry' => 'ferry_bookings', 'pelni' => 'pelni_bookings'][$bookingType] ?? 'bookings';
    $b = db()->prepare("SELECT booking_code FROM `$codeTable` WHERE id = ?");
    $b->execute([$bookingId]);
    $bookingCode = $b->fetchColumn() ?: null;

    $itemName = ['tour' => 'Paket Tour', 'ferry' => 'Tiket Ferry', 'pelni' => 'Tiket Kapal PELNI'][$bookingType] ?? 'Paket';
    $amount = (int)round($grossAmount);
    $data = [
        'method'        => $method,
        'merchant_ref'  => $merchantRef,
        'amount'        => $amount,
        'customer_name' => $customer['name'] ?? 'Pelanggan',
        'customer_email'=> $customer['email'] ?? 'noreply@' . preg_replace('#^https?://#', '', defined('BASE_URL') ? BASE_URL : 'tourandtravel.web.id'),
        'customer_phone'=> $customer['phone'] ?? '',
        'order_items'   => [[
            'name'     => $itemName . ($bookingCode ? ' ' . $bookingCode : ''),
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
        db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, gateway, order_id, reference, pay_code, pay_url, checkout_url, gross_amount, status) VALUES (?, ?, ?, 'tripay', ?, ?, ?, ?, ?, ?, 'pending')")
            ->execute([$bookingType, $bookingId, $bookingCode, $merchantRef, $d['reference'], $d['pay_code'] ?? null, $d['pay_url'] ?? null, $d['checkout_url'] ?? null, $grossAmount]);
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

    // Sinkron booking (kolom payment_status) sesuai tipe — pola sama dgn midtrans
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

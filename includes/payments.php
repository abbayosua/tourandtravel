<?php
/**
 * payments.php — integrasi Midtrans Snap (tanpa composer).
 *
 * Fungsi inti (diuji unit):
 *  - verifyMidtransSignature()  : sha512(order_id+status_code+gross_amount+serverKey)
 *  - midtransMapStatus()        : transaction_status → status internal
 *  - handleMidtransNotification(): idempotent, update payments + bookings
 *  - generateMidtransOrderId()  : TAT-{T}-{bookingId}-{random}
 *
 * Fungsi Snap API (step berikutnya): createMidtransSnapTransaction()
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function midtransServerKey(): string {
    return (string)getSetting('midtrans_server_key', '');
}

function midtransBaseUrl(): string {
    return getSetting('midtrans_env', 'sandbox') === 'production'
        ? 'https://api.midtrans.com'
        : 'https://api.sandbox.midtrans.com';
}

function midtransEnabled(): bool {
    return getSetting('payment_enabled', '1') === '1' && midtransServerKey() !== '';
}

/**
 * Buat Snap transaction di Midtrans untuk satu booking, simpan row payments.
 *
 * @param string $bookingType tour|hotel|flight|train|transfer|attraction|esim
 * @param int    $bookingId   id booking
 * @param float  $grossAmount total tagihan
 * @param array  $customer    ['name'=>, 'email'=>, 'phone'=>] opsional
 * @return array ['ok'=>bool, 'redirect_url'=?, 'order_id'=?, 'error'=?]
 */
function createMidtransSnapTransaction(string $bookingType, int $bookingId, float $grossAmount, array $customer = []): array {
    if (!midtransEnabled()) {
        return ['ok' => false, 'error' => 'payment_disabled'];
    }

    // Idempoten pembuatan: bila sudah ada order pending untuk booking ini, pakai lagi
    $stmt = db()->prepare("SELECT * FROM payments WHERE booking_type = ? AND booking_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$bookingType, $bookingId]);
    $existing = $stmt->fetch();
    $orderId = $existing['order_id'] ?? generateMidtransOrderId($bookingType, $bookingId);

    $codeCol = ['tour' => 'booking_code'];
    $bookingCode = null;
    if ($bookingType === 'tour') {
        $b = db()->prepare("SELECT booking_code FROM bookings WHERE id = ?");
        $b->execute([$bookingId]);
        $bookingCode = $b->fetchColumn() ?: null;
    }

    $payload = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => (int)round($grossAmount),
        ],
        'item_details' => [[
            'id' => $bookingType . '-' . $bookingId,
            'price' => (int)round($grossAmount),
            'quantity' => 1,
            'name' => 'Booking ' . ucfirst($bookingType) . ($bookingCode ? ' ' . $bookingCode : ''),
        ]],
        'customer_details' => array_filter([
            'first_name' => $customer['name'] ?? null,
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phone'] ?? null,
        ]),
    ];

    $ch = curl_init(midtransBaseUrl() . '/v1/snap/transactions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(midtransServerKey() . ':'),
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $data = json_decode((string)$res, true);
    if ($http !== 201 || empty($data['redirect_url'])) {
        return ['ok' => false, 'error' => 'midtrans_error', 'http' => $http, 'detail' => $data['error_messages'] ?? $curlErr];
    }

    if (!$existing) {
        db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, order_id, gross_amount, status) VALUES (?, ?, ?, ?, ?, 'pending')")
            ->execute([$bookingType, $bookingId, $bookingCode, $orderId, $grossAmount]);
    }

    return ['ok' => true, 'redirect_url' => $data['redirect_url'], 'order_id' => $orderId, 'token' => $data['token'] ?? null];
}

/** Prefix type singkat untuk order id */
function midtransTypePrefix(string $bookingType): string {
    $map = ['tour' => 'T', 'hotel' => 'H', 'flight' => 'F', 'train' => 'R', 'transfer' => 'X', 'attraction' => 'A', 'esim' => 'E'];
    return $map[$bookingType] ?? 'G';
}

/** TAT-{TYPE}-{bookingId}-{random} — deterministik + unik */
function generateMidtransOrderId(string $bookingType, int $bookingId): string {
    return 'TAT-' . midtransTypePrefix($bookingType) . '-' . $bookingId . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/** Signature Midtrans: sha512(order_id + status_code + gross_amount + serverKey) */
function verifyMidtransSignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool {
    $expected = hash('sha512', $orderId . $statusCode . $grossAmount . midtransServerKey());
    return is_string($signatureKey) && $signatureKey !== '' && hash_equals($expected, $signatureKey);
}

/** Map transaction_status Midtrans → status internal */
function midtransMapStatus(string $transactionStatus): string {
    switch ($transactionStatus) {
        case 'capture':
        case 'settlement':
            return 'paid';
        case 'expire':
            return 'expired';
        case 'deny':
        case 'cancel':
            return 'failed';
        case 'challenge':
            return 'challenge';
        default:
            return 'pending';
    }
}

/**
 * Handler notifikasi webhook — IDEMPOTEN.
 * Flow: verifikasi signature → cari payment by order_id → bila status berubah
 * menjadi paid → update payments + booking payment_status; duplikat = no-op.
 *
 * @return bool true bila notif sah & diproses (termasuk duplikat no-op),
 *              false bila signature salah / order tak dikenal.
 */
function handleMidtransNotification(array $notif): bool {
    $orderId = (string)($notif['order_id'] ?? '');
    $statusCode = (string)($notif['status_code'] ?? '');
    $gross = (string)($notif['gross_amount'] ?? '');
    $sig = (string)($notif['signature_key'] ?? '');

    if ($orderId === '' || !verifyMidtransSignature($orderId, $statusCode, $gross, $sig)) {
        return false;
    }

    $stmt = db()->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $payment = $stmt->fetch();
    if (!$payment) {
        // order tak dikenal — jangan buat row dari payload luar (log saja oleh caller)
        return false;
    }

    $newStatus = midtransMapStatus((string)($notif['transaction_status'] ?? 'pending'));

    // IDEMPOTEN: bila sudah final (paid/expired/failed), tidak ubah apa pun
    if (in_array($payment['status'], ['paid', 'expired', 'failed'], true)) {
        return true;
    }
    if ($payment['status'] === $newStatus) {
        return true; // duplikat status sama
    }

    $txnId = (string)($notif['transaction_id'] ?? '');
    $payType = (string)($notif['payment_type'] ?? '');
    $paidAt = $newStatus === 'paid' ? date('Y-m-d H:i:s') : null;

    $upd = db()->prepare("UPDATE payments SET status=?, payment_type=?, transaction_id=?, raw_payload=?, paid_at=? WHERE id=?");
    $upd->execute([$newStatus, $payType, $txnId, json_encode($notif, JSON_UNESCAPED_UNICODE), $paidAt, $payment['id']]);

    // Email status berubah (paid/invoice) — tidak pernah throw
    if (function_exists('sendEmailTemplate') && $payment['booking_type'] === 'tour') {
        $b = db()->prepare("SELECT booking_code, email, name FROM bookings WHERE id = ?");
        $b->execute([$payment['booking_id']]);
        if ($bk = $b->fetch()) {
            if ($newStatus === 'paid') {
                sendEmailTemplate($bk['email'], 'invoice', [
                    'order_id' => $orderId,
                    'amount' => formatRupiah($payment['gross_amount']),
                    'booking_code' => $bk['booking_code'],
                    'name' => $bk['name'],
                    'subject' => 'Pembayaran Diterima - ' . $bk['booking_code'],
                ]);
            } else {
                sendEmailTemplate($bk['email'], 'booking-status', [
                    'booking_code' => $bk['booking_code'],
                    'status' => $newStatus,
                    'track_link' => BASE_URL . '/track.php?code=' . $bk['booking_code'],
                    'subject' => 'Status Booking - ' . $bk['booking_code'],
                ]);
            }
        }
    }

    // Sinkron booking (semua vertikal memakai kolom payment_status)
    $typeMap = [
        'tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings',
        'train' => 'train_bookings', 'transfer' => 'transfer_bookings',
        'attraction' => 'attraction_bookings', 'esim' => 'connectivity_bookings',
    ];
    $table = $typeMap[$payment['booking_type']] ?? null;
    if ($table) {
        db()->prepare("UPDATE `$table` SET payment_status = ? WHERE id = ?")
            ->execute([$newStatus, $payment['booking_id']]);
    }

    return true;
}

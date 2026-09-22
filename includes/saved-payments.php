<?php
/**
 * includes/saved-payments.php — Backlog #8: saved payment methods (Midtrans tokenisasi).
 * 1-click pay: user memilih kartu tersimpan → charge dengan saved_token_id via Midtrans Core API.
 * Semua fail-soft; token = Midtrans tokenized (tidak menyimpan PAN mentah).
 */

/** Semua metode aktif milik user (default dulu). */
function getSavedPaymentMethods(int $userId): array {
    if ($userId <= 0) return [];
    $stmt = db()->prepare("SELECT * FROM saved_payment_methods WHERE user_id = ? AND is_active = 1 ORDER BY is_default DESC, id DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Satu metode by id (harus milik user & aktif). */
function getSavedPaymentMethod(int $userId, int $methodId): ?array {
    $stmt = db()->prepare("SELECT * FROM saved_payment_methods WHERE id = ? AND user_id = ? AND is_active = 1");
    $stmt->execute([$methodId, $userId]);
    return $stmt->fetch() ?: null;
}

/** Simpan token baru; kartu pertama otomatis default. Return id. */
function savePaymentMethod(int $userId, string $token, ?string $brand = null, ?string $maskedNumber = null, ?int $expMonth = null, ?int $expYear = null): int {
    $token = trim($token);
    if ($userId <= 0 || $token === '') return 0;
    // idempotent per token
    $chk = db()->prepare("SELECT id FROM saved_payment_methods WHERE token = ? AND user_id = ?");
    $chk->execute([$token, $userId]);
    if ($existing = $chk->fetchColumn()) return (int)$existing;

    $cnt = db()->prepare("SELECT COUNT(*) FROM saved_payment_methods WHERE user_id = ? AND is_active = 1");
    $cnt->execute([$userId]);
    $isFirst = ((int)$cnt->fetchColumn()) === 0;

    $ins = db()->prepare("INSERT INTO saved_payment_methods (user_id, token, brand, masked_number, expiry_month, expiry_year, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([$userId, $token, $brand, $maskedNumber, $expMonth, $expYear, $isFirst ? 1 : 0]);
    return (int)db()->lastInsertId();
}

/** Hapus (soft) metode milik user. */
function removePaymentMethod(int $userId, int $methodId): bool {
    $stmt = db()->prepare("UPDATE saved_payment_methods SET is_active = 0 WHERE id = ? AND user_id = ?");
    $stmt->execute([$methodId, $userId]);
    return $stmt->rowCount() > 0;
}

/** Set default; default lama otomatis dilepas. */
function setDefaultPaymentMethod(int $userId, int $methodId): bool {
    db()->beginTransaction();
    try {
        db()->prepare("UPDATE saved_payment_methods SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        $stmt = db()->prepare("UPDATE saved_payment_methods SET is_default = 1 WHERE id = ? AND user_id = ? AND is_active = 1");
        $stmt->execute([$methodId, $userId]);
        db()->commit();
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('setDefaultPaymentMethod: ' . $e->getMessage());
        return false;
    }
}

/**
 * 1-click charge: buat order Midtrans dengan saved_token_id.
 * Return Snap result / error — fail-soft. Midtrans Sandbox: charge endpoint Core API.
 */
function chargeWithSavedToken(int $userId, int $methodId, string $orderId, float $amount): array {
    if (!method_exists('midtransServerKey', 'midtransServerKey')) {
        // fallback: baca langsung setting
    }
    $sk = function_exists('midtransServerKey') ? midtransServerKey() : '';
    if ($sk === '') return ['error' => 'payment_disabled'];
    $method = getSavedPaymentMethod($userId, $methodId);
    if (!$method) return ['error' => 'method_not_found'];

    $payload = [
        'payment_type' => 'credit_card',
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => (int)round($amount),
        ],
        'credit_card' => [
            'token_id' => $method['token'],
            'authentication' => false, // 1-click: tanpa 3DS (token sudah terverifikasi)
        ],
    ];
    $ch = curl_init('https://api.sandbox.midtrans.com/v2/charge');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($sk . ':'),
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$resp, true);
    if ($code >= 400) {
        return ['error' => $data['status_message'] ?? "HTTP $code", 'http_code' => $code];
    }
    $status = $data['transaction_status'] ?? 'pending';
    return [
        'status' => $status,           // capture|settlement|pending|deny
        'transaction_id' => $data['transaction_id'] ?? '',
        'status_code' => $data['status_code'] ?? '',
        'raw' => $data,
    ];
}

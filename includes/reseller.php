<?php
/**
 * Reseller account helpers — role check, balance CRUD, topup, booking discount.
 * Depends on: includes/db.php, includes/functions.php
 */

/** Cek apakah user adalah reseller. */
function isReseller(int $userId): bool {
    $stmt = db()->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return ($stmt->fetchColumn() ?? '') === 'reseller';
}

/** Ambil saldo reseller. */
function getResellerBalance(int $userId): float {
    $stmt = db()->prepare("SELECT COALESCE(reseller_balance, 0) FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}

/** Tambah saldo reseller (topup approved). Return saldo baru. */
function topUpReseller(int $userId, float $amount): float {
    if ($amount <= 0) return getResellerBalance($userId);
    db()->prepare("UPDATE users SET reseller_balance = reseller_balance + ? WHERE id = ? AND role = 'reseller'")
        ->execute([$amount, $userId]);

    // Log ke wallet_transactions sebagai referensi
    try {
        db()->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description, reference_type) VALUES (?, ?, 'earn', ?, 'reseller_topup')")
            ->execute([$userId, $amount, 'Topup reseller: ' . formatRupiah($amount)]);
    } catch (Throwable $e) {}

    return getResellerBalance($userId);
}

/**
 * Kurangi saldo reseller untuk booking.
 * Return saldo baru, atau false jika saldo tidak cukup.
 */
function spendResellerBalance(int $userId, float $amount, string $desc, ?int $bookingId = null): float|false {
    if ($amount <= 0) return false;
    $balance = getResellerBalance($userId);
    if ($balance < $amount) return false;

    db()->prepare("UPDATE users SET reseller_balance = reseller_balance - ? WHERE id = ? AND role = 'reseller' AND reseller_balance >= ?")
        ->execute([$amount, $userId, $amount]);

    $newBalance = getResellerBalance($userId);
    if ($newBalance >= $balance) return false; // tidak terpotong

    // Catat ke wallet_transactions sebagai referensi
    try {
        db()->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description, reference_type, reference_id) VALUES (?, ?, 'spend', ?, 'reseller_booking', ?)")
            ->execute([$userId, -$amount, $desc, $bookingId]);
    } catch (Throwable $e) {}

    return $newBalance;
}

/** Ambil riwayat topup reseller. */
function getResellerTopupHistory(int $userId, int $limit = 50): array {
    $stmt = db()->prepare("SELECT * FROM reseller_topups WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT " . (int)$limit);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Ambil harga reseller untuk tour tertentu.
 * Return ['price' => float, 'min_pax' => int] atau null jika tidak ada.
 */
function getResellerTourPrice(int $tourId): ?array {
    $stmt = db()->prepare("SELECT reseller_price, min_pax FROM reseller_tour_prices WHERE tour_id = ? AND active = 1");
    $stmt->execute([$tourId]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return ['price' => (float)$row['reseller_price'], 'min_pax' => (int)$row['min_pax']];
}

/** Format rupiah (ikutilah format yang ada di functions.php). */
if (!function_exists('formatRupiah')) {
    function formatRupiah(float $amount): string {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

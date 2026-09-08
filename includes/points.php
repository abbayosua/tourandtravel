<?php
/**
 * Loyalty points — ledger + saldo + earn otomatis saat booking paid.
 * Rate: settings points_earning_rate (% dari total, default 1) → 1 point per 100 IDR.
 * Idempotent per (booking_type, booking_id, reason) via uniq key.
 */
function getPointsBalance(int $userId): int {
    $stmt = db()->prepare("SELECT COALESCE(SUM(points), 0) FROM points_ledger WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getPointsLedger(int $userId, int $limit = 50): array {
    $stmt = db()->prepare("SELECT * FROM points_ledger WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT " . (int)$limit);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Catat points; return points yang tercatat (0 bila duplikat). */
function recordPoints(int $userId, int $points, string $reason, ?string $bookingType = null, ?int $bookingId = null, ?string $bookingCode = null, ?string $note = null): int {
    if ($points === 0) return 0;
    try {
        $stmt = db()->prepare("INSERT IGNORE INTO points_ledger (user_id, points, booking_type, booking_id, booking_code, reason, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $points, $bookingType, $bookingId, $bookingCode, $reason, $note]);
        return $stmt->rowCount() > 0 ? $points : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

/** Earn otomatis saat booking jadi paid. Idempotent — dipanggil berulang aman. */
function awardPointsForPaidBooking(string $bookingType, int $bookingId, int $userId, float $totalPrice, ?string $bookingCode = null): int {
    if ($userId <= 0 || $totalPrice <= 0) return 0;
    $ratePct = (float)(getSetting('points_earning_rate', '1') ?: '1');
    if ($ratePct <= 0) return 0;
    $points = (int)floor($totalPrice * $ratePct / 100 / 100); // 1 point per 100 IDR
    if ($points < 1) $points = 1;
    return recordPoints($userId, $points, 'earn', $bookingType, $bookingId, $bookingCode, 'Earn ' . $ratePct . '% dari booking');
}

/** Redeem — tolak bila saldo kurang. Return saldo baru atau null bila gagal. */
function redeemPoints(int $userId, int $points, ?string $bookingType = null, ?int $bookingId = null, ?string $note = null): ?int {
    $points = abs($points);
    if ($points < 1) return null;
    if (getPointsBalance($userId) < $points) return null;
    $ok = recordPoints($userId, -$points, 'redeem', $bookingType, $bookingId, null, $note);
    return $ok !== 0 ? getPointsBalance($userId) : null;
}

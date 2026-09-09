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
        if ($stmt->rowCount() > 0 && isset($_SESSION['tier_badge'][$userId])) unset($_SESSION['tier_badge'][$userId]);
        return $stmt->rowCount() > 0 ? $points : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

/** Data tier + poin untuk header badge. Cache per session, bust saat points berubah. */
function getTierBadgeInfo(int $userId): array {
    if ($userId <= 0) return [];
    if (isset($_SESSION['tier_badge'][$userId])) return $_SESSION['tier_badge'][$userId];
    $stmt = db()->prepare("SELECT u.tier, ut.display_name, ut.icon, ut.color FROM users u LEFT JOIN user_tiers ut ON ut.tier_name = u.tier WHERE u.id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) return [];
    $tier = $row['tier'] ?: 'explorer';
    $info = [
        'tier'         => $tier,
        'display_name' => $row['display_name'] ?: ucfirst($tier),
        'icon'         => $row['icon'] ?: 'bi-compass',
        'color'        => $row['color'] ?: '#6c757d',
        'points'       => getPointsBalance($userId),
    ];
    $_SESSION['tier_badge'][$userId] = $info;
    return $info;
}

function getTierMultiplier(int $userId): float {
    $stmt = db()->prepare("SELECT ut.earning_rate FROM users u JOIN user_tiers ut ON u.tier = ut.tier_name WHERE u.id = ?");
    $stmt->execute([$userId]);
    $rate = $stmt->fetchColumn();
    return $rate ? (float)$rate : 1.0;
}

/** Earn otomatis saat booking jadi paid. Idempotent — dipanggil berulang aman. */
function awardPointsForPaidBooking(string $bookingType, int $bookingId, int $userId, float $totalPrice, ?string $bookingCode = null): int {
    if ($userId <= 0 || $totalPrice <= 0) return 0;
    $ratePct = (float)(getSetting('points_earning_rate', '1') ?: '1');
    if ($ratePct <= 0) return 0;
    $points = (int)floor($totalPrice * $ratePct / 100 / 100); // 1 point per 100 IDR
    if ($points < 1) $points = 1;
    $multiplier = getTierMultiplier($userId);
    $points = (int)ceil($points * $multiplier);
    $note = 'Earn ' . $ratePct . '% dari booking';
    if ($multiplier > 1) $note .= ' (x' . $multiplier . ' ' . ($_SESSION['user_tier'] ?? 'explorer') . ')';
    return recordPoints($userId, $points, 'earn', $bookingType, $bookingId, $bookingCode, $note);
}

/**
 * Auto-assign tier based on completed booking count.
 * Called after payment. Reads thresholds from settings.
 */
function autoAssignTier(int $userId): string {
    if ($userId <= 0) return 'explorer';
    $stmt = db()->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('confirmed')");
    $stmt->execute([$userId]);
    $bookingCount = (int)$stmt->fetchColumn();

    $silver = (int)getSetting('loyalty_silver_threshold', '2');
    $gold = (int)getSetting('loyalty_gold_threshold', '5');
    $platinum = (int)getSetting('loyalty_joyplus_threshold', '10');

    if ($bookingCount >= $platinum) $tier = 'platinum';
    elseif ($bookingCount >= $gold) $tier = 'gold';
    elseif ($bookingCount >= $silver) $tier = 'silver';
    else $tier = 'explorer';

    db()->prepare("UPDATE users SET tier = ? WHERE id = ? AND tier != ?")->execute([$tier, $userId, $tier]);
    return $tier;
}

/** Redeem — tolak bila saldo kurang. Return saldo baru atau null bila gagal. */
function redeemPoints(int $userId, int $points, ?string $bookingType = null, ?int $bookingId = null, ?string $note = null): ?int {
    $points = abs($points);
    if ($points < 1) return null;
    if (getPointsBalance($userId) < $points) return null;
    $ok = recordPoints($userId, -$points, 'redeem', $bookingType, $bookingId, null, $note);
    return $ok !== 0 ? getPointsBalance($userId) : null;
}

<?php
/**
 * includes/bundle.php — Fase 5: bundle flight+hotel cross-sell.
 * Diskon 5% untuk booking hotel bila user yang sama membooking flight
 * dalam window 24 jam (sebelum/sesudah), dan sebaliknya.
 *
 * API:
 *  - getBundleWindowHours()            : 24
 *  - hasBundlableFlight($userId)       : ada flight booking user dalam window?
 *  - hasBundlableHotel($userId)        : ada hotel booking user dalam window?
 *  - getBundleDiscount($userId, $type) : ['eligible'=>bool,'pct'=>float,'ref_booking_id'=>int,'ref_code'=>string]
 *  - generateBundleCoupon($userId, $refBookingId) : buat kupon sekali-pakai 5% (idempotent per user+ref)
 */

const BUNDLE_DISCOUNT_PCT = 5.0;
const BUNDLE_WINDOW_HOURS = 24;

function getBundleWindowHours(): int {
    return BUNDLE_WINDOW_HOURS;
}

/** Ada flight booking oleh user dalam window 24 jam (pending/confirmed)? */
function hasBundlableFlight(int $userId): ?array {
    if ($userId <= 0) return null;
    $stmt = db()->prepare("SELECT id, total_price FROM flight_bookings
        WHERE user_id = ? AND status IN ('pending','confirmed')
        AND created_at >= NOW() - INTERVAL " . BUNDLE_WINDOW_HOURS . " HOUR
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/** Ada hotel booking oleh user dalam window 24 jam (pending/confirmed)? */
function hasBundlableHotel(int $userId): ?array {
    if ($userId <= 0) return null;
    $stmt = db()->prepare("SELECT id, total_price FROM hotel_bookings
        WHERE user_id = ? AND status IN ('pending','confirmed')
        AND created_at >= NOW() - INTERVAL " . BUNDLE_WINDOW_HOURS . " HOUR
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Cek eligibilitas bundle untuk booking baru bertipe 'hotel' atau 'flight'.
 * Return: ['eligible', 'pct', 'ref_booking_id', 'ref_code'] atau eligible=false.
 */
function getBundleDiscount(int $userId, string $newBookingType): array {
    $result = ['eligible' => false, 'pct' => 0.0, 'ref_booking_id' => null, 'ref_code' => null];
    if ($userId <= 0) return $result;

    if ($newBookingType === 'hotel') {
        $ref = hasBundlableFlight($userId);
    } elseif ($newBookingType === 'flight') {
        $ref = hasBundlableHotel($userId);
    } else {
        return $result;
    }

    if (!$ref) return $result;
    $result['eligible'] = true;
    $result['pct'] = BUNDLE_DISCOUNT_PCT;
    $result['ref_booking_id'] = (int)$ref['id'];
    $result['ref_code'] = '#' . $ref['id'];
    return $result;
}

/**
 * Generate kupon bundle sekali-pakai (5%, berlaku 7 hari) untuk user.
 * Idempotent per (user, ref_booking): kembalikan kode yang sudah ada.
 */
function generateBundleCoupon(int $userId, int $refBookingId): ?string {
    if ($userId <= 0 || $refBookingId <= 0) return null;

    $descPrefix = 'bundle-' . $userId . '-' . $refBookingId;
    $existing = db()->prepare("SELECT code FROM promo_codes WHERE description = ? LIMIT 1");
    $existing->execute([$descPrefix]);
    if ($code = $existing->fetchColumn()) return $code;

    $code = 'BUNDLE' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $ins = db()->prepare("INSERT INTO promo_codes (code, description, discount_type, discount_value, usage_limit, valid_from, valid_until, is_active)
        VALUES (?, ?, 'percentage', ?, 1, CURRENT_DATE, CURRENT_DATE + INTERVAL 7 DAY, 1)");
    $ok = $ins->execute([$code, $descPrefix, BUNDLE_DISCOUNT_PCT]);
    return $ok ? $code : null;
}

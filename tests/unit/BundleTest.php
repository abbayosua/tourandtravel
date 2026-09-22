<?php
/**
 * BundleTest — Fase 5: bundle flight+hotel cross-sell.
 * Window 24 jam (boundary 23h/25h), eligibilitas cross-type, kupon idempotent,
 * non-login ditolak, tipe lain (tour) tidak eligible.
 */
require_once __DIR__ . '/../../includes/bundle.php';

const BD_UID = 999030;

function bdCleanup(): void {
    db()->prepare("DELETE FROM flight_bookings WHERE user_id = ?")->execute([BD_UID]);
    db()->prepare("DELETE FROM hotel_bookings WHERE user_id = ?")->execute([BD_UID]);
    db()->prepare("DELETE FROM promo_codes WHERE description LIKE ?")->execute(['bundle-' . BD_UID . '-%']);
    db()->prepare("DELETE FROM users WHERE id = ?")->execute([BD_UID]);
}

function bdSetup(): void {
    bdCleanup();
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (?, 'BD Tester', 'bd@t.local', 'x')")
        ->execute([BD_UID]);
}

function bdMakeFlight(string $agoInterval): int {
    db()->exec("INSERT INTO flight_bookings (schedule_id, user_id, name, email, phone, departure_date, total_price, status, created_at)
        VALUES (1, " . BD_UID . ", 'B', 'bd@t.local', '0812', CURDATE(), 1000000, 'confirmed', NOW() - INTERVAL $agoInterval)");
    return (int)db()->lastInsertId();
}

function bdMakeHotel(string $agoInterval): int {
    db()->exec("INSERT INTO hotel_bookings (hotel_id, user_id, checkin, checkout, rooms, guests, name, phone, email, total_price, status, created_at)
        VALUES (1, " . BD_UID . ", CURDATE() + INTERVAL 5 DAY, CURDATE() + INTERVAL 7 DAY, 1, 2, 'B', '0812', 'bd@t.local', 500000, 'pending', NOW() - INTERVAL $agoInterval)");
    return (int)db()->lastInsertId();
}

function testWindow24hBoundary() {
    bdSetup();
    // flight 23 jam lalu → hotel eligible
    bdMakeFlight('23 HOUR');
    assertTrue(getBundleDiscount(BD_UID, 'hotel')['eligible'], 'flight 23h → hotel eligible');

    // hapus flight, buat 25 jam lalu → NOT eligible
    db()->prepare("DELETE FROM flight_bookings WHERE user_id = ?")->execute([BD_UID]);
    bdMakeFlight('25 HOUR');
    assertTrue(!getBundleDiscount(BD_UID, 'hotel')['eligible'], 'flight 25h → hotel NOT eligible');

    // boundary exact: 24 jam tepat masih dalam window (created_at >= NOW() - INTERVAL 24 HOUR)
    db()->prepare("DELETE FROM flight_bookings WHERE user_id = ?")->execute([BD_UID]);
    bdMakeFlight('24 HOUR');
    assertTrue(getBundleDiscount(BD_UID, 'hotel')['eligible'], 'flight exactly 24h → eligible (inclusive)');

    bdCleanup();
}

function testOutsideWindowReturnsZeroPct() {
    bdSetup();
    bdMakeFlight('72 HOUR'); // jauh di luar window
    $r = getBundleDiscount(BD_UID, 'hotel');
    assertTrue(!$r['eligible'], 'di luar window → not eligible');
    assertEquals(0.0, $r['pct'], 'pct = 0 di luar window');
    assertEquals(null, $r['ref_booking_id'], 'tanpa ref booking');

    bdCleanup();
}

function testCrossTypeEligibility() {
    bdSetup();
    // flight → hotel eligible
    bdMakeFlight('1 HOUR');
    assertTrue(getBundleDiscount(BD_UID, 'hotel')['eligible'], 'flight dulu → hotel eligible');
    // hotel → flight eligible (tanpa hapus hotel; hotel dibuat baru saja)
    db()->prepare("DELETE FROM flight_bookings WHERE user_id = ?")->execute([BD_UID]);
    bdMakeHotel('1 HOUR');
    assertTrue(getBundleDiscount(BD_UID, 'flight')['eligible'], 'hotel dulu → flight eligible');
    // hotel → hotel NOT eligible (harus cross-type)
    assertTrue(!getBundleDiscount(BD_UID, 'hotel')['eligible'], 'hotel → hotel tidak eligible');
    // tour → tidak eligible tipe apapun
    assertTrue(!getBundleDiscount(BD_UID, 'tour')['eligible'], 'tour → tidak eligible');

    bdCleanup();
}

function testNotLoggedInReturnsNotEligible() {
    bdSetup();
    $r = getBundleDiscount(0, 'hotel');
    assertTrue(!$r['eligible'], 'user_id 0 → not eligible');
    assertEquals(0.0, $r['pct']);

    bdCleanup();
}

function testCouponIdempotentPerUserRef() {
    bdSetup();
    $fbId = bdMakeFlight('1 HOUR');

    $c1 = generateBundleCoupon(BD_UID, $fbId);
    assertTrue($c1 !== null && $c1 !== '', 'kupon pertama dibuat');
    $c2 = generateBundleCoupon(BD_UID, $fbId);
    assertEquals($c1, $c2, 'kupon kedua → kode sama (idempotent)');
    assertEquals(1, (int)db()->prepare("SELECT COUNT(*) FROM promo_codes WHERE description = ?")
        ->execute(['bundle-' . BD_UID . '-' . $fbId]) ? db()->query("SELECT COUNT(*) FROM promo_codes WHERE description = 'bundle-" . BD_UID . "-" . $fbId . "'")->fetchColumn() : 0, 'hanya 1 baris promo_codes');

    $row = db()->prepare("SELECT discount_type, discount_value, usage_limit, is_active FROM promo_codes WHERE code = ?");
    $row->execute([$c1]);
    $r = $row->fetch();
    assertEquals('percentage', $r['discount_type']);
    assertEquals(5.0, (float)$r['discount_value']);
    assertEquals(1, (int)$r['usage_limit']);
    assertEquals(1, (int)$r['is_active']);

    bdCleanup();
}

function testCouponRefundForInvalidInput() {
    bdSetup();
    assertTrue(generateBundleCoupon(0, 1) === null, 'user_id 0 → null');
    assertTrue(generateBundleCoupon(BD_UID, 0) === null, 'ref 0 → null');

    bdCleanup();
}

function testCouponValid7Days() {
    bdSetup();
    $fbId = bdMakeFlight('1 HOUR');
    $c = generateBundleCoupon(BD_UID, $fbId);
    $row = db()->prepare("SELECT valid_from, valid_until FROM promo_codes WHERE code = ?");
    $row->execute([$c]);
    $r = $row->fetch();
    assertEquals(date('Y-m-d'), $r['valid_from'], 'valid_from = hari ini');
    assertEquals(date('Y-m-d', strtotime('+7 days')), $r['valid_until'], 'valid_until = +7 hari');

    bdCleanup();
}

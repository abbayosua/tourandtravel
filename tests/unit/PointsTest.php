<?php
/**
 * PointsTest — ledger, earn otomatis idempotent, redeem sad path.
 * User test: id=1 (FK users). Rate default 1% → 1 point per 100 IDR.
 */

const PT_UID = 1;

function ptCleanup() {
    db()->prepare("DELETE FROM points_ledger WHERE user_id = ?")->execute([PT_UID]);
}

function testEarnOtomatisRateOnePercent() {
    ptCleanup();
    // total 1.500.000 IDR × 1% = 15.000 → /100 = 150 points
    $pts = awardPointsForPaidBooking('tour', 999001, PT_UID, 1500000.0, 'TAT-PT01');
    assertEquals(150, $pts);
    assertEquals(150, getPointsBalance(PT_UID));
    ptCleanup();
}

function testEarnIdempotent() {
    ptCleanup();
    $pts1 = awardPointsForPaidBooking('tour', 999001, PT_UID, 1500000.0, 'TAT-PT01');
    $pts2 = awardPointsForPaidBooking('tour', 999001, PT_UID, 1500000.0, 'TAT-PT01'); // duplikat
    assertEquals(150, $pts1);
    assertEquals(0, $pts2, 'duplikat → 0 (no-op)');
    assertEquals(150, getPointsBalance(PT_UID), 'saldo tetap 150');
    ptCleanup();
}

function testEarnMinimalSatuPoint() {
    ptCleanup();
    // total 5.000 × 1% = 50 → /100 = 0 → di-floor jadi minimal 1
    $pts = awardPointsForPaidBooking('hotel', 999001, PT_UID, 5000.0, null);
    assertEquals(1, $pts, 'minimal 1 point');
    ptCleanup();
}

function testEarnZeroWhenNoUserOrZeroTotal() {
    ptCleanup();
    assertEquals(0, awardPointsForPaidBooking('tour', 999001, 0, 100000.0));
    assertEquals(0, awardPointsForPaidBooking('tour', 999001, PT_UID, 0.0));
    assertEquals(0, getPointsBalance(PT_UID));
    ptCleanup();
}

function testRedeemHappyAndInsufficient() {
    ptCleanup();
    awardPointsForPaidBooking('tour', 999001, PT_UID, 1500000.0, 'TAT-PT01'); // 150
    // redeem 100 → sisa 50
    $sisa = redeemPoints(PT_UID, 100, 'tour', 999001, 'Redeem test');
    assertEquals(50, $sisa);
    // redeem 100 > saldo 50 → null
    assertEquals(null, redeemPoints(PT_UID, 100));
    // redeem 50 → sisa 0
    assertEquals(0, redeemPoints(PT_UID, 50));
    // redeem 1 > saldo 0 → null
    assertEquals(null, redeemPoints(PT_UID, 1));
    ptCleanup();
}

function testLedgerMengurutkanTerbaru() {
    ptCleanup();
    awardPointsForPaidBooking('tour', 999001, PT_UID, 1500000.0, 'TAT-PT01');
    redeemPoints(PT_UID, 50);
    $ledger = getPointsLedger(PT_UID);
    assertTrue(count($ledger) >= 2);
    assertEquals('redeem', $ledger[0]['reason'], 'terbaru dulu');
    ptCleanup();
}

// === Tier & Multiplier Tests ===

function testTierDefaultExplorer() {
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TIER%'")->execute([PT_UID]);
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TGOLD%'")->execute([PT_UID]);
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TPLAT%'")->execute([PT_UID]);
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
    $tier = autoAssignTier(PT_UID);
    assertEquals('explorer', $tier, '0 bookings → explorer');
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
}

function testTierUpgradeSilver() {
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TIER%'")->execute([PT_UID]);
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
    for ($i = 0; $i < 2; $i++) {
        db()->prepare("INSERT IGNORE INTO bookings (user_id, tour_id, tour_date_id, name, email, phone, participants, total_price, booking_code, status, created_at) VALUES (?, 61, 1, 'T', 't@t.t', '0', 1, 100000, ?, 'confirmed', NOW())")->execute([PT_UID, 'TAT-TIER' . $i]);
    }
    $tier = autoAssignTier(PT_UID);
    assertEquals('silver', $tier, '2 bookings → silver');
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TIER%'")->execute([PT_UID]);
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
}

function testTierUpgradeGold() {
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TGOLD%'")->execute([PT_UID]);
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
    for ($i = 0; $i < 5; $i++) {
        db()->prepare("INSERT IGNORE INTO bookings (user_id, tour_id, tour_date_id, name, email, phone, participants, total_price, booking_code, status, created_at) VALUES (?, 61, 1, 'T', 't@t.t', '0', 1, 100000, ?, 'confirmed', NOW())")->execute([PT_UID, 'TAT-TGOLD' . $i]);
    }
    $tier = autoAssignTier(PT_UID);
    assertEquals('gold', $tier, '5 bookings → gold');
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TGOLD%'")->execute([PT_UID]);
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
}

function testTierUpgradePlatinum() {
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TPLAT%'")->execute([PT_UID]);
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
    for ($i = 0; $i < 10; $i++) {
        db()->prepare("INSERT IGNORE INTO bookings (user_id, tour_id, tour_date_id, name, email, phone, participants, total_price, booking_code, status, created_at) VALUES (?, 61, 1, 'T', 't@t.t', '0', 1, 100000, ?, 'confirmed', NOW())")->execute([PT_UID, 'TAT-TPLAT' . $i]);
    }
    $tier = autoAssignTier(PT_UID);
    assertEquals('platinum', $tier, '10 bookings → platinum');
    db()->prepare("DELETE FROM bookings WHERE user_id = ? AND booking_code LIKE 'TAT-TPLAT%'")->execute([PT_UID]);
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
}

function testTierMultiplierExplorer() {
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
    $m = getTierMultiplier(PT_UID);
    assertEquals(1.0, $m, 'explorer multiplier = 1.0');
}

function testTierMultiplierSilver() {
    db()->prepare("UPDATE users SET tier = 'silver' WHERE id = ?")->execute([PT_UID]);
    $m = getTierMultiplier(PT_UID);
    assertEquals(1.2, $m, 'silver multiplier = 1.2');
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
}

function testTierMultiplierGold() {
    db()->prepare("UPDATE users SET tier = 'gold' WHERE id = ?")->execute([PT_UID]);
    $m = getTierMultiplier(PT_UID);
    assertEquals(1.5, $m, 'gold multiplier = 1.5');
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
}

function testTierMultiplierPlatinum() {
    db()->prepare("UPDATE users SET tier = 'platinum' WHERE id = ?")->execute([PT_UID]);
    $m = getTierMultiplier(PT_UID);
    assertEquals(2.0, $m, 'platinum multiplier = 2.0');
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
}

function testEarnAppliesMultiplier() {
    ptCleanup();
    db()->prepare("UPDATE users SET tier = 'silver' WHERE id = ?")->execute([PT_UID]);
    // 1.500.000 × 1% / 100 = 150 base × 1.2 = 180
    $pts = awardPointsForPaidBooking('tour', 999099, PT_UID, 1500000.0, 'TAT-MULT');
    assertEquals(180, $pts, 'silver x1.2 → 180 points');
    assertEquals(180, getPointsBalance(PT_UID));
    db()->prepare("UPDATE users SET tier = 'explorer' WHERE id = ?")->execute([PT_UID]);
    ptCleanup();
}

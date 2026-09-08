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

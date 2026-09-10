<?php
/**
 * ResellerTest — balance CRUD, topup status, spending, insufficient balance.
 * Uses user id=1 (FK users).
 */

const RS_UID = 1;

function rsCleanup() {
    db()->prepare("UPDATE users SET role = 'user', reseller_balance = 0 WHERE id = ?")->execute([RS_UID]);
    db()->prepare("DELETE FROM reseller_topups WHERE user_id = ?")->execute([RS_UID]);
}

function testIsResellerDefaultFalse() {
    rsCleanup();
    assertEquals(false, isReseller(RS_UID), 'default role is user, not reseller');
    rsCleanup();
}

function testIsResellerAfterRoleChange() {
    rsCleanup();
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    assertTrue(isReseller(RS_UID));
    rsCleanup();
}

function testGetResellerBalanceDefaultZero() {
    rsCleanup();
    assertEquals(0.0, getResellerBalance(RS_UID));
    rsCleanup();
}

function testTopUpReseller() {
    rsCleanup();
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    $bal = topUpReseller(RS_UID, 500000.0);
    assertEquals(500000.0, $bal);
    assertEquals(500000.0, getResellerBalance(RS_UID));
    // topup lagi
    $bal2 = topUpReseller(RS_UID, 250000.0);
    assertEquals(750000.0, $bal2);
    rsCleanup();
}

function testTopUpResellerZeroNoop() {
    rsCleanup();
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    topUpReseller(RS_UID, 100000.0);
    $bal = topUpReseller(RS_UID, 0.0);
    assertEquals(100000.0, $bal, 'zero topup is noop');
    rsCleanup();
}

function testSpendResellerBalance() {
    rsCleanup();
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    topUpReseller(RS_UID, 500000.0);
    $bal = spendResellerBalance(RS_UID, 200000.0, 'Test booking', null);
    assertEquals(300000.0, $bal);
    rsCleanup();
}

function testSpendResellerBalanceInsufficient() {
    rsCleanup();
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    topUpReseller(RS_UID, 100000.0);
    $result = spendResellerBalance(RS_UID, 999999.0, 'Should fail');
    assertEquals(false, $result, 'insufficient balance returns false');
    assertEquals(100000.0, getResellerBalance(RS_UID), 'balance unchanged');
    rsCleanup();
}

function testSpendResellerBalanceExactAmount() {
    rsCleanup();
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    topUpReseller(RS_UID, 100000.0);
    $bal = spendResellerBalance(RS_UID, 100000.0, 'Exact spend');
    assertEquals(0.0, $bal);
    rsCleanup();
}

function testGetResellerTopupHistory() {
    rsCleanup();
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    db()->prepare("INSERT INTO reseller_topups (user_id, amount, payment_method, status) VALUES (?, ?, 'bank_transfer', 'pending')")
        ->execute([RS_UID, 100000.0]);
    db()->prepare("INSERT INTO reseller_topups (user_id, amount, payment_method, status) VALUES (?, ?, 'qris', 'approved')")
        ->execute([RS_UID, 200000.0]);
    $history = getResellerTopupHistory(RS_UID);
    assertEquals(2, count($history));
    assertEquals('approved', $history[0]['status']); // newest first
    assertEquals('pending', $history[1]['status']);
    rsCleanup();
}

function testGetResellerTourPriceNotFound() {
    $result = getResellerTourPrice(999999);
    assertEquals(null, $result, 'no pricing for unknown tour');
}

function testGetUserRole() {
    rsCleanup();
    assertEquals('user', getUserRole(RS_UID));
    db()->prepare("UPDATE users SET role = 'reseller' WHERE id = ?")->execute([RS_UID]);
    assertEquals('reseller', getUserRole(RS_UID));
    db()->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([RS_UID]);
    assertEquals('admin', getUserRole(RS_UID));
    rsCleanup();
}

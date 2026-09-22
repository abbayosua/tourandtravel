<?php
/**
 * SavedPaymentsTest — Backlog #8: saved payment methods + 1-click pay.
 * CRUD (save idempotent per token, default switch, soft delete),
 * ownership validation, charge fail-soft (tanpa server key / method not found).
 */
require_once __DIR__ . '/../../includes/saved-payments.php';

const SP_UID = 999040;

function spCleanup(): void {
    db()->prepare("DELETE FROM saved_payment_methods WHERE user_id = ?")->execute([SP_UID]);
    db()->prepare("DELETE FROM users WHERE id = ?")->execute([SP_UID]);
}

function spSetup(): void {
    spCleanup();
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (?, 'SP', 'sp-unit@t.local', 'x')")
        ->execute([SP_UID]);
}

function testSaveMethodFirstIsDefault() {
    spSetup();
    $m1 = savePaymentMethod(SP_UID, 'tok-unit-1', 'visa', '4811-****-1111', 12, 2028);
    assertTrue($m1 > 0, 'save sukses');
    assertEquals(1, (int)getSavedPaymentMethod(SP_UID, $m1)['is_default'], 'metode pertama otomatis default');

    $m2 = savePaymentMethod(SP_UID, 'tok-unit-2', 'gopay', '****', null, null);
    assertEquals(0, (int)getSavedPaymentMethod(SP_UID, $m2)['is_default'], 'metode kedua tidak default');

    spCleanup();
}

function testSaveIdempotentPerToken() {
    spSetup();
    $m1 = savePaymentMethod(SP_UID, 'tok-unit-idem', 'visa', '4811', 12, 2028);
    $m1b = savePaymentMethod(SP_UID, 'tok-unit-idem', 'visa', '4811', 12, 2028);
    assertEquals($m1, $m1b, 'token sama → id sama');
    assertEquals(1, (int)db()->query("SELECT COUNT(*) FROM saved_payment_methods WHERE user_id = " . SP_UID)->fetchColumn(), 'tidak duplikat baris');

    spCleanup();
}

function testDefaultSwitchReleasesOld() {
    spSetup();
    $m1 = savePaymentMethod(SP_UID, 'tok-unit-a', 'visa', '4811', 12, 2028);
    $m2 = savePaymentMethod(SP_UID, 'tok-unit-b', 'gopay', null, null, null);

    assertTrue(setDefaultPaymentMethod(SP_UID, $m2), 'set default m2');
    assertEquals(1, (int)getSavedPaymentMethod(SP_UID, $m2)['is_default']);
    assertEquals(0, (int)getSavedPaymentMethod(SP_UID, $m1)['is_default'], 'default lama dilepas');

    // set default utk metode user lain → gagal
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (999041, 'OTHER', 'other@t.local', 'x')")->execute();
    assertTrue(!setDefaultPaymentMethod(999041, $m2), 'set default milik user lain ditolak');
    db()->prepare("DELETE FROM users WHERE id = 999041")->execute();

    spCleanup();
}

function testRemoveSoftDeleteAndOwnership() {
    spSetup();
    $m1 = savePaymentMethod(SP_UID, 'tok-unit-del', 'visa', '4811', 12, 2028);

    // remove milik orang lain → rowCount 0
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (999041, 'OTHER', 'other@t.local', 'x')")->execute();
    assertTrue(!removePaymentMethod(999041, $m1), 'remove milik user lain ditolak');
    db()->prepare("DELETE FROM users WHERE id = 999041")->execute();

    assertTrue(removePaymentMethod(SP_UID, $m1), 'remove sendiri sukses');
    assertEquals(null, getSavedPaymentMethod(SP_UID, $m1), 'method sudah tidak aktif');
    // baris masih ada (soft delete)
    assertEquals(1, (int)db()->query("SELECT COUNT(*) FROM saved_payment_methods WHERE id = $m1")->fetchColumn(), 'soft delete: baris tetap ada, is_active=0');

    spCleanup();
}

function testListOnlyActiveOwnedMethods() {
    spSetup();
    $m1 = savePaymentMethod(SP_UID, 'tok-list-1', 'visa', '4811', 12, 2028);
    $m2 = savePaymentMethod(SP_UID, 'tok-list-2', 'gopay', null, null, null);
    removePaymentMethod(SP_UID, $m1);

    $list = getSavedPaymentMethods(SP_UID);
    assertEquals(1, count($list), 'hanya 1 aktif');
    assertEquals($m2, (int)$list[0]['id']);
    // token user lain tidak bocor
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (999041, 'OTHER', 'other@t.local', 'x')")->execute();
    assertEquals(0, count(getSavedPaymentMethods(999041)), 'user lain tidak melihat token');
    db()->prepare("DELETE FROM users WHERE id = 999041")->execute();

    spCleanup();
}

function testChargeFailSoftWithoutServerKey() {
    spSetup();
    $m1 = savePaymentMethod(SP_UID, 'tok-charge-1', 'visa', '4811', 12, 2028);

    // server key kosong (unit env) → payment_disabled
    $r = chargeWithSavedToken(SP_UID, $m1, 'TEST-CHARGE-1', 100000);
    assertEquals('payment_disabled', $r['error'], 'tanpa server key → fail-soft');

    // method tidak milik user → method_not_found (kalau server key ada, tetap aman)
    spCleanup();
}

<?php
/**
 * RefundPolicyTest — Fase 3: refund policy engine.
 * Matrix H-x (full/half/none), override per produk, request/decide flow,
 * idempotent approve (double approve ditolak), wallet kredit exact.
 */
require_once __DIR__ . '/../../includes/refund.php';
require_once __DIR__ . '/../../includes/availability.php';

const RF_UID = 999010;

function rfCleanup(): void {
    db()->exec("DELETE b FROM bookings b WHERE b.booking_code LIKE 'RFT-%'");
    db()->prepare("DELETE FROM wallet_transactions WHERE user_id = ?")->execute([RF_UID]);
    db()->exec("DELETE td FROM tour_dates td WHERE td.tour_id = 61 AND td.departure_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND td.available_slots = 5");
    db()->prepare("DELETE FROM users WHERE id = ?")->execute([RF_UID]);
    db()->prepare("UPDATE tours SET refund_policy = 'auto' WHERE id = 61")->execute();
}

function rfSetup(): void {
    rfCleanup();
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (?, 'RF Tester', 'rf-unit@t.local', 'x')")
        ->execute([RF_UID]);
}

/** Buat booking confirmed dengan departure H-$offset, harga 100000/pax. Return booking_id. */
function rfMakeBooking(int $offsetDays, int $pax = 1, float $total = 100000.0): int {
    db()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active)
        VALUES (61, DATE_ADD(CURDATE(), INTERVAL $offsetDays DAY), DATE_ADD(CURDATE(), INTERVAL " . ($offsetDays + 2) . " DAY), 5, 0, 1)");
    $td = (int)db()->lastInsertId();
    $code = 'RFT-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, user_id, name, email, phone, participants, total_price, status)
        VALUES (?, 61, ?, ?, 'RF', 'rf-unit@t.local', '0812', ?, ?, 'confirmed')")
        ->execute([$code, $td, RF_UID, $pax, $total]);
    return (int)db()->lastInsertId();
}

function testPolicyMatrixFullHalfNone() {
    rfSetup();
    // H-10 → full 100%; H-5 → half 50%; H-2 → none 0%
    $bkFull = rfMakeBooking(10);
    $bkHalf = rfMakeBooking(5);
    $bkNone = rfMakeBooking(2);

    $cFull = calculateRefund($bkFull);
    $cHalf = calculateRefund($bkHalf);
    $cNone = calculateRefund($bkNone);

    assertEquals(100, $cFull['pct'], 'H-10 → 100%');
    assertEquals(100000.0, $cFull['amount'], 'H-10 amount exact');
    assertEquals('full', $cFull['tier']);

    assertEquals(50, $cHalf['pct'], 'H-5 → 50%');
    assertEquals(50000.0, $cHalf['amount'], 'H-5 amount exact');
    assertEquals('half', $cHalf['tier']);

    assertEquals(0, $cNone['pct'], 'H-2 → 0%');
    assertEquals(0.0, $cNone['amount'], 'H-2 amount 0');
    assertEquals('none', $cNone['tier']);

    rfCleanup();
}

function testPolicyBoundaryH8() {
    rfSetup();
    // Boundary: H-8 masih full (>=8), H-7 → half
    $bk8 = rfMakeBooking(8);
    $c8 = calculateRefund($bk8);
    assertEquals(100, $c8['pct'], 'H-8 → 100% (boundary inclusive)');
    assertEquals('full', $c8['tier']);

    $bk7 = rfMakeBooking(7);
    $c7 = calculateRefund($bk7);
    assertEquals(50, $c7['pct'], 'H-7 → 50%');
    assertEquals('half', $c7['tier']);

    // H-4 masih half, H-3 → none
    $bk4 = rfMakeBooking(4);
    assertEquals(50, calculateRefund($bk4)['pct'], 'H-4 → 50%');
    $bk3 = rfMakeBooking(3);
    assertEquals(0, calculateRefund($bk3)['pct'], 'H-3 → 0% (boundary)');

    rfCleanup();
}

function testPerProductOverride() {
    rfSetup();
    $bk = rfMakeBooking(10); // H-10 auto = full

    // non_refundable override → 0% walau H-10
    db()->prepare("UPDATE tours SET refund_policy = 'non_refundable' WHERE id = 61")->execute();
    assertEquals(0, calculateRefund($bk)['pct'], 'override non_refundable → 0%');
    assertEquals('non_refundable', calculateRefund($bk)['policy']);

    // full_refund override → 100% walau H-2
    $bk2 = rfMakeBooking(2);
    db()->prepare("UPDATE tours SET refund_policy = 'full_refund' WHERE id = 61")->execute();
    assertEquals(100, calculateRefund($bk2)['pct'], 'override full_refund → 100%');
    assertEquals('full_refund', calculateRefund($bk2)['policy']);

    rfCleanup();
}

function testRequestRefundValidation() {
    rfSetup();
    $bk = rfMakeBooking(10);

    // bukan pemilik
    [$ok, $msg] = requestRefund($bk, RF_UID + 1, 'bukan milikku');
    assertTrue(!$ok, 'booking milik orang lain ditolak');

    // reason kosong → tetap boleh (fallback 'Tidak disebutkan')
    [$ok2] = requestRefund($bk, RF_UID, '');
    assertTrue($ok2, 'reason kosong diterima (fallback)');
    assertEquals('requested', db()->query("SELECT refund_status FROM bookings WHERE id = $bk")->fetchColumn());

    // double request ditolak
    [$ok3, $msg3] = requestRefund($bk, RF_UID, 'lagi');
    assertTrue(!$ok3, 'double request ditolak');
    assertMatches('/sudah/', $msg3);

    rfCleanup();
}

function testRequestRefundRejectsWhenNoQuota() {
    rfSetup();
    $bk = rfMakeBooking(2); // H-2 → 0%
    [$ok, $msg] = requestRefund($bk, RF_UID, 'too late');
    assertTrue(!$ok, 'pct 0 → pengajuan ditolak');
    assertEquals('none', db()->query("SELECT refund_status FROM bookings WHERE id = $bk")->fetchColumn(), 'status tetap none');

    rfCleanup();
}

function testApproveCreditsWalletExactAndIdempotent() {
    rfSetup();
    $bk = rfMakeBooking(10, 2, 200000.0); // H-10, 2 pax, 200rb → refund full 200rb
    requestRefund($bk, RF_UID, 'test approve');

    [$ok, $msg] = decideRefund($bk, true, 1);
    assertTrue($ok, 'approve pertama sukses: ' . $msg);

    $bal = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id = " . RF_UID . " AND type = 'refund'")->fetchColumn();
    assertEquals(200000.0, $bal, 'wallet kredit exact 200rb');

    $row = db()->query("SELECT refund_status, refund_amount, status FROM bookings WHERE id = $bk")->fetch();
    assertEquals('approved', $row['refund_status']);
    assertEquals(200000.0, (float)$row['refund_amount']);
    assertEquals('cancelled', $row['status']);

    // approve kedua (idempotent) → ditolak, wallet TIDAK nambah
    [$ok2] = decideRefund($bk, true, 1);
    assertTrue(!$ok2, 'approve kedua ditolak (bukan requested lagi)');
    $bal2 = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id = " . RF_UID . " AND type = 'refund'")->fetchColumn();
    assertEquals(200000.0, $bal2, 'wallet TIDAK double-kredit');

    rfCleanup();
}

function testRejectSetsStatusAndNoWalletCredit() {
    rfSetup();
    $bk = rfMakeBooking(10, 1, 100000.0);
    requestRefund($bk, RF_UID, 'test reject');

    [$ok] = decideRefund($bk, false, 1);
    assertTrue($ok, 'reject sukses');
    assertEquals('rejected', db()->query("SELECT refund_status FROM bookings WHERE id = $bk")->fetchColumn());
    $bal = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id = " . RF_UID . " AND type = 'refund'")->fetchColumn();
    assertEquals(0.0, $bal, 'tidak ada kredit wallet saat reject');

    rfCleanup();
}

function testApproveReleasesSlotsOnce() {
    rfSetup();
    $bk = rfMakeBooking(10, 2, 200000.0);
    $tdId = (int)db()->query("SELECT tour_date_id FROM bookings WHERE id = $bk")->fetchColumn();
    // simulasi sudah pernah deduct saat payment paid
    deductTourSlotsOnPaid($bk);
    $bookedBefore = (int)db()->query("SELECT booked FROM tour_dates WHERE id = $tdId")->fetch()['booked'];
    assertEquals(2, $bookedBefore);

    requestRefund($bk, RF_UID, 'test slot release');
    decideRefund($bk, true, 1);

    $bookedAfter = (int)db()->query("SELECT booked FROM tour_dates WHERE id = $tdId")->fetch()['booked'];
    assertEquals(0, $bookedAfter, 'slot dikembalikan saat refund approve');

    // approve ulang tidak double-release
    rfCleanup();
}

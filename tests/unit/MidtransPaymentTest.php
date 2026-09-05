<?php
/**
 * MidtransPaymentTest — logika inti payment:
 * signature sha512, mapping status, idempotensi handler.
 *
 * NOTA: handler diuji terhadap DB lokal dengan fixture yang di-cleanup;
 * server key diset sementara lalu dikembalikan.
 */

require_once __DIR__ . '/../../includes/payments.php';

function midtransTestSetup(): string {
    // simpan server key lama, set fixture
    $old = getSetting('midtrans_server_key', '');
    setSetting('midtrans_server_key', 'SB-Mid_server-UNITTEST');
    // tabel payments harus ada
    $n = db()->query("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='payments'")->fetch()['c'];
    assertTrue((int)$n === 1, 'tabel payments harus ada (jalankan migrate-payments.sql)');
    return $old;
}

function midtransTestTeardown(string $oldKey): void {
    setSetting('midtrans_server_key', $oldKey);
    db()->exec("DELETE FROM payments WHERE order_id LIKE 'UNIT-%'");
    db()->exec("UPDATE bookings SET payment_status='unpaid' WHERE booking_code LIKE 'UNIT-%'");
}

function testVerifySignatureValidSha512() {
    $old = midtransTestSetup();
    try {
        $orderId = 'UNIT-ORDER-1'; $statusCode = '200'; $gross = '100000.00';
        $expected = hash('sha512', $orderId . $statusCode . $gross . 'SB-Mid_server-UNITTEST');
        assertTrue(verifyMidtransSignature($orderId, $statusCode, $gross, $expected), 'signature valid harus diterima');
    } finally { midtransTestTeardown($old); }
}

function testVerifySignatureRejectsWrongSignature() {
    $old = midtransTestSetup();
    try {
        $bad = str_repeat('a', 128);
        assertTrue(!verifyMidtransSignature('UNIT-ORDER-1', '200', '100000.00', $bad), 'signature salah harus ditolak');
        assertTrue(!verifyMidtransSignature('UNIT-ORDER-1', '200', '100000.00', ''), 'signature kosong harus ditolak');
    } finally { midtransTestTeardown($old); }
}

function testVerifySignatureTamperedAmountRejected() {
    $old = midtransTestSetup();
    try {
        $sig = hash('sha512', 'UNIT-ORDER-1' . '200' . '999999.00' . 'SB-Mid_server-UNITTEST');
        assertTrue(!verifyMidtransSignature('UNIT-ORDER-1', '200', '100000.00', $sig), 'gross_amount tampered harus ditolak');
    } finally { midtransTestTeardown($old); }
}

function testMidtransStatusMapping() {
    assertSame('paid', midtransMapStatus('capture'), 'capture → paid');
    assertSame('paid', midtransMapStatus('settlement'), 'settlement → paid');
    assertSame('expired', midtransMapStatus('expire'), 'expire → expired');
    assertSame('failed', midtransMapStatus('deny'), 'deny → failed');
    assertSame('failed', midtransMapStatus('cancel'), 'cancel → failed');
    assertSame('challenge', midtransMapStatus('challenge'), 'challenge tetap challenge');
    assertSame('pending', midtransMapStatus('pending'), 'pending tetap pending');
    assertSame('pending', midtransMapStatus('apa-saja-lain'), 'status tak dikenal → pending (aman)');
}

function testPaymentHandlerIdempotentTwoCalls() {
    $old = midtransTestSetup();
    try {
    // fixture booking
    db()->exec("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status, payment_status)
                VALUES ('UNIT-BK1', 61, 1, 'Unit Tester', 'unit@test.local', '0812', 2, 150000, 'pending', 'unpaid')");
    $bookingId = (int)db()->lastInsertId();

    // payment row pending
    db()->exec("INSERT INTO payments (booking_type, booking_id, booking_code, order_id, gross_amount, status)
                VALUES ('tour', $bookingId, 'UNIT-BK1', 'UNIT-PAY-1', 150000, 'pending')");
    $paymentId = (int)db()->lastInsertId();

    $notif = [
        'order_id' => 'UNIT-PAY-1',
        'status_code' => '200',
        'gross_amount' => '150000.00',
        'transaction_status' => 'settlement',
        'payment_type' => 'bank_transfer',
        'transaction_id' => 'TX-UNIT-1',
    ];
    $sig = hash('sha512', $notif['order_id'] . $notif['status_code'] . $notif['gross_amount'] . 'SB-Mid_server-UNITTEST');

    // panggil 1
    assertTrue(handleMidtransNotification(array_merge($notif, ['signature_key' => $sig])), 'panggilan pertama harus sukses');
    // panggil 2 (duplikat webhook) — harus sukses tanpa mengubah apa pun
    assertTrue(handleMidtransNotification(array_merge($notif, ['signature_key' => $sig])), 'panggilan duplikat harus idempotent');

    // payment paid 1x
    $p = db()->query("SELECT status, paid_at, transaction_id FROM payments WHERE id = $paymentId")->fetch();
    assertSame('paid', $p['status'], 'payment status = paid');
    assertTrue($p['paid_at'] !== null, 'paid_at terisi');

    // booking paid
    $b = db()->query("SELECT payment_status, status FROM bookings WHERE id = $bookingId")->fetch();
    assertSame('paid', $b['payment_status'], 'booking payment_status = paid');

    // tidak ada row duplikat
    $cnt = db()->query("SELECT COUNT(*) c FROM payments WHERE order_id = 'UNIT-PAY-1'")->fetch()['c'];
    assertEquals(1, (int)$cnt, 'idempoten: tetap 1 row payment');
    } finally { midtransTestTeardown($old); }
}

function testPaymentHandlerRejectsBadSignature() {
    $old = midtransTestSetup();
    try {
    $notif = [
        'order_id' => 'UNIT-PAY-TAMPER',
        'status_code' => '200',
        'gross_amount' => '150000.00',
        'transaction_status' => 'settlement',
        'signature_key' => str_repeat('0', 128),
    ];
    assertTrue(!handleMidtransNotification($notif), 'signature salah harus ditolak');
    $cnt = db()->query("SELECT COUNT(*) c FROM payments WHERE order_id = 'UNIT-PAY-TAMPER'")->fetch()['c'];
    assertEquals(0, (int)$cnt, 'tidak ada payment dibuat dari notif tak sah');
    } finally { midtransTestTeardown($old); }
}

function testPaymentHandlerUnknownOrderReturnsFalse() {
    $old = midtransTestSetup();
    try {
    $sig = hash('sha512', 'UNIT-UNKNOWN' . '200' . '50000.00' . 'SB-Mid_server-UNITTEST');
    $ok = handleMidtransNotification([
        'order_id' => 'UNIT-UNKNOWN', 'status_code' => '200', 'gross_amount' => '50000.00',
        'transaction_status' => 'settlement', 'signature_key' => $sig,
    ]);
    assertTrue($ok === false || $ok === null, 'order tak dikenal → false/null (log saja)');
    } finally { midtransTestTeardown($old); }
}

function testCreateOrderIdFormat() {
    $id = generateMidtransOrderId('tour', 42);
    assertMatches('/^TAT-T-42-[A-Za-z0-9]+$/', $id, 'format TAT-{TYPE}-{ID}-{random}');
    $id2 = generateMidtransOrderId('hotel', 7);
    assertMatches('/^TAT-H-7-/', $id2, 'prefix type mapping');
}

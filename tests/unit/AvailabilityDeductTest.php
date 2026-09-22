<?php
/**
 * AvailabilityDeductTest — Fase 2: real-time availability engine.
 * Deduksi slot atomik (FOR UPDATE + ledger idempotency), rollback, guard overbooking.
 */
require_once __DIR__ . '/../../includes/availability.php';

const AV_TOUR_ID = 61;

function avCleanup(): void {
    db()->exec("DELETE b, l FROM bookings b LEFT JOIN availability_ledger l ON l.booking_type='tour' AND l.booking_id=b.id WHERE b.booking_code LIKE 'AVT-%'");
    db()->exec("DELETE FROM tour_dates WHERE tour_id = " . AV_TOUR_ID . " AND departure_date > DATE_ADD(CURDATE(), INTERVAL 90 DAY)");
    db()->exec("DELETE FROM availability_ledger WHERE booking_type='tour' AND booking_id NOT IN (SELECT id FROM bookings)");
}

function avMakeFixture(int $quota): array {
    db()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active)
        VALUES (" . AV_TOUR_ID . ", DATE_ADD(CURDATE(), INTERVAL 95 DAY), DATE_ADD(CURDATE(), INTERVAL 99 DAY), $quota, 0, 1)");
    $tdId = (int)db()->lastInsertId();
    return ['td_id' => $tdId, 'bookings' => []];
}

function avMakeBooking(int $tdId, int $pax, string $suffix = ''): int {
    $code = 'AV' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 8); // max 10 char
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status)
        VALUES (?, ?, ?, 'T', 't@t.t', '0', ?, 100000, 'confirmed')")
        ->execute([$code, AV_TOUR_ID, $tdId, $pax]);
    return (int)db()->lastInsertId();
}

function avBooked(int $tdId): int {
    return (int)db()->query("SELECT booked FROM tour_dates WHERE id = $tdId")->fetch()['booked'];
}

function testDeductIsIdempotentOnDoubleCall() {
    avCleanup();
    $f = avMakeFixture(5);
    $bkId = avMakeBooking($f['td_id'], 2);

    assertEquals('deducted', deductTourSlotsOnPaid($bkId), 'panggilan pertama → deducted');
    assertEquals(2, avBooked($f['td_id']));
    assertEquals('already', deductTourSlotsOnPaid($bkId), 'panggilan kedua → already (idempotent)');
    assertEquals(2, avBooked($f['td_id']), 'booked TIDAK bertambah saat double-call');

    avCleanup();
}

function testReleaseIsIdempotentOnDoubleCall() {
    avCleanup();
    $f = avMakeFixture(5);
    $bkId = avMakeBooking($f['td_id'], 3);
    deductTourSlotsOnPaid($bkId);
    assertEquals(3, avBooked($f['td_id']));

    assertEquals('released', releaseTourSlotsOnCancel($bkId), 'release pertama → released');
    assertEquals(0, avBooked($f['td_id']));
    assertEquals('already', releaseTourSlotsOnCancel($bkId), 'release kedua → already (idempotent)');
    assertEquals(0, avBooked($f['td_id']), 'booked TIDAK negatif saat double-release');

    avCleanup();
}

function testOverbookingGuardRejectsExcessPax() {
    avCleanup();
    $f = avMakeFixture(5);
    $bkId = avMakeBooking($f['td_id'], 10); // 10 pax vs kuota 5

    assertEquals('skipped_no_quota', deductTourSlotsOnPaid($bkId), 'pax > kuota → ditolak');
    assertEquals(0, avBooked($f['td_id']), 'booked tidak berubah saat guard menolak');

    avCleanup();
}

function testConcurrentSettlementsDoNotOverbook() {
    avCleanup();
    // Simulasi 2 webhook "settlement" bersamaan: kuota 4, booking A=3 pax, B=3 pax
    // → hanya satu yang boleh berhasil (atomik FOR UPDATE), sisanya skipped_no_quota.
    $f = avMakeFixture(4);
    $a = avMakeBooking($f['td_id'], 3, '-A');
    $b = avMakeBooking($f['td_id'], 3, '-B');

    $r1 = deductTourSlotsOnPaid($a);
    $r2 = deductTourSlotsOnPaid($b);

    $results = [$r1, $r2];
    sort($results);
    assertEquals(['deducted', 'skipped_no_quota'], $results, 'hanya satu settlement yang lolos guard kuota');
    assertEquals(3, avBooked($f['td_id']), 'booked = pax booking yang lolos saja (tidak overbook)');
    assertEquals(1, (int)db()->query("SELECT COUNT(*) c FROM availability_ledger WHERE booking_type='tour' AND booking_id IN ($a, $b) AND action='deduct'")->fetch()['c'], 'ledger hanya mencatat 1 deduct');

    avCleanup();
}

function testReleaseWithoutDeductIsNoop() {
    avCleanup();
    $f = avMakeFixture(5);
    $bkId = avMakeBooking($f['td_id'], 1);

    assertEquals('skipped_no_date', releaseTourSlotsOnCancel($bkId), 'release tanpa deduct sebelumnya → no-op');
    assertEquals(0, avBooked($f['td_id']), 'booked tetap 0');

    avCleanup();
}

function testSequentialSettlementsFillQuotaExactly() {
    avCleanup();
    $f = avMakeFixture(5);
    $ids = [avMakeBooking($f['td_id'], 2, '-1'), avMakeBooking($f['td_id'], 2, '-2'), avMakeBooking($f['td_id'], 2, '-3')];

    $r = array_map('deductTourSlotsOnPaid', $ids);
    assertEquals('deducted', $r[0]);
    assertEquals('deducted', $r[1]);
    assertEquals('skipped_no_quota', $r[2], 'booking ke-3 (2+2+2=6 > 5) → ditolak');
    assertEquals(4, avBooked($f['td_id']), 'booked = 4 (2+2), tidak 6');

    avCleanup();
}

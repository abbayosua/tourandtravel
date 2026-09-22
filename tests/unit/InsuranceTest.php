<?php
/**
 * InsuranceTest — Fase 4: travel insurance add-on.
 * Premi 3% exact (termasuk pembulatan ratusan), CRUD addon idempotent,
 * booking tanpa addon tidak terpengaruh (total & badge).
 */
require_once __DIR__ . '/../../includes/insurance.php';

function insCleanup(): void {
    db()->exec("DELETE FROM booking_addons WHERE booking_type = 'tour' AND booking_id > 900000000");
    db()->exec("DELETE b FROM bookings b WHERE b.booking_code LIKE 'INS-%'");
    db()->exec("DELETE td FROM tour_dates td WHERE td.tour_id = 61 AND td.departure_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 18 DAY) AND DATE_ADD(CURDATE(), INTERVAL 22 DAY)");
}

function insMakeBooking(float $total = 100000.0): int {
    db()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active)
        VALUES (61, DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 22 DAY), 5, 0, 1)");
    $td = (int)db()->lastInsertId();
    $code = 'INS-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status)
        VALUES (?, 61, ?, 'I', 'i@t.t', '0812', 1, ?, 'confirmed')")
        ->execute([$code, $td, $total]);
    return (int)db()->lastInsertId();
}

function testPremiumThreePercentExact() {
    assertEquals(3000.0, calculateInsurancePremium(100000.0), '3% dari 100rb = 3rb');
    assertEquals(6000.0, calculateInsurancePremium(200000.0), '3% dari 200rb = 6rb');
    assertEquals(15000.0, calculateInsurancePremium(500000.0), '3% dari 500rb = 15rb');
    assertEquals(3000000.0, calculateInsurancePremium(100000000.0), '3% dari 100jt = 3jt');
}

function testPremiumRoundsToHundreds() {
    // 333333 * 0.03 = 9999.99 → bulat ke 10000
    assertEquals(10000.0, calculateInsurancePremium(333333.0), '9999.99 dibulatkan ke 10000');
    // 12345 * 0.03 = 370.35 → 400
    assertEquals(400.0, calculateInsurancePremium(12345.0), '370.35 dibulatkan ke 400');
    // 12340 * 0.03 = 370.2 → 400
    assertEquals(400.0, calculateInsurancePremium(12340.0), '370.2 dibulatkan ke 400');
    // 11650 * 0.03 = 349.5 → 300 (round half to even via PHP round)
    assertTrue(in_array(calculateInsurancePremium(11650.0), [300.0, 400.0]), '349.5 ke 300 atau 400 (PHP round)');
}

function testPremiumEdgeCases() {
    assertEquals(0.0, calculateInsurancePremium(0.0), 'total 0 → premi 0');
    assertEquals(0.0, calculateInsurancePremium(-1000.0), 'total negatif → premi 0');
    assertEquals(0.0, calculateInsurancePremium(100.0), 'total 100 → premi 3 → dibulatkan ke 0');
    assertEquals(300.0, calculateInsurancePremium(11650.0), '349.5 dibulatkan ke 300 (PHP round half-away-from-zero down)');
}

function testAddonCrudIdempotent() {
    insCleanup();
    $bkId = insMakeBooking(100000.0);

    assertTrue(addInsuranceAddon('tour', $bkId, 3000.0, 'plan-basic'), 'insert sukses');
    assertEquals(3000.0, getInsuranceAddon('tour', $bkId), 'terbaca 3000');

    // replace (idempotent via ON DUPLICATE KEY)
    assertTrue(addInsuranceAddon('tour', $bkId, 5000.0, 'plan-pro'), 'replace sukses');
    assertEquals(5000.0, getInsuranceAddon('tour', $bkId), 'amount terupdate ke 5000');
    assertEquals(1, (int)db()->query("SELECT COUNT(*) FROM booking_addons WHERE booking_type='tour' AND booking_id=$bkId AND type='insurance'")->fetchColumn(), 'hanya 1 baris insurance');

    assertTrue(removeInsuranceAddon('tour', $bkId), 'remove sukses');
    assertEquals(0.0, getInsuranceAddon('tour', $bkId), 'setelah remove terbaca 0');

    insCleanup();
}

function testBookingWithoutAddonUnchanged() {
    insCleanup();
    $bkId = insMakeBooking(100000.0);

    // tanpa addon: premi 0, total booking tetap, tidak ada baris addon
    assertEquals(0.0, getInsuranceAddon('tour', $bkId), 'tanpa addon premi 0');
    $row = db()->query("SELECT total_price FROM bookings WHERE id = $bkId")->fetch();
    assertEquals(100000.0, (float)$row['total_price'], 'total booking tidak berubah');
    assertEquals(0, (int)db()->query("SELECT COUNT(*) FROM booking_addons WHERE booking_type='tour' AND booking_id=$bkId")->fetchColumn(), 'tidak ada baris addon');

    insCleanup();
}

function testAddonPersistsAcrossBookingStates() {
    insCleanup();
    $bkId = insMakeBooking(100000.0);
    addInsuranceAddon('tour', $bkId, 3000.0);

    // status berubah (cancelled) — addon tetap (audit trail), tidak auto-hapus
    db()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bkId]);
    assertEquals(3000.0, getInsuranceAddon('tour', $bkId), 'addon tetap ada setelah cancel');

    insCleanup();
}

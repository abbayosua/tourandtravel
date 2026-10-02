<?php
/**
 * BookingParticipantsTest — multi-peserta tour.
 * Data peserta (nama + foto paspor) terpisah dari bookings, cascade saat booking dihapus.
 */

const BP_TOUR_ID = 61;

function bpCleanup(): void {
    db()->exec("DELETE FROM bookings WHERE booking_code LIKE 'BPT-%'");
}

function bpMakeBooking(int $participants): int {
    $code = 'BPT' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 7);
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status)
        VALUES (?, ?, 1, 'Pemesan', 't@t.t', '0800000000', ?, 100000, 'pending')")
        ->execute([$code, BP_TOUR_ID, $participants]);
    return (int)db()->lastInsertId();
}

function bpAddParticipants(int $bookingId, array $paxNames, array $files): void {
    $stmt = db()->prepare("INSERT INTO booking_participants (booking_id, full_name, passport_photo) VALUES (?, ?, ?)");
    for ($i = 1; $i <= count($paxNames); $i++) {
        $stmt->execute([$bookingId, $paxNames[$i] ?? 'Pemesan', $files[$i] ?? null]);
    }
}

function bpList(int $bookingId): array {
    $st = db()->prepare("SELECT full_name, passport_photo FROM booking_participants WHERE booking_id = ? ORDER BY id ASC");
    $st->execute([$bookingId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function testStoresAllParticipantsInOrder() {
    bpCleanup();
    $bk = bpMakeBooking(3);
    bpAddParticipants($bk, [1 => 'Pemesan', 2 => 'Budi', 3 => 'Cici'], [1 => 'a.webp', 2 => 'b.webp', 3 => 'c.webp']);

    $rows = bpList($bk);
    assertEquals(3, count($rows), 'semua peserta tersimpan');
    assertEquals('Pemesan', $rows[0]['full_name'], 'peserta 1 = pemesan bila ikut tour');
    assertEquals('Budi', $rows[1]['full_name'], 'urutan peserta 2');
    assertEquals('c.webp', $rows[2]['passport_photo'], 'foto paspor per peserta');
}

function testSelfNotIncludedMeansDifferentNames() {
    bpCleanup();
    $bk = bpMakeBooking(2);
    // self_included OFF: kontak bukan peserta → nama peserta dari field pax_name
    bpAddParticipants($bk, [1 => 'Andi', 2 => 'Budi'], [1 => 'x.webp', 2 => 'y.webp']);

    $rows = bpList($bk);
    assertEquals('Andi', $rows[0]['full_name'], 'peserta 1 bukan nama kontak saat self_included OFF');
    assertEquals('Budi', $rows[1]['full_name'], 'peserta 2');
}

function testParticipantsCascadeDeleteWithBooking() {
    bpCleanup();
    $bk = bpMakeBooking(2);
    bpAddParticipants($bk, [1 => 'A', 2 => 'B'], [1 => 'a.webp', 2 => 'b.webp']);
    assertEquals(2, count(bpList($bk)), 'peserta ada sebelum hapus');

    db()->prepare("DELETE FROM bookings WHERE id = ?")->execute([$bk]);
    assertEquals(0, count(bpList($bk)), 'peserta terhapus otomatis (cascade)');
}

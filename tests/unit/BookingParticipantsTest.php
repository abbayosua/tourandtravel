<?php
/**
 * BookingParticipantsTest — multi-peserta tour.
 * Helper: collectParticipantNames/collectPassportFiles/save/get/update/add/delete.
 */

require_once __DIR__ . '/../../includes/participants.php';

const BP_TOUR_ID = 61;

function bpCleanup(): void {
    db()->exec("DELETE FROM bookings WHERE booking_code LIKE 'BPT%'");
}

function bpMakeBooking(int $participants): int {
    $code = 'BPT' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 7);
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status)
        VALUES (?, ?, 1, 'Pemesan', 't@t.t', '0800000000', ?, 100000, 'pending')")
        ->execute([$code, BP_TOUR_ID, $participants]);
    return (int)db()->lastInsertId();
}

function bpFakePassport(string $name = 'bp-test'): string {
    $dir = dirname(__DIR__, 2) . '/uploads/passports';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $f = $name . substr(bin2hex(random_bytes(4)), 0, 8) . '.webp';
    file_put_contents($dir . '/' . $f, 'x');
    return $f;
}

function testStoresAllParticipantsInOrder() {
    bpCleanup();
    $bk = bpMakeBooking(3);
    $files = [1 => bpFakePassport(), 2 => bpFakePassport(), 3 => bpFakePassport()];
    saveBookingParticipants($bk, [1 => 'Pemesan', 2 => 'Budi', 3 => 'Cici'], $files);

    $rows = getBookingParticipants($bk);
    assertEquals(3, count($rows), 'semua peserta tersimpan');
    assertEquals('Pemesan', $rows[0]['full_name'], 'peserta 1 = pemesan bila ikut tour');
    assertEquals('Budi', $rows[1]['full_name'], 'urutan peserta 2');
    assertEquals($files[3], $rows[2]['passport_photo'], 'foto paspor per peserta');

    foreach ($files as $f) @unlink(dirname(__DIR__, 2) . '/uploads/passports/' . $f);
    bpCleanup();
}

function testCollectNamesUsesContactWhenSelfIncluded() {
    bpCleanup();
    $_POST = ['pax_name_2' => 'Budi'];
    $errors = [];
    $names = collectParticipantNames(2, true, 'Pemesan Kontak', $errors);
    assertEquals([], $errors, 'tanpa error');
    assertEquals('Pemesan Kontak', $names[1], 'self included → nama peserta 1 = kontak');
    assertEquals('Budi', $names[2], 'peserta 2 dari field pax_name_2');

    // self NOT included → nama peserta 1 wajib diisi manual
    $errors2 = [];
    $names2 = collectParticipantNames(2, false, 'Pemesan Kontak', $errors2);
    assertTrue(count($errors2) === 1, 'peserta 1 kosong → error');
    assertEquals('', $names2[1], 'peserta 1 bukan nama kontak saat self OFF');
    $_POST = [];
    bpCleanup();
}

function testCollectPassportFilesRejectsInvalid() {
    $errors = [];
    $files = collectPassportFiles(1, $errors);
    assertEquals([], $files, 'tanpa file → kosong');
    assertEquals(1, count($errors), 'satu error wajib upload');

    $_POST = ['passport_file_1' => '../../etc/passwd'];
    $errors2 = [];
    collectPassportFiles(1, $errors2);
    assertTrue(count($errors2) === 1, 'path traversal ditolak');
    $_POST = [];
}

function testUpdateAddDeleteParticipant() {
    bpCleanup();
    $bk = bpMakeBooking(2);
    $f1 = bpFakePassport();
    saveBookingParticipants($bk, [1 => 'A', 2 => 'B'], [1 => $f1]);
    $rows = getBookingParticipants($bk);

    updateParticipantNames($bk, [(int)$rows[0]['id'] => 'A Revisi']);
    assertEquals('A Revisi', getBookingParticipants($bk)[0]['full_name'], 'nama peserta terupdate');

    $newId = addParticipant($bk, 'C');
    assertEquals(3, count(getBookingParticipants($bk)), 'peserta bertambah');

    assertTrue(deleteParticipant($bk, (int)$rows[1]['id']), 'hapus peserta berhasil');
    assertEquals(2, count(getBookingParticipants($bk)), 'peserta berkurang');
    assertTrue(!deleteParticipant($bk, 99999999), 'hapus id asing → false');

    @unlink(dirname(__DIR__, 2) . '/uploads/passports/' . $f1);
    bpCleanup();
}

function testParticipantsCascadeDeleteWithBooking() {
    bpCleanup();
    $bk = bpMakeBooking(2);
    saveBookingParticipants($bk, [1 => 'A', 2 => 'B'], []);
    assertEquals(2, count(getBookingParticipants($bk)), 'peserta ada sebelum hapus');

    db()->prepare("DELETE FROM bookings WHERE id = ?")->execute([$bk]);
    assertEquals(0, count(getBookingParticipants($bk)), 'peserta terhapus otomatis (cascade)');
}

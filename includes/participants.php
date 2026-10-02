<?php
/**
 * includes/participants.php — helper multi-peserta booking (tour).
 * Dipakai tour-detail.php (booking publik) & reseller-booking.php.
 */

/** Kumpulkan nama peserta dari POST. Peserta #1 = pemesan bila selfIncluded. */
function collectParticipantNames(int $participants, bool $selfIncluded, string $contactName, array &$errors): array {
    $names = [];
    for ($i = 1; $i <= $participants; $i++) {
        $n = ($i === 1 && $selfIncluded) ? $contactName : trim($_POST["pax_name_$i"] ?? '');
        if ($n === '') $errors[] = t('Nama penumpang') . " #$i " . t('wajib diisi');
        $names[$i] = $n;
    }
    return $names;
}

/** Kumpulkan nama file paspor (hasil upload AJAX pax-upload-ajax.php) dari POST. */
function collectPassportFiles(int $participants, array &$errors): array {
    $files = [];
    $dir = dirname(__DIR__) . '/uploads/passports';
    for ($i = 1; $i <= $participants; $i++) {
        $f = basename(trim($_POST["passport_file_$i"] ?? ''));
        if ($f === '' || !preg_match('/^[A-Za-z0-9]+\.webp$/', $f) || !is_file($dir . '/' . $f)) {
            $errors[] = t('Foto paspor peserta') . " #$i " . t('wajib diupload');
            continue;
        }
        $files[$i] = $f;
    }
    return $files;
}

/** Simpan baris peserta untuk sebuah booking. */
function saveBookingParticipants(int $bookingId, array $names, array $files, string $fallbackName = ''): void {
    $stmt = db()->prepare("INSERT INTO booking_participants (booking_id, full_name, passport_photo) VALUES (?, ?, ?)");
    foreach ($names as $i => $n) {
        $stmt->execute([$bookingId, $n !== '' ? $n : $fallbackName, $files[$i] ?? null]);
    }
}

/** Ambil peserta sebuah booking (urut sesuai input). */
function getBookingParticipants(int $bookingId): array {
    try {
        $st = db()->prepare("SELECT id, full_name, passport_photo FROM booking_participants WHERE booking_id = ? ORDER BY id ASC");
        $st->execute([$bookingId]);
        return $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/** Ambil peserta banyak booking sekaligus → [booking_id => [rows]]. */
function getBookingParticipantsMap(array $bookingIds): array {
    $map = [];
    $ids = array_values(array_filter(array_map('intval', $bookingIds)));
    if (!$ids) return $map;
    try {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = db()->prepare("SELECT id, booking_id, full_name, passport_photo FROM booking_participants WHERE booking_id IN ($in) ORDER BY id ASC");
        $st->execute($ids);
        foreach ($st->fetchAll() as $r) { $map[(int)$r['booking_id']][] = $r; }
    } catch (Throwable $e) { $map = []; }
    return $map;
}

/** Ganti seluruh nama peserta sebuah booking (dipakai admin). */
function updateParticipantNames(int $bookingId, array $namesById): void {
    $st = db()->prepare("UPDATE booking_participants SET full_name = ? WHERE id = ? AND booking_id = ?");
    foreach ($namesById as $id => $name) {
        $name = trim((string)$name);
        if ($name !== '') $st->execute([$name, (int)$id, $bookingId]);
    }
}

/** Tambah peserta baru (nama wajib, paspor opsional). */
function addParticipant(int $bookingId, string $name, ?string $passportFile = null): int {
    db()->prepare("INSERT INTO booking_participants (booking_id, full_name, passport_photo) VALUES (?, ?, ?)")
        ->execute([$bookingId, $name, $passportFile]);
    return (int)db()->lastInsertId();
}

/** Hapus peserta. Return true bila ada baris yang terhapus. */
function deleteParticipant(int $bookingId, int $participantId): bool {
    $st = db()->prepare("DELETE FROM booking_participants WHERE id = ? AND booking_id = ?");
    $st->execute([$participantId, $bookingId]);
    return $st->rowCount() > 0;
}

/** Validasi nama file paspor (untuk admin/upload manual). */
function isStoredPassportFile(string $filename): bool {
    $f = basename($filename);
    return $f !== '' && (bool)preg_match('/^[A-Za-z0-9]+\.webp$/', $f)
        && is_file(dirname(__DIR__) . '/uploads/passports/' . $f);
}

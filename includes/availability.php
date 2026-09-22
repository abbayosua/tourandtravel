<?php
/**
 * includes/availability.php — Fase 2: real-time availability engine.
 * Deduksi slot ATOMIK via transaksi DB + SELECT ... FOR UPDATE (mencegah overbooking
 * saat dua webhook settlement tiba bersamaan).
 *
 * Sumber kebenaran kuota:
 *  - tour: tour_dates.available_slots (kuota) vs tour_dates.booked (terpakai)
 *  - kolom price_calendar.slots_booked ikut dicatat bila baris kalender utk tanggal tsb ada
 *
 * Semua fungsi idempotent per (booking_type, booking_id) via tabel availability_ledger.
 */

/**
 * Deduksi slot saat payment settlement (paid). Return:
 *  'deducted' | 'skipped_no_date' | 'skipped_no_quota' | 'already'
 */
function deductTourSlotsOnPaid(int $bookingId): string {
    $b = db()->prepare("SELECT tour_date_id, participants, status FROM bookings WHERE id = ?");
    $b->execute([$bookingId]);
    $bk = $b->fetch();
    if (!$bk || empty($bk['tour_date_id'])) return 'skipped_no_date';
    if ($bk['status'] === 'cancelled') return 'skipped_no_date';

    // Idempotency: ledger sudah mencatat sukses → skip
    if (availabilityLedgerExists('tour', $bookingId)) return 'already';

    $tourDateId = (int)$bk['tour_date_id'];
    $pax = (int)$bk['participants'];
    if ($pax < 1) return 'skipped_no_date';

    db()->beginTransaction();
    try {
        // Kunci baris tour_date
        $stmt = db()->prepare("SELECT available_slots, booked FROM tour_dates WHERE id = ? FOR UPDATE");
        $stmt->execute([$tourDateId]);
        $td = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$td) { db()->rollBack(); return 'skipped_no_date'; }

        $booked = (int)$td['booked'] + $pax;
        if ($booked > (int)$td['available_slots']) {
            db()->rollBack();
            return 'skipped_no_quota';
        }
        db()->prepare("UPDATE tour_dates SET booked = ? WHERE id = ?")->execute([$booked, $tourDateId]);

        // Sinkron price_calendar.slots_booked bila baris kalender ada (best effort)
        $cal = db()->prepare("SELECT pc.id FROM price_calendar pc
            JOIN tour_dates td ON td.id = ? AND td.tour_id = pc.item_id
            WHERE pc.item_type = 'tour' AND pc.date = td.departure_date FOR UPDATE");
        $cal->execute([$tourDateId]);
        if ($calRow = $cal->fetch(PDO::FETCH_ASSOC)) {
            db()->prepare("UPDATE price_calendar SET slots_booked = slots_booked + ? WHERE id = ?")
                ->execute([$pax, $calRow['id']]);
        }

        availabilityLedgerInsert('tour', $bookingId, 'deduct', $pax);
        db()->commit();
        return 'deducted';
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('deductTourSlotsOnPaid: ' . $e->getMessage());
        return 'skipped_no_quota';
    }
}

/**
 * Kembalikan slot saat booking dibatalkan / payment gagal permanen.
 * Idempotent: hanya rollback bila ledger deduct ada dan release belum ada.
 */
function releaseTourSlotsOnCancel(int $bookingId): string {
    $b = db()->prepare("SELECT tour_date_id, participants, status FROM bookings WHERE id = ?");
    $b->execute([$bookingId]);
    $bk = $b->fetch();
    if (!$bk || empty($bk['tour_date_id'])) return 'skipped_no_date';
    if (!availabilityLedgerExists('tour', $bookingId, 'deduct')) return 'skipped_no_date';
    if (availabilityLedgerExists('tour', $bookingId, 'release')) return 'already';

    $tourDateId = (int)$bk['tour_date_id'];
    $pax = max(1, (int)$bk['participants']);

    db()->beginTransaction();
    try {
        db()->prepare("UPDATE tour_dates SET booked = GREATEST(0, booked - ?) WHERE id = ?")->execute([$pax, $tourDateId]);

        $cal = db()->prepare("SELECT pc.id FROM price_calendar pc
            JOIN tour_dates td ON td.id = ? AND td.tour_id = pc.item_id
            WHERE pc.item_type = 'tour' AND pc.date = td.departure_date FOR UPDATE");
        $cal->execute([$tourDateId]);
        if ($calRow = $cal->fetch(PDO::FETCH_ASSOC)) {
            db()->prepare("UPDATE price_calendar SET slots_booked = GREATEST(0, slots_booked - ?) WHERE id = ?")
                ->execute([$pax, $calRow['id']]);
        }

        availabilityLedgerInsert('tour', $bookingId, 'release', $pax);
        db()->commit();
        return 'released';
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('releaseTourSlotsOnCancel: ' . $e->getMessage());
        return 'skipped_no_date';
    }
}

function availabilityLedgerExists(string $type, int $bookingId, string $action = 'deduct'): bool {
    $stmt = db()->prepare("SELECT COUNT(*) FROM availability_ledger WHERE booking_type = ? AND booking_id = ? AND action = ?");
    $stmt->execute([$type, $bookingId, $action]);
    return (bool)$stmt->fetchColumn();
}

function availabilityLedgerInsert(string $type, int $bookingId, string $action, int $slots): void {
    db()->prepare("INSERT INTO availability_ledger (booking_type, booking_id, action, slots) VALUES (?, ?, ?, ?)")
        ->execute([$type, $bookingId, $action, $slots]);
}

<?php
/**
 * includes/refund.php — Fase 3: refund policy engine (self-service).
 *
 * Policy default ('auto'), berdasarkan hari menuju keberangkatan (H-x):
 *  - H >= 8  : FULL   (100%)
 *  - H 4–7   : HALF   (50%)
 *  - H <= 3  : NONE   (0%)
 *
 * Override per produk via tours.refund_policy:
 *  - 'full_refund'    → selalu 100%
 *  - 'non_refundable' → selalu 0%
 *  - 'auto'           → pakai aturan H-x di atas
 *
 * Semua perubahan status refund lewat requestRefund() / decideRefund().
 */

/**
 * Hitung kebijakan refund untuk 1 booking.
 * Return: ['pct' => 0|50|100, 'amount' => float, 'tier' => 'full'|'half'|'none', 'days_left' => int, 'policy' => string]
 */
function calculateRefund(int $bookingId): ?array {
    $b = db()->prepare("SELECT b.total_price, b.tour_id, td.departure_date
        FROM bookings b
        LEFT JOIN tour_dates td ON td.id = b.tour_date_id
        WHERE b.id = ?");
    $b->execute([$bookingId]);
    $bk = $b->fetch();
    if (!$bk) return null;

    $policy = 'auto';
    if (!empty($bk['tour_id'])) {
        $t = db()->prepare("SELECT refund_policy FROM tours WHERE id = ?");
        $t->execute([(int)$bk['tour_id']]);
        $policy = (string)($t->fetchColumn() ?: 'auto');
    }

    $total = (float)$bk['total_price'];
    $daysLeft = $bk['departure_date'] ? (int)floor((strtotime($bk['departure_date']) - strtotime(date('Y-m-d'))) / 86400) : -1;

    if ($policy === 'full_refund') {
        $pct = 100; $tier = 'full';
    } elseif ($policy === 'non_refundable') {
        $pct = 0;   $tier = 'none';
    } else {
        if ($daysLeft < 0) { $pct = 0; $tier = 'none'; }
        elseif ($daysLeft >= 8) { $pct = 100; $tier = 'full'; }
        elseif ($daysLeft >= 4) { $pct = 50; $tier = 'half'; }
        else { $pct = 0; $tier = 'none'; }
    }

    return [
        'pct' => $pct,
        'amount' => round($total * $pct / 100, 2),
        'tier' => $tier,
        'days_left' => $daysLeft,
        'policy' => $policy,
    ];
}

/**
 * User ajukan refund. Return [ok(bool), msg].
 * Validasi: booking milik user (atau guest dgn bukti), status confirmed, belum pernah refund final.
 */
function requestRefund(int $bookingId, int $userId, string $reason): array {
    $b = db()->prepare("SELECT user_id, status, refund_status FROM bookings WHERE id = ?");
    $b->execute([$bookingId]);
    $bk = $b->fetch();
    if (!$bk) return [false, 'Booking tidak ditemukan'];
    if ((int)($bk['user_id'] ?? 0) !== $userId) return [false, 'Bukan booking Anda'];
    if ($bk['status'] !== 'confirmed') return [false, 'Hanya booking confirmed yang bisa diajukan refund'];
    if (in_array($bk['refund_status'], ['requested', 'approved'], true)) return [false, 'Refund sudah diajukan/sebelumnya'];
    if ($bk['refund_status'] === 'rejected') return [false, 'Pengajuan refund sebelumnya sudah ditolak'];

    $calc = calculateRefund($bookingId);
    if (!$calc || $calc['pct'] === 0) return [false, 'Booking ini sudah tidak bisa direfund (melewati batas waktu / non-refundable)'];

    db()->prepare("UPDATE bookings SET refund_status = 'requested', refund_reason = ? WHERE id = ?")
        ->execute([$reason, $bookingId]);
    return [true, 'Pengajuan refund diterima. Estimasi refund: ' . $calc['pct'] . '%'];
}

/**
 * Admin putuskan refund. approve=true → kredit wallet + slot release + status approved.
 * Return [ok(bool), msg].
 */
function decideRefund(int $bookingId, bool $approve, int $adminId): array {
    $b = db()->prepare("SELECT user_id, refund_status, status, participants, tour_date_id FROM bookings WHERE id = ?");
    $b->execute([$bookingId]);
    $bk = $b->fetch();
    if (!$bk) return [false, 'Booking tidak ditemukan'];
    if ($bk['refund_status'] !== 'requested') return [false, 'Tidak ada pengajuan refund pending'];

    if (!$approve) {
        db()->prepare("UPDATE bookings SET refund_status = 'rejected' WHERE id = ?")->execute([$bookingId]);
        return [true, 'Refund ditolak'];
    }

    $calc = calculateRefund($bookingId);
    if (!$calc || $calc['amount'] <= 0) {
        db()->prepare("UPDATE bookings SET refund_status = 'rejected' WHERE id = ?")->execute([$bookingId]);
        return [false, 'Amount refund 0 — pengajuan otomatis ditolak'];
    }

    db()->beginTransaction();
    try {
        db()->prepare("UPDATE bookings SET refund_status = 'approved', refund_amount = ?, status = 'cancelled' WHERE id = ?")
            ->execute([$calc['amount'], $bookingId]);

        require_once __DIR__ . '/wallet.php';
        addWalletTransaction(
            (int)$bk['user_id'],
            (float)$calc['amount'],
            'refund',
            'Refund booking #' . $bookingId . ' (' . $calc['pct'] . '%)',
            'booking',
            $bookingId
        );
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('decideRefund: ' . $e->getMessage());
        return [false, 'Gagal memproses refund'];
    }

    // Kembalikan slot (idempotent via availability_ledger) — di luar transaksi
    // karena releaseTourSlotsOnCancel membuka transaksinya sendiri.
    require_once __DIR__ . '/availability.php';
    releaseTourSlotsOnCancel($bookingId);

    return [true, 'Refund disetujui: ' . $calc['amount'] . ' dikredit ke wallet'];
}

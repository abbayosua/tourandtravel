<?php
/**
 * booking-resume.php — normalisasi "lanjutkan pembayaran" lintas vertikal booking.
 *
 * Tiap tabel punya nama kolom VA berbeda (train: va_number, pelni: supplier_va, dst).
 * Registry di bawah adalah satu-satunya sumber kebenaran pemetaan kolom + URL resume,
 * sehingga my-bookings.php cukup memanggil bookingResumeInfo() untuk semua tipe.
 */

/** Pemetaan tipe booking → kolom VA/total/deadline/pay-status + URL lanjutkan. */
function bookingResumeRegistry(): array {
    return [
        'train' => [
            'va_bank' => 'va_bank',
            'va_number' => 'va_number',
            'total' => 'payment_total',
            'deadline' => 'payment_deadline',
            'pay_status' => 'payment_status',
            'url' => fn(array $r) => 'train-booking.php?done=' . urlencode((string)($r['booking_code'] ?? '')),
        ],
        'pelni' => [
            'va_bank' => 'supplier_bank',
            'va_number' => 'supplier_va',
            'total' => 'supplier_total',
            'deadline' => 'supplier_deadline',
            'pay_status' => 'payment_status',
            'url' => fn(array $r) => 'pelni-booking.php?booking=' . urlencode((string)($r['booking_code'] ?? '')),
        ],
        'hotel' => [
            'va_bank' => 'va_bank',
            'va_number' => 'va_number',
            'total' => 'payment_total',
            'deadline' => 'va_expires_at',
            'pay_status' => 'payment_status',
            'url' => fn(array $r) => 'nusatrip-book.php?step=result&booking_id=' . (int)($r['id'] ?? 0),
        ],
        'flight' => [
            'va_bank' => 'va_bank',
            'va_number' => 'va_number',
            'total' => 'payment_total',
            'deadline' => 'payment_deadline',
            'pay_status' => 'payment_status',
            'url' => fn(array $r) => 'booking-success.php?code=' . urlencode((string)($r['booking_code'] ?? '')) . '&btype=flight',
        ],
    ];
}

/**
 * Ringkasan pembayaran satu booking.
 * @return array{has_pending:bool, va_bank:string, va_number:string, total:float, deadline:string, pay_status:string, resume_url:string}
 */
function bookingResumeInfo(string $type, array $row): array {
    $reg = bookingResumeRegistry()[$type] ?? null;
    if (!$reg) {
        return ['has_pending' => false, 'va_bank' => '', 'va_number' => '', 'total' => 0.0, 'deadline' => '', 'pay_status' => '', 'resume_url' => ''];
    }
    $vaNumber = trim((string)($row[$reg['va_number']] ?? ''));
    $vaBank = trim((string)($row[$reg['va_bank']] ?? ''));
    $total = (float)($row[$reg['total']] ?? ($row['total_price'] ?? 0));
    $deadline = (string)($row[$reg['deadline']] ?? '');
    $payStatus = (string)($row[$reg['pay_status']] ?? '');
    $cancelled = in_array((string)($row['status'] ?? ''), ['cancelled', 'canceled'], true);
    $hasPending = !$cancelled && $vaNumber !== '' && !in_array($payStatus, ['paid', 'settled', 'confirmed'], true);

    return [
        'has_pending' => $hasPending,
        'va_bank' => $vaBank,
        'va_number' => $vaNumber,
        'total' => $total,
        'deadline' => $deadline,
        'pay_status' => $payStatus,
        'resume_url' => ($reg['url'])($row),
    ];
}

<?php
/**
 * TripayPaymentTest — mode manual/instant + signature + callback idempoten.
 *
 * Dijalankan oleh tests/unit/run.php (sebagai testTripayPayment()) maupun
 * standalone: php tests/unit/TripayPaymentTest.php
 *
 * Catatan: sebelumnya file ini memakai kode prosedural + exit() di top-level,
 * sehingga saat di-require run.php ia menghentikan runner dan test setelahnya
 * (mis. WishlistTest) tidak pernah jalan. Kini dibungkus test* function.
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/payments.php';
require_once __DIR__ . '/../../includes/tripay.php';

// Fallback assertion saat standalone (run.php menyediakan assertSame sendiri).
if (!function_exists('assertSame')) {
    function assertSame($expected, $actual, string $msg = ''): void {
        if ($expected !== $actual) {
            throw new RuntimeException(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
        }
    }
}

function checkTripay(string $label, bool $cond): void {
    if (!$cond) $GLOBALS['tripayFail'][] = $label;
}

function testTripayPayment(): void {
    $GLOBALS['tripayFail'] = [];

    // 1. Default mode = manual
    setSetting('payment_mode', 'manual');
    checkTripay('default manual', tripayMode() === 'manual');
    checkTripay('instant mati saat manual', tripayInstantEnabled() === false);

    // 2. Instant + tripay tanpa kredensial → tetap manual (fallback)
    setSetting('payment_mode', 'instant');
    setSetting('payment_gateway', 'tripay');
    setSetting('tripay_api_key', '');
    setSetting('tripay_private_key', '');
    setSetting('tripay_merchant_code', '');
    checkTripay('instant butuh kredensial', tripayInstantEnabled() === false);

    // 3. Instant + tripay berkredensial → aktif
    setSetting('tripay_api_key', 'TESTKEY');
    setSetting('tripay_private_key', 'TESTPRIV');
    setSetting('tripay_merchant_code', 'T0001');
    checkTripay('instant aktif dgn kredensial', tripayInstantEnabled() === true);
    checkTripay('gateway tripay', tripayGateway() === 'tripay');

    // 4. Signature sesuai rumus dokumen (merchantCode.merchantRef.amount)
    $sig = tripaySignature('INV55567', 1500000, 'ytf6ooi2gmlNPfpchd94jDOk8hRWOu', 'T0001');
    checkTripay('signature HMAC-SHA256 rumus', $sig === '9f167eba844d1fcb369404e2bda53702e2f78f7aa12e91da6715414e65b8c86a');

    // 5. Callback signature = HMAC(rawJson)
    $raw = '{"reference":"T1","status":"PAID"}';
    checkTripay('callback signature', tripayCallbackSignature($raw, 'k') === hash_hmac('sha256', $raw, 'k'));

    // 6. Map status
    checkTripay('map PAID', tripayMapStatus('PAID') === 'paid');
    checkTripay('map EXPIRED', tripayMapStatus('EXPIRED') === 'expired');
    checkTripay('map REFUND→failed', tripayMapStatus('REFUND') === 'failed');
    checkTripay('map UNPAID→pending', tripayMapStatus('UNPAID') === 'pending');

    // 7. Ref format
    $ref = generateTripayRef(131);
    checkTripay('ref prefix', str_starts_with($ref, 'TAT-T-131-'));

    // 8. Callback: reference tak dikenal → false
    checkTripay('callback unknown → false', handleTripayCallback(['reference' => 'NOPE-'.time(), 'merchant_ref' => 'NOPE', 'status' => 'PAID']) === false);

    // 9. Callback idempoten: buat payment tripay pending lalu PAID 2x
    db()->exec("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) VALUES ('TRIPAY-T1', 131, 1, 'Tripay T', 't@t.local', '08', 2, 1000000, 'pending')");
    $bid = (int)db()->lastInsertId();
    $mref = generateTripayRef($bid);
    db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, gateway, order_id, reference, gross_amount, status) VALUES ('tour', ?, 'TRIPAY-T1', 'tripay', ?, 'TRIPAY-REF-T1', 1000000, 'pending')")->execute([$bid, $mref]);
    $cb = ['reference' => 'TRIPAY-REF-T1', 'merchant_ref' => $mref, 'status' => 'PAID', 'payment_method_code' => 'BRIVA'];
    checkTripay('callback PAID ok', handleTripayCallback($cb) === true);
    $st = db()->query("SELECT status FROM payments WHERE reference='TRIPAY-REF-T1'")->fetchColumn();
    checkTripay('payment jadi paid', $st === 'paid');
    checkTripay('callback ulang no-op', handleTripayCallback($cb) === true);
    // cleanup
    db()->exec("DELETE FROM payments WHERE reference='TRIPAY-REF-T1'");
    db()->exec("DELETE FROM bookings WHERE booking_code='TRIPAY-T1'");

    // kembalikan default manual
    setSetting('payment_mode', 'manual');
    setSetting('payment_gateway', 'midtrans');
    setSetting('tripay_api_key', '');
    setSetting('tripay_private_key', '');
    setSetting('tripay_merchant_code', '');

    // Label channel tripay: suffix "Virtual Account" ikut bahasa aktif.
    $_SESSION['lang'] = 'zh'; $_COOKIE['lang'] = 'zh';
    checkTripay('label VA zh', tripayLocalizedLabel('BRI Virtual Account') === 'BRI 虚拟账户');
    $_SESSION['lang'] = 'en'; $_COOKIE['lang'] = 'en';
    checkTripay('label VA en', tripayLocalizedLabel('BRI Virtual Account') === 'BRI Virtual Account');
    $_SESSION['lang'] = 'id'; $_COOKIE['lang'] = 'id';
    checkTripay('label VA id', tripayLocalizedLabel('BRI Virtual Account') === 'BRI Virtual Account');
    checkTripay('label QRIS tetap', tripayLocalizedLabel('QRIS') === 'QRIS');

    assertSame([], $GLOBALS['tripayFail'], 'Tripay checks gagal');
}

// Standalone runner (bukan di-require run.php).
if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    $GLOBALS['tripayFail'] = [];
    try {
        testTripayPayment();
    } catch (Throwable $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        exit(1);
    }
    echo "\n" . count($GLOBALS['tripayFail']) . " failed\n";
    exit($GLOBALS['tripayFail'] ? 1 : 0);
}

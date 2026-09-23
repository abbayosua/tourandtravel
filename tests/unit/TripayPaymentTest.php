<?php
/**
 * TripayPaymentTest — mode manual/instant + signature + callback idempoten.
 * Run: php tests/unit/TripayPaymentTest.php (DB lokal tourandtravel).
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/payments.php';
require_once __DIR__ . '/../../includes/tripay.php';

$pass = 0; $fail = 0;
function check($label, $cond) {
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS: $label\n"; }
    else { $fail++; echo "FAIL: $label\n"; }
}

// 1. Default mode = manual
setSetting('payment_mode', 'manual');
check('default manual', tripayMode() === 'manual');
check('instant mati saat manual', tripayInstantEnabled() === false);

// 2. Instant + tripay tanpa kredensial → tetap manual (fallback)
setSetting('payment_mode', 'instant');
setSetting('payment_gateway', 'tripay');
setSetting('tripay_api_key', '');
setSetting('tripay_private_key', '');
setSetting('tripay_merchant_code', '');
check('instant butuh kredensial', tripayInstantEnabled() === false);

// 3. Instant + tripay berkredensial → aktif
setSetting('tripay_api_key', 'TESTKEY');
setSetting('tripay_private_key', 'TESTPRIV');
setSetting('tripay_merchant_code', 'T0001');
check('instant aktif dgn kredensial', tripayInstantEnabled() === true);
check('gateway tripay', tripayGateway() === 'tripay');

// 4. Signature sesuai rumus dokumen (merchantCode.merchantRef.amount)
$sig = tripaySignature('INV55567', 1500000, 'ytf6ooi2gmlNPfpchd94jDOk8hRWOu', 'T0001');
check('signature HMAC-SHA256 rumus', $sig === '9f167eba844d1fcb369404e2bda53702e2f78f7aa12e91da6715414e65b8c86a');

// 5. Callback signature = HMAC(rawJson)
$raw = '{"reference":"T1","status":"PAID"}';
check('callback signature', tripayCallbackSignature($raw, 'k') === hash_hmac('sha256', $raw, 'k'));

// 6. Map status
check('map PAID', tripayMapStatus('PAID') === 'paid');
check('map EXPIRED', tripayMapStatus('EXPIRED') === 'expired');
check('map REFUND→failed', tripayMapStatus('REFUND') === 'failed');
check('map UNPAID→pending', tripayMapStatus('UNPAID') === 'pending');

// 7. Ref format
$ref = generateTripayRef(131);
check('ref prefix', str_starts_with($ref, 'TAT-T-131-'));

// 8. Callback: reference tak dikenal → false
check('callback unknown → false', handleTripayCallback(['reference' => 'NOPE-'.time(), 'merchant_ref' => 'NOPE', 'status' => 'PAID']) === false);

// 9. Callback idempoten: buat payment tripay pending lalu PAID 2x
db()->exec("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) VALUES ('TRIPAY-T1', 131, 1, 'Tripay T', 't@t.local', '08', 2, 1000000, 'pending')");
$bid = (int)db()->lastInsertId();
$mref = generateTripayRef($bid);
db()->prepare("INSERT INTO payments (booking_type, booking_id, booking_code, gateway, order_id, reference, gross_amount, status) VALUES ('tour', ?, 'TRIPAY-T1', 'tripay', ?, 'TRIPAY-REF-T1', 1000000, 'pending')")->execute([$bid, $mref]);
$cb = ['reference' => 'TRIPAY-REF-T1', 'merchant_ref' => $mref, 'status' => 'PAID', 'payment_method_code' => 'BRIVA'];
check('callback PAID ok', handleTripayCallback($cb) === true);
$st = db()->query("SELECT status FROM payments WHERE reference='TRIPAY-REF-T1'")->fetchColumn();
check('payment jadi paid', $st === 'paid');
check('callback ulang no-op', handleTripayCallback($cb) === true);
// cleanup
db()->exec("DELETE FROM payments WHERE reference='TRIPAY-REF-T1'");
db()->exec("DELETE FROM bookings WHERE booking_code='TRIPAY-T1'");

// kembalikan default manual
setSetting('payment_mode', 'manual');
setSetting('payment_gateway', 'midtrans');
setSetting('tripay_api_key', '');
setSetting('tripay_private_key', '');
setSetting('tripay_merchant_code', '');

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);

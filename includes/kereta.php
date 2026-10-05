<?php
/**
 * kereta.php — Kereta Api API integration
 * https://klikmbc.biz/v2/ - Train schedule search
 */

define('KERETA_BASE', 'https://klikmbc.biz/v2');

require_once __DIR__ . '/flight-cache.php';

// Daftar lengkap stasiun (sumber: klikmbc.biz/v2/backup/getcode-train.js) — 281 stasiun.
$KERETA_STATION_MAP = require __DIR__ . '/data/kereta-stations.php';       // CODE => "CODE - Nama, Kota"
$KERETA_STATION_CODE_MAP = array_flip($KERETA_STATION_MAP);               // "CODE - Nama, Kota" => CODE

function keretaStationLabel($code) {
    global $KERETA_STATION_MAP;
    return $KERETA_STATION_MAP[$code] ?? '';
}

function keretaSearchTrips($fromCode, $toCode, $date, $adults = 1, $children = 0, $infants = 0) {
    $cacheKey = 'kereta:' . $fromCode . ':' . $toCode . ':' . $date . ':' . $adults . ':' . $children;
    $cached = flightCacheGet($cacheKey);
    if ($cached !== null && isset($cached['trips'])) {
        return $cached['trips'];
    }

    $fromLabel = keretaStationLabel($fromCode);
    $toLabel = keretaStationLabel($toCode);

    $dateFormatted = date('j M Y', strtotime($date));

    $params = [
        'train_from' => $fromLabel,
        'train_to' => $toLabel,
        'train_datedeparture' => $dateFormatted,
        'dewasa' => $adults,
        'anak' => $children,
        'bayi' => $infants,
        'dewasa_number' => $adults,
        'bayi_number' => $infants,
        'goback' => 0,
        'train_dateback' => '',
        'show_cashback' => 1,
    ];

    $url = KERETA_BASE . '/form_train_result_cari?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (compatible; TourAndTravel)',
            'Accept: text/html,application/xhtml+xml',
        ],
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return [];

    $trips = keretaParseTrips($html, $date);

    if (!empty($trips)) {
        flightCacheSet($cacheKey, 'kereta', ['trips' => $trips], count($trips));
    }

    return $trips;
}

function keretaParseTrips($html, $date) {
    $trips = [];
    $seen = [];

    preg_match_all('/<form[^>]*method=["\']POST["\'][^>]*action=["\'][^"\']*form_train_result_detailorder[^"\']*["\'][^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);

    if (empty($forms)) {
        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);
    }

    foreach ($forms as $form) {
        $block = $form[1];

        $trainName = '';
        if (preg_match('/train_name["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainName = trim($m[1]);
        }

        $trainCode = '';
        if (preg_match('/train_code["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainCode = trim($m[1]);
        }

        $trainFrom = '';
        if (preg_match('/train_from["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainFrom = trim($m[1]);
        }

        $trainTo = '';
        if (preg_match('/train_to["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainTo = trim($m[1]);
        }

        $trainDate = '';
        if (preg_match('/train_date["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainDate = trim($m[1]);
        }

        $trainDatetime = '';
        if (preg_match('/train_datetime["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainDatetime = trim($m[1]);
        }

        $trainClass = '';
        if (preg_match('/train_class["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainClass = trim($m[1]);
        }

        $trainPrice = 0;
        if (preg_match('/train_price["\']?\s*value=["\']([\d.]+)["\']/i', $block, $m)) {
            $trainPrice = (float)str_replace('.', '', $m[1]);
        }

        if (!$trainName || !$trainCode) continue;

        $key = $trainName . '|' . $trainClass . '|' . $trainDatetime;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $departureTime = '';
        $arrivalTime = '';
        if (preg_match('/(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})\s*-\s*(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})/', $trainDatetime, $tm)) {
            $departureTime = $tm[1];
            $arrivalTime = $tm[2];
        } elseif (preg_match('/(\d{2}:\d{2})/', $trainDatetime, $tm)) {
            $departureTime = $tm[1];
        }

        $trips[] = [
            'train_name' => $trainName,
            'train_code' => $trainCode,
            'train_from' => $trainFrom,
            'train_to' => $trainTo,
            'train_date' => $trainDate ?: $date,
            'train_datetime' => $trainDatetime,
            'train_class' => $trainClass,
            'train_price' => $trainPrice,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
        ];
    }

    usort($trips, function($a, $b) {
        return strcmp($a['departure_time'], $b['departure_time']);
    });

    return $trips;
}

/**
 * ------------------------------------------------
 * Booking KAI langsung ke penyedia (klikmbc.biz).
 * Flow: search -> detail -> booking (invoice) -> pilih VA -> confirm -> checkout.
 * Semua langkah dijalankan server-side dalam satu request.
 * ------------------------------------------------
 */

if (!defined('KERETA_UA')) {
    define('KERETA_UA', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36');
}

function keretaApiSession() {
    return ['cookie' => tempnam(sys_get_temp_dir(), 'kereta_')];
}

function keretaApiHttp($s, $method, $path, $data = [], $referer = null, $ajax = false) {
    $url = str_starts_with($path, 'http') ? $path : KERETA_BASE . '/' . ltrim($path, '/');
    $ch = curl_init();
    $headers = ['User-Agent: ' . KERETA_UA, 'Accept: text/html,application/xhtml+xml'];
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    if ($ajax) $headers[] = 'X-Requested-With: XMLHttpRequest';
    if (strtoupper($method) === 'GET' && $data) $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEJAR => $s['cookie'], CURLOPT_COOKIEFILE => $s['cookie'],
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body === false ? '' : (string) $body];
}

function keretaApiSearch($s, $from, $to, $date, $dewasa = 1) {
    $ts = strtotime($date);
    if (!$ts) return ['error' => 'invalid_date'];
    $fdate = date('D, d M Y', $ts);
    $params = [
        'train_from' => $from, 'train_to' => $to, 'train_datedeparture' => $fdate,
        'dewasa' => $dewasa, 'anak' => 0, 'bayi' => 0,
        'dewasa_number' => $dewasa, 'bayi_number' => 0,
        'goback' => 0, 'train_dateback' => '', 'show_cashback' => 1,
    ];
    keretaApiHttp($s, 'GET', '/', [], null);
    [$code, $html] = keretaApiHttp($s, 'GET', 'form_train_result_cari?' . http_build_query($params), [], KERETA_BASE . '/');
    if ($code !== 200) return ['error' => 'search HTTP ' . $code];
    preg_match_all('/<form[^>]*action="form_train_result_detailorder"[^>]*>(.*?)<\/form>/s', $html, $fm);
    $rows = [];
    foreach ($fm[1] as $body) {
        if (!str_contains($body, 'value="PESAN"')) continue;
        preg_match_all('/<input type=hidden name=([^ >]+) value="([^"]*)">/', $body, $im, PREG_SET_ORDER);
        $r = [];
        foreach ($im as $m) $r[$m[1]] = $m[2];
        if (!empty($r['train_code'])) $rows[] = $r;
    }
    return ['schedules' => $rows];
}

function keretaApiDetail($s, $t) {
    $referer = KERETA_BASE . '/form_train_result_cari';
    $data = [
        'session' => $t['session'] ?? '', 'goback' => '0', 'train_name' => $t['train_name'] ?? '',
        'train_class' => $t['train_class'] ?? '', 'train_subclass' => $t['train_subclass'] ?? '',
        'train_code' => $t['train_code'] ?? '', 'train_from' => $t['train_from'] ?? '', 'train_to' => $t['train_to'] ?? '',
        'train_route' => $t['train_route'] ?? '', 'train_real_date' => $t['train_real_date'] ?? '',
        'train_date' => $t['train_date'] ?? '', 'train_real_date_back' => '', 'train_date_back' => $t['train_date_back'] ?? '',
        'train_datetime' => $t['train_datetime'] ?? '', 'train_duration' => $t['train_duration'] ?? '',
        'train_fare' => $t['train_fare'] ?? '', 'adult' => '1', 'child' => '0', 'infant' => '0',
        'show_cashback' => '1', 'uat' => '',
    ];
    [$code, $html] = keretaApiHttp($s, 'POST', 'form_train_result_detailorder', $data, $referer);
    $get = fn($id) => preg_match('/id="?' . $id . '"?(?:[^>]*?)value="([^"]*)"/', $html, $m) ? $m[1] : '';
    $csrf = $get('csrf_token');
    if (!$csrf) return ['error' => 'detail CSRF not found HTTP ' . $code];
    return [
        'getsession' => $get('getsession'), 'train_price' => $get('train_price'),
        'train_seat' => $get('train_seat'), 'train_duration' => $get('train_duration'),
        'csrf' => $csrf,
    ];
}

function keretaApiBook($s, $t, $d, $pax, $phone, $email, $notes = '') {
    $nama = '';
    foreach ($pax as $i => $p) {
        $n = $i + 1;
        $nama .= "$n. {$p['title']}. {$p['name']} - Identitas: {$p['id']}<br />";
    }
    $count = (string)max(1, count($pax));
    $data = [
        'train_name' => $t['train_name'], 'train_code' => $t['train_code'],
        'train_class' => $t['train_class'], 'train_subclass' => $t['train_subclass'],
        'train_date' => $t['train_date'], 'train_real_date' => $t['train_real_date'],
        'train_from' => $t['train_from'], 'train_to' => $t['train_to'],
        'train_adult' => $count, 'train_child' => '0', 'train_infant' => '0',
        'nama' => $nama, 'session' => $d['getsession'], 'train_price' => $d['train_price'],
        'train_seat' => $d['train_seat'] ?? '', 'train_datetime' => $t['train_datetime'],
        'phone' => $phone, 'email' => $email,
        'session_back' => '', 'train_name_back' => '', 'train_code_back' => '',
        'train_class_back' => '', 'train_subclass_back' => '', 'train_date_back' => '',
        'train_real_date_back' => '', 'train_price_back' => '', 'train_seat_back' => '',
        'train_datetime_back' => '', 'train_duration' => $d['train_duration'] ?? ($t['train_duration'] ?? ''),
        'train_duration_back' => '', 'goback' => '0', 'autoissued' => '', 'uat' => '',
        'notes' => $notes !== '' ? $notes : '1. <br />', 'csrf_token' => $d['csrf'], 'dob' => '',
    ];
    [$code, $html] = keretaApiHttp($s, 'POST', 'form_train_result_booking_b2c', $data, KERETA_BASE . '/form_train_result_detailorder', true);
    if (preg_match('/value="(\d{10,})"/', $html, $m)) return ['invoice' => $m[1]];
    return ['error' => trim(preg_replace('/\s+/', ' ', strip_tags($html))) ?: 'booking HTTP ' . $code];
}

function keretaApiPaymentOptions($s, $inv) {
    [$code, $html] = keretaApiHttp($s, 'GET', 'payment?invoice=' . urlencode($inv), [], KERETA_BASE . '/checkout_payment?invoice=' . urlencode($inv));
    preg_match_all('/<option value="([^"]+)"[^>]*>([^<]+)<\/option>/', $html, $om, PREG_SET_ORDER);
    $va = [];
    $tr = [];
    foreach ($om as $o) {
        $v = html_entity_decode($o[1]);
        if (!str_contains($v, '|')) continue;
        $e = ['value' => $v, 'label' => trim($o[2])];
        if (stripos($o[2], 'virtual') !== false) $va[] = $e;
        else $tr[] = $e;
    }
    return ['va_options' => $va, 'transfer_options' => $tr];
}

function keretaApiConfirm($s, $inv, $opsiVa, $opsiTr = '', $paymentOption = 'VIRTUALACCOUNT') {
    $data = [
        'invoice' => $inv, 'payment_option' => $paymentOption, 'opsi_va' => $opsiVa,
        'opsi_transfer' => $opsiTr !== '' ? $opsiTr : 'Bank Mandiri|1330013058136|MMBC TOUR TRAVEL', 'loginid' => '', 'idpassword' => '',
    ];
    [$code, $html] = keretaApiHttp($s, 'POST', 'confirm_payment', $data, KERETA_BASE . '/payment?invoice=' . urlencode($inv), true);
    return ['ok' => str_contains($html, 'checkout_payment')];
}

function keretaApiCheckout($s, $inv) {
    [$code, $html] = keretaApiHttp($s, 'GET', 'checkout_payment?invoice=' . urlencode($inv), [], KERETA_BASE . '/payment?invoice=' . urlencode($inv));
    $out = ['order' => $inv];
    if (preg_match('/Nomor Virtual Akun<br>\s*<span[^>]*><b>(\d+)<\/b>/', $html, $m)) { $out['account'] = $m[1]; $out['account_type'] = 'va'; }
    elseif (preg_match('/Masukkan Nomor VA <b>(\d+)<\/b>/', $html, $m)) { $out['account'] = $m[1]; $out['account_type'] = 'va'; }
    elseif (preg_match('/No\. Rekening Tujuan[^0-9]{0,120}?(\d{6,})/s', $html, $m)) { $out['account'] = $m[1]; $out['account_type'] = 'transfer'; }
    if (preg_match('/Total Bayar\s*<span[^>]*>\s*<b>(Rp[\d\.,]+)<\/b>/', $html, $m)) $out['total'] = $m[1];
    if (preg_match('/(\d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2} WIB)/', $html, $m)) $out['deadline'] = $m[1];
    if (str_contains($html, 'Menunggu pembayaran')) $out['status'] = 'Menunggu pembayaran';
    return $out;
}

/**
 * Pilih metode bayar (VA/transfer) + confirm + checkout dalam satu langkah.
 * @param string $payMethod 'VA:BSI'|'VA:Permata'|'VA:Muamalat'|'TRANSFER:Mandiri'|'TRANSFER:BCA'|'TRANSFER:BRI'|'TRANSFER:BNI'
 */
function keretaApiChooseAndConfirm($s, $inv, $payMethod = 'VA:Permata') {
    $p = keretaApiPaymentOptions($s, $inv);
    [$kind, $bankKey] = array_pad(explode(':', $payMethod, 2), 2, '');
    $isTransfer = strtoupper(trim($kind)) === 'TRANSFER';
    $pool = $isTransfer ? $p['transfer_options'] : $p['va_options'];
    $pick = null;
    foreach ($pool as $o) { if ($bankKey !== '' && stripos($o['label'], $bankKey) !== false) { $pick = $o; break; } }
    if (!$pick) $pick = $pool[0] ?? null;
    if (!$pick) return ['ok' => false, 'error' => 'no_payment_options'];

    $paymentOption = $isTransfer ? 'TRANSFERBANK' : 'VIRTUALACCOUNT';
    $opsiVa = $isTransfer ? ($p['va_options'][0]['value'] ?? '') : $pick['value'];
    $opsiTr = $isTransfer ? $pick['value'] : ($p['transfer_options'][0]['value'] ?? 'Bank Mandiri|1330013058136|MMBC TOUR TRAVEL');
    keretaApiConfirm($s, $inv, $opsiVa, $opsiTr, $paymentOption);
    $ck = keretaApiCheckout($s, $inv);
    $parts = explode('|', (string)$pick['value']);

    return [
        'ok' => true,
        'method' => $isTransfer ? 'TRANSFER' : 'VA',
        'account_type' => $ck['account_type'] ?? ($isTransfer ? 'transfer' : 'va'),
        'bank' => $pick['label'] ?? '',
        'account' => $ck['account'] ?? ($parts[1] ?? ''),
        'total' => $ck['total'] ?? '',
        'deadline' => $ck['deadline'] ?? '',
        'status' => $ck['status'] ?? 'Menunggu pembayaran',
    ];
}

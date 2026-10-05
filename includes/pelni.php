<?php
/**
 * pelni.php — PELNI API integration
 * https://klikmbc.biz/v2/ - PELNI ship booking
 */

define('PELNI_BASE', 'https://klikmbc.biz/v2');

require_once __DIR__ . '/flight-cache.php';

$PELNI_PORT_MAP = [
    '256' => 'Pulau Batam, Kota Batam',
    '431' => 'Tanjung Priok, Jakarta Utara',
];

$PELNI_PORT_CODE_MAP = [
    'Pulau Batam, Kota Batam' => '256',
    'Tanjung Priok, Jakarta Utara' => '431',
];

function pelniLogo() {
    return PELNI_BASE . '/images/logo-pelni-white-2023.png';
}

function pelniSearchPort($query) {
    $url = PELNI_BASE . '/getcode-pelabuhanv2.php?term=' . urlencode($query);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (compatible; TourAndTravel)',
        ],
    ]);
    $json = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$json || $httpCode !== 200) return [];

    $data = json_decode($json, true);
    if (!is_array($data)) return [];

    return $data;
}

function pelniSearchTrips($fromCode, $toCode, $date, $adults = 1, $children = 0, $male = 1, $female = 0, $fromName = '', $toName = '') {
    $cacheKey = 'pelni:' . $fromCode . ':' . $toCode . ':' . $date . ':' . $adults . ':' . $children;
    $cached = flightCacheGet($cacheKey);
    if ($cached !== null && isset($cached['trips'])) {
        return $cached['trips'];
    }

    global $PELNI_PORT_MAP;
    if ($fromName === '') $fromName = $PELNI_PORT_MAP[$fromCode] ?? '';
    if ($toName === '') $toName = $PELNI_PORT_MAP[$toCode] ?? '';

    $dateFormatted = date('j M Y', strtotime($date));

    $params = [
        'pelabuhan_from' => $fromName,
        'pelabuhan_to' => $toName,
        'pelabuhan_datedeparture' => $dateFormatted,
        'dewasa' => $adults,
        'bayi' => $children,
        'male' => $male,
        'female' => $female,
        'pelabuhan_from_final' => $fromName,
        'pelabuhan_to_final' => $toName,
        'pelabuhan_from_code' => $fromCode,
        'pelabuhan_to_code' => $toCode,
    ];

    $url = PELNI_BASE . '/form_pelni_result_cari?' . http_build_query($params);

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

    $trips = pelniParseTrips($html, $date);

    if (!empty($trips)) {
        flightCacheSet($cacheKey, 'pelni', ['trips' => $trips], count($trips));
    }

    return $trips;
}

function pelniParseTrips($html, $date) {
    $trips = [];
    $seen = [];

    preg_match_all('/<form[^>]*method=["\']POST["\'][^>]*action=["\'][^"\']*form_pelni_result_detailorder[^"\']*["\'][^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);

    if (empty($forms)) {
        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);
    }

    foreach ($forms as $form) {
        $block = $form[1];

        $shipName = '';
        if (preg_match('/ship_name["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipName = trim($m[1]);
        }

        $shipNumber = '';
        if (preg_match('/ship_number["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipNumber = trim($m[1]);
        }

        $shipCode = '';
        if (preg_match('/ship_code["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipCode = trim($m[1]);
        }

        $shipFrom = '';
        if (preg_match('/ship_from["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipFrom = trim($m[1]);
        }

        $shipTo = '';
        if (preg_match('/ship_to["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipTo = trim($m[1]);
        }

        $shipFromCode = '';
        if (preg_match('/ship_from_code["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipFromCode = trim($m[1]);
        }

        $shipToCode = '';
        if (preg_match('/ship_to_code["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipToCode = trim($m[1]);
        }

        $shipDate = '';
        if (preg_match('/ship_date["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipDate = trim($m[1]);
        }

        $shipDatetime = '';
        if (preg_match('/ship_datetime["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipDatetime = trim($m[1]);
        }

        $shipClass = '';
        if (preg_match('/ship_class["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipClass = trim($m[1]);
        }

        $shipPrice = 0;
        if (preg_match('/ship_price["\']?\s*value=["\']([\d.]+)["\']/i', $block, $m)) {
            $shipPrice = (float)str_replace('.', '', $m[1]);
        }

        if (!$shipName || !$shipCode) continue;

        $key = $shipName . '|' . $shipClass . '|' . $shipDatetime;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $departureTime = '';
        $arrivalTime = '';
        if (preg_match('/(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})\s*-\s*(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})/', $shipDatetime, $tm)) {
            $departureTime = $tm[1];
            $arrivalTime = $tm[2];
        } elseif (preg_match('/(\d{2}:\d{2})/', $shipDatetime, $tm)) {
            $departureTime = $tm[1];
        }

        $trips[] = [
            'ship_name' => $shipName,
            'ship_number' => $shipNumber,
            'ship_code' => $shipCode,
            'ship_from' => $shipFrom,
            'ship_to' => $shipTo,
            'ship_from_code' => $shipFromCode,
            'ship_to_code' => $shipToCode,
            'ship_date' => $shipDate ?: $date,
            'ship_datetime' => $shipDatetime,
            'ship_class' => $shipClass,
            'ship_price' => $shipPrice,
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
 * ===== Booking langsung ke penyedia (klikmbc.biz) =====
 * Flow: GET / (session) -> GET cari -> POST detailorder (csrf)
 *       -> POST booking_b2c (invoice) -> GET payment -> POST confirm -> GET checkout (VA).
 * Dipakai saat payment gateway dimatikan: pembeli bayar langsung via VA dari penyedia.
 */
const PELNI_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36';

function pelniSession(): array {
    return ['cookie' => tempnam(sys_get_temp_dir(), 'pelni_')];
}

function pelniHttp(array $s, string $method, string $path, array $data = [], ?string $referer = null, bool $ajax = false): array {
    $url = str_starts_with($path, 'http') ? $path : PELNI_BASE . '/' . ltrim($path, '/');
    $ch = curl_init();
    $headers = ['User-Agent: ' . PELNI_UA, 'Accept: text/html,application/xhtml+xml'];
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    if ($ajax) $headers[] = 'X-Requested-With: XMLHttpRequest';
    if (strtoupper($method) === 'GET' && $data) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
    }
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEJAR => $s['cookie'],
        CURLOPT_COOKIEFILE => $s['cookie'],
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

/** Ekstrak semua hidden input pada tiap form detailorder hasil pencarian. */
function pelniParseScheduleRows(string $html): array {
    preg_match_all('/<form[^>]*action="form_pelni_result_detailorder"[^>]*>(.*?)<\/form>/s', $html, $fm);
    $rows = [];
    foreach ($fm[1] as $body) {
        preg_match_all('/<input type=hidden name=([^ >]+) value="([^"]*)">/', $body, $im, PREG_SET_ORDER);
        $row = [];
        foreach ($im as $m) $row[$m[1]] = $m[2];
        if (!empty($row['ship_code'])) $rows[] = $row;
    }
    return $rows;
}

function pelniSupplierSearch(array $s, string $fromName, string $toName, string $fromCode, string $toCode, string $date, int $adults = 1, int $children = 0): array {
    $params = [
        'pelabuhan_from' => $fromName, 'pelabuhan_to' => $toName,
        'pelabuhan_datedeparture' => date('D, d M Y', strtotime($date)),
        'dewasa' => $adults, 'bayi' => $children, 'male' => $adults, 'female' => 0,
        'pelabuhan_from_final' => $fromName, 'pelabuhan_to_final' => $toName,
        'pelabuhan_from_code' => $fromCode, 'pelabuhan_to_code' => $toCode,
    ];
    pelniHttp($s, 'GET', '/');
    [$code, $html] = pelniHttp($s, 'GET', 'form_pelni_result_cari?' . http_build_query($params), [], PELNI_BASE . '/');
    if ($code !== 200 || !$html) return ['error' => "search_http_$code"];
    return ['rows' => pelniParseScheduleRows($html)];
}

function pelniSupplierDetail(array $s, array $ship, string $referer): array {
    $data = [
        'ship_name' => $ship['ship_name'] ?? '', 'ship_number' => $ship['ship_number'] ?? '',
        'ship_code' => $ship['ship_code'] ?? '', 'ship_from' => $ship['ship_from'] ?? '',
        'ship_from_code' => $ship['ship_from_code'] ?? '', 'ship_to' => $ship['ship_to'] ?? '',
        'ship_to_code' => $ship['ship_to_code'] ?? '', 'ship_route' => $ship['ship_route'] ?? '',
        'ship_date' => $ship['ship_date'] ?? '', 'ship_datetime' => $ship['ship_datetime'] ?? '',
        'ship_class' => $ship['ship_class'] ?? '', 'ship_publishfare' => '', 'ship_tax' => '',
        'ship_price' => $ship['ship_price'] ?? '',
        'ship_inforoute' => $ship['ship_inforoute'] ?? (($ship['ship_from'] ?? '') . '|' . ($ship['ship_to'] ?? '')),
        'adult' => $ship['adult'] ?? '1', 'child' => $ship['child'] ?? '',
        'infant' => $ship['infant'] ?? '0', 'male' => '1', 'female' => '0',
    ];
    [$code, $html] = pelniHttp($s, 'POST', 'form_pelni_result_detailorder', $data, $referer);
    if (!preg_match('/name="csrf_token".*?value="([^"]+)"/', $html, $m)) {
        return ['error' => "detail_csrf_http_$code"];
    }
    return ['csrf' => $m[1]];
}

function pelniSupplierBooking(array $s, array $ship, string $csrf, string $referer, array $pax, string $phone, string $email): array {
    $nama = $dob = $ident = '';
    foreach ($pax as $p) {
        $nama .= ($p['title'] ?? 'Mr') . '. ' . ($p['name'] ?? '') . '<br />';
        $dob .= ($p['dob'] ?? '') . '<br />';
        $ident .= ($p['id'] ?? '') . '<br />';
    }
    $data = [
        'ship_name' => $ship['ship_name'], 'ship_number' => $ship['ship_number'],
        'ship_code' => $ship['ship_code'], 'ship_date' => $ship['ship_date'],
        'ship_datetime' => $ship['ship_datetime'], 'ship_class' => $ship['ship_class'],
        'ship_from' => $ship['ship_from'], 'ship_from_code' => $ship['ship_from_code'],
        'ship_to' => $ship['ship_to'], 'ship_to_code' => $ship['ship_to_code'],
        'ship_adult' => $ship['adult'] ?? '1', 'ship_child' => $ship['child'] ?? '',
        'ship_infant' => $ship['infant'] ?? '0',
        'nama' => $nama, 'dob' => $dob, 'identitas' => $ident,
        'phone' => $phone, 'email' => $email, 'family' => '', 'notes' => '<br />',
        'csrf_token' => $csrf,
    ];
    [$code, $html] = pelniHttp($s, 'POST', 'form_pelni_result_booking_b2c', $data, $referer, true);
    if (preg_match('/value="(\d{10,})"/', $html, $m)) {
        return ['invoice' => $m[1]];
    }
    return ['error' => trim(preg_replace('/\s+/', ' ', strip_tags($html))) ?: "booking_http_$code"];
}

function pelniSupplierPaymentOptions(array $s, string $invoice): array {
    [$code, $html] = pelniHttp($s, 'GET', 'payment?invoice=' . urlencode($invoice), [], PELNI_BASE . '/checkout_payment?invoice=' . urlencode($invoice));
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

function pelniSupplierConfirm(array $s, string $invoice, string $opsiVa, string $opsiTr, string $paymentOption = 'VIRTUALACCOUNT'): array {
    $data = ['invoice' => $invoice, 'payment_option' => $paymentOption, 'opsi_va' => $opsiVa, 'opsi_transfer' => $opsiTr, 'loginid' => '', 'idpassword' => ''];
    [$code, $html] = pelniHttp($s, 'POST', 'confirm_payment', $data, PELNI_BASE . '/payment?invoice=' . urlencode($invoice), true);
    return ['ok' => str_contains($html, 'checkout_payment')];
}

function pelniSupplierCheckout(array $s, string $invoice): array {
    [$code, $html] = pelniHttp($s, 'GET', 'checkout_payment?invoice=' . urlencode($invoice), [], PELNI_BASE . '/payment?invoice=' . urlencode($invoice));
    $out = [];
    if (preg_match('/Nomor Virtual Akun<br>\s*<span[^>]*><b>(\d+)<\/b>/', $html, $m)) { $out['account'] = $m[1]; $out['account_type'] = 'va'; }
    elseif (preg_match('/Masukkan Nomor VA <b>(\d+)<\/b>/', $html, $m)) { $out['account'] = $m[1]; $out['account_type'] = 'va'; }
    elseif (preg_match('/No\. Rekening Tujuan[^0-9]{0,120}?(\d{6,})/s', $html, $m)) { $out['account'] = $m[1]; $out['account_type'] = 'transfer'; }
    if (preg_match('/Total Bayar\s*<span[^>]*>\s*<b>(Rp[\d\.,]+)<\/b>/', $html, $m)) $out['total'] = $m[1];
    if (preg_match('/Batas Pembayaran.{0,200}?(\d{2} \w{3} \d{4} \d{2}:\d{2} WIB)/s', $html, $m)) $out['deadline'] = $m[1];
    elseif (preg_match('/(\d{2} \w{3} \d{4} \d{2}:\d{2} WIB)/', $html, $m)) $out['deadline'] = $m[1];
    if (preg_match('/No\. Pesanan<br>\s*<span[^>]*>(\d+)<\/span>/', $html, $m)) $out['order'] = $m[1];
    if (preg_match('/Menunggu pembayaran/', $html)) $out['status'] = 'Menunggu pembayaran';
    return $out;
}

/**
 * Booking penuh ke penyedia. Re-search memakai kode pelabuhan, lalu cocokkan ship_code.
 * @param array $pax [['title'=>'Mr','name'=>'...','dob'=>'dd-mm-yyyy','id'=>'...'], ...]
 * @return array {ok:bool, invoice?, va?, bank?, total?, deadline?, status?, error?}
 */
function pelniSupplierBook(string $fromName, string $toName, string $fromCode, string $toCode, string $date, string $shipCode, array $pax, string $phone, string $email, int $adults = 1, string $payMethod = 'VA:Permata'): array {
    $s = pelniSession();
    $cleanup = function () use ($s) { @unlink($s['cookie']); };

    $sr = pelniSupplierSearch($s, $fromName, $toName, $fromCode, $toCode, $date, max(1, $adults), 0);
    if (isset($sr['error'])) { $cleanup(); return ['ok' => false, 'error' => $sr['error']]; }

    $ship = null;
    foreach ($sr['rows'] as $row) {
        if (($row['ship_code'] ?? '') === $shipCode) { $ship = $row; break; }
    }
    if (!$ship && !empty($sr['rows'])) $ship = $sr['rows'][0];
    if (!$ship) { $cleanup(); return ['ok' => false, 'error' => 'schedule_not_found']; }
    $ship['adult'] = (string) max(1, $adults);

    $d = pelniSupplierDetail($s, $ship, PELNI_BASE . '/form_pelni_result_cari');
    if (isset($d['error'])) { $cleanup(); return ['ok' => false, 'error' => $d['error']]; }

    $b = pelniSupplierBooking($s, $ship, $d['csrf'], PELNI_BASE . '/form_pelni_result_detailorder', $pax, $phone, $email);
    if (isset($b['error'])) { $cleanup(); return ['ok' => false, 'error' => $b['error']]; }

    $invoice = $b['invoice'];
    $p = pelniSupplierPaymentOptions($s, $invoice);

    [$kind, $bankKey] = array_pad(explode(':', $payMethod, 2), 2, '');
    $isTransfer = strtoupper(trim($kind)) === 'TRANSFER';
    $pool = $isTransfer ? $p['transfer_options'] : $p['va_options'];
    $pick = null;
    foreach ($pool as $o) { if ($bankKey !== '' && stripos($o['label'], $bankKey) !== false) { $pick = $o; break; } }
    if (!$pick) $pick = $pool[0] ?? null;
    if (!$pick) { $cleanup(); return ['ok' => false, 'error' => 'no_payment_options', 'invoice' => $invoice]; }

    $paymentOption = $isTransfer ? 'TRANSFERBANK' : 'VIRTUALACCOUNT';
    $opsiVa = $isTransfer ? ($p['va_options'][0]['value'] ?? '') : $pick['value'];
    $opsiTr = $isTransfer ? $pick['value'] : ($p['transfer_options'][0]['value'] ?? 'Bank Mandiri|1330013058136|MMBC TOUR TRAVEL');
    pelniSupplierConfirm($s, $invoice, $opsiVa, $opsiTr, $paymentOption);
    $c = pelniSupplierCheckout($s, $invoice);
    $cleanup();

    $accountParts = explode('|', (string)$pick['value']);
    $accountFromOption = $accountParts[1] ?? null;

    return [
        'ok' => true,
        'invoice' => $invoice,
        'method' => $isTransfer ? 'TRANSFER' : 'VA',
        'account_type' => $c['account_type'] ?? ($isTransfer ? 'transfer' : 'va'),
        'va' => $c['account'] ?? $accountFromOption,
        'bank' => $pick['label'] ?? null,
        'total' => $c['total'] ?? null,
        'deadline' => $c['deadline'] ?? null,
        'status' => $c['status'] ?? 'Menunggu pembayaran',
    ];
}

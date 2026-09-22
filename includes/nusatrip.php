<?php
/**
 * nusatrip.php — Native NusaTrip API client (hotel search → booking → payment).
 * Spec: ~/Documents/nusatrip/API.md (reverse-engineered APK 1.1.4081, tested live).
 *
 * - Signed host: https://nusatrip-api.com/api/
 * - Unsigned legacy: http://www.nusatrip.com/
 * - Auth: static token + AES-CBC Signature header (B.g / B.java:1131).
 * - Booking = guest OK (omit customerId). Payment diteruskan langsung ke Nusatrip.
 */

define('NUSA_API', 'https://nusatrip-api.com/api/');
define('NUSA_LEGACY', 'http://www.nusatrip.com/');
define('NUSA_TOKEN', '3f05d494df7741369f363436c9620e0f50a5c82c22c6004a745500a427a4ea7d');
define('NUSA_VERSION', '1.5');
define('NUSA_UA', 'NusaTrip Android App/1.1.4081');
define('NUSA_KEY_STR', '38edee80bd05bd2c0baa5447a004da7e0c1509787fc16415e7a20d4f626a7464');
define('NUSA_PROXY', getenv('NUSA_PROXY') ?: '');
define('NUSA_ESCAPE_KEYS', ['contact', 'items', 'payment', 'roomItems', 'frequentFlyer', 'ssrOutbound', 'ssrInbound', 'deviceInfo']);

/** Modul NusaTrip aktif? Toggle dari admin (setting nusatrip_module_enabled, default aktif). */
function nusaModuleEnabled(): bool {
    if (!function_exists('getSetting')) return true;
    return (string)getSetting('nusatrip_module_enabled', '1') === '1';
}

/** Bangun param JSON untuk CRC (B.g): sort alpha, escape " → \" utk key khusus. */
function nusaParamJson(array $p): string {
    ksort($p, SORT_STRING);
    $parts = [];
    foreach ($p as $k => $v) {
        $v = (string)$v;
        if (in_array($k, NUSA_ESCAPE_KEYS, true)) $v = str_replace('"', '\\"', $v);
        $parts[] = '"' . $k . '":"' . $v . '"';
    }
    return '{' . implode(',', $parts) . '}';
}

/** Signature = (IV_HEX + CT_HEX).upper, AES-128-CBC zero-pad, key=MD5(KEY_STR). */
function nusaSign(array $params, ?int $nowMs = null, ?string $iv = null): string {
    $json = nusaParamJson($params);
    $crc = sprintf('%u', crc32($json));
    $plain = ((string)($nowMs ?? (int)(microtime(true) * 1000))) . '|' . $crc;
    $key = md5(NUSA_KEY_STR, true);
    $ivBin = $iv ?? random_bytes(16);
    $pad = 16 - (strlen($plain) % 16); // 1..16, zero-pad to block
    $plainPadded = $plain . str_repeat("\x00", $pad);
    $ct = openssl_encrypt($plainPadded, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $ivBin);
    return strtoupper(bin2hex($ivBin) . bin2hex($ct));
}

function nusaHeaders(string $sig): array {
    return ['Signature: ' . $sig, 'Accept: application/json', 'User-Agent: ' . NUSA_UA];
}

function nusaFormEncode(array $form): string {
    $parts = [];
    foreach ($form as $k => $v) $parts[] = rawurlencode((string)$k) . '=' . rawurlencode((string)$v);
    return implode('&', $parts);
}

function nusaCurl(string $url, array $headers, ?array $form = null): array {
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING => '', CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => NUSA_UA];
    if (NUSA_PROXY !== '') $opts[CURLOPT_PROXY] = NUSA_PROXY;
    if ($form !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = nusaFormEncode($form);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1';
        $opts[CURLOPT_HTTPHEADER] = $headers;
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode(is_string($body) ? $body : '', true);
    return ['http' => $code, 'raw' => is_string($body) ? $body : '', 'data' => is_array($data) ? $data : null];
}

/** GET signed ke nusatrip-api.com. $params tanpa token/version (ditambah otomatis). */
function nusaGet(string $ep, array $params = [], string $version = NUSA_VERSION): array {
    $params['token'] = NUSA_TOKEN;
    $params['version'] = $version;
    $sig = nusaSign($params);
    $url = NUSA_API . ltrim($ep, '/') . '?' . http_build_query($params);
    return nusaCurl($url, nusaHeaders($sig));
}

/** POST signed: version di query (sniffed), full params di query + form body (legacy B.C/r/d). */
function nusaPost(string $ep, array $params = [], string $version = NUSA_VERSION): array {
    $params['token'] = NUSA_TOKEN;
    $params['version'] = $version;
    $sig = nusaSign($params);
    $h = nusaHeaders($sig);
    $url = NUSA_API . ltrim($ep, '/') . '?' . http_build_query($params);
    return nusaCurl($url, $h, $params);
}

// ---------- Search chain ----------

function nusaYmd(string $d): string {
    return date('Ymd', strtotime($d));
}

/** Unsigned: GET location/search?name=&lang=en */
function nusaLocationSearch(string $q): array {
    $url = NUSA_LEGACY . 'location/search?name=' . urlencode($q) . '&lang=en';
    return nusaCurl($url, ['Accept: Application/json', 'User-Agent: ' . NUSA_UA]);
}

/** Signed hotel_search. Returns raw (hotels[].id = hotelId). */
function nusaHotelSearch(string $locationId, string $checkin, string $checkout, int $guests = 1): array {
    return nusaGet('hotel_search', ['locationId' => $locationId, 'checkinDate' => nusaYmd($checkin),
        'checkoutDate' => nusaYmd($checkout), 'guests' => (string)$guests, 'partial' => 'true', 'lang' => 'en']);
}

function nusaHotelDetail(string $hotelId): array {
    return nusaGet('hotel_detail', ['hotelId' => $hotelId, 'lang' => 'en']);
}

function nusaHotelRates(string $hotelId, string $checkin, string $checkout, int $guests = 1): array {
    return nusaGet('hotel_rates', ['hotelId' => $hotelId, 'checkinDate' => nusaYmd($checkin),
        'checkoutDate' => nusaYmd($checkout), 'guests' => (string)$guests, 'partial' => 'true', 'lang' => 'en']);
}

function nusaCancelPolicy(string $locationId, string $hotelId, string $checkin, string $checkout, string $roomRef): array {
    return nusaGet('hotel_cancellationpolicy', ['locationId' => $locationId, 'hotelId' => $hotelId,
        'checkinDate' => nusaYmd($checkin), 'checkoutDate' => nusaYmd($checkout),
        'roomReference' => $roomRef, 'lang' => 'en']);
}

// ---------- Flight chain (guest OK) ----------

function nusaAirportSearch(string $q): array {
    $url = NUSA_LEGACY . 'airport/search?name=' . urlencode($q) . '&lang=en';
    return nusaCurl($url, ['Accept: application/json', 'User-Agent: ' . NUSA_UA]);
}

function nusaFlightSearch(string $from, string $to, string $dateYmd, int $adults = 1): array {
    $dd = preg_match('/^\d{8}$/', $dateYmd) ? $dateYmd : date('Ymd', strtotime($dateYmd));
    return nusaGet('flight_search', ['adultNum' => (string)$adults, 'childNum' => '0', 'infantNum' => '0',
        'departure' => $from, 'arrival' => $to, 'departDate' => $dd,
        'partial' => '1', 'currency' => 'IDR', 'lang' => 'en']);
}

/** Ambil opsi termurah dari flight_search (outbounds keyed by id). */
function nusaCheapestFlight(array $data): ?array {
    $ob = $data['outbounds'] ?? [];
    if (!is_array($ob) || !$ob) return null;
    $best = null;
    foreach ($ob as $o) {
        if (!is_array($o) || !isset($o['one_way_fare'])) continue;
        if ($best === null || $o['one_way_fare'] < $best['one_way_fare']) $best = $o;
    }
    return $best;
}

function nusaFlightItem(string $outboundParam, int $adults = 1): array {
    return nusaPost('transaction_flight_item', ['adultNum' => (string)$adults, 'childNum' => '0',
        'infantNum' => '0', 'outboundParam' => $outboundParam, 'lang' => 'en']);
}

// ---------- Booking chain (guest OK) ----------

/** roomItems: ["<guests>#<special_deal>#<book_reference>"] 3 segmen, deal kosong bila null. */
function nusaRoomItems(int $guests, $specialDeal, string $bookRef): string {
    $deal = $specialDeal === null ? '' : (string)$specialDeal;
    if ($deal === 'null') $deal = '';
    return json_encode([(string)$guests . '#' . $deal . '#' . $bookRef]);
}

function nusaHotelItem(string $hotelId, string $checkin, string $checkout, string $roomItemsJson): array {
    return nusaPost('transaction_hotel_item', ['hotelId' => $hotelId, 'checkinDate' => nusaYmd($checkin),
        'checkoutDate' => nusaYmd($checkout), 'roomItems' => $roomItemsJson, 'lang' => 'en']);
}

function nusaCheckoutAttributes(string $cartSession, string $checkoutId, string $bookingTime): array {
    return nusaPost('transaction_checkout_attributes', ['cartSession' => $cartSession,
        'checkoutId' => $checkoutId, 'bookingTime' => $bookingTime, 'lang' => 'en']);
}

function nusaValidate(string $cartSession, string $checkoutId, string $bookingTime, string $currency = 'IDR'): array {
    return nusaPost('transaction_validate', ['checkoutId' => $checkoutId, 'cartSession' => $cartSession,
        'bookingTime' => $bookingTime, 'currency' => $currency, 'retry' => '', 'lang' => 'en'], '2.1');
}

function nusaSubmit(array $fields): array {
    $fields['lang'] = $fields['lang'] ?? 'en';
    return nusaPost('transaction_submit', $fields);
}

function nusaResult(string $taskId, string $checkoutId, string $retry = '0', ?string $lastState = null): array {
    $p = ['taskId' => $taskId, 'checkoutId' => $checkoutId, 'retry' => $retry, 'lang' => 'en'];
    if ($lastState !== null && $retry !== '0') $p['lastState'] = $lastState;
    return nusaGet('transaction_result', $p);
}

/** Nomor VA hanya ada di transaction_summary?ref= (result tidak pernah berisi VA). */
function nusaSummary(string $ref): array {
    return nusaGet('transaction_summary', ['ref' => $ref, 'lang' => 'en']);
}

/** Polling result ala aplikasi (r.java, p.java:q): max 9x jeda 5dt, retry+1 + lastState. */
function nusaPollResult(string $taskId, string $checkoutId, int $maxTries = 9, int $sleepSec = 5): array {
    $last = null;
    for ($i = 0; $i < $maxTries; $i++) {
        if ($i > 0) sleep($sleepSec);
        $retry = (string)$i;
        $r = nusaResult($taskId, $checkoutId, $retry, $i === 0 ? null : (string)($last['lastState'] ?? '1'));
        $d = $r['data'] ?? null;
        if (!is_array($d)) return $r;
        $last = $d;
        if ((int)($d['finalStatus'] ?? 0) !== 0) return $r;
    }
    return nusaResult($taskId, $checkoutId, '0');
}

/** deviceFingerPrint: 32-byte hex UPPER (64 char). */
function nusaFingerprint(): string {
    return strtoupper(bin2hex(random_bytes(32)));
}

function nusaDeviceInfo(string $lang = 'en'): string {
    return json_encode(['device_manufacturer' => 'Google', 'device_model' => 'sdk_gphone64_arm64',
        'app_version' => '1.1.4081', 'app_lang' => $lang], JSON_UNESCAPED_SLASHES);
}

/** Contact guest: tanpa customerId. checkoutPass hanya bila createAccount=true. */
function nusaContact(string $title, string $first, string $last, string $email, string $phone, bool $withPass = false): string {
    $c = ['title' => $title, 'firstName' => $first, 'lastName' => $last,
        'email' => $email, 'verifyEmail' => $email, 'phoneNo' => $phone,
        'nationality' => 'ID', 'contactId' => '0-0'];
    if ($withPass) { $c['checkoutPass'] = '123qwe!@#QWE'; $c['checkoutVpass'] = '123qwe!@#QWE'; }
    return json_encode($c, JSON_UNESCAPED_SLASHES);
}

function nusaItems(string $title, string $first, string $last, string $bookingTime): string {
    $guest = ['title' => $title, 'firstName' => $first, 'lastName' => $last, 'contactId' => '', 'nationality' => 'ID'];
    return json_encode([['preferences' => new stdClass(), 'note' => '',
        'insurance' => ['applyInsurance' => 'false', 'applyInsurancePreference' => ''],
        'occupancies' => [['guest' => $guest]], 'bookingTime' => $bookingTime]], JSON_UNESCAPED_SLASHES);
}

/** Normalisasi hotel_search item → format kartu live kita. */
function nusaNormalizeHotel(array $h): array {
    $id = (string)($h['id'] ?? '');
    $price = (int)($h['rate'] ?? 0);
    return ['source' => 'nusatrip', 'external_id' => $id, 'hotel_id' => $id,
        'name' => $h['name'] ?? '', 'star' => isset($h['star']) ? (int)$h['star'] : null,
        'price' => $price, 'currency' => $h['cur'] ?? 'IDR',
        'price_formatted' => $price > 0 ? 'Rp' . number_format($price, 0, ',', '.') : null,
        'image' => $h['photo_link'] ?? null,
        'lat' => $h['lat'] ?? null, 'lng' => $h['long'] ?? null,
        'address' => $h['address'] ?? null, 'desc' => $h['desc'] ?? null,
        'url' => is_string($h['uri'] ?? null) ? $h['uri'] : null,
        'room_category' => $h['room_category'] ?? null, 'board_type' => $h['board_type'] ?? null,
        'location_id' => $h['location_id'] ?? null, '_raw' => $h];
}

/** Search kota end-to-end: location → hotel_search → normalisasi + sort termurah. */
function nusaSearchCity(string $q, string $checkin, string $checkout, int $guests = 1): array {
    $loc = nusaLocationSearch($q);
    $list = $loc['data'] ?? null;
    if (($loc['http'] ?? 0) !== 200 || !is_array($list) || !$list)
        return ['error' => 'Kota tidak ditemukan di NusaTrip', 'hotels' => []];
    $first = $list[0];
    $locationId = (string)($first['val'] ?? '');
    if ($locationId === '') return ['error' => 'locationId kosong', 'hotels' => []];
    $s = nusaHotelSearch($locationId, $checkin, $checkout, $guests);
    $hotels = $s['data']['hotels'] ?? null;
    if (($s['http'] ?? 0) !== 200 || !is_array($hotels))
        return ['error' => 'hotel_search gagal (HTTP ' . ($s['http'] ?? 0) . ')', 'hotels' => []];
    $out = array_map('nusaNormalizeHotel', $hotels);
    usort($out, fn($a, $b) => ($a['price'] ?: PHP_INT_MAX) <=> ($b['price'] ?: PHP_INT_MAX));
    return ['source' => 'nusatrip', 'location_id' => $locationId,
        'location_label' => strip_tags((string)($first['value'] ?? $q)),
        'count' => count($out), 'hotels' => $out];
}

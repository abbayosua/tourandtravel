<?php
/**
 * hotelapi.php — Live hotel search & detail client (curl only, tanpa API key resmi).
 *
 * Sumber live (lihat HOTEL-ENDPOINTS.md):
 *   - Booking.com — autocomplete kota + harga/ketersediaan + detail (per hotel)
 *   - OYO         — daftar hotel per kota + harga + foto + geo
 *   - NusaTrip    — daftar hotel per kota + harga (butuh rkey/key)
 *
 * Semua respons dinormalisasi ke bentuk seragam:
 *   ['source','external_id','name','star','price','currency','price_formatted',
 *    'image','lat','lng','address','desc','url']
 *
 * Hasil live di-cache di tabel hotel_cache (includes/hotel-cache.php).
 * Bila request gagal, fungsi mengembalikan ['error' => ...] sehingga pemanggil
 * bisa fallback ke data lokal.
 */

require_once __DIR__ . '/hotel-cache.php';
require_once __DIR__ . '/nusatrip.php';

define('HOTEL_UA', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36');
define('BOOKING_GQL', 'https://www.booking.com/dml/graphql?lang=en-gb');
define('BOOKING_AUTOCOMPLETE', 'https://accommodations.booking.com/autocomplete.json');

/**
 * Live hotel API aktif? (setting hotel_live_enabled, default aktif)
 */
function hotelApiEnabled(): bool {
    return (string)getSetting('hotel_live_enabled', '1') === '1';
}

/**
 * Daftar proxy opsional (satu per baris ip:port). File gitignored.
 */
function hotelApiProxies(): array {
    $f = __DIR__ . '/hotel-proxies.txt';
    if (!is_file($f)) return [];
    return array_values(array_filter(array_map('trim', file($f))));
}

function hotelApiHttpPost(string $url, $payload, array $headers = []): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_USERAGENT => HOTEL_UA,
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    return is_string($out) ? $out : '';
}

function hotelApiHttpGet(string $url, ?string $proxy = null, array $headers = [], bool $json = true): string {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_USERAGENT => HOTEL_UA,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => true,
    ];
    if ($json) {
        $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2TLS;
        $opts[CURLOPT_HTTPHEADER] = $headers ?: ['Accept: application/json', 'Referer: https://www.nusatrip.com/hotels'];
    } elseif ($headers) {
        $opts[CURLOPT_HTTPHEADER] = $headers;
    }
    if ($proxy) $opts[CURLOPT_PROXY] = $proxy;
    curl_setopt_array($ch, $opts);
    $out = curl_exec($ch);
    curl_close($ch);
    return is_string($out) ? $out : '';
}

/**
 * Bungkus request yang bisa di-cache.
 */
function hotelApiCached(string $source, string $cacheKey, callable $fetch, callable $counter = null): array {
    $cached = hotelCacheGet($cacheKey);
    if ($cached !== null) {
        $cached['_from_cache'] = true;
        return $cached;
    }
    $data = $fetch();
    if (is_array($data) && !isset($data['error'])) {
        $count = $counter ? (int)$counter($data) : 0;
        hotelCacheSet($cacheKey, $source, $data, $count);
    }
    return is_array($data) ? $data : ['error' => 'Respons tidak valid'];
}

// ============================================================
// Booking.com
// ============================================================

function hotelApiAutocomplete(string $q): array {
    if (trim($q) === '') return ['results' => []];
    return hotelApiCached('booking', hotelCacheKey('booking', ['autocomplete', strtolower(trim($q))]), function () use ($q) {
        $raw = hotelApiHttpPost(BOOKING_AUTOCOMPLETE, [
            'query' => $q, 'pageview_id' => '', 'aid' => 800210, 'language' => 'en-us', 'size' => 8,
        ], ['Origin: https://www.booking.com', 'Referer: https://www.booking.com/']);
        $d = json_decode($raw, true);
        if (!isset($d['results']) || !is_array($d['results'])) return ['error' => 'autocomplete kosong'];
        $out = [];
        foreach ($d['results'] as $r) {
            $out[] = [
                'label' => strip_tags($r['label'] ?? ''),
                'label1' => strip_tags($r['label1'] ?? ''),
                'label2' => strip_tags($r['label2'] ?? ''),
                'dest_id' => $r['dest_id'] ?? null,
                'dest_type' => $r['dest_type'] ?? null,
                'cc' => $r['cc1'] ?? null,
                'nr_hotels' => $r['nr_hotels'] ?? null,
            ];
        }
        return ['results' => $out];
    }, fn($d) => count($d['results'] ?? []));
}

function hotelApiBookingPrices(string $cc, string $pg, string $start, int $days = 7, int $adults = 2, int $rooms = 1): string {
    if ($cc === '' || $pg === '') return '';
    $q = 'query AvailabilityCalendar($input: AvailabilityCalendarQueryInput!) { availabilityCalendar(input: $input) { ... on AvailabilityCalendarQueryResult { hotelId days { available avgPriceFormatted checkin minLengthOfStay __typename } __typename } ... on AvailabilityCalendarQueryError { message __typename } __typename } }';
    $payload = [
        'operationName' => 'AvailabilityCalendar',
        'variables' => ['input' => [
            'travelPurpose' => 2,
            'pagenameDetails' => ['countryCode' => $cc, 'pagename' => $pg],
            'searchConfig' => ['searchConfigDate' => ['startDate' => $start, 'amountOfDays' => max(1, $days)], 'nbAdults' => max(1, $adults), 'nbRooms' => max(1, $rooms)],
        ]],
        'extensions' => new stdClass(),
        'query' => $q,
    ];
    return hotelApiHttpPost(BOOKING_GQL, $payload, ['Origin: https://www.booking.com', 'Referer: https://www.booking.com/hotel/' . $cc . '/' . $pg . '.html']);
}

function hotelApiBookingDetail(int $hotelId): string {
    if ($hotelId <= 0) return '';
    $q = 'query P($input: PropertyDetailsQueryInput!) { propertyDetails(input: $input) { ... on Property { id name accommodationType { id name } location { address city country latitude longitude } starRating { value symbol description { translation } caption { translation } } reviews { totalScore reviewsCount } facilities { id icon } photos { main { id } all { id } } rooms { id occupancy { __typename } bedConfigurations { beds { count } } } __typename } } }';
    $payload = [
        'operationName' => 'P',
        'variables' => ['input' => ['hotelId' => $hotelId]],
        'extensions' => new stdClass(),
        'query' => $q,
    ];
    return hotelApiHttpPost(BOOKING_GQL, $payload, ['Origin: https://www.booking.com', 'Referer: https://www.booking.com/']);
}

/**
 * Detail hotel Booking.com (gabungan: detail + harga + kamar + alamat), dinormalisasi.
 */
function hotelApiBookingHotel(string $cc, string $pg, string $start, int $days = 7, int $adults = 2, int $rooms = 1, int $hotelId = 0): array {
    $cc = trim($cc); $pg = trim($pg);
    if ($cc === '' || $pg === '') return ['error' => 'pagename/cc Booking.com tidak valid'];
    return hotelApiCached('booking', hotelCacheKey('booking', ['hotel', $cc, $pg, $start, $days, $adults, $rooms, $hotelId]), function () use ($cc, $pg, $start, $days, $adults, $rooms, $hotelId) {
        $prices = json_decode(hotelApiBookingPrices($cc, $pg, $start, $days, $adults, $rooms), true);
        $cal = $prices['data']['availabilityCalendar'] ?? null;
        if (is_array($cal) && isset($cal['message'])) return ['error' => 'Booking.com: ' . $cal['message']];
        $hid = $hotelId ?: (int)($cal['hotelId'] ?? 0);
        $detail = $hid ? json_decode(hotelApiBookingDetail($hid), true) : null;
        $prop = $detail['data']['propertyDetails'] ?? null;
        if (!$prop && !$cal) return ['error' => 'Booking.com tidak mengembalikan data hotel'];

        $photoIds = array_column($prop['photos']['all'] ?? [], 'id');
        return [
            'source' => 'booking',
            'hotel_id' => $hid,
            'name' => $prop['name'] ?? null,
            'type' => $prop['accommodationType']['name'] ?? null,
            'star' => isset($prop['starRating']['value']) ? (int)$prop['starRating']['value'] : null,
            'score' => $prop['reviews']['totalScore'] ?? null,
            'reviews_count' => $prop['reviews']['reviewsCount'] ?? null,
            'address' => $prop['location']['address'] ?? null,
            'city' => $prop['location']['city'] ?? null,
            'country' => $prop['location']['country'] ?? null,
            'lat' => $prop['location']['latitude'] ?? null,
            'lng' => $prop['location']['longitude'] ?? null,
            'facility_icons' => array_column($prop['facilities'] ?? [], 'icon'),
            'rooms' => $prop['rooms'] ?? [],
            'images' => array_map(fn($id) => 'https://cf.bstatic.com/xdata/images/hotel/square600/' . $id . '.jpg', array_slice($photoIds, 0, 10)),
            'booking_url' => "https://www.booking.com/hotel/$cc/$pg.html",
            'prices' => $cal['days'] ?? [],
            'cc' => $cc,
            'pagename' => $pg,
        ];
    }, fn($d) => count($d['prices'] ?? []));
}

/**
 * Parse URL hotel Booking.com → [cc, pagename].
 */
function hotelApiParseBookingUrl(string $url): array {
    if (preg_match('#booking\.com/hotel/([a-z]{2})/([a-z0-9\-]+)#i', $url, $m)) {
        return [strtolower($m[1]), preg_replace('/\.html?.*$/', '', $m[2])];
    }
    return [null, null];
}

// ============================================================
// OYO
// ============================================================

/** Modul OYO aktif? Toggle dari admin (setting oyo_module_enabled, default aktif). */
function oyoModuleEnabled(): bool {
    if (!function_exists('getSetting')) return true;
    return (string)getSetting('oyo_module_enabled', '1') === '1';
}

function hotelApiOyo(string $city): array {
    if (!oyoModuleEnabled()) return ['error' => 'Modul OYO nonaktif', 'source' => 'oyorooms', 'hotels' => []];
    $city = trim($city);
    if ($city === '') return ['error' => 'Kota kosong', 'hotels' => []];
    return hotelApiCached('oyo', hotelCacheKey('oyo', ['list', $city]), function () use ($city) {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($city)));
        $slug = trim($slug, '-');
        if ($slug === '') return ['error' => 'Kota tidak valid', 'source' => 'oyorooms', 'hotels' => []];
        $html = hotelApiHttpGet('https://www.oyorooms.com/id/hotels-in-' . $slug . '/', null, [], false);
        if (!$html || strpos($html, 'hotelCardListing') === false) {
            return ['error' => 'OYO: tidak ada hasil / diblokir', 'source' => 'oyorooms', 'hotels' => []];
        }
        preg_match_all('/listingHotelDescription__hotelName[^>]*>([^<]+)/', $html, $mn);
        preg_match_all('/listingPrice__finalPrice[^>]*>([^<]+)/', $html, $mp);
        preg_match_all('/href="(\/id\/(\d+)\/?)"/', $html, $mu);
        preg_match_all('/latitude" content="([^"]+)/', $html, $mlat);
        preg_match_all('/longitude" content="([^"]+)/', $html, $mlng);
        preg_match_all('/streetAddress" title="([^"]+)/', $html, $ma);
        preg_match_all('/images\.oyoroomscdn\.com\/[^"\s]+/', $html, $mi);

        $n = count($mn[1]);
        $hotels = [];
        for ($i = 0; $i < $n; $i++) {
            $priceRaw = trim($mp[1][$i] ?? '');
            $price = (int)preg_replace('/\D/', '', $priceRaw);
            $hotels[] = [
                'source' => 'oyorooms',
                'external_id' => $mu[2][$i] ?? null,
                'name' => html_entity_decode(trim($mn[1][$i]), ENT_QUOTES, 'UTF-8'),
                'star' => null,
                'price' => $price,
                'currency' => 'IDR',
                'price_formatted' => $price > 0 ? 'Rp' . number_format($price, 0, ',', '.') : ($priceRaw ?: null),
                'image' => !empty($mi[0][$i]) ? 'https://' . $mi[0][$i] : null,
                'lat' => $mlat[1][$i] ?? null,
                'lng' => $mlng[1][$i] ?? null,
                'address' => html_entity_decode(trim($ma[1][$i] ?? ''), ENT_QUOTES, 'UTF-8'),
                'desc' => null,
                'url' => 'https://www.oyorooms.com' . ($mu[1][$i] ?? ''),
            ];
        }
        return ['source' => 'oyorooms', 'city' => $city, 'count' => count($hotels), 'hotels' => $hotels];
    }, fn($d) => count($d['hotels'] ?? []));
}

// ============================================================
// NusaTrip
// ============================================================

function hotelApiNusaAuto(string $q): array {
    if (function_exists('nusaModuleEnabled') && !nusaModuleEnabled()) return ['error' => 'Modul NusaTrip nonaktif', 'results' => []];
    return hotelApiCached('nusatrip', hotelCacheKey('nusatrip', ['auto', strtolower(trim($q))]), function () use ($q) {
        $raw = hotelApiHttpGet('https://www.nusatrip.com/location/search?name=' . urlencode($q));
        $d = json_decode($raw, true);
        if (!is_array($d)) return ['error' => 'NusaTrip autocomplete kosong', 'results' => []];
        $out = [];
        foreach ($d as $x) {
            if (!is_array($x)) continue;
            $out[] = ['label' => trim(strip_tags($x['value'] ?? '')), 'location_id' => $x['val'] ?? null];
        }
        return ['results' => $out];
    }, fn($d) => count($d['results'] ?? []));
}

function hotelApiNusatrip(string $token): array {
    if (function_exists('nusaModuleEnabled') && !nusaModuleEnabled()) return ['error' => 'Modul NusaTrip nonaktif', 'hotels' => []];
    $token = trim($token);
    if ($token === '') return ['error' => 'Token NusaTrip kosong', 'hotels' => []];
    if (ctype_digit($token)) {
        $param = 'key';
    } elseif (preg_match('/^[a-f0-9]{32,160}$/i', $token)) {
        $param = 'rkey';
    } else {
        return ['error' => 'Token tidak valid (key angka atau rkey hex dari /hotels/result?...)', 'hotels' => []];
    }
    return hotelApiCached('nusatrip', hotelCacheKey('nusatrip', ['list', $token]), function () use ($param, $token) {
        $url = "https://www.nusatrip.com/hotels/result?$param=$token";
        $raw = hotelApiHttpGet($url);
        $usedProxy = null;
        if (!$raw) {
            foreach (array_slice(hotelApiProxies(), 0, 5) as $p) {
                $raw = hotelApiHttpGet($url, $p);
                if ($raw) { $usedProxy = $p; break; }
            }
        }
        $d = json_decode($raw, true);
        if (!isset($d['hotel_list']) || !$d['hotel_list']) {
            return ['error' => 'NusaTrip: key tidak valid / kosong', 'hotels' => []];
        }
        $hotels = [];
        foreach ($d['hotel_list'] as $h) {
            $price = (int)($h['baseLowestRate'] ?? 0);
            $hotels[] = [
                'source' => 'nusatrip',
                'external_id' => $h['uri'] ?? ($h['location_id'] ?? null),
                'name' => $h['name'] ?? '',
                'star' => isset($h['star']) ? (int)$h['star'] : null,
                'price' => $price,
                'currency' => $h['baseCurrency'] ?? 'IDR',
                'price_formatted' => $price > 0 ? 'Rp' . number_format($price, 0, ',', '.') : null,
                'image' => !empty($h['photo_main']) ? 'https://him.nusatrip.net' . $h['photo_main'] : null,
                'lat' => $h['latitude'] ?? null,
                'lng' => $h['longitude'] ?? null,
                'address' => $h['address'] ?? null,
                'desc' => $h['location_desc'] ?? null,
                'url' => 'https://www.nusatrip.com/id/hotel' . ($h['uri'] ?? ''),
            ];
        }
        return ['source' => 'nusatrip', 'count' => count($hotels), 'via_proxy' => $usedProxy, 'hotels' => $hotels];
    }, fn($d) => count($d['hotels'] ?? []));
}

// ============================================================
// Unified search & detail
// ============================================================

/**
 * Pilih sumber live. 'auto' → NusaTrip native (tanpa rkey), fallback OYO bila gagal.
 * Modul yang nonaktif otomatis dilewati: bila keduanya mati → '' (tanpa live).
 */
function hotelApiResolveSource(?string $source = null): string {
    $source = $source ?: (string)getSetting('hotel_live_source', 'auto');
    $nusaOn = !function_exists('nusaModuleEnabled') || nusaModuleEnabled();
    $oyoOn = !function_exists('oyoModuleEnabled') || oyoModuleEnabled();
    if ($source === 'auto') $source = $nusaOn ? 'nusatrip' : ($oyoOn ? 'oyo' : '');
    if ($source === 'nusatrip' && !$nusaOn) $source = $oyoOn ? 'oyo' : '';
    if ($source === 'oyo' && !$oyoOn) $source = $nusaOn ? 'nusatrip' : '';
    if (!in_array($source, ['oyo', 'nusatrip'], true)) $source = $oyoOn ? 'oyo' : ($nusaOn ? 'nusatrip' : '');
    return $source;
}

/**
 * Cari hotel live per kota. Mengembalikan daftar ternormalisasi siap render.
 *
 * @param array $opts ['source'=>oyo|nusatrip|auto, 'stars'=>int, 'min_price'=>float, 'max_price'=>float, 'limit'=>int, 'sort'=>price|price_desc|stars]
 * @return array ['source'=>, 'count'=>, 'hotels'=>[], 'error'=>?]
 */
function hotelApiSearch(string $city, array $opts = []): array {
    if (!hotelApiEnabled()) return ['error' => 'Live hotel API dimatikan', 'hotels' => [], 'count' => 0];
    $city = trim($city);
    if ($city === '') return ['error' => 'Kota kosong', 'hotels' => [], 'count' => 0];

    $source = hotelApiResolveSource($opts['source'] ?? null);
    if ($source === '') return ['source' => '', 'count' => 0, 'hotels' => [], 'error' => 'Semua modul live nonaktif'];
    if ($source === 'nusatrip') {
        // Native API: butuh tanggal valid utk hotel_search (default +7/+8).
        $ci = (string)($opts['checkin'] ?? '');
        $co = (string)($opts['checkout'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ci)) $ci = date('Y-m-d', strtotime('+7 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $co) || strtotime($co) <= strtotime($ci)) $co = date('Y-m-d', strtotime($ci . ' +1 day'));
        $guests = max(1, (int)($opts['guests'] ?? 2));
        $res = hotelApiCached('nusatrip', hotelCacheKey('nusatrip', ['native', strtolower($city), $ci, $co, $guests]),
            fn() => nusaSearchCity($city, $ci, $co, $guests),
            fn($d) => count($d['hotels'] ?? []));
        // Fallback ke OYO bila NusaTrip kosong/gagal (hanya bila modul OYO aktif)
        if (isset($res['error']) || empty($res['hotels'])) {
            if (!oyoModuleEnabled()) return ['source' => $source, 'count' => 0, 'hotels' => [], 'error' => $res['error'] ?? 'NusaTrip kosong; fallback OYO nonaktif'];
            $res = hotelApiOyo($city);
            $source = 'oyorooms';
        }
    } else {
        $res = hotelApiOyo($city);
    }
    if (isset($res['error']) && empty($res['hotels'])) {
        return ['source' => $source, 'count' => 0, 'hotels' => [], 'error' => $res['error']];
    }

    $hotels = $res['hotels'] ?? [];

    $stars = (int)($opts['stars'] ?? 0);
    $minPrice = $opts['min_price'] ?? '';
    $maxPrice = $opts['max_price'] ?? '';
    $hotels = array_values(array_filter($hotels, function ($h) use ($stars, $minPrice, $maxPrice) {
        if ($stars > 0 && (int)($h['star'] ?? 0) !== $stars) return false;
        $p = (float)($h['price'] ?? 0);
        if ($minPrice !== '' && $minPrice !== null && $p > 0 && $p < (float)$minPrice) return false;
        if ($maxPrice !== '' && $maxPrice !== null && $p > 0 && $p > (float)$maxPrice) return false;
        return true;
    }));

    $sort = $opts['sort'] ?? 'price';
    usort($hotels, function ($a, $b) use ($sort) {
        if ($sort === 'price_desc') return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        if ($sort === 'stars') return ((int)($b['star'] ?? 0)) <=> ((int)($a['star'] ?? 0)) ?: (($a['price'] ?? 0) <=> ($b['price'] ?? 0));
        return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
    });

    if (!empty($opts['limit'])) $hotels = array_slice($hotels, 0, (int)$opts['limit']);

    return ['source' => $res['source'] ?? $source, 'count' => count($hotels), 'hotels' => $hotels, '_from_cache' => $res['_from_cache'] ?? false];
}

/**
 * Ambil detail hotel live dari sumber kota (cari item by external_id).
 * Untuk Booking.com gunakan hotelApiBookingHotel() langsung.
 */
function hotelApiFind(string $source, string $city, string $externalId, array $opts = []): array {
    $res = hotelApiSearch($city, array_merge($opts, ['source' => $source, 'limit' => 0]));
    foreach ($res['hotels'] ?? [] as $h) {
        if ((string)$h['external_id'] === (string)$externalId) return $h;
    }
    return ['error' => 'Hotel tidak ditemukan'];
}

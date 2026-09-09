<?php
// Duffel API v2 client — token override via env DUFFEL_TOKEN (lihat DEPLOY.md)
if (!defined('DUFFEL_TOKEN')) {
    define('DUFFEL_TOKEN', 'duffel_test_ghVya5DdJhoM2xlZ8Vxn-PWmWilHZy4shiNbBBrrG0p');
}
define('DUFFEL_BASE', 'https://api.duffel.com');
define('DUFFEL_VERSION', 'v2');
require_once __DIR__ . '/flight-cache.php';

function duffelRequest($method, $path, $body = null) {
    $ch = curl_init(DUFFEL_BASE . $path);
    $headers = ['Authorization: Bearer ' . DUFFEL_TOKEN, 'Duffel-Version: ' . DUFFEL_VERSION, 'Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['error' => $err, 'http_code' => 0];
    $data = json_decode($resp, true);
    if ($code >= 400) {
        $msg = $data['errors'][0]['message'] ?? "HTTP $code";
        return ['error' => $msg, 'http_code' => $code, 'raw' => $data];
    }
    return ['data' => $data['data'] ?? $data, 'http_code' => $code];
}

function parseIata($str) {
    // Extract IATA like "Jakarta (CGK)" -> "CGK", or "CGK" -> "CGK"
    if (preg_match('/\(([A-Z]{3})\)\s*$/', trim($str), $m)) return strtoupper($m[1]);
    $t = strtoupper(trim($str));
    if (preg_match('/^[A-Z]{3}$/', $t)) return $t;
    return null;
}

/**
 * Cari offers multi-slice (oneway/roundtrip/multicity).
 * - Mode lama: duffelSearchOffers($origin, $dest, $date, ...) tetap didukung.
 * - Mode multi: duffelSearchOffers(null, null, null, $cabin, $pax, [
 *     ['origin' => 'CGK (Jakarta)', 'destination' => 'DPS (Bali)', 'departure_date' => '2026-12-01'],
 *     ...
 *   ])
 */
function duffelSearchOffers($origin, $dest, $date, $cabinClass = 'economy', $passengers = 1, array $slices = []) {
    if (empty($slices)) {
        $slices = [['origin' => (string)$origin, 'destination' => (string)$dest, 'departure_date' => (string)$date]];
    }
    $slices = array_slice($slices, 0, 6); // Duffel max 6 slices
    if (count($slices) < 1) return ['error' => 'Minimal 1 leg.'];

    $parsedSlices = [];
    foreach ($slices as $i => $s) {
        $o = parseIata($s['origin'] ?? '');
        $d = parseIata($s['destination'] ?? '');
        if (!$o || !$d) return ['error' => 'Kode bandara tidak valid. Contoh: CGK, DPS, atau pilih dari daftar.'];
        if ($o === $d) return ['error' => 'Kota asal dan tujuan leg ' . ($i + 1) . ' tidak boleh sama.'];
        $ts = strtotime($s['departure_date'] ?? '');
        if (!$ts) return ['error' => 'Tanggal leg ' . ($i + 1) . ' tidak valid.'];
        $dateStr = date('Y-m-d', $ts);
        if ($dateStr < date('Y-m-d')) return ['error' => 'Tanggal leg ' . ($i + 1) . ' tidak boleh di masa lalu.'];
        if ($dateStr > date('Y-m-d', strtotime('+360 days'))) return ['error' => 'Tanggal leg ' . ($i + 1) . ' terlalu jauh (maks 360 hari).'];
        $parsedSlices[] = ['origin' => $o, 'destination' => $d, 'departure_date' => $dateStr];
    }
    $allowed = ['economy','premium_economy','business','first'];
    if (!in_array($cabinClass, $allowed)) $cabinClass = 'economy';
    $passengers = max(1, min(9, (int)$passengers));
    // Cache only single-city searches (not multi-city)
    $useCache = count($parsedSlices) === 1;
    if ($useCache) {
        $cacheKey = flightCacheKey('duffel', $parsedSlices[0]['origin'], $parsedSlices[0]['destination'], $parsedSlices[0]['departure_date'], $cabinClass, $passengers);
        $cached = flightCacheGet($cacheKey);
        if ($cached !== null) {
            $cached['_from_cache'] = true;
            return $cached;
        }
    }
    $pax = array_fill(0, $passengers, ['type' => 'adult']);
    $res = duffelRequest('POST', '/air/offer_requests', [
        'data' => ['slices' => $parsedSlices, 'passengers' => $pax, 'cabin_class' => $cabinClass]
    ]);
    if (isset($res['error'])) return $res;
    // offer_requests returns {data:{id, offers:[]}}
    $offers = $res['data']['offers'] ?? [];
    $result = ['offers' => $offers, 'offer_request_id' => $res['data']['id'] ?? null];
    // Store in cache (single-city only)
    if ($useCache) {
        flightCacheSet($cacheKey, 'duffel', $result, count($offers));
    }
    return $result;
}

function duffelGetOffer($offerId) {
    $res = duffelRequest('GET', "/air/offers/$offerId");
    if (isset($res['error'])) return $res;
    return ['offer' => $res['data']];
}

function duffelCreateOrder($offerId, $passengersData, $payments = null) {
    // passengersData: array of ['id'=>pass_id, 'given_name'=>, 'family_name'=>, 'born_on'=>, 'email'=>, 'phone_number'=>, 'gender'=>, 'title'=>]
    // Fetch offer to get currency/amount if payments not provided
    $offerRes = duffelGetOffer($offerId);
    if (isset($offerRes['error'])) return $offerRes;
    $offer = $offerRes['offer'];
    if (!$payments) {
        $payments = [['type' => 'balance', 'currency' => $offer['total_currency'], 'amount' => $offer['total_amount']]];
    }
    $res = duffelRequest('POST', '/air/orders', [
        'data' => ['type' => 'instant', 'selected_offers' => [$offerId], 'passengers' => $passengersData, 'payments' => $payments]
    ]);
    if (isset($res['error'])) return $res;
    return ['order' => $res['data']];
}

function duffelFormatDuration($iso) {
    if (!$iso) return '-';
    if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $iso, $m)) {
        $h = $m[1] ?? 0; $min = $m[2] ?? 0;
        if ($h && $min) return "{$h}j {$min}m";
        if ($h) return "{$h}j";
        return "{$min}m";
    }
    return $iso;
}

function duffelFormatPrice($amount, $currency) {
    // Convert EUR approx to IDR for display, simple rate
    $rates = ['EUR' => 17500, 'USD' => 16200, 'GBP' => 20500, 'IDR' => 1];
    $rate = $rates[$currency] ?? 1;
    if ($currency === 'IDR') return formatRupiah((float)$amount);
    $idr = (float)$amount * $rate;
    return formatRupiah($idr) . " <small class='text-muted' style='font-size:11px'>($currency " . number_format((float)$amount, 2) . ")</small>";
}

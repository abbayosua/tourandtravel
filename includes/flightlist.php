<?php
// FlightList (Kiwi/Tequila proxy) - gratis tanpa auth
// Primary source for demo; fallback ke Duffel/DB jika tidak terjangkau
define('FLIGHTLIST_BASE', 'https://www.flightlist.io/api/search.php');

function flightlistCabin($cabin) {
    return match($cabin) {
        'business' => 'C',
        'first' => 'F',
        'premium_economy' => 'W',
        default => 'M',
    };
}

function flightlistSearchOffers($origin, $dest, $date, $cabinClass = 'economy', $passengers = 1) {
    $originCode = parseIata($origin);
    $destCode = parseIata($dest);
    if (!$originCode || !$destCode) return ['error' => 'Kode bandara tidak valid. Contoh: CGK, DPS, atau pilih dari daftar.'];
    if ($originCode === $destCode) return ['error' => 'Kota asal dan tujuan tidak boleh sama.'];
    $ts = strtotime($date);
    if (!$ts) return ['error' => 'Tanggal tidak valid.'];
    $dateStr = date('Y-m-d', $ts);
    if ($dateStr < date('Y-m-d')) return ['error' => 'Tanggal keberangkatan tidak boleh di masa lalu.'];
    if ($dateStr > date('Y-m-d', strtotime('+360 days'))) return ['error' => 'Tanggal terlalu jauh (maks 360 hari).'];
    $passengers = max(1, min(9, (int)$passengers));
    $cabin = flightlistCabin($cabinClass);
    // FlightList expects DD/MM/YYYY
    $df = date('d/m/Y', $ts);
    $dt = $df; // oneway single date range same
    $params = [
        'fly_from' => 'airport:' . $originCode,
        'fly_to'   => 'airport:' . $destCode,
        'date_from' => $df,
        'date_to'   => $dt,
        'adults' => $passengers,
        'children' => 0,
        'infants' => 0,
        'selected_cabins' => $cabin,
        'curr' => 'USD',
        'limit' => 30,
        'sort' => 'price',
        'flight_type' => 'oneway',
        'max_stopovers' => 10,
        'max_fly_duration' => 60,
    ];
    $url = FLIGHTLIST_BASE . '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: TourAndTravel/1.0'],
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['error' => 'FlightList tidak terjangkau: ' . $err, 'unreachable' => true];
    if ($code >= 400 || $resp === false || $resp === '') return ['error' => 'FlightList tidak terjangkau (HTTP ' . $code . ')', 'unreachable' => true];
    $data = json_decode($resp, true);
    if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
        // Check if response is HTML (blocked)
        if (strpos($resp, '<html') !== false) return ['error' => 'FlightList dibatasi (HTML)', 'unreachable' => true];
        return ['error' => 'Format FlightList tidak valid', 'unreachable' => true];
    }
    $offers = $data['data'];
    // Persist to session for detail page
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!isset($_SESSION['flightlist_offers'])) $_SESSION['flightlist_offers'] = [];
    foreach ($offers as $o) {
        $_SESSION['flightlist_offers'][$o['id']] = $o;
    }
    // Keep only last 200 to avoid bloating
    if (count($_SESSION['flightlist_offers']) > 200) {
        $_SESSION['flightlist_offers'] = array_slice($_SESSION['flightlist_offers'], -200, null, true);
    }
    return ['offers' => $offers, 'search_id' => $data['search_id'] ?? null, 'currency' => $data['currency'] ?? 'USD'];
}

function flightlistFormatPrice($priceUsd) {
    // USD -> IDR approx 16200
    $idr = (float)$priceUsd * 16200;
    return formatRupiah($idr) . " <small class='text-muted' style='font-size:11px'>(\$" . number_format((float)$priceUsd, 0) . ")</small>";
}

function flightlistFormatDuration($seconds) {
    if (!$seconds) return '-';
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    if ($h && $m) return "{$h}j {$m}m";
    if ($h) return "{$h}j";
    return "{$m}m";
}

function flightlistGetOffer($id) {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    if (isset($_SESSION['flightlist_offers'][$id])) return ['offer' => $_SESSION['flightlist_offers'][$id]];
    // Try to fetch via minimal re-search -> not available without context, return not found
    return ['error' => 'Penerbangan FlightList tidak ditemukan (sesi kadaluarsa). Silakan cari ulang.'];
}

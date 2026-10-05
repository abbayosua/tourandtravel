<?php
/**
 * live-source.php — Urutan prioritas sumber live + toggle modul (1 jalur search+beli).
 *
 * Tiap sumber mengunci checkout-nya:
 *   hotel : nusatrip → native (nusatrip-book.php), oyo → link provider,
 *           booking → link provider, lokal → form lokal (hotel_bookings).
 *   flight: nusatrip → native (nusatrip-flight-book.php), duffel → order Duffel
 *           (flight-detail.php?offer_id=), flightlist → lanjut Kiwi/deep-link
 *           (flight-detail.php?fl_offer_id=), lokal → form lokal (flight_bookings).
 *
 * Setting (auto-create via setSetting, tanpa migrasi):
 *   hotel_source_order  CSV, default "nusatrip,oyo" (+ "lokal" = lewati live)
 *   flight_live_enabled master on/off, default "1"
 *   flight_source_order CSV, default "nusatrip,lokal"
 *   flight_duffel_enabled / flight_flightlist_enabled, default "0"
 * Legacy hotel_live_source (auto/nusatrip/oyo) dipakai bila order kosong.
 */

function liveModuleOn(string $settingKey, string $default = '1'): bool
{
    if (!function_exists('getSetting')) return $default === '1';
    return (string)getSetting($settingKey, $default) === '1';
}

/** Duffel aktif? Toggle admin (default mati — butuh API key funded). */
function duffelModuleEnabled(): bool
{
    return liveModuleOn('flight_duffel_enabled', '0');
}

/** FlightList aktif? Toggle admin (default mati). */
function flightlistModuleEnabled(): bool
{
    return liveModuleOn('flight_flightlist_enabled', '0');
}

/** Master flight live aktif? Mati → hanya jadwal lokal. */
function flightApiEnabled(): bool
{
    if (!function_exists('getSetting')) return true;
    return (string)getSetting('flight_live_enabled', '1') === '1';
}

/**
 * Parse CSV urutan sumber: buang duplikat & nilai tak dikenal.
 * 'lokal' selalu lolos (tanpa toggle — inventori sendiri).
 */
function liveParseOrder(string $raw, array $allowed): array
{
    $out = [];
    foreach (explode(',', $raw) as $p) {
        $p = strtolower(trim($p));
        if ($p !== '' && in_array($p, $allowed, true) && !in_array($p, $out, true)) $out[] = $p;
    }
    return $out;
}

/** Cek modul sumber menyala (lokal selalu menyala). */
function liveSourceOn(string $domain, string $src): bool
{
    if ($src === 'lokal') return true;
    if ($domain === 'hotel') {
        if ($src === 'nusatrip') return !function_exists('nusaModuleEnabled') || nusaModuleEnabled();
        if ($src === 'oyo') return !function_exists('oyoModuleEnabled') || oyoModuleEnabled();
        return false;
    }
    if ($src === 'nusatrip') return !function_exists('nusaModuleEnabled') || nusaModuleEnabled();
    if ($src === 'duffel') return duffelModuleEnabled();
    if ($src === 'flightlist') return flightlistModuleEnabled();
    return false;
}

/**
 * Urutan sumber hotel aktif. Default "nusatrip" (SUNSET OYO: oyo selalu
 * dikeluarkan dari order efektif; fungsi OYO tetap ada = reversibel).
 * Legacy hotel_live_source: auto → default; nusatrip/lokal → kunci; oyo (sunset) → nusatrip.
 */
function hotelLiveOrder(): array
{
    $allowed = ['nusatrip', 'oyo', 'lokal'];
    $raw = function_exists('getSetting') ? trim((string)getSetting('hotel_source_order', '')) : '';
    if ($raw === '' && function_exists('getSetting')) {
        $leg = trim((string)getSetting('hotel_live_source', 'auto'));
        if ($leg === 'nusatrip') return ['nusatrip'];
        if ($leg === 'lokal') return ['lokal'];
        $raw = 'nusatrip';
    }
    $order = liveParseOrder($raw === '' ? 'nusatrip' : $raw, $allowed);
    // SUNSET OYO: jangan pernah coba OYO di runtime.
    $order = array_values(array_filter($order, fn($s) => $s !== 'oyo'));
    return array_values(array_filter($order, fn($s) => liveSourceOn('hotel', $s)));
}

/**
 * Urutan sumber flight aktif. Default "nusatrip,lokal" (= perilaku kini).
 * Master mati → ["lokal"].
 */
function flightLiveOrder(): array
{
    $allowed = ['nusatrip', 'duffel', 'flightlist', 'lokal'];
    if (!flightApiEnabled()) return ['lokal'];
    $raw = function_exists('getSetting') ? trim((string)getSetting('flight_source_order', '')) : '';
    $order = liveParseOrder($raw === '' ? 'nusatrip,lokal' : $raw, $allowed);
    if (empty($order)) $order = ['nusatrip', 'lokal'];
    return array_values(array_filter($order, fn($s) => liveSourceOn('flight', $s)));
}

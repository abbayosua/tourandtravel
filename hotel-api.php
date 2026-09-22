<?php
/**
 * hotel-api.php — JSON proxy untuk live hotel API (Booking.com, OYO, NusaTrip).
 * Lihat HOTEL-ENDPOINTS.md + includes/hotelapi.php.
 *
 * Aksi (GET):
 *   ?action=autocomplete&q=Jakarta
 *   ?action=prices&cc=id&pagename=<slug>&start=YYYY-MM-DD&days=7&adults=2&rooms=1
 *   ?action=detail&hotel_id=6257224
 *   ?action=hotel&cc=id&pagename=<slug>&start=YYYY-MM-DD&days=7&adults=2&rooms=1
 *   ?action=oyo&q=jakarta
 *   ?action=nusatrip&rkey=<token>            (atau key=<angka>)
 *   ?action=nusa_auto&q=batam
 *   ?action=search&city=jakarta&source=auto&stars=&min_price=&max_price=&limit=&sort=price
 *   ?action=find&source=oyo&city=jakarta&external_id=12345
 *
 * Catatan: endpoint ini read-only (tanpa booking). Semua hasil live dari pihak ketiga.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/hotelapi.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

$action = $_GET['action'] ?? '';
$out = ['error' => 'unknown action'];
$status = 200;

try {
    switch ($action) {
        case 'autocomplete':
            $out = hotelApiAutocomplete($_GET['q'] ?? '');
            break;

        case 'prices':
            $cc = $_GET['cc'] ?? '';
            $pg = $_GET['pagename'] ?? '';
            if (!empty($_GET['hotel_url'])) {
                [$cc, $pg] = hotelApiParseBookingUrl($_GET['hotel_url']);
            }
            $raw = hotelApiBookingPrices((string)$cc, (string)$pg, $_GET['start'] ?? date('Y-m-d'), (int)($_GET['days'] ?? 7), (int)($_GET['adults'] ?? 2), (int)($_GET['rooms'] ?? 1));
            if ($raw === '') { $out = ['error' => 'cc/pagename tidak valid']; $status = 400; }
            else { echo $raw; exit; }
            break;

        case 'detail':
            $hid = (int)($_GET['hotel_id'] ?? 0);
            $raw = hotelApiBookingDetail($hid);
            if ($raw === '') { $out = ['error' => 'hotel_id tidak valid']; $status = 400; }
            else { echo $raw; exit; }
            break;

        case 'hotel':
            $cc = $_GET['cc'] ?? '';
            $pg = $_GET['pagename'] ?? '';
            if (!empty($_GET['hotel_url'])) {
                [$cc, $pg] = hotelApiParseBookingUrl($_GET['hotel_url']);
            }
            $out = hotelApiBookingHotel((string)$cc, (string)$pg, $_GET['start'] ?? date('Y-m-d'), (int)($_GET['days'] ?? 7), (int)($_GET['adults'] ?? 2), (int)($_GET['rooms'] ?? 1), (int)($_GET['hotel_id'] ?? 0));
            if (isset($out['error'])) $status = 400;
            break;

        case 'oyo':
            $out = hotelApiOyo($_GET['q'] ?? 'jakarta');
            if (isset($out['error']) && empty($out['hotels'])) $status = 502;
            break;

        case 'nusatrip':
            $out = hotelApiNusatrip((string)($_GET['rkey'] ?? $_GET['key'] ?? $_GET['token'] ?? ''));
            if (isset($out['error'])) $status = 400;
            break;

        case 'nusa_auto':
            $out = hotelApiNusaAuto($_GET['q'] ?? 'jakarta');
            break;

        case 'search':
            $out = hotelApiSearch((string)($_GET['city'] ?? ''), [
                'source' => $_GET['source'] ?? null,
                'stars' => $_GET['stars'] ?? 0,
                'min_price' => $_GET['min_price'] ?? '',
                'max_price' => $_GET['max_price'] ?? '',
                'limit' => $_GET['limit'] ?? 30,
                'sort' => $_GET['sort'] ?? 'price',
                'checkin' => $_GET['checkin'] ?? '',
                'checkout' => $_GET['checkout'] ?? '',
                'guests' => $_GET['guests'] ?? 2,
            ]);
            if (isset($out['error']) && empty($out['hotels'])) $status = 502;
            break;

        case 'nusa_rates':
            $r = nusaHotelRates((string)($_GET['hotel_id'] ?? ''), (string)($_GET['checkin'] ?? ''), (string)($_GET['checkout'] ?? ''), (int)($_GET['guests'] ?? 1));
            $out = $r['data'] ?? ['error' => 'rates gagal'];
            if (empty($out['rooms'] ?? null)) $status = 502;
            break;

        case 'nusa_detail':
            $r = nusaHotelDetail((string)($_GET['hotel_id'] ?? ''));
            $out = $r['data'] ?? ['error' => 'detail gagal'];
            if (!isset($out['name'])) $status = 502;
            break;

        case 'nusa_policy':
            $r = nusaCancelPolicy((string)($_GET['location_id'] ?? ''), (string)($_GET['hotel_id'] ?? ''), (string)($_GET['checkin'] ?? ''), (string)($_GET['checkout'] ?? ''), (string)($_GET['room_ref'] ?? ''));
            $out = $r['data'] ?? ['error' => 'policy gagal'];
            break;

        case 'find':
            $out = hotelApiFind((string)($_GET['source'] ?? 'oyo'), (string)($_GET['city'] ?? ''), (string)($_GET['external_id'] ?? ''));
            if (isset($out['error'])) $status = 404;
            break;
    }
} catch (Throwable $e) {
    $out = ['error' => 'exception: ' . $e->getMessage()];
    $status = 500;
}

http_response_code($status);
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

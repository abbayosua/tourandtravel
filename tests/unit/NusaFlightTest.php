<?php
/**
 * NusaFlightTest — validasi helper booking tiket pesawat NusaTrip (fungsi murni, tanpa network).
 * Lihat includes/nusatrip.php + nusatrip-flight-book.php.
 */

require_once __DIR__ . '/../../includes/nusatrip.php';

function testNusaNormalizeFlightFromOutbound() {
    $o = [
        'id' => 'IUIU851', 'airline_code' => 'IU', 'one_way_fare_idr' => 1107720,
        'duration' => 105, 'available_seat' => 1, 'baggage' => 15, 'class_type' => 'ECONOMY', 'param' => 'opaque',
        'segments' => [[
            'departure_airport_code' => 'BTH', 'arrival_airport_code' => 'CGK', 'airline_code' => 'IU',
            'flight_number' => 'IU851', 'departure_time' => '20260924080000', 'arrival_time' => '20260924094500',
        ]],
    ];
    $n = nusaNormalizeFlight($o, [['iataCode' => 'IU', 'name' => 'Super Air Jet']], 'BTH', 'CGK');
    assertSame('nusatrip', $n['source']);
    assertSame('Super Air Jet', $n['airline_name']);
    assertSame('IU851', $n['flight_number']);
    assertSame('BTH', $n['from']);
    assertSame('CGK', $n['to']);
    assertSame(0, $n['stops']);
    assertSame(1107720.0, $n['price']);
    assertSame('20260924080000', $n['dep']);
    assertSame('20260924094500', $n['arr']);
}

function testNusaNormalizeFlightCountsStops() {
    $o = ['segments' => [['departure_airport_code' => 'A'], ['departure_airport_code' => 'B'], ['departure_airport_code' => 'C']], 'one_way_fare' => 500];
    $n = nusaNormalizeFlight($o);
    assertSame(2, $n['stops'], '3 segmen → 2 transit');
    assertSame(500.0, $n['price'], 'fallback one_way_fare');
}

function testNusaFlightItemsShape() {
    $items = json_decode(nusaFlightItems([
        ['title' => 'MR', 'first' => 'Angga', 'last' => 'Saputra', 'birth' => '19900101'],
    ], 'BT123', 'domestic'), true);
    assertTrue(is_array($items) && count($items) === 1, 'satu item');
    assertSame('domestic', $items[0]['flightRoute']);
    assertSame('BT123', $items[0]['bookingTime']);
    $p = $items[0]['passengers'][0];
    assertSame('Angga', $p['firstName']);
    assertSame('19900101', $p['birthDate']);
    assertSame(0, $p['type'], 'dewasa = 0 (int)');
    assertSame('ID', $p['nationality']);
}

function testNusaFlightPaymentVaAndCard() {
    $va = json_decode(nusaFlightPayment(16, 'bankid123'), true);
    assertSame(16, $va['paymentInstrument']);
    assertSame('bankid123', $va['bankId']);
    $cc = json_decode(nusaFlightPayment(6), true);
    assertSame(6, $cc['paymentInstrument']);
    assertTrue(!array_key_exists('bankId', $cc), 'kartu tanpa bankId');
}

function testNusaFlightDobNormalizes() {
    assertSame('19900101', nusaFlightDob('01-01-1990'));
    assertSame('19900101', nusaFlightDob('1990-01-01'));
    assertSame('19900101', nusaFlightDob('19900101'));
}

function testNusaParseIataExtractsCode() {
    assertSame('CGK', nusaParseIata('Jakarta (CGK)'));
    assertSame('BTH', nusaParseIata('Batam (BTH) · Hang Nadim'));
    assertSame('CGK', nusaParseIata('Jakarta (CGK) · Soekarno Hatta'));
    assertSame('CGK', nusaParseIata('cgk'));
    assertSame('CGK', nusaParseIata('CGK'));
    assertSame(null, nusaParseIata('Batam'), 'tanpa kode IATA → null');
    assertSame(null, nusaParseIata(''));
}

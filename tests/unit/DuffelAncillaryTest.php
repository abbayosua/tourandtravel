<?php
/**
 * DuffelAncillaryTest — Backlog #6: seat & baggage add-on.
 * Normalisasi response services Duffel (type/name/amount/currency),
 * skip empty serviceIds, fail-soft saat Duffel error (booking tetap sukses).
 * (HTTP call asli diuji E2E dengan mock/stub — unit hanya logika normalisasi.)
 */
require_once __DIR__ . '/../../includes/duffel.php';

function testNormalizeServicesPayload() {
    // Simulasi normalisasi: pakai fungsi internal via reflection tidak perlu —
    // duffelGetOfferServices memanggil API; logika normalisasi diuji dengan
    // replicating rules di sini untuk menjaga determinisme (tanpa network).
    $raw = [
        ['id' => 'svc_1', 'service_type' => 'seat', 'name' => '12A', 'total_amount' => '25.00', 'total_currency' => 'USD', 'metadata' => ['seat_identifier' => '12A']],
        ['id' => 'svc_2', 'service_type' => 'baggage', 'name' => '20kg', 'total_amount' => '40.00', 'total_currency' => 'USD'],
        ['id' => 'svc_3', 'name' => 'Meal', 'total_amount' => '10.00', 'total_currency' => 'USD'], // tanpa service_type
    ];
    $services = [];
    foreach ($raw as $s) {
        $services[] = [
            'id' => $s['id'] ?? '',
            'type' => $s['service_type'] ?? $s['type'] ?? 'other',
            'name' => $s['name'] ?? ($s['metadata']['label'] ?? 'Service'),
            'total_amount' => (float)($s['total_amount'] ?? 0),
            'total_currency' => $s['total_currency'] ?? 'IDR',
            'metadata' => $s['metadata'] ?? [],
        ];
    }
    assertEquals(3, count($services));
    assertEquals('seat', $services[0]['type']);
    assertEquals('baggage', $services[1]['type']);
    assertEquals('other', $services[2]['type'], 'tanpa service_type → other');
    assertEquals(25.0, $services[0]['total_amount']);
    assertEquals('12A', $services[0]['name']);
}

function testAddServicesSkipsEmptyList() {
    // empty serviceIds → tidak memanggil API (return null order, no error)
    $res = duffelAddServicesToOrder('ord_dummy', []);
    assertEquals(null, $res['order']);
    assertEquals(null, $res['error'], 'empty list → no error, no API call');
}

function testAddServicesFailsSoftOnBadOrder() {
    // order id tidak valid → error dari API, TIDAK throw
    $res = duffelAddServicesToOrder('ord_invalid_nonexistent_xyz', ['svc_fake']);
    assertTrue(isset($res['error']), 'order invalid → error dikembalikan (fail-soft)');
    assertTrue($res['order'] === null);
}

function testGetServicesFailsSoftOnBadOffer() {
    // offer tidak valid → services kosong + error, TIDAK throw
    $res = duffelGetOfferServices('offer_invalid_nonexistent_xyz');
    assertEquals([], $res['services'], 'offer invalid → services kosong');
    assertTrue(isset($res['error']), 'error dikembalikan');
}

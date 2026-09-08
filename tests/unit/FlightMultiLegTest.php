<?php
/**
 * FlightMultiLegTest — logika render multi-leg (jumlah leg & stops & total segmen).
 */

function fmlCompute(array $offer): array {
    $slices = $offer['slices'] ?? [];
    $totalStops = 0;
    $totalSegments = 0;
    foreach ($slices as $sl) {
        $segs = $sl['segments'] ?? [];
        $totalSegments += count($segs);
        $totalStops += max(0, count($segs) - 1);
    }
    return ['legs' => count($slices), 'stops' => $totalStops, 'segments' => $totalSegments];
}

function testMultiLegCountsFromFixture() {
    $offer = json_decode((string)file_get_contents(__DIR__ . '/../fixtures/duffel-multileg-offer.json'), true);
    assertTrue(is_array($offer), 'fixture valid');
    $r = fmlCompute($offer);
    assertEquals(2, $r['legs'], '2 leg');
    assertEquals(1, $r['stops'], 'leg1 0 + leg2 1 transit');
    assertEquals(3, $r['segments'], 'total segmen 3');
}

function testSingleLegNoStops() {
    $offer = ['slices' => [['segments' => [['a' => 1]]]]];
    $r = fmlCompute($offer);
    assertEquals(1, $r['legs']);
    assertEquals(0, $r['stops']);
}

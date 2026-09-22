<?php
/**
 * MapLeafletTest — komponen peta Leaflet (includes/components/map-leaflet.php).
 * Regresi: aset harus lokal (assets/vendor/leaflet), bukan CDN + SRI rapuh.
 */

require_once __DIR__ . '/../../includes/components/map-leaflet.php';

function testRenderMapUsesLocalAssetsAndDiv() {
    $_SESSION['lang'] = 'id'; $_COOKIE['lang'] = 'id';
    $points = [['lat' => -6.2, 'lng' => 106.8, 'label' => 'X', 'price' => 'Rp1', 'link' => null]];
    ob_start();
    renderMap('testMapA', $points, -6.2, 106.8, 10);
    renderMap('testMapB', $points, -6.2, 106.8, 10);
    $html = ob_get_clean();
    assertContains('/assets/vendor/leaflet/leaflet.css', $html, 'leaflet css lokal');
    assertContains('/assets/vendor/leaflet/leaflet.js', $html, 'leaflet js lokal');
    assertContains('data-testid="leaflet-map"', $html);
    assertContains('L.map(el)', $html);
    assertContains('invalidateSize', $html);
    assertTrue(strpos($html, 'unpkg.com/leaflet') === false, 'tidak boleh pakai CDN unpkg');
    assertTrue(strpos($html, 'integrity=') === false, 'tidak boleh pakai SRI/integrity');
    // Aset hanya dimuat sekali walau dua peta.
    assertSame(1, substr_count($html, 'leaflet.css'), 'css hanya sekali');
}

function testRenderMapEmptyPointsFallback() {
    $_SESSION['lang'] = 'id'; $_COOKIE['lang'] = 'id';
    ob_start();
    renderMap('testMapEmpty', [], -6.2, 106.8, 10);
    $html = ob_get_clean();
    assertContains('Peta tidak tersedia', $html);
    assertTrue(strpos($html, 'data-testid="leaflet-map"') === false, 'tak ada div peta saat kosong');
}

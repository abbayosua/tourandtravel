<?php
/**
 * FlashSalesTest — engine: aktif, kadaluarsa, stok habis, persen diskon.
 * Seed berasal dari migrate-flash-sales.sql (tour 61 aktif 15%, 63 kadaluarsa, 64 stok habis).
 */

function testActiveFlashSaleGivesDiscount() {
    $r = getFlashSalePrice(1000.0, 'tour', 61);
    assertEquals(850.0, $r['price'], '1000 -15% = 850');
    assertTrue(is_array($r['flash']), 'flash sale aktif');
    assertEquals(15, (int)$r['flash']['discount_percent']);
}

function testExpiredFlashSaleFallsBack() {
    $r = getFlashSalePrice(1000.0, 'tour', 63);
    assertEquals(1000.0, $r['price'], 'kadaluarsa → harga normal');
    assertEquals(null, $r['flash']);
}

function testSoldOutFlashSaleFallsBack() {
    $r = getFlashSalePrice(1000.0, 'tour', 64);
    assertEquals(1000.0, $r['price'], 'stok habis → harga normal');
    assertEquals(null, $r['flash']);
}

function testUnknownItemHasNoFlash() {
    $r = getFlashSalePrice(500.0, 'tour', 999999);
    assertEquals(500.0, $r['price']);
    assertEquals(null, $r['flash']);
}

function testGetActiveFlashSaleReturnsRow() {
    $fs = getActiveFlashSale('tour', 62);
    assertTrue(is_array($fs), 'tour 62 aktif');
    assertEquals(15, (int)$fs['discount_percent']);
}

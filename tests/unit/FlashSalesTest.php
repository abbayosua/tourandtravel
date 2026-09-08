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

function testInactiveFlashSaleFallsBack() {
    db()->prepare("DELETE FROM flash_sales WHERE item_type='tour' AND item_id=65")->execute();
    db()->prepare("INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, is_active) VALUES ('tour', 65, 40, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 1 DAY, 10, 0)")->execute();
    try {
        $r = getFlashSalePrice(1000.0, 'tour', 65);
        assertEquals(1000.0, $r['price'], 'is_active=0 → harga normal');
        assertEquals(null, $r['flash']);
    } finally {
        db()->prepare("DELETE FROM flash_sales WHERE item_type='tour' AND item_id=65")->execute();
    }
}

function testNotYetStartedFlashSaleFallsBack() {
    db()->prepare("DELETE FROM flash_sales WHERE item_type='tour' AND item_id=65")->execute();
    db()->prepare("INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, is_active) VALUES ('tour', 65, 40, NOW() + INTERVAL 1 HOUR, NOW() + INTERVAL 1 DAY, 10, 1)")->execute();
    try {
        $r = getFlashSalePrice(1000.0, 'tour', 65);
        assertEquals(1000.0, $r['price'], 'belum mulai → harga normal');
        assertEquals(null, $r['flash']);
    } finally {
        db()->prepare("DELETE FROM flash_sales WHERE item_type='tour' AND item_id=65")->execute();
    }
}

function testSoldCountReachesLimitFallsBack() {
    // sold=limit → habis (item 65); sisa 1 → aktif (item 66, item berbeda karena cache per item)
    db()->prepare("DELETE FROM flash_sales WHERE item_type='tour' AND item_id IN (65, 66)")->execute();
    db()->prepare("INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, sold_count, is_active) VALUES ('tour', 65, 40, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 1 DAY, 2, 2, 1)")->execute();
    db()->prepare("INSERT INTO flash_sales (item_type, item_id, discount_percent, starts_at, ends_at, stock_limit, sold_count, is_active) VALUES ('tour', 66, 40, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 1 DAY, 2, 1, 1)")->execute();
    try {
        $r = getFlashSalePrice(1000.0, 'tour', 65);
        assertEquals(1000.0, $r['price'], 'sold=limit → harga normal');
        $r2 = getFlashSalePrice(1000.0, 'tour', 66);
        assertEquals(600.0, $r2['price'], 'sisa 1 → diskon 40%');
    } finally {
        db()->prepare("DELETE FROM flash_sales WHERE item_type='tour' AND item_id IN (65, 66)")->execute();
    }
}

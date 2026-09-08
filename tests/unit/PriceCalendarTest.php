<?php
/**
 * PriceCalendarTest — helper getPriceForDate().
 * Happy: harga dari price_calendar; fallback ke default/base.
 * Sad: tanggal invalid, item tak dikenal → default/null.
 */

function testPriceForDateFromCalendar() {
    // tour 61 ada di seed 90 hari (hari ini tercakup)
    $today = date('Y-m-d');
    $price = getPriceForDate('tour', 61, $today, 1.0);
    assertTrue($price > 0, 'harga dari kalender harus > 0');
    assertTrue($price > 0 && $price !== 1.0, 'harus ambil dari kalender, bukan default');
}

function testPriceForDateFallbackToDefault() {
    // tanggal jauh di luar seed → fallback default
    assertEquals(777.0, getPriceForDate('tour', 61, '2030-01-01', 777.0));
}

function testPriceForDateInvalidDateReturnsDefault() {
    assertEquals(55.0, getPriceForDate('tour', 61, 'bukan-tanggal', 55.0));
    assertEquals(66.0, getPriceForDate('tour', 61, '2026-13-99', 66.0));
}

function testPriceForDateUnknownItemReturnsNull() {
    assertEquals(null, getPriceForDate('tour', 99999999, '2026-01-01', null));
}

function testPriceForDateWeekendHigherThanBase() {
    // weekend seeded +15%: cari satu tanggal weekend dalam 7 hari & bandingkan dgn base
    $base = (float)db()->query("SELECT price FROM tours WHERE id = 61")->fetchColumn();
    $foundWeekend = false;
    for ($i = 0; $i < 7; $i++) {
        $d = date('Y-m-d', strtotime("+{$i} day"));
        if (in_array((int)date('N', strtotime($d)), [6, 7], true)) {
            $p = getPriceForDate('tour', 61, $d, 0.0);
            assertTrue($p > $base, "weekend $d ($p) harus > base ($base)");
            $foundWeekend = true;
        }
    }
    assertTrue($foundWeekend, 'minimal ada 1 weekend dalam 7 hari');
}

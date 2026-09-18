<?php
/**
 * PdfBrochureTest — template brosur ala Balindo (includes/pdf-brochure.php).
 */

require_once __DIR__ . '/../../includes/pdf-brochure.php';

function testBrochureDuration() {
    assertSame('8D7N', pdfBrochureDuration(['title' => '8D HUNAN ZHANGJIAJIE', 'duration_days' => null, 'duration_nights' => null], []), 'parse judul 8D');
    assertSame('2D1N', pdfBrochureDuration(['title' => 'Beijing Tour', 'duration_days' => null, 'duration_nights' => null], [['x'], ['x']]), '2 hari => 2D1N');
    assertSame('1D', pdfBrochureDuration(['title' => 'Day Tour', 'duration_days' => null, 'duration_nights' => null], [['x']]), '1 hari => 1D');
    assertSame('3D2N', pdfBrochureDuration(['title' => 'Trip', 'duration_days' => 3, 'duration_nights' => 2], []), 'kolom DB diutamakan');
}

function testBrochureParseDesc() {
    [$intro, $hi] = pdfBrochureParseDesc("Chongqing Tour\n\nHighlights:\n- Pesan untuk besok\n- Pembatalan gratis\n", 'Chongqing Wulong Karst Tour');
    assertTrue(strpos(implode(' ', $hi), 'Tour') === false, 'heading highlights tidak masuk chip');
    assertContains('Pesan untuk besok', implode('|', $hi), 'bullet masuk highlight');
    // judul tour tidak bocor ke intro
    assertTrue(strpos($intro, 'Chongqing Wulong') === false, 'judul tidak duplikat di intro');
}

function testBrochureHtmlStructure() {
    $tour = ['title' => 'Tour Test 8D', 'category' => 'China', 'location_city' => null, 'description' => "Jelajah seru.\n- A\n- B\n", 'price' => 1000000, 'price_currency' => 'IDR', 'max_participants' => 20, 'duration_days' => null, 'duration_nights' => null];
    $days = [['day_number' => 1, 'title' => 'Day 1 — Tiba', 'description' => 'Sampai hotel.', 'meals' => 'Dinner', 'accommodation' => 'Hotel X']];
    $deps = [['date' => '2026-10-14', 'price' => 8990000, 'currency' => 'IDR', 'slots' => null, 'slots_booked' => null]];
    $h = pdfTourBrochureHtml($tour, $days, '', $deps);
    assertContains('PROGRAM PERJALANAN', $h, 'ada tabel itinerary');
    assertContains('D1', $h, 'ada badge hari');
    assertTrue(strpos($h, 'Day 1 — Day 1') === false, 'tidak dobel prefix');
    assertContains('JADWAL KEBERANGKATAN', $h, 'ada tabel harga');
    assertContains('DAFTAR SEKARANG', $h, 'ada CTA');
}

function testBrochureLines() {
    assertSame(['A', 'B'], pdfBrochureLines("- A\n• B\n\n", 5), 'bullet dibersihkan');
    assertSame([], pdfBrochureLines(null), 'null → kosong');
}

function testBrochureNewColumns() {
    $tour = ['title' => 'Tour Full', 'category' => 'China', 'location_city' => null,
        'description' => 'Intro.', 'price' => 998, 'price_currency' => 'SGD', 'max_participants' => 30,
        'duration_days' => 8, 'duration_nights' => 7, 'route_cities' => 'A - B',
        'highlights' => "Bund\nWest Lake", 'includes' => "Tiket\nHotel",
        'excludes' => "Tipping", 'flight_info' => 'CGK - PVG',
        'meeting_point' => 'Terminal 3', 'important_notes' => "Deposit\nLunas H-21"];
    $deps = [['departure_date' => '2026-10-14', 'return_date' => '2026-10-21',
        'available_slots' => 30, 'booked' => 0,
        'price_adult' => 700, 'price_child' => 620, 'price_single' => 170, 'note' => 'Low Season']];
    $h = pdfTourBrochureHtml($tour, [], '', $deps);
    assertContains('HIGHLIGHTS', $h, 'ada highlights DB');
    assertContains('West Lake', $h, 'isi highlight DB');
    assertContains('JADWAL PENERBANGAN', $h, 'ada flight info');
    assertContains('Titik kumpul', $h, 'ada meeting point');
    assertContains('PAKET SUDAH TERMASUK', $h, 'ada includes');
    assertContains('PAKET BELUM TERMASUK', $h, 'ada excludes');
    assertContains('CATATAN PENTING', $h, 'ada notes');
    assertContains('ANAK', $h, 'tabel tier ada kolom anak');
    assertContains('Sisa 30 seat', $h, 'slot dari tour_dates');
    assertTrue(strpos($h, '&lt;br&gt;') === false, 'tanggal tidak ke-escape');
    assertContains('dalam SGD', $h, 'footnote pakai currency tour');
}

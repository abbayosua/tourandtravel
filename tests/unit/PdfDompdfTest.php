<?php
/**
 * PdfDompdfTest — renderer PDF Dompdf bersama (includes/pdf-dompdf.php).
 * Regresi: judul hari tidak dobel prefix, HTML terstruktur, output %PDF- valid.
 */

require_once __DIR__ . '/../../includes/pdf-dompdf.php';

function testPdfDayTitleNoDoublePrefix() {
    assertSame('Day 1 — Guilin', pdfDayTitle(1, 'Day 1 — Guilin'), 'judul DB tidak ditambah prefix');
    assertSame('Day 2 — Explore Guilin', pdfDayTitle(2, 'Day 2 — Explore Guilin'), 'judul DB tidak ditambah prefix');
    assertSame('Day 3 — Kuta', pdfDayTitle(3, 'Kuta'), 'judul polos diberi prefix');
    assertSame('Day 4', pdfDayTitle(4, ''), 'judul kosong jadi Day N');
}

function testPdfTourHtmlStructure() {
    $days = [
        ['day_number' => 1, 'title' => 'Day 1 — Guilin', 'description' => 'Hari pertama.', 'meals' => 'Breakfast', 'accommodation' => 'Hotel'],
    ];
    $h = pdfTourHtml('Tour Kuta', 'Category: Alam', '', $days);
    assertContains('TourAndTravel', $h, 'ada topbar brand');
    assertContains('Tour Kuta', $h, 'ada judul tour');
    assertContains('Day 1 — Guilin', $h, 'ada judul hari');
    assertTrue(strpos($h, 'Day 1 — Day 1') === false, 'tidak dobel prefix Day');
    assertContains('Breakfast', $h, 'ada badge meals');
    assertContains('Hotel', $h, 'ada badge akomodasi');
}

function testPdfUserHtmlStructure() {
    $days = [1 => ['items' => [
        ['item_type' => 'tour', 'tour_id' => 0, 'hotel_id' => 0, 'title' => 'Tour Kota Tua', 'time_label' => '09:00', 'note' => 'Bawa kamera'],
    ]], 2 => ['items' => []]];
    $h = pdfUserHtml('Trip Bali', 'Start: 20 Sep 2026', 'Budi', $days);
    assertContains('Budi', $h, 'ada nama user');
    assertContains('Tour Kota Tua', $h, 'ada judul item');
    assertContains('Free day', $h, 'hari kosong ada fallback');
}

function testPdfLocalImgMissingReturnsEmpty() {
    assertSame('', pdfLocalImg(''), 'path kosong → kosong');
    assertSame('', pdfLocalImg('https://example.com/x.jpg'), 'URL remote dilewati');
    assertSame('', pdfLocalImg('assets/img/placeholder.svg'), 'SVG dilewati');
    assertSame('', pdfLocalImg('uploads/tidak-ada-xyz.jpg'), 'file hilang → kosong');
}

function testPdfRendersValidPdf() {
    $h = pdfUserHtml('Trip Test', '', 'User', [1 => ['items' => []]]);
    $pdf = pdfNew();
    $pdf->loadHtml($h, 'UTF-8');
    $pdf->render();
    $out = $pdf->output();
    assertSame('%PDF-', substr($out, 0, 5), 'magic bytes PDF');
    assertTrue(strlen($out) > 500, 'PDF tidak kosong');
}

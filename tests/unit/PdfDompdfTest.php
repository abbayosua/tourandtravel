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
    assertContains(siteName(), $h, 'ada topbar brand');
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
    $prev = $_SESSION['lang'] ?? null;
    $_SESSION['lang'] = 'en';
    $h = pdfUserHtml('Trip Bali', 'Start: 20 Sep 2026', 'Budi', $days);
    assertContains('Budi', $h, 'ada nama user');
    assertContains('Tour Kota Tua', $h, 'ada judul item');
    assertContains('Free day', $h, 'hari kosong ada fallback');
    if ($prev === null) { unset($_SESSION['lang']); } else { $_SESSION['lang'] = $prev; }
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

function testPdfDayTitleLocalized() {
    assertSame('Day 3 — Kuta', pdfDayTitle(3, 'Kuta', 'en'));
    assertSame('Hari 3 — Kuta', pdfDayTitle(3, 'Kuta', 'id'));
    assertSame('第3天 — Kuta', pdfDayTitle(3, 'Kuta', 'zh'));
    assertSame('Day 1 — Guilin', pdfDayTitle(1, 'Day 1 — Guilin', 'id'), 'judul berprefix Day dibiarkan apa adanya');
    assertSame('Day 4', pdfDayTitle(4, '', 'en'));
}

function testPdfUserHtmlLocalized() {
    $days = [
        1 => ['items' => []],
        2 => ['items' => [['item_type' => 'tour', 'tour_id' => 0, 'hotel_id' => 0, 'title' => 'X', 'time_label' => '', 'note' => '']]],
    ];
    $prev = $_SESSION['lang'] ?? null;

    $_SESSION['lang'] = 'id';
    $id = pdfUserHtml('Trip', '', 'Budi', $days);
    assertContains('Hari 1', $id, 'hari bahasa id');
    assertContains('Hari bebas', $id, 'empty day bahasa id');

    $_SESSION['lang'] = 'zh';
    $zh = pdfUserHtml('Trip', '', 'Budi', $days);
    assertContains('第1天', $zh, 'hari bahasa zh');
    assertContains('自由活动', $zh, 'empty day bahasa zh');
    assertContains('旅行团', $zh, 'badge tipe bahasa zh');

    if ($prev === null) { unset($_SESSION['lang']); } else { $_SESSION['lang'] = $prev; }
}

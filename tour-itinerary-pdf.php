<?php
/**
 * tour-itinerary-pdf.php — Download PDF itinerary tour (publik, tanpa login)
 * GET /tour-itinerary-pdf.php?slug=xxx
 *
 * Render via Dompdf, template brosur ala Balindo — lihat includes/pdf-brochure.php.
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/pdf-dompdf.php';
require_once 'includes/pdf-brochure.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { http_response_code(404); die('Slug required.'); }

$pdfLang = strtolower(trim($_GET['pdf_lang'] ?? $_GET['lang'] ?? ''));
if ($pdfLang !== 'all' && !in_array($pdfLang, ['id', 'en', 'zh'], true)) $pdfLang = getCurrentLang();

$stmt = db()->prepare("SELECT * FROM tours WHERE slug = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$slug]);
$tour = $stmt->fetch();
if (!$tour) { http_response_code(404); die('Tour not found.'); }

$itineraries = getItineraries($tour['id']);
$coverImg = pdfLocalImg(getTourImage($tour, 'large'), 'class="coverimg"', 800);

$stmt = db()->prepare("SELECT departure_date, return_date, available_slots, booked, price_adult, price_child, price_single, price_twin, price_triple, note, note_en, note_zh FROM tour_dates WHERE tour_id = ? AND is_active = 1 AND departure_date >= CURDATE() ORDER BY departure_date LIMIT 12");
$stmt->execute([(int)$tour['id']]);
$deps = $stmt->fetchAll();

if ($pdfLang === 'all') {
    $html = pdfTourBrochureHtml($tour, $itineraries, $coverImg, $deps, 'en');
    $zhBody = pdfTourBrochureHtml($tour, $itineraries, $coverImg, $deps, 'zh');
    $zhBody = preg_replace('#^.*<body>#s', '', $zhBody);
    $zhBody = preg_replace('#</body>.*$#s', '', $zhBody);
    $html = str_replace('</body></html>', '<div class="page" style="page-break-before:always">' . $zhBody . '</div></body></html>', $html);
} else {
    $html = pdfTourBrochureHtml($tour, $itineraries, $coverImg, $deps, $pdfLang);
}

$filename = 'tour-itinerary-' . $tour['slug'] . '-' . $pdfLang . '.pdf';
$filename = substr(preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename), 0, 90) . '.pdf';

pdfStreamDownload(pdfNew($pdfLang === 'all' ? 'en' : $pdfLang), $html, $filename, '/tmp/tour_itinerary_output.pdf');

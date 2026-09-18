<?php
/**
 * itinerary-pdf.php — Generate PDF untuk itinerary user
 *
 * GET /itinerary-pdf.php?id=123
 * Dilindungi login: hanya pemilik itinerary yang bisa download.
 *
 * Render via Dompdf (HTML/CSS) — lihat includes/pdf-dompdf.php.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/pdf-dompdf.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(404);
    die('Itinerary ID required.');
}

$stmt = db()->prepare("SELECT * FROM user_itineraries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$itinerary = $stmt->fetch();

if (!$itinerary) {
    http_response_code(404);
    die('Itinerary not found.');
}

$stmt = db()->prepare(
    "SELECT d.id AS day_id, d.day_number, it.id AS item_id, it.item_type,
            it.tour_id, it.hotel_id, it.title, it.note, it.time_label, it.sort_order
     FROM user_itinerary_days d
     LEFT JOIN user_itinerary_items it ON it.day_id = d.id
     WHERE d.itinerary_id = ?
     ORDER BY d.day_number, it.sort_order, it.id"
);
$stmt->execute([$id]);
$rows = $stmt->fetchAll();

$days = [];
foreach ($rows as $r) {
    $dayNum = (int)$r['day_number'];
    if (!isset($days[$dayNum])) {
        $days[$dayNum] = ['items' => []];
    }
    if ($r['item_id']) {
        $days[$dayNum]['items'][] = $r;
    }
}

$userStmt = db()->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userName = $userStmt->fetchColumn() ?: 'User';

$sub = '';
if (!empty($itinerary['start_date'])) {
    $sub = 'Start: ' . date('d M Y', strtotime($itinerary['start_date']));
}

$html = pdfUserHtml($itinerary['title'], $sub, $userName, $days);

$filename = 'itinerary-' . $id . '-' . preg_replace('/[^a-zA-Z0-9]/', '_', $itinerary['title']) . '.pdf';
$filename = substr($filename, 0, 80) . '.pdf';

pdfStreamDownload(pdfNew(), $html, $filename);

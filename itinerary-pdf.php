<?php
/**
 * itinerary-pdf.php — Generate PDF untuk itinerary user
 * 
 * GET /itinerary-pdf.php?id=123
 * Dilindungi login: hanya pemilik itinerary yang bisa download.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/fpdf.php';

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

// Validasi ownership
$stmt = db()->prepare("SELECT * FROM user_itineraries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$itinerary = $stmt->fetch();

if (!$itinerary) {
    http_response_code(404);
    die('Itinerary not found.');
}

// Ambil days + items
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

// Group by day
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

// Ambil nama user
$userStmt = db()->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userName = $userStmt->fetchColumn() ?: 'User';

// ============================================================
// PDF Class
// ============================================================
class ItineraryPDF extends FPDF
{
    protected $bgFile = 'assets/img/bg-itinerary.jpg';
    protected $userId;
    protected $userName;

    public function setUserInfo($userId, $userName)
    {
        $this->userId = $userId;
        $this->userName = $userName;
    }

    public function Header()
    {
        // Background image (watermark style)
        if (is_file($this->bgFile)) {
            $this->Image($this->bgFile, 0, 0, 210, 297);
            // Overlay semi-transparent white (simulasi alpha via fill)
            $this->SetFillColor(255, 255, 255);
            // Alternatif: gambar background lalu timpa rect putih semi-transparan
            // FPDF tidak punya alpha native untuk fill, jadi gunakan blend via GD temp image
        }

        // Header bar
        $this->SetFillColor(0, 100, 210);
        $this->Rect(0, 0, 210, 14, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetXY(10, 4);
        $this->Cell(0, 6, 'TourAndTravel — ' . $this->userName . ' | Itinerary', 0, 0, 'C');
        $this->Ln(18);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb} — Generated ' . date('d M Y'), 0, 0, 'C');
    }

    /**
     * Ambil foto pertama dari tour/hotel untuk ditampilkan di PDF.
     */
    public function fetchPhoto($itemType, $tourId, $hotelId)
    {
        if ($itemType === 'tour' && $tourId) {
            $stmt = db()->prepare("SELECT photo FROM tours WHERE id = ? LIMIT 1");
            $stmt->execute([$tourId]);
            $photo = $stmt->fetchColumn();
            if ($photo && is_file($photo)) return $photo;
            // Coba path dengan BASE_URL
            if ($photo) return ltrim($photo, '/');
        }
        if ($itemType === 'hotel' && $hotelId) {
            $stmt = db()->prepare("SELECT image FROM hotels WHERE id = ? LIMIT 1");
            $stmt->execute([$hotelId]);
            $image = $stmt->fetchColumn();
            if ($image && is_file($image)) return $image;
            if ($image) return ltrim($image, '/');
        }
        return null;
    }
}

// ============================================================
// Generate PDF
// ============================================================
$pdf = new ItineraryPDF('P', 'mm', 'A4');
$pdf->setUserInfo($userId, $userName);
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// === Title Section ===
$pdf->SetFont('Helvetica', 'B', 18);
$pdf->SetTextColor(26, 26, 46);
$pdf->Cell(0, 10, $itinerary['title'], 0, 1, 'L');

if ($itinerary['start_date']) {
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(90, 97, 120);
    $pdf->Cell(0, 6, 'Start: ' . date('d M Y', strtotime($itinerary['start_date'])), 0, 1, 'L');
}

$pdf->Ln(4);

// Separator
$pdf->SetDrawColor(0, 100, 210);
$pdf->SetLineWidth(0.5);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(6);

// === Days ===
$itemIcons = [
    'tour' => "\xE2\x9C\x88",    // plane-like (will use text)
    'hotel' => "\xF0\x9F\x8F\xA8", // hotel emoji
    'flight' => "\xE2\x9C\x88",
    'custom' => "\xE2\x9C\x85",   // checkmark
];

$typeLabels = [
    'tour' => 'Tour',
    'hotel' => 'Hotel',
    'flight' => 'Flight',
    'custom' => 'Activity',
];

$typeColors = [
    'tour' => [0, 100, 210],
    'hotel' => [220, 53, 69],
    'flight' => [33, 37, 41],
    'custom' => [13, 202, 240],
];

if (empty($days)) {
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 10, 'No activities planned yet.', 0, 1, 'C');
}

foreach ($days as $dayNum => $day) {
    // Day header
    $pdf->SetFillColor(240, 245, 250);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->SetTextColor(0, 100, 210);
    
    // Day card background
    $startY = $pdf->GetY();
    $pdf->Rect(10, $startY, 190, 8, 'F');
    $pdf->SetXY(14, $startY + 1);
    $pdf->Cell(0, 6, 'Day ' . $dayNum, 0, 1, 'L');
    $pdf->Ln(4);

    if (empty($day['items'])) {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetX(14);
        $pdf->Cell(0, 6, 'Free day / no activities', 0, 1, 'L');
        $pdf->Ln(2);
        continue;
    }

    foreach ($day['items'] as $item) {
        $type = $item['item_type'];
        $color = $typeColors[$type] ?? [108, 117, 125];
        $label = $typeLabels[$type] ?? 'Custom';

        // Item card
        $x0 = 14;
        $y0 = $pdf->GetY();
        $cardW = 182;

        // Card border
        $pdf->SetDrawColor(220, 220, 225);
        $pdf->SetFillColor(252, 252, 254);
        $pdf->Rect($x0, $y0, $cardW, 1, 'F'); // placeholder height, will extend

        // Left color bar
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x0, $y0, 3, 8, 'F'); // height updated later

        // Type badge
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetXY($x0 + 8, $y0 + 1);
        $pdf->Cell(20, 4, strtoupper($label), 0, 1, 'L');

        // Title
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(26, 26, 46);
        $pdf->SetX($x0 + 8);
        $pdf->MultiCell($cardW - 50, 6, $item['title'], 0, 'L');
        $titleBottom = $pdf->GetY();

        // Time label
        if ($item['time_label']) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(90, 97, 120);
            $pdf->SetX($x0 + 8);
            $pdf->Cell(0, 5, "\xE2\x8F\xB0 " . $item['time_label'], 0, 1, 'L');
        }

        // Note
        if ($item['note']) {
            $pdf->SetFont('Helvetica', 'I', 9);
            $pdf->SetTextColor(108, 117, 125);
            $pdf->SetX($x0 + 8);
            $pdf->MultiCell($cardW - 20, 5, $item['note'], 0, 'L');
        }

        // Photo
        $photoPath = $pdf->fetchPhoto($type, (int)$item['tour_id'], (int)$item['hotel_id']);
        $photoX = $x0 + $cardW - 48;
        $photoY = $y0 + 2;
        if ($photoPath && is_file($photoPath)) {
            // Resize proportionally to fit ~40x30mm
            $pdf->Image($photoPath, $photoX, $photoY, 40, 0, 'JPG');
        }

        $itemBottom = max($pdf->GetY(), $y0 + 10);

        // Draw card border height
        $pdf->SetDrawColor(220, 220, 225);
        $pdf->Rect($x0, $y0, $cardW, $itemBottom - $y0, 'D');

        // Update left bar height
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x0, $y0, 3, $itemBottom - $y0, 'F');

        $pdf->SetY($itemBottom + 3);
    }

    // Separator antar day
    $pdf->SetDrawColor(230, 230, 235);
    $pdf->SetLineWidth(0.2);
    $pdf->Line(14, $pdf->GetY(), 196, $pdf->GetY());
    $pdf->Ln(4);
}

// === Footer brand message ===
$pdf->Ln(10);
$pdf->SetFont('Helvetica', 'I', 9);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 6, 'Generated by TourAndTravel — Your World of Joy', 0, 1, 'C');

// Output
$filename = 'itinerary-' . $id . '-' . preg_replace('/[^a-zA-Z0-9]/', '_', $itinerary['title']) . '.pdf';
$filename = substr($filename, 0, 80) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$pdf->Output('D', $filename);
exit;

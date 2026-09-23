<?php
/**
 * pdf-dompdf.php — Shared Dompdf renderer untuk itinerary PDF.
 *
 * Pengganti FPDF manual: layout ditulis sebagai HTML/CSS (mudah dibaguskan),
 * lalu Dompdf yang render ke PDF. Font DejaVu Sans (bundled Dompdf) agar
 * karakter unicode (—, •, aksen) tidak pecah.
 */

if (!class_exists('Dompdf\Dompdf') && is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Dompdf\Dompdf;
use Dompdf\Options;

function pdfFontFamily(?string $lang = null): string
{
    return ($lang ?? getCurrentLang()) === 'zh' ? 'NotoSansSC' : 'DejaVu Sans';
}

function pdfRegisterCjkFont(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $ttf = __DIR__ . '/../assets/fonts/NotoSansSC-Regular.ttf';
    if (!is_file($ttf) || !class_exists('Dompdf\Dompdf')) return;
    try {
        $opt = new Options();
        $fontDir = $opt->getFontDir();
        $dest = rtrim($fontDir, '/\\') . '/NotoSansSC-Regular.ttf';
        if (!is_file($dest)) @copy($ttf, $dest);
        $tmp = new Dompdf($opt);
        $tmp->getFontMetrics()->registerFont(['family' => 'NotoSansSC', 'weight' => 'normal', 'style' => 'normal'], 'file://' . $dest);
        $tmp->getFontMetrics()->registerFont(['family' => 'NotoSansSC', 'weight' => 'bold', 'style' => 'normal'], 'file://' . $dest);
    } catch (Throwable $e) {
    }
}

function pdfNew(?string $lang = null): Dompdf
{
    $opt = new Options();
    $lang = $lang ?? getCurrentLang();
    $font = $lang === 'zh' ? 'NotoSansSC' : 'DejaVu Sans';
    if ($lang === 'zh') pdfRegisterCjkFont();
    $opt->set('defaultFont', $font);
    $opt->set('isRemoteEnabled', false);
    $opt->set('isHtml5ParserEnabled', true);
    $opt->set('chroot', dirname(__DIR__));
    $opt->set('tempDir', sys_get_temp_dir());
    $pdf = new Dompdf($opt);
    $pdf->setPaper('A4', 'portrait');
    return $pdf;
}

/**
 * Ubah path lokal (uploads/..., assets/...) jadi <img> data-URI agar Dompdf
 * tidak perlu remote/chroot. URL remote & SVG dilewati (return '').
 */
function pdfLocalImg(string $path, string $attrs = '', int $maxW = 0): string
{
    $path = trim($path);
    if ($path === '') return '';
    if (defined('BASE_URL') && str_starts_with($path, BASE_URL)) {
        $path = substr($path, strlen(BASE_URL));
    }
    $path = ltrim($path, '/');
    if (preg_match('#^https?://#i', $path) || preg_match('#^data:#i', $path)) return '';
    if (str_ends_with(strtolower($path), '.svg')) return '';
    $full = dirname(__DIR__) . '/' . $path;
    if (!is_file($full)) return '';
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    if ($ext === 'webp' || ($maxW > 0 && in_array($ext, ['jpg', 'jpeg', 'png'], true))) {
        $img = $ext === 'png' ? @imagecreatefrompng($full) : @imagecreatefromstring(@file_get_contents($full));
        if ($img) {
            $w = imagesx($img);
            if ($maxW > 0 && $w > $maxW) {
                $h = imagesy($img);
                $nw = $maxW;
                $nh = (int)round($h * $nw / $w);
                $small = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($small, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($img);
                $img = $small;
            }
            ob_start();
            imagejpeg($img, null, 78);
            $data = ob_get_clean();
            imagedestroy($img);
            if ($data) return '<img src="data:image/jpeg;base64,' . base64_encode($data) . '" ' . $attrs . ' />';
            return '';
        }
    }
    $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
    $data = @file_get_contents($full);
    if (!$data) return '';
    return '<img src="data:' . $mime . ';base64,' . base64_encode($data) . '" ' . $attrs . ' />';
}

/** Judul hari tanpa dobel prefix: DB sudah menyimpan "Day 1 — ...". */
function pdfDayTitle(int $dayNum, string $title): string
{
    $title = trim($title);
    if (preg_match('/^day\s*\d+\s*[\x{2014}\x{2013}\-]/iu', $title)) return $title;
    if ($title === '') return 'Day ' . $dayNum;
    return 'Day ' . $dayNum . ' — ' . $title;
}

function pdfBaseCss(): string
{
    return <<<CSS
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1a1a2e; margin: 0; }
    .topbar { background: #0064d2; color: #fff; text-align: center; font-size: 11pt; font-weight: bold; padding: 10px 0; margin: 0 0 14px 0; }
    .cover { width: 100%; display: block; margin-bottom: 10px; }
    h1 { font-size: 20pt; margin: 0 0 4px 0; color: #1a1a2e; }
    .meta { color: #5a6178; font-size: 9pt; margin-bottom: 8px; }
    hr.blue { border: 0; border-top: 2px solid #0064d2; margin: 8px 0 14px 0; }
    .day { background: #f0f5fa; border-left: 4px solid #0064d2; padding: 6px 10px; margin: 14px 0 8px 0; page-break-inside: avoid; page-break-after: avoid; }
    .day h2 { font-size: 12pt; color: #0064d2; margin: 0; }
    .desc { color: #3c3c50; font-size: 10pt; margin: 4px 0 6px 0; }
    .badge { display: inline-block; color: #fff; font-size: 8pt; padding: 2px 8px; margin: 0 4px 4px 0; }
    .badge.meal { background: #28a745; }
    .badge.acc { background: #17a2b8; }
    .badge.type-tour { background: #0064d2; }
    .badge.type-hotel { background: #dc3545; }
    .badge.type-flight { background: #212529; }
    .badge.type-custom { background: #0dcaf0; }
    table.item { width: 100%; border: 1px solid #dcdce1; background: #fcfcfe; margin: 6px 0; border-collapse: collapse; page-break-inside: avoid; }
    table.item td { vertical-align: top; padding: 6px 8px; }
    table.item td.bar-tour { background: #0064d2; width: 4px; padding: 0; }
    table.item td.bar-hotel { background: #dc3545; width: 4px; padding: 0; }
    table.item td.bar-flight { background: #212529; width: 4px; padding: 0; }
    table.item td.bar-custom { background: #0dcaf0; width: 4px; padding: 0; }
    .item-title { font-weight: bold; font-size: 11pt; }
    .item-time { color: #5a6178; font-size: 9pt; }
    .item-note { color: #6c757d; font-size: 9pt; font-style: italic; }
    .brand { text-align: center; color: #969696; font-size: 9pt; font-style: italic; margin-top: 16px; }
    .empty { text-align: center; color: #969696; font-size: 11pt; padding: 20px 0; }
    CSS;
}

/**
 * HTML untuk PDF itinerary tour (publik). $days: tiap item
 * ['day_number','title','description','meals','accommodation'].
 */
function pdfTourHtml(string $title, string $meta, string $coverImgTag, array $days): string
{
    $h = '<html><head><meta charset="utf-8"><style>' . pdfBaseCss() . '</style></head><body>';
    $h .= '<div class="topbar">TourAndTravel &mdash; Tour Itinerary</div>';
    if ($coverImgTag !== '') $h .= '<div>' . $coverImgTag . '</div>';
    $h .= '<h1>' . e($title) . '</h1>';
    if ($meta !== '') $h .= '<div class="meta">' . e($meta) . '</div>';
    $h .= '<hr class="blue" />';
    if (empty($days)) {
        $h .= '<div class="empty">No itinerary details available.</div>';
    }
    foreach ($days as $d) {
        $h .= '<div class="day"><h2>' . e(pdfDayTitle((int)$d['day_number'], $d['title'] ?? '')) . '</h2></div>';
        if (!empty($d['description'])) $h .= '<div class="desc">' . e(strip_tags($d['description'])) . '</div>';
        if (!empty($d['meals'])) $h .= '<span class="badge meal">' . e($d['meals']) . '</span>';
        if (!empty($d['accommodation'])) $h .= '<span class="badge acc">' . e($d['accommodation']) . '</span>';
    }
    $h .= '<div class="brand">Generated by TourAndTravel &mdash; Your World of Joy</div>';
    return $h . '</body></html>';
}

/**
 * HTML untuk PDF itinerary milik user. $days: [dayNum => ['items' => [...]]],
 * tiap item ['item_type','tour_id','hotel_id','title','time_label','note'].
 */
function pdfUserHtml(string $title, string $sub, string $userName, array $days): string
{
    $labels = ['tour' => 'Tour', 'hotel' => 'Hotel', 'flight' => 'Flight', 'custom' => 'Activity'];
    $h = '<html><head><meta charset="utf-8"><style>' . pdfBaseCss() . '</style></head><body>';
    $h .= '<div class="topbar">TourAndTravel &mdash; ' . e($userName) . ' | Itinerary</div>';
    $h .= '<h1>' . e($title) . '</h1>';
    if ($sub !== '') $h .= '<div class="meta">' . e($sub) . '</div>';
    $h .= '<hr class="blue" />';
    if (empty($days)) {
        $h .= '<div class="empty">No activities planned yet.</div>';
    }
    foreach ($days as $dayNum => $day) {
        $h .= '<div class="day"><h2>Day ' . (int)$dayNum . '</h2></div>';
        if (empty($day['items'])) {
            $h .= '<div class="desc">Free day / no activities</div>';
            continue;
        }
        foreach ($day['items'] as $it) {
            $type = $it['item_type'] ?? 'custom';
            $bar = in_array($type, ['tour', 'hotel', 'flight'], true) ? $type : 'custom';
            $h .= '<table class="item"><tr><td class="bar-' . $bar . '"></td><td>';
            $h .= '<span class="badge type-' . $bar . '">' . strtoupper($labels[$type] ?? 'Activity') . '</span>';
            $h .= '<div class="item-title">' . e($it['title'] ?? '') . '</div>';
            if (!empty($it['time_label'])) $h .= '<div class="item-time">' . e($it['time_label']) . '</div>';
            if (!empty($it['note'])) $h .= '<div class="item-note">' . e($it['note']) . '</div>';
            $h .= '</td><td width="130">' . pdfPhotoForItem($type, (int)($it['tour_id'] ?? 0), (int)($it['hotel_id'] ?? 0)) . '</td></tr></table>';
        }
    }
    $h .= '<div class="brand">Generated by TourAndTravel &mdash; Your World of Joy</div>';
    return $h . '</body></html>';
}

/** Foto tour/hotel untuk item itinerary user; '' bila tidak ada lokal. */
function pdfPhotoForItem(string $type, int $tourId, int $hotelId): string
{
    try {
        if ($type === 'tour' && $tourId) {
            $stmt = db()->prepare("SELECT cover_image, wiki_image FROM tours WHERE id = ? LIMIT 1");
            $stmt->execute([$tourId]);
            $row = $stmt->fetch();
            if ($row) {
                foreach (['cover_image', 'wiki_image'] as $c) {
                    if (!empty($row[$c])) {
                        $tag = pdfLocalImg('uploads/' . ltrim($row[$c], '/'), 'width="120"');
                        if ($tag !== '') return $tag;
                    }
                }
            }
        }
        if ($type === 'hotel' && $hotelId) {
            $stmt = db()->prepare("SELECT cover_image FROM hotels WHERE id = ? LIMIT 1");
            $stmt->execute([$hotelId]);
            $img = $stmt->fetchColumn();
            if ($img) {
                $tag = pdfLocalImg('uploads/' . ltrim($img, '/'), 'width="120"');
                if ($tag !== '') return $tag;
            }
        }
    } catch (Throwable $e) {
    }
    return '';
}

/** Render HTML ke PDF lalu download (web) atau tulis file (CLI). */
function pdfStreamDownload(Dompdf $pdf, string $html, string $filename, ?string $cliPath = null): void
{
    $pdf->loadHtml($html, 'UTF-8');
    $pdf->render();
    try {
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $pdf->getCanvas()->page_text(250, 815, 'Page {PAGE_NUM} / {PAGE_COUNT} - TourAndTravel', $font, 8, [0.6, 0.6, 0.6]);
    } catch (Throwable $e) {
    }
    if (php_sapi_name() === 'cli') {
        $out = $cliPath ?: '/tmp/tat_pdf_output.pdf';
        file_put_contents($out, $pdf->output());
        echo 'OK: ' . $out . ' (' . filesize($out) . ' bytes)';
        exit;
    }
    $pdf->stream($filename, ['Attachment' => true]);
    exit;
}

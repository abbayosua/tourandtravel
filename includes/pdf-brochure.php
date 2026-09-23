<?php
/**
 * pdf-brochure.php — Template brosur tour ala Balindo untuk Dompdf.
 *
 * Gaya: frame emas (#d4b86a), navy (#0f2b6b), hero durasi besar, tabel
 * itinerary HARI|PROGRAM|HOTEL|MEALS, jadwal + tier harga, flight info,
 * include/exclude, catatan penting, CTA. Semua dinamis dari DB:
 * tours (highlights, includes, excludes, flight_info, meeting_point,
 * important_notes, route_cities, duration_*) + itineraries + tour_dates.
 * Layout pakai tabel (aman untuk Dompdf, tanpa flex/JS).
 */

function pdfBrochureCss(string $lang = 'id'): string
{
    $font = $lang === 'zh' ? "'NotoSansSC', 'DejaVu Sans', sans-serif" : "'DejaVu Sans', sans-serif";
    return <<<CSS
    @page { size: A4 portrait; margin: 9mm 8mm 12mm 8mm; }
    body { font-family: $font; font-size: 9pt; color: #1a1a2e; margin: 0; }
    .page { border: 2.5px solid #d4b86a; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; page-break-inside: auto; }
    .secbar, .secbar-green, .secbar-red { page-break-after: avoid; }
    table.itin tr, table.price tr { page-break-inside: avoid; }
    table.head { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.head td { vertical-align: top; padding: 0; }
    .brand { font-size: 13pt; font-weight: bold; color: #0f2b6b; letter-spacing: 2px; }
    .brand-sub { font-size: 7pt; letter-spacing: 3px; color: #0f2b6b; font-weight: bold; }
    .head-right { text-align: right; font-size: 7pt; color: #888; width: 170px; }
    .hero { background: #0f2b6b; border-radius: 8px; padding: 12px 14px; margin: 6px 0 8px 0; }
    .hero table { width: 100%; border-collapse: collapse; }
    .hero table td { vertical-align: middle; padding: 0; }
    .dur { color: #d4b86a; font-size: 34pt; font-weight: bold; width: 130px; }
    .hero-title { color: #ffffff; font-size: 13pt; font-weight: bold; }
    .hero-sub { color: #d4b86a; font-size: 8pt; margin-top: 2px; }
    .tagline { font-size: 9pt; color: #33334d; margin: 6px 0 8px 0; }
    .coverimg { width: 100%; display: block; border-radius: 6px; margin: 4px 0 8px 0; }
    .secbar { background: #0f2b6b; color: #fff; font-weight: bold; font-size: 9pt;
      letter-spacing: 1px; text-align: center; border-radius: 6px; padding: 6px 0; margin: 10px 0 6px 0; }
    .secbar-green { background: #1a7a33; color: #fff; font-weight: bold; font-size: 9pt;
      letter-spacing: 1px; text-align: center; border-radius: 6px; padding: 6px 0; margin: 10px 0 6px 0; }
    .secbar-red { background: #b02a37; color: #fff; font-weight: bold; font-size: 9pt;
      letter-spacing: 1px; text-align: center; border-radius: 6px; padding: 6px 0; margin: 10px 0 6px 0; }
    table.chips { width: 100%; border-collapse: collapse; }
    table.chips td { width: 50%; vertical-align: top; padding: 2px 3px; }
    .chip { background: #faf6ea; border: 1px solid #d4b86a; border-radius: 6px;
      padding: 4px 7px; font-size: 8pt; color: #5a4a1a; }
    table.info { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.info td { border: 1px solid #d4b86a; background: #faf6ea; padding: 5px 8px; font-size: 8pt; width: 25%; }
    table.info .k { color: #888; font-size: 7pt; }
    table.info .v { font-weight: bold; color: #0f2b6b; font-size: 9pt; }
    .flight { background: #0f2b6b; color: #ffffff; border-radius: 6px; padding: 5px 9px; font-size: 8.5pt; margin: 3px 0; }
    .meet { background: #faf6ea; border: 1px solid #d4b86a; border-radius: 6px; padding: 5px 9px; font-size: 8pt; color: #5a4a1a; margin-top: 4px; }
    table.itin { width: 100%; border-collapse: collapse; }
    table.itin th { background: #0f2b6b; color: #fff; font-size: 8pt; padding: 6px 6px; }
    table.itin td { border: 1px solid #ddd; vertical-align: top; padding: 5px 6px; font-size: 8pt; }
    .daybadge { background: #d4b86a; color: #0f2b6b; font-weight: bold; border-radius: 4px;
      padding: 2px 6px; font-size: 8pt; white-space: nowrap; }
    .daytitle { font-weight: bold; color: #0f2b6b; }
    .prog { color: #33334d; margin-top: 2px; }
    table.price { width: 100%; border-collapse: collapse; }
    table.price th { background: #0f2b6b; color: #fff; font-size: 7.5pt; padding: 6px 4px; }
    table.price td { border: 1px solid #ddd; padding: 5px 4px; font-size: 8pt; text-align: center; }
    table.price td.l { text-align: left; }
    .avail { color: #1a7a33; font-weight: bold; }
    .full { color: #b02a37; font-weight: bold; }
    ul.tick { margin: 4px 0 4px 2px; padding: 0; font-size: 8.5pt; color: #33334d; list-style: none; }
    ul.tick li { margin-bottom: 3px; }
    ul.notes { margin: 4px 0 4px 16px; padding: 0; font-size: 8.5pt; color: #33334d; }
    ul.notes li { margin-bottom: 2px; }
    .cta { background: #0f2b6b; border-radius: 8px; padding: 10px 14px; margin-top: 10px; text-align: center; }
    .cta .t1 { color: #d4b86a; font-weight: bold; font-size: 11pt; }
    .cta .t2 { color: #fff; font-size: 8pt; margin-top: 2px; }
    .foot { text-align: center; color: #999; font-size: 7pt; margin-top: 8px; }
    CSS;
}

/** Durasi brosur: kolom duration_* dulu, lalu parse judul ("8D ..."), lalu jumlah hari. */
function pdfBrochureDuration(array $tour, array $days): string
{
    $d = (int)($tour['duration_days'] ?? 0);
    $n = (int)($tour['duration_nights'] ?? 0);
    if ($d <= 0 && preg_match('/(\d{1,2})\s*D/i', $tour['title'] ?? '', $m)) $d = (int)$m[1];
    if ($d <= 0) $d = max(1, count($days));
    if ($n <= 0 && $d > 1) $n = $d - 1;
    return $n > 0 ? $d . 'D' . $n . 'N' : $d . 'D';
}

/** Ambil intro + highlight dari deskripsi tour (baris bullet -, •, emoji). */
function pdfBrochureParseDesc(string $desc, string $title = ''): array
{
    $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $desc));
    $intro = [];
    $hi = [];
    $addHi = function (string $x) use (&$hi) {
        $x = trim($x);
        if ($x === '' || count($hi) >= 6) return;
        if (preg_match('/^(tour highlights|highlights|package includes|the package includes|itinerary|highlights\s*:)/i', $x)) return;
        foreach ($hi as $old) {
            if (mb_stripos($old, mb_substr($x, 0, 40)) !== false || mb_stripos($x, mb_substr($old, 0, 40)) !== false) return;
        }
        $hi[] = mb_substr($x, 0, 90);
    };
    foreach (preg_split('/\r?\n/', $plain) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if ($title !== '' && mb_stripos($title, mb_substr($line, 0, 30)) !== false && mb_strlen($line) < mb_strlen($title) + 20) continue;
        if (preg_match('/^[-•*▪·]+\s*(.+)$/u', $line, $m)) {
            $addHi($m[1]);
        } elseif (count($hi) === 0 && count($intro) < 2 && mb_strlen($line) > 20) {
            $intro[] = $line;
        } elseif (preg_match('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $line)) {
            $addHi(preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]+\s*/u', '', $line));
        }
    }
    return [implode(' ', $intro), $hi];
}

/** Pecah teks satu-item-per-baris jadi list bersih (max $max). */
function pdfBrochureLines(?string $text, int $max = 12): array
{
    $out = [];
    foreach (preg_split('/\r?\n/', (string)$text) as $line) {
        $line = trim(preg_replace('/^[-•*▪·\s]+/u', '', trim($line)));
        if ($line === '') continue;
        $out[] = mb_substr($line, 0, 160);
        if (count($out) >= $max) break;
    }
    return $out;
}

/**
 * HTML brosur tour. $tour: row tours. $days: rows itineraries.
 * $coverImgTag: <img> data-URI atau ''. $deps: rows tour_dates
 * (departure_date, return_date, available_slots, booked, price_adult,
 * price_child, price_single, note) ATAU rows price_calendar lama
 * (date, price, currency, slots, slots_booked).
 */
/** Kamus label brosur PDF per bahasa (id/en/zh). Tambah bahasa = tambah kolom tiap key. */
function pdfBrochureLabels(string $lang): array
{
    $d = [
        'highlights' => ['id' => 'HIGHLIGHTS / DESTINASI UTAMA', 'en' => 'HIGHLIGHTS / TOP DESTINATIONS', 'zh' => '旅游亮点'],
        'info' => ['id' => 'INFORMASI TOUR', 'en' => 'TOUR INFORMATION', 'zh' => '旅游信息'],
        'duration' => ['id' => 'DURASI', 'en' => 'DURATION', 'zh' => '天数'],
        'category' => ['id' => 'KATEGORI', 'en' => 'CATEGORY', 'zh' => '类别'],
        'maxpax' => ['id' => 'MAX PESERTA', 'en' => 'MAX PARTICIPANTS', 'zh' => '最多人数'],
        'fromprice' => ['id' => 'HARGA MULAI', 'en' => 'STARTING FROM', 'zh' => '起价'],
        'pax' => ['id' => 'pax', 'en' => 'pax', 'zh' => '人'],
        'flight' => ['id' => 'JADWAL PENERBANGAN / FLIGHT', 'en' => 'FLIGHT SCHEDULE', 'zh' => '航班信息'],
        'meetpoint' => ['id' => 'Titik kumpul: ', 'en' => 'Meeting point: ', 'zh' => '集合地点：'],
        'itinerary' => ['id' => 'PROGRAM PERJALANAN / ITINERARY', 'en' => 'TRAVEL PROGRAM / ITINERARY', 'zh' => '行程安排'],
        'itinerary_empty' => ['id' => 'Detail itinerary menyusul. Hubungi kami untuk info lengkap.', 'en' => 'Detailed itinerary to follow. Contact us for full info.', 'zh' => '详细行程即将公布，请联系我们获取完整信息。'],
        'th_day' => ['id' => 'HARI', 'en' => 'DAY', 'zh' => '天数'],
        'th_program' => ['id' => 'PROGRAM PERJALANAN', 'en' => 'TRAVEL PROGRAM', 'zh' => '行程内容'],
        'th_hotel' => ['id' => 'HOTEL', 'en' => 'HOTEL', 'zh' => '酒店'],
        'th_meals' => ['id' => 'MEALS', 'en' => 'MEALS', 'zh' => '餐饮'],
        'schedule' => ['id' => 'JADWAL KEBERANGKATAN / HARGA PAKET', 'en' => 'DEPARTURE DATES / PACKAGE PRICES', 'zh' => '出发日期 / 套餐价格'],
        'th_departure' => ['id' => 'KEBERANGKATAN', 'en' => 'DEPARTURE', 'zh' => '出发日期'],
        'th_note' => ['id' => 'KET', 'en' => 'NOTE', 'zh' => '备注'],
        'th_adult' => ['id' => 'DEWASA', 'en' => 'ADULT', 'zh' => '成人'],
        'th_child' => ['id' => 'ANAK', 'en' => 'CHILD', 'zh' => '儿童'],
        'th_single' => ['id' => 'SINGLE', 'en' => 'SINGLE', 'zh' => '单房差'],
        'th_status' => ['id' => 'STATUS', 'en' => 'STATUS', 'zh' => '状态'],
        'th_pricepax' => ['id' => 'HARGA / PAX', 'en' => 'PRICE / PAX', 'zh' => '价格 / 人'],
        'seats_left' => ['id' => 'Sisa %d seat', 'en' => '%d seats left', 'zh' => '还剩 %d 个座位'],
        'full' => ['id' => 'Penuh', 'en' => 'Full', 'zh' => '已满'],
        'available' => ['id' => 'Tersedia', 'en' => 'Available', 'zh' => '有位'],
        'price_note' => ['id' => '*Harga dalam %s per orang. Dapat berubah mengikuti kurs & kebijakan maskapai.', 'en' => '*Price in %s per person. Subject to exchange rate & airline policy.', 'zh' => '*价格为 %s/人，可能随汇率及航空公司政策调整。'],
        'include' => ['id' => 'PAKET SUDAH TERMASUK / INCLUDE', 'en' => 'PACKAGE INCLUDES', 'zh' => '费用包含'],
        'exclude' => ['id' => 'PAKET BELUM TERMASUK / EXCLUDE', 'en' => 'PACKAGE EXCLUDES', 'zh' => '费用不含'],
        'notes' => ['id' => 'CATATAN PENTING / IMPORTANT NOTES', 'en' => 'IMPORTANT NOTES', 'zh' => '重要须知'],
        'cta' => ['id' => 'DAFTAR SEKARANG — SEAT TERBATAS!', 'en' => 'REGISTER NOW — LIMITED SEATS!', 'zh' => '立即报名 — 座位有限！'],
        'cta_sub' => ['id' => 'Syarat & Ketentuan Berlaku', 'en' => 'Terms & Conditions Apply', 'zh' => '须遵守条款和条件'],
        'foot' => ['id' => 'Generated by TourAndTravel — Your World of Joy', 'en' => 'Generated by TourAndTravel — Your World of Joy', 'zh' => '由 TourAndTravel 生成 — Your World of Joy'],
    ];
    $o = [];
    foreach ($d as $k => $v) $o[$k] = $v[$lang] ?? $v['id'];
    return $o;
}

function pdfTourBrochureHtml(array $tour, array $days, string $coverImgTag, array $deps, string $lang = 'id'): string
{
    if (!in_array($lang, ['id', 'en', 'zh'], true)) $lang = 'id';
    $L = pdfBrochureLabels($lang);
    $tc = fn(string $f) => tContentLang($tour, $f, $lang);
    $tourTitle = $tc('title');
    $tourCat = $tc('category');
    $route = trim($tc('route_cities') ?: trim(($tc('location_city') ?: '') . ($tourCat ? ' • ' . $tourCat : ''), ' •'));
    $dur = pdfBrochureDuration($tour, $days);
    $hiDb = pdfBrochureLines($tc('highlights') ?: null, 8);
    [$intro, $hiAuto] = pdfBrochureParseDesc($tc('description'), $tourTitle);
    $hi = $hiDb ?: $hiAuto;
    $inc = pdfBrochureLines($tc('includes') ?: null);
    $exc = pdfBrochureLines($tc('excludes') ?: null);
    $flights = pdfBrochureLines($tc('flight_info') ?: null, 6);
    $notes = pdfBrochureLines($tc('important_notes') ?: null, 8);
    $cur = $tour['price_currency'] ?? 'IDR';
    $from = (float)$tour['price'];
    foreach ($deps as $dp) {
        if (isset($dp['price_adult']) && $dp['price_adult'] > 0) $from = min($from ?: PHP_FLOAT_MAX, (float)$dp['price_adult']);
        elseif (isset($dp['price']) && $dp['price'] > 0) $from = min($from ?: PHP_FLOAT_MAX, (float)$dp['price']);
    }
    $price = formatCurrency($from, $cur, $cur);
    $web = defined('BASE_URL') ? preg_replace('#^https?://#', '', BASE_URL) : 'tourandtravel.web.id';

    $h = '<html><head><meta charset="utf-8"><style>' . pdfBrochureCss($lang) . '</style></head><body>';

    // ===== Halaman 1: cover =====
    $h .= '<div class="page">';
    $h .= '<table class="head"><tr><td><div class="brand">TOURANDTRAVEL</div>'
        . '<div class="brand-sub">TOUR &amp; TRAVEL</div></td>'
        . '<td class="head-right">YOUR WORLD OF JOY<br>' . e($web) . '</td></tr></table>';
    $h .= '<div class="hero"><table><tr><td class="dur">' . $dur . '</td>'
        . '<td><div class="hero-title">' . e($tourTitle) . '</div>'
        . ($route !== '' ? '<div class="hero-sub">' . e($route) . '</div>' : '') . '</td></tr></table></div>';
    if ($intro !== '') $h .= '<div class="tagline">' . e(mb_substr($intro, 0, 320)) . '</div>';
    if ($coverImgTag !== '') $h .= '<div>' . $coverImgTag . '</div>';
    if ($hi) {
        $h .= '<div class="secbar">' . e($L['highlights']) . '</div><table class="chips"><tr>';
        $c = 0;
        foreach ($hi as $x) {
            if ($c > 0 && $c % 2 === 0) $h .= '</tr><tr>';
            $h .= '<td><div class="chip">&#10003; ' . e($x) . '</div></td>';
            $c++;
        }
        if ($c % 2 === 1) $h .= '<td></td>';
        $h .= '</tr></table>';
    }
    $h .= '<div class="secbar">' . e($L['info']) . '</div><table class="info"><tr>'
        . '<td><div class="k">' . e($L['duration']) . '</div><div class="v">' . $dur . '</div></td>'
        . '<td><div class="k">' . e($L['category']) . '</div><div class="v">' . e($tourCat ?: '-') . '</div></td>'
        . '<td><div class="k">' . e($L['maxpax']) . '</div><div class="v">' . (int)($tour['max_participants'] ?? 20) . ' ' . e($L['pax']) . '</div></td>'
        . '<td><div class="k">' . e($L['fromprice']) . '</div><div class="v">' . e($price) . '</div></td>'
        . '</tr></table>';
    if ($flights) {
        $h .= '<div class="secbar">' . e($L['flight']) . '</div>';
        foreach ($flights as $f) $h .= '<div class="flight">&#9992; ' . e($f) . '</div>';
    }
    if (($meet = $tc('meeting_point')) !== '') $h .= '<div class="meet">' . e($L['meetpoint']) . e($meet) . '</div>';
    $h .= '</div>';

    // ===== Halaman 2: itinerary =====
    $h .= '<div class="page">';
    $h .= '<div class="secbar">' . e($L['itinerary']) . '</div>';
    if (empty($days)) {
        $h .= '<div class="tagline">' . e($L['itinerary_empty']) . '</div>';
    } else {
        $h .= '<table class="itin"><tr><th width="60">' . e($L['th_day']) . '</th><th>' . e($L['th_program']) . '</th>'
            . '<th width="100">' . e($L['th_hotel']) . '</th><th width="60">' . e($L['th_meals']) . '</th></tr>';
        foreach ($days as $d) {
            $dn = (int)$d['day_number'];
            $dTitle = trim((string)tContentLang($d, 'title', $lang));
            $dDesc = trim((string)tContentLang($d, 'description', $lang));
            $dTitle = preg_match('/^day\s*\d+\s*[\x{2014}\x{2013}\-]/iu', $dTitle)
                ? trim(preg_replace('/^day\s*\d+\s*[\x{2014}\x{2013}\-]\s*/iu', '', $dTitle))
                : $dTitle;
            $h .= '<tr><td><span class="daybadge">D' . $dn . '</span></td><td>';
            if ($dTitle !== '') $h .= '<div class="daytitle">' . e($dTitle) . '</div>';
            if ($dDesc !== '') $h .= '<div class="prog">' . e(mb_substr(strip_tags($dDesc), 0, 600)) . '</div>';
            $h .= '</td><td>' . e(tContentLang($d, 'accommodation', $lang) ?: '-') . '</td>'
                . '<td>' . e(tContentLang($d, 'meals', $lang) ?: '-') . '</td></tr>';
        }
        $h .= '</table>';
    }
    $h .= '</div>';

    // ===== Halaman 3: jadwal + harga tier =====
    if ($deps) {
        $tier = isset($deps[0]['price_adult']) || isset($deps[0]['departure_date']);
        $h .= '<div class="page">';
        $h .= '<div class="secbar">' . e($L['schedule']) . '</div>';
        if ($tier) {
            $h .= '<table class="price"><tr><th>' . e($L['th_departure']) . '</th><th>' . e($L['th_note']) . '</th><th>' . e($L['th_adult']) . '</th><th>' . e($L['th_child']) . '</th><th>' . e($L['th_single']) . '</th><th>' . e($L['th_status']) . '</th></tr>';
            foreach ($deps as $dp) {
                $tgl = date('d M Y', strtotime($dp['departure_date'] ?? $dp['date']));
                if (!empty($dp['return_date'])) $tgl .= "\n" . date('d M Y', strtotime($dp['return_date']));
                $pa = !empty($dp['price_adult']) ? formatCurrency($dp['price_adult'], $cur, $cur) : '-';
                $pc = !empty($dp['price_child']) ? formatCurrency($dp['price_child'], $cur, $cur) : '-';
                $ps = !empty($dp['price_single']) ? formatCurrency($dp['price_single'], $cur, $cur) : '-';
                $slots = (int)($dp['available_slots'] ?? $dp['slots'] ?? 0);
                $booked = (int)($dp['booked'] ?? $dp['slots_booked'] ?? 0);
                if ($slots > 0) {
                    $sisa = $slots - $booked;
                    $st = $sisa > 0 ? '<span class="avail">' . e(sprintf($L['seats_left'], $sisa)) . '</span>' : '<span class="full">' . e($L['full']) . '</span>';
                } else $st = '<span class="avail">' . e($L['available']) . '</span>';
                $h .= '<tr><td class="l">' . nl2br(e($tgl)) . '</td><td>' . e(tContentLang($dp, 'note', $lang) ?: '-') . '</td>'
                    . '<td>' . e($pa) . '</td><td>' . e($pc) . '</td><td>' . e($ps) . '</td><td>' . $st . '</td></tr>';
            }
        } else {
            $h .= '<table class="price"><tr><th>' . e($L['th_departure']) . '</th><th>' . e($L['th_pricepax']) . '</th><th>' . e($L['th_status']) . '</th></tr>';
            foreach ($deps as $dp) {
                $tgl = date('d M Y', strtotime($dp['date']));
                $pr = formatCurrency($dp['price'], $dp['currency'] ?? 'IDR', $dp['currency'] ?? 'IDR');
                $slots = (int)($dp['slots'] ?? 0);
                $booked = (int)($dp['slots_booked'] ?? 0);
                $st = ($slots > 0 && $slots - $booked <= 0)
                    ? '<span class="full">' . e($L['full']) . '</span>'
                    : '<span class="avail">' . e($slots > 0 ? sprintf($L['seats_left'], $slots - $booked) : $L['available']) . '</span>';
                $h .= '<tr><td class="l">' . e($tgl) . '</td><td>' . e($pr) . '</td><td>' . $st . '</td></tr>';
            }
        }
        $h .= '</table>';
        $h .= '<div class="tagline">' . e(sprintf($L['price_note'], $cur)) . '</div>';
        $h .= '</div>';
    }

    // ===== Halaman 4: include / exclude / catatan + CTA =====
    $h .= '<div class="page">';
    if ($inc) {
        $h .= '<div class="secbar-green">' . e($L['include']) . '</div><ul class="tick">';
        foreach ($inc as $x) $h .= '<li>&#10003; ' . e($x) . '</li>';
        $h .= '</ul>';
    }
    if ($exc) {
        $h .= '<div class="secbar-red">' . e($L['exclude']) . '</div><ul class="tick">';
        foreach ($exc as $x) $h .= '<li>&#10005; ' . e($x) . '</li>';
        $h .= '</ul>';
    }
    if ($notes) {
        $h .= '<div class="secbar">' . e($L['notes']) . '</div><ul class="notes">';
        foreach ($notes as $x) $h .= '<li>' . e($x) . '</li>';
        $h .= '</ul>';
    }
    $h .= '<div class="cta"><div class="t1">' . e($L['cta']) . '</div>'
        . '<div class="t2">' . e($web) . ' &bull; ' . e($L['cta_sub']) . '</div></div>';
    $h .= '<div class="foot">' . e($L['foot']) . '</div>';
    $h .= '</div>';

    return $h . '</body></html>';
}

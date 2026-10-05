<?php
/**
 * AJAX city/airport search untuk origin/destination pesawat.
 * Sumber utama: NusaTrip airport/search (mengembalikan kode IATA).
 * Fallback: daftar kota bandara statis bila NusaTrip nonaktif/tak terjangkau.
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/nusatrip.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo json_encode([]); exit; }

/** Format label NusaTrip (HTML) → "Kota (IATA) · Nama Bandara". */
function cityAjaxAirportLabel(array $a): ?array {
    $code = strtoupper((string)($a['value'] ?? ''));
    if (!preg_match('/^[A-Z]{3}$/', $code)) return null; // skip _JKT (all airports) dll.
    $raw = (string)($a['label'] ?? '');
    $city = '';
    if (preg_match('/<strong>(.*?)<\/strong>/s', $raw, $m)) $city = trim(html_entity_decode(strip_tags($m[1])));
    $airport = '';
    if (preg_match('/\([A-Z]{3}\s*:\s*([^)]+)\)/s', $raw, $m)) $airport = trim($m[1]);
    $label = $city !== '' ? $city . ' (' . $code . ')' : $code;
    if ($airport !== '') $label .= ' · ' . $airport;
    return ['label' => $label, 'code' => $code];
}

$result = [];
if (function_exists('nusaModuleEnabled') && nusaModuleEnabled()) {
    $res = nusaAirportSearch($q);
    $rows = $res['data'] ?? null;
    if (($res['http'] ?? 0) === 200 && is_array($rows)) {
        $seen = [];
        foreach ($rows as $a) {
            if (!is_array($a)) continue;
            $item = cityAjaxAirportLabel($a);
            if ($item === null || isset($seen[$item['code']])) continue;
            $seen[$item['code']] = true;
            $result[] = ['label' => $item['label']];
            if (count($result) >= 8) break;
        }
    }
}

if (!$result) {
    // Fallback statis (NusaTrip nonaktif / tak terjangkau).
    $staticCities = [
        'Jakarta (CGK)', 'Jakarta (HLP)', 'Denpasar (DPS)', 'Surabaya (SUB)',
        'Yogyakarta (YIA)', 'Yogyakarta (JOG)', 'Medan (KNO)', 'Makassar (UPG)',
        'Bandung (BDO)', 'Batam (BTH)', 'Palembang (PLM)', 'Semarang (SRG)',
        'Solo (SOC)', 'Balikpapan (BPN)', 'Pekanbaru (PKU)', 'Manado (MDC)',
        'Padang (PDG)', 'Lombok (LOP)', 'Banjarmasin (BDJ)', 'Pontianak (PNK)',
        'Jambi (DJB)', 'Bengkulu (BKS)', 'Ambon (AMQ)', 'Jayapura (DJJ)',
        'Kupang (KOE)', 'Mataram (AMI)', 'Tanjung Pinang (TNJ)', 'Tarakan (TRK)',
        'Singapore (SIN)', 'Kuala Lumpur (KUL)', 'Bangkok (BKK)',
        'Tokyo (NRT)', 'Tokyo (HND)', 'Osaka (KIX)', 'Seoul (ICN)',
        'Hong Kong (HKG)', 'Taipei (TPE)', 'Dubai (DXB)',
    ];
    $ql = strtolower($q);
    foreach ($staticCities as $city) {
        if (strpos(strtolower($city), $ql) !== false) {
            $result[] = ['label' => $city];
            if (count($result) >= 8) break;
        }
    }
}

echo json_encode($result);

<?php
/**
 * AJAX city search for flight origin/destination
 */
require_once 'includes/config.php';
require_once 'includes/db.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo json_encode([]); exit; }

// Indonesian cities + airport codes from flights table
$indonesianCities = [
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

$result = [];
$q = strtolower($q);

foreach ($indonesianCities as $city) {
    if (strpos(strtolower($city), $q) !== false) {
        $result[] = ['label' => $city];
    }
    if (count($result) >= 8) break;
}

echo json_encode($result);

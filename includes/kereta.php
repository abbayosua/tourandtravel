<?php
/**
 * kereta.php — Kereta Api API integration
 * https://klikmbc.biz/v2/ - Train schedule search
 */

define('KERETA_BASE', 'https://klikmbc.biz/v2');

require_once __DIR__ . '/flight-cache.php';

$KERETA_STATION_MAP = [
    'JAKK' => 'JAKK - Jakarta Kota, Jakarta',
    'GMR' => 'GMR - Gambir, Jakarta',
    'BD' => 'BD - Bandung, Bandung',
    'YK' => 'YK - Yogyakarta, Yogyakarta',
    'SGU' => 'SGU - Surabaya Gubeng, Surabaya',
    'SMT' => 'SMT - Semarang Tawang Bank Jateng, Semarang',
];

$KERETA_STATION_CODE_MAP = [
    'Jakarta Kota, Jakarta' => 'JAKK',
    'Gambir, Jakarta' => 'GMR',
    'Bandung, Bandung' => 'BD',
    'Yogyakarta, Yogyakarta' => 'YK',
    'Surabaya Gubeng, Surabaya' => 'SGU',
    'Semarang Tawang Bank Jateng, Semarang' => 'SMT',
];

function keretaStationLabel($code) {
    global $KERETA_STATION_MAP;
    return $KERETA_STATION_MAP[$code] ?? '';
}

function keretaSearchTrips($fromCode, $toCode, $date, $adults = 1, $children = 0, $infants = 0) {
    $cacheKey = 'kereta:' . $fromCode . ':' . $toCode . ':' . $date . ':' . $adults . ':' . $children;
    $cached = flightCacheGet($cacheKey);
    if ($cached !== null && isset($cached['trips'])) {
        return $cached['trips'];
    }

    $fromLabel = keretaStationLabel($fromCode);
    $toLabel = keretaStationLabel($toCode);

    $dateFormatted = date('j M Y', strtotime($date));

    $params = [
        'train_from' => $fromLabel,
        'train_to' => $toLabel,
        'train_datedeparture' => $dateFormatted,
        'dewasa' => $adults,
        'anak' => $children,
        'bayi' => $infants,
        'dewasa_number' => $adults,
        'bayi_number' => $infants,
        'goback' => 0,
        'train_dateback' => '',
        'show_cashback' => 1,
    ];

    $url = KERETA_BASE . '/form_train_result_cari?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (compatible; TourAndTravel)',
            'Accept: text/html,application/xhtml+xml',
        ],
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return [];

    $trips = keretaParseTrips($html, $date);

    if (!empty($trips)) {
        flightCacheSet($cacheKey, 'kereta', ['trips' => $trips], count($trips));
    }

    return $trips;
}

function keretaParseTrips($html, $date) {
    $trips = [];
    $seen = [];

    preg_match_all('/<form[^>]*method=["\']POST["\'][^>]*action=["\'][^"\']*form_train_result_detailorder[^"\']*["\'][^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);

    if (empty($forms)) {
        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);
    }

    foreach ($forms as $form) {
        $block = $form[1];

        $trainName = '';
        if (preg_match('/train_name["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainName = trim($m[1]);
        }

        $trainCode = '';
        if (preg_match('/train_code["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainCode = trim($m[1]);
        }

        $trainFrom = '';
        if (preg_match('/train_from["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainFrom = trim($m[1]);
        }

        $trainTo = '';
        if (preg_match('/train_to["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainTo = trim($m[1]);
        }

        $trainDate = '';
        if (preg_match('/train_date["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainDate = trim($m[1]);
        }

        $trainDatetime = '';
        if (preg_match('/train_datetime["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainDatetime = trim($m[1]);
        }

        $trainClass = '';
        if (preg_match('/train_class["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $trainClass = trim($m[1]);
        }

        $trainPrice = 0;
        if (preg_match('/train_price["\']?\s*value=["\']([\d.]+)["\']/i', $block, $m)) {
            $trainPrice = (float)str_replace('.', '', $m[1]);
        }

        if (!$trainName || !$trainCode) continue;

        $key = $trainName . '|' . $trainClass . '|' . $trainDatetime;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $departureTime = '';
        $arrivalTime = '';
        if (preg_match('/(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})\s*-\s*(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})/', $trainDatetime, $tm)) {
            $departureTime = $tm[1];
            $arrivalTime = $tm[2];
        } elseif (preg_match('/(\d{2}:\d{2})/', $trainDatetime, $tm)) {
            $departureTime = $tm[1];
        }

        $trips[] = [
            'train_name' => $trainName,
            'train_code' => $trainCode,
            'train_from' => $trainFrom,
            'train_to' => $trainTo,
            'train_date' => $trainDate ?: $date,
            'train_datetime' => $trainDatetime,
            'train_class' => $trainClass,
            'train_price' => $trainPrice,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
        ];
    }

    usort($trips, function($a, $b) {
        return strcmp($a['departure_time'], $b['departure_time']);
    });

    return $trips;
}

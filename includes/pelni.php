<?php
/**
 * pelni.php — PELNI API integration
 * https://klikmbc.biz/v2/ - PELNI ship booking
 */

define('PELNI_BASE', 'https://klikmbc.biz/v2');

require_once __DIR__ . '/flight-cache.php';

$PELNI_PORT_MAP = [
    '256' => 'Pulau Batam, Kota Batam',
    '431' => 'Tanjung Priok, Jakarta Utara',
];

$PELNI_PORT_CODE_MAP = [
    'Pulau Batam, Kota Batam' => '256',
    'Tanjung Priok, Jakarta Utara' => '431',
];

function pelniLogo() {
    return PELNI_BASE . '/images/logo-pelni-white-2023.png';
}

function pelniSearchPort($query) {
    $url = PELNI_BASE . '/getcode-pelabuhanv2.php?term=' . urlencode($query);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (compatible; TourAndTravel)',
        ],
    ]);
    $json = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$json || $httpCode !== 200) return [];

    $data = json_decode($json, true);
    if (!is_array($data)) return [];

    return $data;
}

function pelniSearchTrips($fromCode, $toCode, $date, $adults = 1, $children = 0, $male = 1, $female = 0) {
    $cacheKey = 'pelni:' . $fromCode . ':' . $toCode . ':' . $date . ':' . $adults . ':' . $children;
    $cached = flightCacheGet($cacheKey);
    if ($cached !== null && isset($cached['trips'])) {
        return $cached['trips'];
    }

    global $PELNI_PORT_MAP;
    $fromName = $PELNI_PORT_MAP[$fromCode] ?? '';
    $toName = $PELNI_PORT_MAP[$toCode] ?? '';

    $dateFormatted = date('j M Y', strtotime($date));

    $params = [
        'pelabuhan_from' => $fromName,
        'pelabuhan_to' => $toName,
        'pelabuhan_datedeparture' => $dateFormatted,
        'dewasa' => $adults,
        'bayi' => $children,
        'male' => $male,
        'female' => $female,
        'pelabuhan_from_final' => $fromName,
        'pelabuhan_to_final' => $toName,
        'pelabuhan_from_code' => $fromCode,
        'pelabuhan_to_code' => $toCode,
    ];

    $url = PELNI_BASE . '/form_pelni_result_cari?' . http_build_query($params);

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

    $trips = pelniParseTrips($html, $date);

    if (!empty($trips)) {
        flightCacheSet($cacheKey, 'pelni', ['trips' => $trips], count($trips));
    }

    return $trips;
}

function pelniParseTrips($html, $date) {
    $trips = [];
    $seen = [];

    preg_match_all('/<form[^>]*method=["\']POST["\'][^>]*action=["\'][^"\']*form_pelni_result_detailorder[^"\']*["\'][^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);

    if (empty($forms)) {
        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);
    }

    foreach ($forms as $form) {
        $block = $form[1];

        $shipName = '';
        if (preg_match('/ship_name["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipName = trim($m[1]);
        }

        $shipNumber = '';
        if (preg_match('/ship_number["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipNumber = trim($m[1]);
        }

        $shipCode = '';
        if (preg_match('/ship_code["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipCode = trim($m[1]);
        }

        $shipFrom = '';
        if (preg_match('/ship_from["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipFrom = trim($m[1]);
        }

        $shipTo = '';
        if (preg_match('/ship_to["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipTo = trim($m[1]);
        }

        $shipDate = '';
        if (preg_match('/ship_date["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipDate = trim($m[1]);
        }

        $shipDatetime = '';
        if (preg_match('/ship_datetime["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipDatetime = trim($m[1]);
        }

        $shipClass = '';
        if (preg_match('/ship_class["\']?\s*value=["\']([^"\']*)["\']/i', $block, $m)) {
            $shipClass = trim($m[1]);
        }

        $shipPrice = 0;
        if (preg_match('/ship_price["\']?\s*value=["\']([\d.]+)["\']/i', $block, $m)) {
            $shipPrice = (float)str_replace('.', '', $m[1]);
        }

        if (!$shipName || !$shipCode) continue;

        $key = $shipName . '|' . $shipClass . '|' . $shipDatetime;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $departureTime = '';
        $arrivalTime = '';
        if (preg_match('/(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})\s*-\s*(\d{1,2}\s+\w+\s+\d{4}\s+\d{2}:\d{2})/', $shipDatetime, $tm)) {
            $departureTime = $tm[1];
            $arrivalTime = $tm[2];
        } elseif (preg_match('/(\d{2}:\d{2})/', $shipDatetime, $tm)) {
            $departureTime = $tm[1];
        }

        $trips[] = [
            'ship_name' => $shipName,
            'ship_number' => $shipNumber,
            'ship_code' => $shipCode,
            'ship_from' => $shipFrom,
            'ship_to' => $shipTo,
            'ship_date' => $shipDate ?: $date,
            'ship_datetime' => $shipDatetime,
            'ship_class' => $shipClass,
            'ship_price' => $shipPrice,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime,
        ];
    }

    usort($trips, function($a, $b) {
        return strcmp($a['departure_time'], $b['departure_time']);
    });

    return $trips;
}

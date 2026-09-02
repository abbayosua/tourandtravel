<?php
/**
 * Easybook Ferry API integration
 * https://www.easybook.com - Ferry booking for Singapore, Indonesia, Malaysia
 */

define('EASYBOOK_BASE', 'https://www.easybook.com');
define('EASYBOOK_PRODUCT_ID', 2); // Ferry

$EASYBOOK_COMPANY_MAP = [
    '6738' => 'Sindo Ferry',
    '1235' => 'Horizon Fast Ferry',
    '1236' => 'Batam Fast Ferry',
    '1234' => 'Majestic Fast Ferry',
];

/**
 * Search place by name
 * Returns array of places with pid (place ID) and spid (sub-place/terminal ID)
 */
function easybookSearchPlace($query) {
    $url = EASYBOOK_BASE . '/api/place?productId=' . EASYBOOK_PRODUCT_ID . '&query=' . urlencode($query);
    
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

/**
 * Search ferry trips from Easybook
 * Returns array of trip data
 */
function easybookSearchTrips($fromPlace, $toPlace, $date, $fromSubPlace = 0, $toSubPlace = 0, $passengers = 1) {
    $params = [
        'fromplace'      => $fromPlace,
        'fromsubplace'   => $fromSubPlace,
        'toplace'        => $toPlace,
        'tosubplace'     => $toSubPlace,
        'departtime'     => $date,
        'pax'            => $passengers,
        'company'        => '',
        'selectedCurrency' => 'IDR',
        'isReturn'       => 'false',
        'isRoundTrip'    => 'false',
        'selectedSeatClass' => 0,
        'endDepartTime'  => '',
        'vehicleType'    => '',
    ];

    $url = EASYBOOK_BASE . '/id-id/ferry/gettrips?' . http_build_query($params);

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

    return easybookParseTrips($html, $date);
}

/**
 * Parse HTML from Easybook ferry trips page
 */
function easybookParseTrips($html, $date) {
    global $EASYBOOK_COMPANY_MAP;
    
    $trips = [];
    $seen = [];
    
    // Find all result-list cards with dep-comp class
    // Pattern: class=" result-list dep-comp{companyId} {from-slug} {to-slug} ..."
    preg_match_all('/class="[^"]*result-list\s+dep-comp(\d+)\s+(\S+)\s+(\S+)/i', $html, $allCards, PREG_SET_ORDER);
    
    foreach ($allCards as $card) {
        $compId = $card[1];
        
        // Get the full card content
        $pos = strpos($html, $card[0]);
        if ($pos === false) continue;
        $block = substr($html, $pos, 3500);
        
        // Extract departure time
        $time = '';
        if (preg_match('/depart-time">([\d:]+)/', $block, $tm)) {
            $time = $tm[1];
        }
        if (!$time) continue;
        
        // Extract available seats
        $seats = 0;
        if (preg_match('/vacancy[^>]*>(\d+)/', $block, $sm)) {
            $seats = (int)$sm[1];
        }
        
        // Extract price from ticket-price section
        $price = 0;
        if (preg_match('/ticket-price[\s\S]*?icon-adult[\s\S]*?Rp[\s.]*([\d,.]+)/i', $block, $pm)) {
            $priceStr = $pm[1];
            $price = (float)str_replace('.', '', $priceStr);
            $price = (float)str_replace(',', '.', $price);
        }
        
        // Extract route terminals
        $from = '';
        $to = '';
        if (preg_match_all('/route-subplace">([^<]+)/', $block, $routes)) {
            $from = trim($routes[1][0] ?? '');
            $to = trim($routes[1][1] ?? '');
        }
        
        // Get company name from map
        $company = $EASYBOOK_COMPANY_MAP[$compId] ?? "Company #$compId";
        
        // Deduplicate by time + company + from
        $key = $time . '|' . $company . '|' . $from;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        
        $trips[] = [
            'company' => $company,
            'departure_time' => $time,
            'available_seats' => $seats,
            'price' => $price,
            'from_terminal' => $from,
            'to_terminal' => $to,
            'date' => $date,
        ];
    }
    
    // Sort by departure time
    usort($trips, function($a, $b) {
        return strcmp($a['departure_time'], $b['departure_time']);
    });
    
    return $trips;
}

/**
 * Format Easybook price to IDR
 */
function easybookFormatPrice($price) {
    if ($price <= 0) return 'Rp 0';
    return 'Rp ' . number_format($price, 0, ',', '.');
}
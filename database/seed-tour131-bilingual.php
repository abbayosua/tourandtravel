<?php
// seed-tour131-bilingual.php — isi kolom _en tour 131 + itinerary + dates (idempotent).
// Hanya UPDATE bila kolom _en masih NULL/kosong. Jalankan: php database/seed-tour131-bilingual.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

function fillEn(string $table, string $idCol, $id, array $fields): void {
    $sets = [];
    $params = [];
    foreach ($fields as $f => $v) {
        $sets[] = "`{$f}_en` = COALESCE(NULLIF(`{$f}_en`, ''), ?)";
        $params[] = $v;
    }
    $params[] = $id;
    db()->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `$idCol` = ?")->execute($params);
}

fillEn('tours', 'id', 131, [
    'title' => 'Beijing Qushui Lanting | Sihui Branch',
    'description' => 'Explore the beauty of Beijing in an unforgettable 5-day tour. Visit Mutianyu Great Wall, Forbidden City, Summer Palace, and experience authentic Qushui Lanting.',
    'category' => 'Beijing',
    'location_city' => 'Beijing',
    'route_cities' => 'Jakarta - Beijing - Mutianyu - Sihui',
    'highlights' => "Mutianyu Great Wall with round-trip cable car\nForbidden City and Tiananmen Square\nSummer Palace and Temple of Heaven\nAuthentic Peking duck dinner\nShopping at Wangfujing Street\nIndonesian-speaking tour leader",
    'includes' => "Round-trip airfare Jakarta - Beijing\n4-star hotel for 4 nights\nAC tourist bus transport\nEntrance tickets to all attractions\nMeals as per program\nIndonesian-speaking tour leader\nExperienced local guide\n1 bottle of mineral water per day\n20kg baggage",
    'excludes' => "China visa IDR 850,000\nTour leader and guide tipping\nPersonal expenses\nTravel insurance\nSingle supplement\nExcess baggage",
    'flight_info' => "GA 890 Jakarta (CGK) 23:15 - Beijing (PEK) 06:10+1\nGA 891 Beijing (PEK) 11:40 - Jakarta (CGK) 16:55",
    'meeting_point' => 'Terminal 3 Soekarno-Hatta Airport, 3 hours before departure',
    'important_notes' => "Passport valid minimum 7 months before departure\n50% deposit on registration, full payment D-21\nParticipants under 18 must be accompanied by parents\nSchedule may change according to field conditions",
]);

$days = [
    1 => ['Mutianyu Great Wall', 'Arrive in Beijing in the morning. Head straight to Mutianyu Great Wall (about 2 hours). Take the cable car up, enjoy mountain views. Lunch at local restaurant. Back to the city in the afternoon, Peking duck dinner, hotel check-in.', 'Lunch, Dinner', '4-Star Beijing Hotel'],
    2 => ['Forbidden City and Tiananmen', 'Breakfast at hotel. Visit Tiananmen Square then Forbidden City with 9,999 rooms from Ming and Qing dynasties. Lunch. Afternoon at Wangfujing Street for shopping. Dinner, back to hotel.', 'Breakfast, Lunch, Dinner', '4-Star Beijing Hotel'],
    3 => ['Summer Palace and Temple of Heaven', 'Breakfast at hotel. Visit Summer Palace with beautiful Kunming Lake, then Temple of Heaven. Lunch. Afternoon jade and herbal shop visit. Mongolian hotpot dinner.', 'Breakfast, Lunch, Dinner', '4-Star Beijing Hotel'],
    4 => ['Qushui Lanting Sihui Experience', 'Breakfast at hotel. Special day with Qushui Lanting Sihui Branch experience: traditional tea ceremony by the stream, calligraphy, Hanfu costumes. Lunch. Free afternoon at Qianmen Street. Farewell dinner.', 'Breakfast, Lunch, Dinner', '4-Star Beijing Hotel'],
    5 => ['Return to Jakarta', 'Breakfast and check-out. Last-minute souvenir shopping at Hongqiao Market. Transfer to airport for the flight back to Jakarta.', 'Breakfast', '-'],
];
foreach ($days as $n => [$t, $d, $m, $a]) {
    $id = db()->prepare("SELECT id FROM itineraries WHERE tour_id = 131 AND day_number = ?")->execute([$n]) ? null : null;
    $st = db()->prepare("SELECT id FROM itineraries WHERE tour_id = 131 AND day_number = ?");
    $st->execute([$n]);
    if ($row = $st->fetch()) {
        fillEn('itineraries', 'id', $row['id'], ['title' => $t, 'description' => $d, 'meals' => $m, 'accommodation' => $a]);
    }
}

$notes = ['Golden Week', 'Year-End Promo', 'Christmas Holiday', 'Low Season', 'Chinese New Year'];
$st = db()->query("SELECT id FROM tour_dates WHERE tour_id = 131 ORDER BY departure_date");
foreach ($st->fetchAll() as $i => $r) {
    if (isset($notes[$i])) fillEn('tour_dates', 'id', $r['id'], ['note' => $notes[$i]]);
}

echo "DONE: tour 131 _en terisi (idempotent)\n";

<?php
/**
 * Generate flight schedules for next 14 days
 * Run: php database/seed-flight-schedules.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Ambil semua flight routes
$flights = db()->query("SELECT id, price FROM flights WHERE is_active = 1")->fetchAll();

$count = 0;
$start = new DateTime('today');
$end = new DateTime('+14 days');

foreach ($flights as $flight) {
    $date = clone $start;
    while ($date <= $end) {
        $d = $date->format('Y-m-d');
        // Random seat availability
        $seats = rand(20, 150);

        $stmt = db()->prepare("SELECT COUNT(*) FROM flight_schedules WHERE flight_id = ? AND departure_date = ?");
        $stmt->execute([$flight['id'], $d]);
        if ($stmt->fetchColumn() == 0) {
            $s = db()->prepare("INSERT INTO flight_schedules (flight_id, departure_date, available_seats, price) VALUES (?, ?, ?, ?)");
            $s->execute([$flight['id'], $d, $seats, $flight['price']]);
            $count++;
        }
        $date->modify('+1 day');
    }
}

echo "Generated $count flight schedules for 14 days.\n";

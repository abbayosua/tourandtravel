<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function analyticsRange(?string $from = null, ?string $to = null): array {
    // default dari waktu MySQL (hindari mismatch timezone PHP vs DB)
    $today = date("Y-m-d");
    try { $today = (string)db()->query("SELECT CURDATE() d")->fetch()["d"]; } catch (Throwable $e) {}
    $from = $from ?: date("Y-m-d", strtotime($today . " -30 days"));
    $to = $to ?: $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date("Y-m-d", strtotime($today . " -30 days"));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = $today;
    return [$from, $to];
}

function analyticsBookingsPerDay(string $from, string $to): array {
    $st = db()->prepare("SELECT DATE(created_at) d, COUNT(*) n FROM bookings WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY GROUP BY DATE(created_at) ORDER BY d");
    $st->execute([$from, $to]);
    return $st->fetchAll();
}

function analyticsRevenuePerVertical(string $from, string $to): array {
    // revenue = total_price booking paid/confirmed (tour) + vertikal lain dgn payment_status paid
    $out = [];
    $map = ['tour' => 'bookings', 'hotel' => 'hotel_bookings', 'flight' => 'flight_bookings',
            'train' => 'train_bookings', 'transfer' => 'transfer_bookings',
            'attraction' => 'attraction_bookings', 'esim' => 'connectivity_bookings'];
    foreach ($map as $type => $table) {
        try {
            $st = db()->prepare("SELECT COUNT(*) n, COALESCE(SUM(total_price),0) total FROM `$table` WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY AND status != 'cancelled'");
            $st->execute([$from, $to]);
            $r = $st->fetch();
            if ((int)$r['n'] > 0) $out[] = ['type' => $type, 'n' => (int)$r['n'], 'total' => (float)$r['total']];
        } catch (Throwable $e) {}
    }
    usort($out, fn($a, $b) => $b['total'] <=> $a['total']);
    return $out;
}

function analyticsTopTours(string $from, string $to, int $limit = 5): array {
    $st = db()->prepare("SELECT t.title, COUNT(b.id) n, COALESCE(SUM(b.total_price),0) total FROM bookings b JOIN tours t ON b.tour_id=t.id WHERE b.created_at BETWEEN ? AND ? + INTERVAL 1 DAY AND b.status != 'cancelled' GROUP BY t.id ORDER BY n DESC LIMIT " . (int)$limit);
    $st->execute([$from, $to]);
    return $st->fetchAll();
}

function analyticsFunnel(string $from, string $to): array {
    $st = db()->prepare("SELECT status, COUNT(*) n FROM bookings WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY GROUP BY status");
    $st->execute([$from, $to]);
    $out = [];
    foreach ($st->fetchAll() as $r) $out[$r['status']] = (int)$r['n'];
    return $out;
}

function analyticsKpi(string $from, string $to): array {
    $b = db()->prepare("SELECT COUNT(*) bookings, COALESCE(SUM(total_price),0) revenue FROM bookings WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY AND status != 'cancelled'");
    $b->execute([$from, $to]);
    $row = $b->fetch();
    $u = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $sub = db()->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
    return [
        'bookings' => (int)$row['bookings'],
        'revenue' => (float)$row['revenue'],
        'users' => (int)$u,
        'subscribers' => (int)$sub,
    ];
}

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

/* ============================================================
   ADMIN DASHBOARD OPERATIONAL SYSTEM — helpers finance & sales
   PRD: ADMINPRD.md (section 3, 5)
   ============================================================ */

function analyticsBookingTables(): array {
    return [
        'tour'       => 'bookings',
        'hotel'      => 'hotel_bookings',
        'flight'     => 'flight_bookings',
        'attraction' => 'attraction_bookings',
        'transfer'   => 'transfer_bookings',
        'train'      => 'train_bookings',
        'esim'       => 'connectivity_bookings',
        'ferry'      => 'ferry_bookings',
    ];
}

/**
 * UNION ALL semua booking tables dengan kolom ternormalisasi.
 * WHERE clause tanggal + status opsional (param binding).
 * Return: array rows [id, booking_code, user_id, name, email, phone, total_price, cogs,
 *          status, payment_status, created_at, btype, item_title, qty_label, travel_label]
 */
function analyticsSalesUnion(string $from, string $to, ?string $type = null, ?string $status = null): array {
    $parts = [];
    $params = [];
    foreach (analyticsBookingTables() as $typeKey => $table) {
        if ($type && $type !== $typeKey) continue;
        $joinMap = [
            'tour'       => ['tours', 'b.tour_id', 't.title'],
            'hotel'      => ['hotels', 'b.hotel_id', 'h.name'],
            'flight'     => ["flight_schedules fs ON b.schedule_id = fs.id JOIN flights f ON fs.flight_id = f.id", 'f.id', "CONCAT(f.airline,' ',f.flight_number)"],
            'attraction' => ['attractions', 'b.attraction_id', 'a.name'],
            'transfer'   => ['transfers', 'b.transfer_id', 'tr.name'],
            'train'      => ['trains', 'b.train_id', 'tr.name'],
            'esim'       => ['connectivity_products', 'b.product_id', 'cp.name'],
            'ferry'      => null, // self-contained (route di tabel booking)
        ];
        [$joinSql, $itemExpr, $qtyExpr, $dateExpr] = match ($typeKey) {
            'tour'       => ["JOIN tours t ON b.tour_id = t.id JOIN tour_dates td ON b.tour_date_id = td.id", 't.title', "CONCAT(b.participants,' pax')", 'td.departure_date'],
            'hotel'      => ["JOIN hotels h ON b.hotel_id = h.id", 'h.name', "CONCAT(b.rooms,' kamar / ',b.guests,' tamu')", "CONCAT(b.checkin,' → ',b.checkout)"],
            'flight'     => ["JOIN flight_schedules fs ON b.schedule_id = fs.id JOIN flights f ON fs.flight_id = f.id", "CONCAT(f.airline,' ',f.flight_number)", "CONCAT(b.pax,' pax')", 'b.departure_date'],
            'attraction' => ["JOIN attractions a ON b.attraction_id = a.id", 'a.name', "CONCAT(b.quantity,' tiket')", 'b.visit_date'],
            'transfer'   => ["JOIN transfers tr ON b.transfer_id = tr.id", 'tr.name', "CONCAT(b.passengers,' pax')", 'b.pickup_date'],
            'train'      => ["JOIN trains tr ON b.train_id = tr.id", 'tr.name', "CONCAT(b.seats,' kursi')", 'b.travel_date'],
            'esim'       => ["JOIN connectivity_products cp ON b.product_id = cp.id", 'cp.name', "CONCAT(b.quantity,' pcs')", 'NULL'],
            'ferry'      => [null, "CONCAT(b.company,': ',b.route_from,' → ',b.route_to)", "CONCAT(b.passengers,' pax')", 'b.departure_date'],
        };

        $sql = "SELECT b.id, " . ($typeKey === 'tour' || $typeKey === 'attraction' || $typeKey === 'transfer' || $typeKey === 'train' || $typeKey === 'esim' || $typeKey === 'ferry' ? 'b.booking_code' : 'NULL') . " AS booking_code,
                b.user_id, b.name, b.email, b.phone, b.total_price, b.cogs, b.status, b.created_at,
                '$typeKey' AS btype,
                $itemExpr AS item_title,
                $qtyExpr AS qty_label,
                " . ($dateExpr === 'NULL' ? 'NULL' : $dateExpr) . " AS travel_label
            FROM `$table` b
            " . ($joinSql ?? '') . "
            WHERE b.created_at BETWEEN ? AND ? + INTERVAL 1 DAY";
        $params[] = $from;
        $params[] = $to;
        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }
        $parts[] = $sql;
    }
    if (!$parts) return [];
    $sql = implode(" UNION ALL ", $parts) . " ORDER BY created_at DESC";
    try {
        $st = db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Revenue trend harian (semua vertikal) untuk line chart dashboard.
 * Return: [ ['d'=>'Y-m-d','revenue'=>float,'n'=>int], ... ] terisi penuh dari from..to
 */
function analyticsRevenueTrend(string $from, string $to): array {
    $rows = [];
    $parts = [];
    $params = [];
    foreach (analyticsBookingTables() as $table) {
        $parts[] = "SELECT DATE(created_at) d, total_price, status FROM `$table` WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY";
        $params[] = $from;
        $params[] = $to;
    }
    $sql = "SELECT d, SUM(CASE WHEN status != 'cancelled' THEN total_price ELSE 0 END) revenue, COUNT(*) n
            FROM (" . implode(" UNION ALL ", $parts) . ") u GROUP BY d ORDER BY d";
    try {
        $st = db()->prepare($sql);
        $st->execute($params);
        foreach ($st->fetchAll() as $r) $rows[$r['d']] = ['d' => $r['d'], 'revenue' => (float)$r['revenue'], 'n' => (int)$r['n']];
    } catch (Throwable $e) {}

    // isi tanggal kosong agar chart kontinu
    $out = [];
    $cur = strtotime($from);
    $end = strtotime($to);
    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $out[] = $rows[$d] ?? ['d' => $d, 'revenue' => 0.0, 'n' => 0];
        $cur = strtotime('+1 day', $cur);
    }
    return $out;
}

/**
 * Count booking per vertikal untuk horizontal bar chart dashboard.
 * Return: [ ['type'=>'tour','n'=>int], ... ] (semua 8 vertikal, 0 jika kosong)
 */
function analyticsBookingsPerVertical(string $from, string $to): array {
    $out = [];
    $parts = [];
    $params = [];
    foreach (analyticsBookingTables() as $typeKey => $table) {
        $parts[] = "SELECT '$typeKey' btype, COUNT(*) n FROM `$table` WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY";
        $params[] = $from;
        $params[] = $to;
    }
    try {
        $st = db()->prepare("SELECT btype, SUM(n) n FROM (" . implode(" UNION ALL ", $parts) . ") u GROUP BY btype");
        $st->execute($params);
        foreach ($st->fetchAll() as $r) $out[$r['btype']] = (int)$r['n'];
    } catch (Throwable $e) {}
    $final = [];
    foreach (array_keys(analyticsBookingTables()) as $typeKey) {
        $final[] = ['type' => $typeKey, 'n' => $out[$typeKey] ?? 0];
    }
    return $final;
}

/**
 * P&L statement: revenue + cogs per vertikal (status confirmed/paid), expenses per kategori.
 * Return: [
 *   'revenue'   => [type => ['total'=>float,'cogs'=>float,'n'=>int]],
 *   'total_revenue' => float, 'total_cogs' => float,
 *   'gross_profit'  => float,
 *   'expenses'  => [category => float], 'total_expenses' => float,
 *   'net_profit'    => float,
 * ]
 */
function accountingPnL(string $from, string $to): array {
    $parts = [];
    $params = [];
    foreach (analyticsBookingTables() as $typeKey => $table) {
        $parts[] = "SELECT '$typeKey' btype, COUNT(*) n, COALESCE(SUM(total_price),0) revenue, COALESCE(SUM(cogs),0) cogs
                    FROM `$table` WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY AND status IN ('confirmed','paid')";
        $params[] = $from;
        $params[] = $to;
    }
    $revenue = [];
    $totalRevenue = 0.0;
    $totalCogs = 0.0;
    try {
        $st = db()->prepare("SELECT btype, SUM(n) n, SUM(revenue) revenue, SUM(cogs) cogs FROM (" . implode(" UNION ALL ", $parts) . ") u GROUP BY btype");
        $st->execute($params);
        foreach ($st->fetchAll() as $r) {
            $rev = (float)$r['revenue'];
            $cg = (float)$r['cogs'];
            $revenue[$r['btype']] = ['total' => $rev, 'cogs' => $cg, 'n' => (int)$r['n']];
            $totalRevenue += $rev;
            $totalCogs += $cg;
        }
    } catch (Throwable $e) {}

    $expenses = [];
    $totalExpenses = 0.0;
    try {
        $st = db()->prepare("SELECT category, COALESCE(SUM(amount),0) total FROM expenses WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY GROUP BY category");
        $st->execute([$from, $to]);
        foreach ($st->fetchAll() as $r) {
            $expenses[$r['category']] = (float)$r['total'];
            $totalExpenses += (float)$r['total'];
        }
    } catch (Throwable $e) {}

    return [
        'revenue'        => $revenue,
        'total_revenue'  => $totalRevenue,
        'total_cogs'     => $totalCogs,
        'gross_profit'   => $totalRevenue - $totalCogs,
        'expenses'       => $expenses,
        'total_expenses' => $totalExpenses,
        'net_profit'     => $totalRevenue - $totalCogs - $totalExpenses,
    ];
}

/**
 * Summary expenses per kategori (dengan count) untuk halaman accounting.
 * Return: [ ['category'=>..., 'n'=>int, 'total'=>float], ... ] urut total desc
 */
function accountingExpensesSummary(string $from, string $to): array {
    try {
        $st = db()->prepare("SELECT category, COUNT(*) n, COALESCE(SUM(amount),0) total
                             FROM expenses WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
                             GROUP BY category ORDER BY total DESC");
        $st->execute([$from, $to]);
        return array_map(fn($r) => ['category' => $r['category'], 'n' => (int)$r['n'], 'total' => (float)$r['total']], $st->fetchAll());
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Top N produk lintas vertikal (dipakai admin/analytics.php bar chart).
 * Exclude cancelled. Return: [ ['title'=>..., 'type'=>..., 'n'=>int, 'total'=>float], ... ] urut total desc
 */
function analyticsRevenuePerProduct(string $from, string $to, ?string $type = null, int $limit = 10): array {
    $top = [];
    foreach (analyticsSalesUnion($from, $to, $type, null) as $r) {
        if (in_array($r['status'], ['cancelled'], true)) continue;
        $key = $r['btype'] . '|' . $r['item_title'];
        if (!isset($top[$key])) $top[$key] = ['title' => $r['item_title'], 'type' => $r['btype'], 'n' => 0, 'total' => 0.0];
        $top[$key]['n']++;
        $top[$key]['total'] += (float)$r['total_price'];
    }
    usort($top, fn($a, $b) => $b['total'] <=> $a['total']);
    return array_slice($top, 0, max(1, $limit));
}

/**
 * Perbandingan bulanan (6 bulan terakhir sampai bulan $to): revenue vs cogs vs expenses vs profit.
 * Return: [ ['month'=>'Y-m','revenue'=>float,'cogs'=>float,'expenses'=>float,'profit'=>float], ... ]
 */
function accountingMonthlyComparison(string $to, int $months = 6): array {
    $start = date('Y-m-01 00:00:00', strtotime(date('Y-m-01', strtotime($to)) . " -" . ($months - 1) . " months"));
    $end = date('Y-m-t 23:59:59', strtotime($to));

    // revenue+cogs per bulan dari booking tables
    $parts = [];
    $params = [];
    foreach (analyticsBookingTables() as $typeKey => $table) {
        $parts[] = "SELECT DATE_FORMAT(created_at,'%Y-%m') m, COALESCE(SUM(total_price),0) revenue, COALESCE(SUM(cogs),0) cogs
                    FROM `$table` WHERE created_at BETWEEN ? AND ? AND status IN ('confirmed','paid') GROUP BY m";
        $params[] = $start;
        $params[] = $end;
    }
    $book = [];
    try {
        $st = db()->prepare("SELECT m, SUM(revenue) revenue, SUM(cogs) cogs FROM (" . implode(" UNION ALL ", $parts) . ") u GROUP BY m");
        $st->execute($params);
        foreach ($st->fetchAll() as $r) $book[$r['m']] = ['revenue' => (float)$r['revenue'], 'cogs' => (float)$r['cogs']];
    } catch (Throwable $e) {}

    // expenses per bulan
    $exp = [];
    try {
        $st = db()->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') m, COALESCE(SUM(amount),0) total
                             FROM expenses WHERE created_at BETWEEN ? AND ? GROUP BY m");
        $st->execute([$start, $end]);
        foreach ($st->fetchAll() as $r) $exp[$r['m']] = (float)$r['total'];
    } catch (Throwable $e) {}

    $out = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $m = date('Y-m', strtotime(date('Y-m-01', strtotime($to)) . " -$i months"));
        $rev = $book[$m]['revenue'] ?? 0.0;
        $cg = $book[$m]['cogs'] ?? 0.0;
        $ex = $exp[$m] ?? 0.0;
        $out[] = ['month' => $m, 'revenue' => $rev, 'cogs' => $cg, 'expenses' => $ex, 'profit' => $rev - $cg - $ex];
    }
    return $out;
}

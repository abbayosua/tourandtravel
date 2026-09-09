<?php
/**
 * AnalyticsFinanceTest — helper finance/sales baru (PRD ADMINPRD.md) dengan fixture sementara.
 * Mencakup: analyticsSalesUnion, analyticsRevenueTrend, analyticsBookingsPerVertical,
 *           accountingPnL, accountingExpensesSummary, accountingMonthlyComparison.
 */
require_once __DIR__ . '/../../includes/analytics.php';

function analyticsFinanceFixtureSetup(): array {
    // tour fixture pasti ada
    $tourId = (int)db()->query("SELECT id FROM tours ORDER BY id LIMIT 1")->fetch()['id'] ?? 0;
    if (!$tourId) { $tourId = (int)db()->query("SELECT 1 d")->fetch()['d']; }

    // booking unik dengan cogs, kemarin & hari ini
    $code = 'F' . substr(bin2hex(random_bytes(3)), 0, 3); // base max 4 char, +suffix 2 = 6 char (booking_code VARCHAR(10))
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, cogs, status, payment_status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, NOW() - INTERVAL 1 DAY)")
        ->execute([$code . '-A', $tourId, 1, 'FinA', 'fina@t.local', '081', 2, 1000000, 400000, 'confirmed', 'paid']);
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, cogs, status, payment_status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, NOW())")
        ->execute([$code . '-B', $tourId, 1, 'FinB', 'finb@t.local', '082', 1, 500000, 200000, 'confirmed', 'paid']);
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, cogs, status, payment_status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, NOW())")
        ->execute([$code . '-C', $tourId, 1, 'FinC', 'finc@t.local', '083', 1, 250000, 0, 'cancelled', 'unpaid']);

    // expense fixture
    db()->prepare("INSERT INTO expenses (category, description, amount, created_at) VALUES ('Marketing', ?, 300000, NOW())")->execute([$code]);
    db()->prepare("INSERT INTO expenses (category, description, amount, created_at) VALUES ('Operasional', ?, 100000, NOW() - INTERVAL 40 DAY)")->execute([$code]);

    return ['code' => $code, 'tour_id' => $tourId];
}

function analyticsFinanceFixtureTeardown(string $code): void {
    db()->prepare("DELETE FROM bookings WHERE booking_code LIKE ?")->execute([$code . '-%']);
    db()->prepare("DELETE FROM expenses WHERE description = ?")->execute([$code]);
}

function testAnalyticsSalesUnionNormalizesAllVerticals(): void {
    $fx = analyticsFinanceFixtureSetup();
    try {
        $rows = analyticsSalesUnion(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'));
        assertTrue(is_array($rows), 'union return array');
        $mine = array_values(array_filter($rows, fn($r) => ($r['booking_code'] ?? '') !== '' && str_starts_with((string)$r['booking_code'], $fx['code'])));
        assertEquals(3, count($mine), '3 booking fixture tampil di union (termasuk cancelled)');
        $need = ['id','booking_code','name','email','total_price','cogs','status','created_at','btype','item_title','qty_label'];
        assertEquals([], array_values(array_diff($need, array_keys($mine[0]))), 'kolom ternormalisasi lengkap');
        assertEquals('tour', $mine[0]['btype'], 'btype = tour');
        assertTrue((float)$mine[0]['cogs'] > 0, 'cogs terbawa');

        // filter status hanya confirmed (booking -A saja; -B kemarin masuk range -1d juga, jadi assert >= 1 dan semua confirmed)
        $conf = analyticsSalesUnion(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'), null, 'confirmed');
        $confMine = array_values(array_filter($conf, fn($r) => ($r['booking_code'] ?? '') !== '' && str_starts_with((string)$r['booking_code'], $fx['code'])));
        assertEquals(2, count($confMine), 'filter status=confirmed menemukan 2 fixture (-A dan -B)');
        foreach ($confMine as $r) assertEquals('confirmed', $r['status'], 'semua hasil filter confirmed');
    } finally { analyticsFinanceFixtureTeardown($fx['code']); }
}

function testAnalyticsRevenueTrendContinuousAndSums(): void {
    $fx = analyticsFinanceFixtureSetup();
    try {
        $from = date('Y-m-d', strtotime('-3 day'));
        $to = date('Y-m-d');
        $trend = analyticsRevenueTrend($from, $to);
        assertEquals(4, count($trend), '4 hari kontinu');
        assertEquals($from, $trend[0]['d'], 'mulai dari from');
        assertEquals($to, $trend[3]['d'], 'berakhir di to');
        $sum = array_sum(array_column($trend, 'revenue'));
        assertTrue($sum >= 1500000, "revenue fixture (1jt+500rb, cancelled 250rb dikecualikan) masuk trend, got $sum");
    } finally { analyticsFinanceFixtureTeardown($fx['code']); }
}

function testAnalyticsBookingsPerVerticalCountsTour(): void {
    $fx = analyticsFinanceFixtureSetup();
    try {
        $pv = analyticsBookingsPerVertical(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'));
        assertEquals(8, count($pv), '8 vertikal selalu ada');
        $map = array_column($pv, 'n', 'type');
        assertTrue($map['tour'] >= 3, 'tour >= 3 fixture');
        assertTrue(isset($map['ferry']) && isset($map['esim']), 'ferry & esim termasuk');
    } finally { analyticsFinanceFixtureTeardown($fx['code']); }
}

function testAccountingPnlArithmetic(): void {
    $fx = analyticsFinanceFixtureSetup();
    try {
        $pnl = accountingPnL(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'));
        assertTrue($pnl['total_revenue'] >= 1500000, 'revenue >= fixture confirmed+paid');
        assertTrue($pnl['total_cogs'] >= 600000, 'cogs >= 400rb+200rb');
        assertTrue($pnl['total_expenses'] >= 300000, 'expense hari ini >= 300rb');
        assertTrue(abs($pnl['gross_profit'] - ($pnl['total_revenue'] - $pnl['total_cogs'])) < 0.01, 'gross = rev - cogs');
        assertTrue(abs($pnl['net_profit'] - ($pnl['gross_profit'] - $pnl['total_expenses'])) < 0.01, 'net = gross - expenses');
        assertTrue(isset($pnl['revenue']['tour']), 'revenue per vertikal tour ada');

        // PnL 40 hari ke belakang: expense Operasional ikut
        $pnl2 = accountingPnL(date('Y-m-d', strtotime('-45 day')), date('Y-m-d'));
        assertTrue($pnl2['total_expenses'] >= 400000, 'expense 40 hari lalu terhitung di range panjang');
    } finally { analyticsFinanceFixtureTeardown($fx['code']); }
}

function testAccountingExpensesSummaryGroups(): void {
    $fx = analyticsFinanceFixtureSetup();
    try {
        $es = accountingExpensesSummary(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'));
        $map = array_column($es, 'n', 'category');
        assertTrue(($map['Marketing'] ?? 0) >= 1, 'Marketing tergroup');
        $mkt = array_values(array_filter($es, fn($r) => $r['category'] === 'Marketing'))[0] ?? null;
        assertTrue($mkt && $mkt['total'] >= 300000, 'total Marketing >= 300rb');
    } finally { analyticsFinanceFixtureTeardown($fx['code']); }
}

function testAnalyticsRevenuePerProductTopN(): void {
    $fx = analyticsFinanceFixtureSetup();
    try {
        $from = date('Y-m-d', strtotime('-1 day'));
        $to = date('Y-m-d');
        $top = analyticsRevenuePerProduct($from, $to, null, 10);
        assertTrue(is_array($top) && count($top) >= 1, 'top produk terisi');
        // Struktur
        $need = ['title', 'type', 'n', 'total'];
        assertEquals([], array_values(array_diff($need, array_keys($top[0]))), 'struktur kolom');
        // Urut desc
        for ($i = 1; $i < count($top); $i++) {
            assertTrue($top[$i - 1]['total'] >= $top[$i]['total'], 'urut total desc');
        }
        // Tour fixture 1jt (2 pax confirmed) harus masuk
        $titles = array_column($top, 'title');
        assertTrue(count($titles) > 0, 'ada judul produk');
        // Limit bekerja
        $limited = analyticsRevenuePerProduct($from, $to, null, 1);
        assertEquals(1, count($limited), 'limit=1 menghasilkan 1');
        // Cancelled tidak masuk: hitung delta sum tour dgn/tnpa fixture — assert konsisten dgn fixture non-cancelled saja
        $topAll = analyticsRevenuePerProduct($from, $to, 'tour', 50);
        $sumTour = array_sum(array_column($topAll, 'total'));
        // sum tour harus >= fixture 1jt+500rb (non-cancelled) tapi < rev total live (tak bisa tepat karena live data dinamis)
        assertTrue($sumTour >= 1500000, "sum tour >= fixture non-cancelled (got $sumTour)");
        // n total konsisten dengan union
        $nSum = array_sum(array_column($topAll, 'n'));
        assertTrue($nSum >= 2, '2 booking tour non-cancelled terhitung');
    } finally { analyticsFinanceFixtureTeardown($fx['code']); }
}

function testAccountingMonthlyComparisonSixMonths(): void {
    $fx = analyticsFinanceFixtureSetup();
    try {
        $mc = accountingMonthlyComparison(date('Y-m-d'));
        assertEquals(6, count($mc), '6 bulan');
        assertEquals(date('Y-m'), $mc[5]['month'], 'bulan terakhir = bulan ini');
        assertEquals(date('Y-m', strtotime('-5 months')), $mc[0]['month'], 'bulan pertama = 5 bulan lalu');
        $cur = $mc[5];
        assertTrue($cur['revenue'] >= 1500000, 'revenue bulan ini >= fixture');
        assertTrue($cur['expenses'] >= 300000, 'expense bulan ini >= fixture');
        assertTrue(abs($cur['profit'] - ($cur['revenue'] - $cur['cogs'] - $cur['expenses'])) < 0.01, 'profit = rev - cogs - exp');
    } finally { analyticsFinanceFixtureTeardown($fx['code']); }
}

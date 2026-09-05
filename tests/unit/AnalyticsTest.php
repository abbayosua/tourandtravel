<?php
/**
 * AnalyticsTest — agregasi dengan fixture sementara.
 */
require_once __DIR__ . '/../../includes/analytics.php';

function analyticsFixtureSetup() {
    // booking fixture hari ini
    db()->exec("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status, payment_status, created_at) VALUES ('ANLY-1', 61, 1, 'A1', 'anly1@t.local', '08', 2, 500000, 'confirmed', 'paid', NOW())");
    db()->exec("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status, payment_status, created_at) VALUES ('ANLY-2', 61, 1, 'A2', 'anly2@t.local', '08', 1, 250000, 'cancelled', 'unpaid', NOW())");
}

function analyticsFixtureTeardown() {
    db()->exec("DELETE FROM bookings WHERE booking_code LIKE 'ANLY-%'");
}

function testAnalyticsKpiCountsNonCancelled() {
    analyticsFixtureSetup();
    try {
        [$from, $to] = analyticsRange();
        $kpi = analyticsKpi($from, $to);
        assertTrue($kpi['bookings'] >= 1, 'ada booking hari ini');
        assertTrue($kpi['revenue'] >= 500000, 'revenue = booking confirmed saja (cancelled tak dihitung)');
    } finally { analyticsFixtureTeardown(); }
}

function testAnalyticsFunnelByStatus() {
    analyticsFixtureSetup();
    try {
        [$from, $to] = analyticsRange();
        $f = analyticsFunnel($from, $to);
        assertTrue(($f['confirmed'] ?? 0) >= 1, 'ada confirmed hari ini');
        assertTrue(($f['cancelled'] ?? 0) >= 1, 'ada cancelled hari ini');
    } finally { analyticsFixtureTeardown(); }
}

function testAnalyticsRangeValidatesInput() {
    [$from, $to] = analyticsRange('SQL-INJECT', 'juga-salah');
    assertMatches('/^\d{4}-\d{2}-\d{2}$/', $from, 'from difallback ke default');
    assertMatches('/^\d{4}-\d{2}-\d{2}$/', $to, 'to difallback ke default');
}

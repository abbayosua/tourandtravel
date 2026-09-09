<?php
/**
 * ABTestTest — A/B testing ringan: varian deterministik per session, persist,
 * impresi idempotent, inactive test fallback, konversi, hasil agregat.
 */

const AB_TEST = 'tour_cta_text';

function abResetSession() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset($_SESSION['ab_variants']);
    if (session_id() === '') session_id('abtestsess' . random_int(1000, 9999));
}

function abCleanup() {
    db()->prepare("DELETE FROM ab_impressions WHERE test_name = ?")->execute([AB_TEST]);
}

function testAbVariantReturnsValidVariant() {
    abResetSession();
    abCleanup();
    $v = abVariant(AB_TEST);
    assertTrue(in_array($v, ['A', 'B'], true), "varian harus A/B, got: $v");
    abCleanup();
}

function testAbVariantDeterministicPerSession() {
    abResetSession();
    abCleanup();
    $v1 = abVariant(AB_TEST);
    $v2 = abVariant(AB_TEST);
    assertSame($v1, $v2, 'panggilan ulang → varian sama (deterministik)');
    abCleanup();
}

function testAbVariantPersistedInSession() {
    abResetSession();
    abCleanup();
    $v = abVariant(AB_TEST);
    assertTrue(isset($_SESSION['ab_variants'][AB_TEST]), 'varian tersimpan di session');
    assertSame($v, $_SESSION['ab_variants'][AB_TEST]);
    abCleanup();
}

function testAbTrackIdempotentPerSession() {
    abResetSession();
    abCleanup();
    abVariant(AB_TEST);
    abTrack(AB_TEST, 'A');
    abTrack(AB_TEST, 'A');
    $stmt = db()->prepare("SELECT COUNT(*) FROM ab_impressions WHERE test_name = ? AND session_id = ?");
    $stmt->execute([AB_TEST, session_id()]);
    assertEquals(1, $stmt->fetchColumn(), 'unique key → hanya 1 impresi per test/session');
    abCleanup();
}

function testAbVariantInactiveTestReturnsEmpty() {
    abResetSession();
    db()->prepare("UPDATE ab_tests SET is_active = 0 WHERE test_name = ?")->execute([AB_TEST]);
    unset($_SESSION['ab_variants']);
    assertEquals('', abVariant(AB_TEST), 'test nonaktif → string kosong');
    db()->prepare("UPDATE ab_tests SET is_active = 1 WHERE test_name = ?")->execute([AB_TEST]);
}

function testAbVariantUnknownTestReturnsEmpty() {
    abResetSession();
    unset($_SESSION['ab_variants']);
    assertEquals('', abVariant('tidak_ada_test_ini'), 'test tak dikenal → kosong');
}

function testAbConvertMarksImpression() {
    abResetSession();
    abCleanup();
    abVariant(AB_TEST);
    abConvert(AB_TEST);
    $stmt = db()->prepare("SELECT converted FROM ab_impressions WHERE test_name = ? AND session_id = ?");
    $stmt->execute([AB_TEST, session_id()]);
    assertEquals(1, (int)$stmt->fetchColumn(), 'impression ditandai converted = 1');
    abCleanup();
}

function testAbResultsAggregates() {
    abResetSession();
    abCleanup();
    // 2 impresi A (1 konversi), 1 impresi B
    db()->prepare("INSERT IGNORE INTO ab_impressions (test_name, variant, user_id, session_id, converted) VALUES (?, 'A', NULL, 'sessA1', 0), (?, 'A', NULL, 'sessA2', 1), (?, 'B', NULL, 'sessB1', 0)")->execute([AB_TEST, AB_TEST, AB_TEST]);
    $rows = abResults(AB_TEST);
    $byVariant = [];
    foreach ($rows as $r) $byVariant[$r['variant']] = $r;
    assertEquals(2, (int)$byVariant['A']['impressions'], 'A: 2 impresi');
    assertEquals(1, (int)$byVariant['A']['conversions'], 'A: 1 konversi');
    assertEquals(1, (int)$byVariant['B']['impressions'], 'B: 1 impresi');
    abCleanup();
}

<?php
/**
 * ReviewTranslationsTest — Backlog #9: review multi-bahasa.
 * Auto-translate kamus (id↔en↔zh), upsert idempotent, fallback chain display,
 * isolate per review (tak bocor antar review).
 */
require_once __DIR__ . '/../../includes/review-translations.php';

function rtTestCleanup(): void {
    db()->exec("DELETE rt FROM review_translations rt JOIN reviews r ON r.id = rt.review_id WHERE r.comment LIKE 'RT-TEST%'");
    db()->exec("DELETE FROM reviews WHERE comment LIKE 'RT-TEST%'");
    db()->exec("DELETE FROM users WHERE id >= 999050 AND email LIKE '%@rttest.local'");
}

function rtTestMakeReview(string $comment, string $lang): int {
    // unique_review constraint (1 review per tour+user) + FK users → user_id unik dengan row users
    $uid = (int)db()->query("SELECT COALESCE(MAX(user_id), 999050) + 1 FROM reviews WHERE user_id >= 999050")->fetchColumn();
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (?, 'RT Tester', ?, 'x')")
        ->execute([$uid, "rt{$uid}@rttest.local"]);
    db()->prepare("INSERT INTO reviews (tour_id, user_id, rating, comment, lang) VALUES (61, ?, 5, ?, ?)")
        ->execute([$uid, $comment, $lang]);
    return (int)db()->lastInsertId();
}

function testAutoTranslateIdToEnAndZh() {
    rtTestCleanup();
    $rid = rtTestMakeReview('RT-TEST Sangat bagus dan bersih', 'id');
    $n = rtGenerateForReview($rid, 'RT-TEST Sangat bagus dan bersih', 'id');
    assertEquals(2, $n, '2 terjemahan dibuat (en+zh)');
    $en = rtGetTranslation($rid, 'en');
    $zh = rtGetTranslation($rid, 'zh');
    assertMatches('/very good/', $en, 'id→en translate "Sangat bagus"');
    assertMatches('/干净/', $zh, 'id→zh translate "bersih"');
    rtTestCleanup();
}

function testAutoTranslateEnToId() {
    rtTestCleanup();
    $rid = rtTestMakeReview('RT-TEST Very good and clean place', 'en');
    rtGenerateForReview($rid, 'RT-TEST Very good and clean place', 'en');
    $id = rtGetTranslation($rid, 'id');
    assertMatches('/bagus/', $id, 'en→id translate "Very good"');
    assertMatches('/bersih/', $id, 'en→id translate "clean"');
    rtTestCleanup();
}

function testGenerateIdempotent() {
    rtTestCleanup();
    $rid = rtTestMakeReview('RT-TEST Mantap', 'id');
    $n1 = rtGenerateForReview($rid, 'RT-TEST Mantap', 'id');
    assertEquals(2, $n1);
    $n2 = rtGenerateForReview($rid, 'RT-TEST Mantap', 'id');
    assertEquals(0, $n2, 'kedua kalinya → 0 (sudah ada)');
    assertEquals(2, (int)db()->query("SELECT COUNT(*) FROM review_translations WHERE review_id = $rid")->fetchColumn());
    rtTestCleanup();
}

function testUpsertOverwritesTranslation() {
    rtTestCleanup();
    $rid = rtTestMakeReview('RT-TEST Upsert', 'id');
    rtSaveTranslation($rid, 'en', 'old text');
    rtSaveTranslation($rid, 'en', 'new text', 'manual');
    assertEquals('new text', rtGetTranslation($rid, 'en'), 'upsert overwrite');
    $src = db()->query("SELECT source FROM review_translations WHERE review_id = $rid AND lang = 'en'")->fetchColumn();
    assertEquals('manual', $src);
    assertEquals(1, (int)db()->query("SELECT COUNT(*) FROM review_translations WHERE review_id = $rid AND lang = 'en'")->fetchColumn(), 'tidak duplikat baris');
    rtTestCleanup();
}

function testDisplayFallbackChain() {
    rtTestCleanup();
    // review en + terjemahan id → tampil id (translated=true)
    $rid = rtTestMakeReview('RT-TEST Very good guide', 'en');
    rtGenerateForReview($rid, 'RT-TEST Very good guide', 'en');
    $review = db()->query("SELECT * FROM reviews WHERE id = $rid")->fetch();
    $d = rtDisplayText($review, 'id');
    assertEquals(true, $d['translated'], 'ada terjemahan → translated=true');
    assertMatches('/pemandu/', $d['text'], 'isi terjemahan id');

    // review id dilihat dgn lang id → teks asli, translated=false
    $rid2 = rtTestMakeReview('RT-TEST Pemandu bagus', 'id');
    $review2 = db()->query("SELECT * FROM reviews WHERE id = $rid2")->fetch();
    $d2 = rtDisplayText($review2, 'id');
    assertEquals(false, $d2['translated'], 'lang sama → tidak translated');
    assertEquals('RT-TEST Pemandu bagus', $d2['text']);

    // review tanpa terjemahan & lang beda → fallback teks asli
    $rid3 = rtTestMakeReview('RT-TEST No translation here', 'zh');
    $review3 = db()->query("SELECT * FROM reviews WHERE id = $rid3")->fetch();
    $d3 = rtDisplayText($review3, 'id');
    assertEquals(false, $d3['translated'], 'fallback → translated=false');
    assertEquals('RT-TEST No translation here', $d3['text'], 'teks asli ditampilkan');

    rtTestCleanup();
}

function testTranslationsIsolatedPerReview() {
    rtTestCleanup();
    $r1 = rtTestMakeReview('RT-TEST A Mantap', 'id');
    $r2 = rtTestMakeReview('RT-TEST B Mantap', 'id');
    rtGenerateForReview($r1, 'RT-TEST A Mantap', 'id');
    // review r2 belum diterjemahkan → null
    assertEquals(null, rtGetTranslation($r2, 'en'), 'terjemahan r1 tidak bocor ke r2');
    rtTestCleanup();
}

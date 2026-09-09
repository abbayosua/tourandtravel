<?php
/**
 * ReviewSubratingsTest — simpan + retrieve sub-rating, rata-rata per aspek.
 * Test langsung ke DB (INSERT/SELECT/AVG), cleanup setelah selesai.
 * Menggunakan user_id unik per review untuk hindari unique constraint.
 */

$_srvIdx = 90000; // base user_id offset untuk test

function _makeReview(int $tourId, int $rating = 5): int {
    global $_srvIdx;
    $_srvIdx++;
    $uid = $_srvIdx;
    // Buat user dummy unik
    db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (?, 'SubratingTest', ?, 'x')")->execute([$uid, "srtest$uid@test.local"]);
    // Cek apakah sudah ada review untuk user+tour ini
    $chk = db()->prepare("SELECT id FROM reviews WHERE tour_id = ? AND user_id = ?");
    $chk->execute([$tourId, $uid]);
    if ($existing = $chk->fetchColumn()) {
        return (int)$existing;
    }
    db()->prepare("INSERT INTO reviews (tour_id, user_id, rating, comment) VALUES (?, ?, ?, 'Test review subrating')")->execute([$tourId, $uid, $rating]);
    return (int)db()->lastInsertId();
}

function _cleanupReview(int $reviewId): void {
    db()->prepare("DELETE FROM review_subratings WHERE review_id = ?")->execute([$reviewId]);
    // ambil user_id dulu untuk cleanup
    $r = db()->prepare("SELECT user_id FROM reviews WHERE id = ?");
    $r->execute([$reviewId]);
    $uid = $r->fetchColumn();
    db()->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
    if ($uid) db()->prepare("DELETE FROM users WHERE id = ? AND name = 'SubratingTest'")->execute([$uid]);
}

function _saveSubratings(int $reviewId, array $aspects): void {
    $stmt = db()->prepare("INSERT IGNORE INTO review_subratings (review_id, aspect, rating) VALUES (?, ?, ?)");
    foreach ($aspects as $aspect => $rating) {
        $stmt->execute([$reviewId, $aspect, (int)$rating]);
    }
}

function _getSubratings(int $reviewId): array {
    $stmt = db()->prepare("SELECT aspect, rating FROM review_subratings WHERE review_id = ? ORDER BY aspect");
    $stmt->execute([$reviewId]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) $out[$row['aspect']] = (int)$row['rating'];
    return $out;
}

function _getAvgByReviewIds(array $reviewIds): array {
    if (empty($reviewIds)) return [];
    $ph = implode(',', array_fill(0, count($reviewIds), '?'));
    $stmt = db()->prepare("SELECT sr.aspect, AVG(sr.rating) AS avg_rating, COUNT(*) AS cnt FROM review_subratings sr WHERE sr.review_id IN ($ph) GROUP BY sr.aspect");
    $stmt->execute($reviewIds);
    $out = [];
    foreach ($stmt->fetchAll() as $row) $out[$row['aspect']] = ['avg' => (float)$row['avg_rating'], 'cnt' => (int)$row['cnt']];
    return $out;
}

// --- Tests ---

function testSaveAndRetrieveSubratings() {
    $rid = _makeReview(64);
    _saveSubratings($rid, ['cleanliness' => 5, 'location' => 4, 'staff' => 3]);
    $got = _getSubratings($rid);
    assertEquals(5, $got['cleanliness'], 'cleanliness = 5');
    assertEquals(4, $got['location'], 'location = 4');
    assertEquals(3, $got['staff'], 'staff = 3');
    assertEquals(3, count($got), '3 aspects saved');
    _cleanupReview($rid);
}

function testSubratingIdempotent() {
    $rid = _makeReview(64);
    _saveSubratings($rid, ['cleanliness' => 4]);
    _saveSubratings($rid, ['cleanliness' => 5]); // duplikat → no-op (INSERT IGNORE)
    $got = _getSubratings($rid);
    assertEquals(4, $got['cleanliness'], 'duplikat tidak menimpa');
    _cleanupReview($rid);
}

function testDeleteCascadeOnReviewDelete() {
    $rid = _makeReview(64);
    _saveSubratings($rid, ['cleanliness' => 5, 'location' => 4]);
    assertEquals(2, count(_getSubratings($rid)), 'before delete: 2 subratings');
    _cleanupReview($rid); // CASCADE via FK
    $stmt = db()->prepare("SELECT COUNT(*) FROM review_subratings WHERE review_id = ?");
    $stmt->execute([$rid]);
    assertEquals(0, (int)$stmt->fetchColumn(), 'subratings deleted via cascade');
}

function testAverageCalculationSingleReview() {
    $rid = _makeReview(64);
    _saveSubratings($rid, ['cleanliness' => 5, 'location' => 3, 'staff' => 4]);
    $avg = _getAvgByReviewIds([$rid]);
    assertTrue(isset($avg['cleanliness']), 'cleanliness avg exists');
    assertEquals(5.0, $avg['cleanliness']['avg'], 'avg cleanliness = 5');
    assertEquals(3.0, $avg['location']['avg'], 'avg location = 3');
    assertEquals(4.0, $avg['staff']['avg'], 'avg staff = 4');
    assertEquals(1, $avg['cleanliness']['cnt'], 'cnt = 1');
    _cleanupReview($rid);
}

function testAverageCalculationMultipleReviews() {
    $rid1 = _makeReview(64);
    $rid2 = _makeReview(64);
    _saveSubratings($rid1, ['cleanliness' => 5]);
    _saveSubratings($rid2, ['cleanliness' => 3]);
    $avg = _getAvgByReviewIds([$rid1, $rid2]);
    assertEquals(4.0, $avg['cleanliness']['avg'], 'avg (5+3)/2 = 4');
    assertEquals(2, $avg['cleanliness']['cnt'], 'cnt = 2');
    _cleanupReview($rid1);
    _cleanupReview($rid2);
}

function testAllSixAspects() {
    $rid = _makeReview(64);
    $all = ['cleanliness' => 5, 'location' => 4, 'staff' => 3, 'value' => 4, 'facilities' => 5, 'comfort' => 4];
    _saveSubratings($rid, $all);
    $got = _getSubratings($rid);
    assertEquals(6, count($got), '6 aspects saved');
    foreach ($all as $k => $v) assertEquals($v, $got[$k], "$k = $v");
    _cleanupReview($rid);
}

function testAverageWithMixedAspects() {
    $rid1 = _makeReview(64);
    $rid2 = _makeReview(64);
    _saveSubratings($rid1, ['cleanliness' => 5, 'location' => 4]);
    _saveSubratings($rid2, ['cleanliness' => 3]); // only cleanliness, no location
    $avg = _getAvgByReviewIds([$rid1, $rid2]);
    assertEquals(4.0, $avg['cleanliness']['avg'], 'avg cleanliness (5+3)/2');
    assertEquals(2, $avg['cleanliness']['cnt'], 'cleanliness cnt=2');
    assertEquals(4.0, $avg['location']['avg'], 'avg location = 4 (single)');
    assertEquals(1, $avg['location']['cnt'], 'location cnt=1');
    _cleanupReview($rid1);
    _cleanupReview($rid2);
}

function testSubratingOutOfRangeIgnored() {
    $rid = _makeReview(64);
    // Direct DB insert to bypass PHP validation
    db()->prepare("INSERT IGNORE INTO review_subratings (review_id, aspect, rating) VALUES (?, ?, ?)")->execute([$rid, 'staff', 0]);
    $got = _getSubratings($rid);
    assertEquals(0, count($got), 'rating 0 not saved (CHECK constraint)');
    _cleanupReview($rid);
}

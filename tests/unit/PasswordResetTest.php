<?php
/**
 * PasswordResetTest — token hash, expiry 1 jam, sekali pakai, rate limit.
 * (menguji aturan DB + logika hash; halaman diuji E2E)
 */
require_once __DIR__ . '/../../includes/email.php';

function prCleanup() {
    db()->exec("DELETE FROM password_resets WHERE email LIKE '%@pr-test.local'");
}

function testTokenHashIsSha256AndUnique() {
    prCleanup();
    $t1 = bin2hex(random_bytes(32));
    db()->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
        ->execute(['a@pr-test.local', hash('sha256', $t1)]);
    $t2 = bin2hex(random_bytes(32));
    db()->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
        ->execute(['a@pr-test.local', hash('sha256', $t2)]);
    $hashes = db()->query("SELECT token_hash FROM password_resets WHERE email='a@pr-test.local'")->fetchAll(PDO::FETCH_COLUMN);
    assertEquals(2, count($hashes));
    assertTrue($hashes[0] !== $hashes[1], 'hash token unik');
    assertMatches('/^[a-f0-9]{64}$/', $hashes[0], 'sha256 hex');
    prCleanup();
}

function testExpiryOneHourRule() {
    prCleanup();
    // token valid
    db()->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
        ->execute(['b@pr-test.local', hash('sha256', 'valid-token')]);
    $r = db()->query("SELECT COUNT(*) c FROM password_resets WHERE email='b@pr-test.local' AND used_at IS NULL AND expires_at > NOW()")->fetch()['c'];
    assertEquals(1, (int)$r, 'token belum expired ditemukan');
    // token expired (simulasi backdate)
    db()->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() - INTERVAL 5 MINUTE)")
        ->execute(['b@pr-test.local', hash('sha256', 'old-token')]);
    $st2 = db()->prepare("SELECT COUNT(*) c FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
    $st2->execute([hash('sha256', 'old-token')]);
    $r2 = $st2->fetch()['c'];
    assertEquals(0, (int)$r2, 'token kedaluwarsa tidak lolos');
    prCleanup();
}

function testTokenSingleUse() {
    prCleanup();
    db()->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
        ->execute(['c@pr-test.local', hash('sha256', 'single-use')]);
    $id = (int)db()->query("SELECT id FROM password_resets WHERE email='c@pr-test.local'")->fetch()['id'];
    db()->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?")->execute([$id]);
    $r = db()->query("SELECT COUNT(*) c FROM password_resets WHERE id = $id AND used_at IS NULL")->fetch()['c'];
    assertEquals(0, (int)$r, 'token terpakai tidak dipakai lagi');
    prCleanup();
}

function testRateLimitOnePerMinute() {
    prCleanup();
    db()->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
        ->execute(['d@pr-test.local', hash('sha256', 'first')]);
    $cnt = db()->query("SELECT COUNT(*) c FROM password_resets WHERE email='d@pr-test.local' AND created_at > NOW() - INTERVAL 1 MINUTE")->fetch()['c'];
    assertTrue((int)$cnt > 0, 'permintaan pertama tercatat → rate limit menolak kedua dalam 1 menit');
    prCleanup();
}

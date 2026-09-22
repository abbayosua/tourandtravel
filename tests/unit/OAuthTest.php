<?php
/**
 * OAuthTest — Fase 1: oauthGoogleUpsert() create / idempotent / link-by-email / invalid payload.
 * (verifikasi token via Google API diuji di E2E oauth-login.spec.ts)
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/oauth.php';

const OA_EMAIL = 'oa-user@oauth-test.local';

function oaCleanup() {
    db()->prepare("DELETE FROM users WHERE email = ?")->execute([OA_EMAIL]);
}

function testOauthCreateNewUser() {
    oaCleanup();
    $id = oauthGoogleUpsert(['sub' => 'SUB-OA-1', 'email' => OA_EMAIL, 'name' => 'OA User', 'picture' => 'http://x/1.png']);
    assertTrue($id > 0, 'user baru dibuat');
    $u = db()->prepare("SELECT name, google_id, avatar_url FROM users WHERE id = ?");
    $u->execute([$id]);
    $row = $u->fetch();
    assertEquals('OA User', $row['name']);
    assertEquals('SUB-OA-1', $row['google_id']);
    assertEquals('http://x/1.png', $row['avatar_url']);
    oaCleanup();
}

function testOauthIdempotentSameGoogleId() {
    oaCleanup();
    $id1 = oauthGoogleUpsert(['sub' => 'SUB-OA-2', 'email' => OA_EMAIL, 'name' => 'OA User']);
    $id2 = oauthGoogleUpsert(['sub' => 'SUB-OA-2', 'email' => OA_EMAIL, 'name' => 'OA User']);
    assertEquals($id1, $id2, 'sub sama → id sama (tidak duplikat)');
    $c = db()->prepare("SELECT COUNT(*) c FROM users WHERE google_id = ?");
    $c->execute(['SUB-OA-2']);
    assertEquals(1, (int)$c->fetch()['c']);
    oaCleanup();
}

function testOauthLinkByEmailForExistingAccount() {
    oaCleanup();
    // akun lama email+password (tanpa google_id)
    db()->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)")
        ->execute(['Legacy User', OA_EMAIL, password_hash('secret123', PASSWORD_DEFAULT)]);
    $legacyId = (int)db()->lastInsertId();

    $id = oauthGoogleUpsert(['sub' => 'SUB-OA-3', 'email' => OA_EMAIL, 'name' => 'Legacy User', 'picture' => 'http://x/av.png']);
    assertEquals($legacyId, $id, 'akun lama ter-link by email, bukan user baru');
    $u = db()->prepare("SELECT google_id, avatar_url FROM users WHERE id = ?");
    $u->execute([$legacyId]);
    $row = $u->fetch();
    assertEquals('SUB-OA-3', $row['google_id'], 'google_id tersimpan di akun lama');
    assertEquals('http://x/av.png', $row['avatar_url']);
    oaCleanup();
}

function testOauthLinkPreservesExistingAvatar() {
    oaCleanup();
    db()->prepare("INSERT INTO users (name, email, password_hash, avatar_url) VALUES (?, ?, ?, 'http://old/av.png')")
        ->execute(['Legacy 2', OA_EMAIL, password_hash('secret123', PASSWORD_DEFAULT)]);
    $legacyId = (int)db()->lastInsertId();

    oauthGoogleUpsert(['sub' => 'SUB-OA-4', 'email' => OA_EMAIL, 'name' => 'Legacy 2', 'picture' => '']);
    $av = db()->prepare("SELECT avatar_url FROM users WHERE id = ?");
    $av->execute([$legacyId]);
    assertEquals('http://old/av.png', $av->fetch()['avatar_url'], 'avatar lama tidak tertimpa picture kosong');
    oaCleanup();
}

function testOauthInvalidPayloadRejected() {
    oaCleanup();
    $threw = false;
    try {
        oauthGoogleUpsert(['sub' => '', 'email' => OA_EMAIL]); // sub kosong
    } catch (InvalidArgumentException $e) {
        $threw = true;
    }
    assertTrue($threw, 'sub kosong → InvalidArgumentException');
    $threw = false;
    try {
        oauthGoogleUpsert(['sub' => 'SUB-X', 'email' => 'bukan-email']); // email invalid
    } catch (InvalidArgumentException $e) {
        $threw = true;
    }
    assertTrue($threw, 'email invalid → InvalidArgumentException');
    $c = db()->prepare("SELECT COUNT(*) c FROM users WHERE email = ?");
    $c->execute([OA_EMAIL]);
    assertEquals(0, (int)$c->fetch()['c'], 'tidak ada user dibuat untuk payload invalid');
    oaCleanup();
}

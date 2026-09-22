<?php
/**
 * includes/oauth.php — Fase 1: logika upsert user dari payload Google (terverifikasi).
 * Dipisah dari api/oauth-google.php agar match/link/create bisa di-unit-test tanpa network.
 */

/**
 * Match user by google_id → link by email → buat baru. Return user_id.
 * $info: payload tokeninfo Google (sub, email, name, picture) — email WAJIB sudah verified.
 */
function oauthGoogleUpsert(array $info): int {
    $googleId = (string)($info['sub'] ?? '');
    $email = strtolower(trim($info['email'] ?? ''));
    $name = trim($info['name'] ?? '') ?: $email;
    $avatar = trim($info['picture'] ?? '');

    if ($googleId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Payload Google tidak lengkap');
    }

    $stmt = db()->prepare("SELECT id FROM users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        if ($userId) {
            db()->prepare("UPDATE users SET google_id = ?, avatar_url = COALESCE(NULLIF(?, ''), avatar_url) WHERE id = ?")
                ->execute([$googleId, $avatar, $userId]);
        }
    }

    if (!$userId) {
        $hash = hashPassword(bin2hex(random_bytes(16)));
        $stmt = db()->prepare("INSERT INTO users (name, email, password_hash, google_id, avatar_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $googleId, $avatar]);
        $userId = (int)db()->lastInsertId();

        require_once __DIR__ . '/email.php';
        sendEmailTemplate($email, 'welcome', ['name' => $name, 'subject' => 'Selamat Datang di ' . SITE_NAME], null);
    }

    return (int)$userId;
}

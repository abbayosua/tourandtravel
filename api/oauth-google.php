<?php
/**
 * api/oauth-google.php — Fase 1: OAuth social login (Google Identity Services).
 * POST JSON { credential } (Google ID token / JWT).
 * Verifikasi via https://oauth2.googleapis.com/tokeninfo → cocokkan email → login/link/buat user.
 */
require_once __DIR__ . '/helpers/cors.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/oauth.php';
require_once __DIR__ . '/helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('method_not_allowed', 'POST only', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$credential = trim($input['credential'] ?? '');

if ($credential === '' || strlen($credential) > 5000) {
    jsonError('invalid_credential', 'Credential kosong atau terlalu panjang');
}

// Verifikasi id_token ke Google (payload JSON atau null)
$verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
$ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true, 'header' => "User-Agent: tourandtravel\r\n"]]);
$raw = @file_get_contents($verifyUrl, false, $ctx);
$info = $raw ? json_decode($raw, true) : null;

if (!is_array($info) || empty($info['email_verified']) || $info['email_verified'] !== 'true') {
    jsonError('invalid_token', 'Token Google tidak valid atau email tidak terverifikasi', 401);
}

$aud = $info['aud'] ?? '';
if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '' && $aud !== GOOGLE_CLIENT_ID) {
    jsonError('invalid_audience', 'Token bukan untuk aplikasi ini', 401);
}

$googleId = (string)($info['sub'] ?? '');
$email = strtolower(trim($info['email'] ?? ''));
$name = trim($info['name'] ?? '') ?: $email;
$avatar = trim($info['picture'] ?? '');

try {
    $userId = oauthGoogleUpsert(['sub' => $googleId, 'email' => $email, 'name' => $name, 'picture' => $avatar]);
} catch (InvalidArgumentException $e) {
    jsonError('invalid_payload', $e->getMessage(), 401);
}

if (!loginUserById((int)$userId)) {
    jsonError('login_failed', 'Gagal membuat session', 500);
}

jsonOk(['user_id' => (int)$userId, 'name' => $name, 'avatar_url' => $avatar]);

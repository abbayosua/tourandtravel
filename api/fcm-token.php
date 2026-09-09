<?php
require_once __DIR__ . '/helpers/cors.php';
require_once __DIR__ . '/helpers/auth-check.php';
require_once __DIR__ . '/helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('method_not_allowed', 'POST only', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$token = trim($input['token'] ?? '');
$lang = trim($input['lang'] ?? '') ?: getCurrentLang();

if ($token === '' || strlen($token) < 32 || strlen($token) > 500) {
    jsonError('invalid_token', 'Format token tidak valid');
}
if ($lang !== '' && !isValidLang($lang)) {
    jsonError('invalid_lang', 'Bahasa tidak didukung');
}

$userId = getAuthUserId();

$stmt = db()->prepare("INSERT INTO fcm_tokens (user_id, token, platform, lang) VALUES (?, ?, 'android', NULLIF(?, '')) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), lang = VALUES(lang), updated_at = NOW()");
$stmt->execute([$userId, $token, $lang ?: null]);

jsonOk();

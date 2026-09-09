<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
cekLogin();

header('Content-Type: application/json');

function jsonPushError(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonPushError('POST only', 405);
}

$csrfToken = $_SESSION['csrf_token'] ?? '';
$postedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if ($csrfToken === '' || !hash_equals($csrfToken, is_string($postedToken) ? $postedToken : '')) {
    jsonPushError('Invalid CSRF token', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonPushError('Invalid JSON body');
}

$target = $input['target'] ?? 'all';
if (!in_array($target, ['all', 'lang', 'user'], true)) {
    jsonPushError('Invalid target');
}

$titleId = trim((string)($input['title_id'] ?? ''));
$bodyId = trim((string)($input['body_id'] ?? ''));
$titleEn = trim((string)($input['title_en'] ?? ''));
$bodyEn = trim((string)($input['body_en'] ?? ''));
$titleZh = trim((string)($input['title_zh'] ?? ''));
$bodyZh = trim((string)($input['body_zh'] ?? ''));

if ($titleId === '' || $bodyId === '') {
    jsonPushError('Judul dan isi pesan (ID) wajib diisi');
}

$langFilter = trim((string)($input['lang'] ?? ''));
if ($target === 'lang' && !isValidLang($langFilter)) {
    jsonPushError('Bahasa tidak valid');
}

$userId = (int)($input['user_id'] ?? 0);
if ($target === 'user' && $userId <= 0) {
    jsonPushError('User ID tidak valid');
}

require_once '../../includes/fcm-push.php';

if (!defined('FCM_SERVER_KEY') || FCM_SERVER_KEY === '') {
    jsonPushError('FCM_SERVER_KEY belum dikonfigurasi', 500);
}

$msgs = ['id' => [$titleId, $bodyId]];
if ($titleEn !== '' && $bodyEn !== '') $msgs['en'] = [$titleEn, $bodyEn];
if ($titleZh !== '' && $bodyZh !== '') $msgs['zh'] = [$titleZh, $bodyZh];

// kumpulkan token sesuai target (left join users utk nama di log bila perlu)
$q = "SELECT t.token, COALESCE(NULLIF(t.lang, ''), 'id') AS lang, t.user_id FROM fcm_tokens t";
$params = [];
if ($target === 'lang') {
    $q .= " WHERE t.lang = ?";
    $params[] = $langFilter;
} elseif ($target === 'user') {
    $q .= " WHERE t.user_id = ?";
    $params[] = $userId;
}
$stmt = db()->prepare($q . " ORDER BY t.updated_at DESC");
$stmt->execute($params);
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!count($tokens)) {
    jsonPushError('Tidak ada device/token ditemukan untuk target ini');
}

$sent = 0;
$failed = 0;
foreach ($tokens as $row) {
    $lang = in_array($row['lang'], ['id', 'en', 'zh'], true) ? $row['lang'] : 'id';
    $msg = $msgs[$lang] ?? $msgs['id'];
    if (sendFcmToToken($row['token'], $msg[0], $msg[1], ['type' => 'broadcast'])) {
        $sent++;
    } else {
        $failed++;
    }
}

// log pengiriman
try {
    $targetLabel = $target === 'all' ? 'all' : ($target === 'lang' ? 'lang:' . $langFilter : 'user:' . $userId);
    db()->prepare("INSERT INTO push_log (admin_id, target, title, sent, failed) VALUES (?, ?, ?, ?, ?)")
        ->execute([$_SESSION['user_id'] ?? 0, $targetLabel, $titleId, $sent, $failed]);
} catch (Throwable $e) {
    // log tidak kritis
}

echo json_encode(['ok' => true, 'sent' => $sent, 'failed' => $failed]);

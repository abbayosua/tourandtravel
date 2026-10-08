<?php
/**
 * admin/ajax/tour-translate-ai.php — pratinjau translate AI AUTO untuk form tour.
 *
 * POST JSON: {"source_lang": "id"|"en"|"zh", "fields": {"title": "...", ...}}
 * Return: {"ok": true, "translations": {"en": {...}, "zh": {...}}}
 *
 * Field yang diizinkan = kolom i18n tabel tours (cegah prompt-injection liar).
 */
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../includes/admin-access.php';
requireAdminPage('tours');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
    exit;
}

$allowedFields = [
    'title', 'category', 'description', 'route_cities', 'highlights',
    'includes', 'excludes', 'flight_info', 'meeting_point', 'important_notes',
];

$src = (string)($input['source_lang'] ?? '');
if (!isValidLang($src)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => t('Bahasa sumber tidak valid')]);
    exit;
}

$rawFields = $input['fields'] ?? [];
if (!is_array($rawFields)) $rawFields = [];
$fields = [];
foreach ($allowedFields as $f) {
    $v = trim((string)($rawFields[$f] ?? ''));
    if ($v !== '') $fields[$f] = $v;
}
if (!$fields) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => t('Isi dulu minimal 1 kolom dalam bahasa sumber.')]);
    exit;
}

require_once '../../includes/atria.php';

try {
    $targets = array_values(array_filter(['id', 'en', 'zh'], fn($l) => $l !== $src));
    $out = atriaTranslateTour($fields, $src, $targets);
    echo json_encode(['ok' => true, 'translations' => $out], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

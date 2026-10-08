<?php
// tests/mock-atria-router.php — mock Atria Chat Completions untuk edge-case test.
// Skenario dari file /tmp/atria-scenario.txt (ditulis test per-kasus) agar bisa
// ganti tanpa restart server. Isi: ok | fenced | invalid-json | http500 | partial | extra | literal-nl
$m = trim(@file_get_contents('/tmp/atria-scenario.txt') ?: 'ok');
file_put_contents('/tmp/atria-mock-last.json', file_get_contents('php://input'));

$scenarios = [
    'ok' => [200, ['en' => ['title' => 'T EN'], 'zh' => ['title' => 'T ZH']]],
    'fenced' => [200, "```json\n{\"en\":{\"title\":\"T EN\"},\"zh\":{\"title\":\"T ZH\"}}\n```"],
    'invalid-json' => [200, 'maaf, saya tidak bisa menerjemahkan itu'],
    'http500' => [500, ['error' => ['message' => 'boom overloaded']]],
    'partial' => [200, ['en' => ['title' => 'T EN']]],
    'extra' => [200, ['en' => ['title' => 'T EN', 'hacked' => 'X'], 'xx' => ['title' => 'Y']]],
    'literal-nl' => [200, ['en' => ['includes' => 'a\\nb']]],
];
[$code, $payload] = $scenarios[$m] ?? $scenarios['ok'];
http_response_code($code);
header('Content-Type: application/json');
if ($code !== 200) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    return;
}
$content = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
echo json_encode(['choices' => [['message' => ['content' => $content]]]], JSON_UNESCAPED_UNICODE);

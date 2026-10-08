<?php
// tests/atria-edge-check.php — 20 edge case AI AUTO translate (jalan: php tests/atria-edge-check.php)
// Grup A: lib tanpa API. Grup B: via mock server. Grup C: butuh API asli (dilewati bila ATRIA_EDGE_LIVE!=1).
$pass = 0;
$fail = 0;
function ok($cond, $name) {
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS $name\n"; }
    else { $fail++; echo "FAIL $name\n"; }
}
function expectThrow($fn, $needle, $name) {
    try { $fn(); ok(false, "$name (tak ada exception)"); }
    catch (Throwable $e) { ok(str_contains($e->getMessage(), $needle), "$name [{$e->getMessage()}]"); }
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/atria.php';

// ---- A. tanpa API ----
ok(atriaStripCodeFence("```json\n{\"a\":1}```") === '{"a":1}', 'A1 fence json');
ok(atriaStripCodeFence("```\n{\"a\":2}\n```") === '{"a":2}', 'A2 fence plain');
ok(atriaStripCodeFence('{"plain":true}') === '{"plain":true}', 'A3 tanpa fence');
ok(atriaStripCodeFence("  ```json\n{\"x\":1}\n```  \n") === '{"x":1}', 'A4 fence+spasi');
ok(atriaTranslateTour([], 'id', ['en']) === [], 'A5 field kosong → []');
ok(atriaTranslateTour(['title' => 'x'], 'id', ['id']) === [], 'A6 target==source → []');
ok(atriaTranslateTour(['t' => '   '], 'id', ['en']) === [], 'A7 whitespace-only → []');
ok(atriaLangName('id') === 'Indonesian' && str_contains(atriaLangName('zh'), 'Chinese'), 'A8 label bahasa');

// ---- B. via mock ----
$mock = getenv('ATRIA_MOCK_BASE') ?: '';
if ($mock === '') { echo "SKIP grup B (tanpa mock server)\n"; }
else {
    $run = function (string $scenario, array $fields, string $src, array $targets) {
        file_put_contents('/tmp/atria-scenario.txt', $scenario);
        return atriaTranslateTour($fields, $src, $targets);
    };
    $r = $run('ok', ['title' => 'Halo'], 'id', ['en', 'zh']);
    ok(($r['en']['title'] ?? '') === 'T EN' && ($r['zh']['title'] ?? '') === 'T ZH', 'B1 ok 2 bahasa');
    $r = $run('fenced', ['title' => 'Halo'], 'id', ['en', 'zh']);
    ok(($r['zh']['title'] ?? '') === 'T ZH', 'B2 fence terkupas');
    expectThrow(fn() => $run('invalid-json', ['title' => 'Halo'], 'id', ['en']), 'bukan JSON', 'B3 invalid JSON → exception');
    expectThrow(fn() => $run('http500', ['title' => 'Halo'], 'id', ['en']), 'HTTP 500', 'B4 http500 → exception');
    $r = $run('partial', ['title' => 'Halo'], 'id', ['en', 'zh']);
    ok(isset($r['en']) && !isset($r['zh']), 'B5 partial: zh hilang tak crash');
    $r = $run('extra', ['title' => 'Halo'], 'id', ['en']);
    ok(($r['en']['title'] ?? '') === 'T EN' && !isset($r['en']['hacked']) && !isset($r['xx']), 'B6 field asing diabaikan');
    $r = $run('literal-nl', ['includes' => 'a'], 'id', ['en']);
    ok(($r['en']['includes'] ?? '') === "a\nb", 'B7 literal \\n → newline asli');
}

// ---- C. API asli (opt-in) ----
if (getenv('ATRIA_EDGE_LIVE') === '1') {
    $r = atriaTranslateTour(['category' => 'Eropa'], 'en', ['id', 'zh']);
    ok(($r['id']['category'] ?? '') !== '' && ($r['zh']['category'] ?? '') !== '', 'C1 EN→ID+ZH string pendek');
    $r = atriaTranslateTour(['title' => 'Test “kutip” & <tag> ✓'], 'id', ['en']);
    ok(str_contains($r['en']['title'] ?? '', 'Test'), 'C2 karakter spesial tak crash JSON');
}

echo "---\nPASS: $pass FAIL: $fail\n";
exit($fail > 0 ? 1 : 0);

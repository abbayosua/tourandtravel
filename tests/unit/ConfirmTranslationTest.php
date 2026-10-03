<?php
/**
 * ConfirmTranslationTest — guard: string konfirmasi JS inline yang membungkus
 * terjemahan di dalam kutip tunggal (mis. confirm + t()) tidak boleh memuat
 * apostrof pada terjemahan en/zh, karena apostrof menutup string literal JS →
 * handler onclick error → dialog konfirmasi tidak muncul sehingga aksi
 * destruktif langsung berjalan.
 *
 * Bila teks perlu apostrof, pakai bentuk aman:
 *   confirm(htmlspecialchars(json_encode(terjemahan), ENT_QUOTES))
 */
function testConfirmTranslationsHaveNoApostrophe(): void {
    $root = dirname(__DIR__, 2);
    $keys = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($it as $f) {
        if ($f->getExtension() !== 'php') continue;
        $p = str_replace('\\', '/', $f->getPathname());
        if (str_contains($p, '/node_modules/') || str_contains($p, '/vendor/') || str_contains($p, '/tests/')) continue;
        $s = @file_get_contents($p);
        if ($s === false) continue;
        if (preg_match_all("/confirm\('<\?=\s*t\('(.*?)'\)\s*\?>'\)/", $s, $m)) {
            foreach ($m[1] as $k) $keys[$k] = true;
        }
    }
    assertTrue(count($keys) > 0, 'harus menemukan key confirm()');

    $bad = [];
    $stmt = db()->prepare("SELECT lang, value FROM translations WHERE `key` = ? AND lang IN ('en','zh')");
    foreach (array_keys($keys) as $k) {
        $stmt->execute([$k]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (strpos((string)$row['value'], "'") !== false) {
                $bad[] = $k . ' [' . $row['lang'] . ']';
            }
        }
    }
    sort($bad);
    assertSame([], $bad, 'Konfirmasi confirm() dgn apostrof di terjemahan (merusak JS onclick)');
}

<?php
/**
 * I18nKeysTest — regresi kelengkapan terjemahan untuk semua key t() di kode.
 *
 * Menjamin setiap key t('...') literal di codebase punya terjemahan en DAN zh
 * di tabel `translations` (perbandingan case-insensitive, karena collation DB
 * ai_ci). Key yang gagal = terjemahan bocor: saat bahasa aktif en/zh, t() akan
 * jatuh ke string Indonesia (fallback = key).
 *
 * Brand / akronim yang sengaja tidak diterjemahkan masuk ke allowlist.
 */

/** Brand / akronim / unit yang sengaja dibiarkan identik di semua bahasa. */
function i18nBrandKeyAllowlist(): array {
    return ['/pax', 'OYO', 'PDF', 'PELNI', 'Pelni', 'QRIS'];
}

/** Kumpulkan semua key t('...') / t("...") literal dari file PHP aplikasi. */
function i18nScanCodeKeys(): array {
    $root = dirname(__DIR__, 2);
    $skip = ['.git', 'node_modules', 'test-results', 'scripts', 'vendor', 'uploads', '.serena'];
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            function ($f) use ($skip) {
                if ($f->isDir()) return !in_array($f->getFilename(), $skip);
                return preg_match('/\.php$/', $f->getFilename()) === 1;
            }
        )
    );
    $keys = [];
    foreach ($it as $f) {
        $src = @file_get_contents($f->getPathname());
        if ($src === false) continue;
        if (preg_match_all("/\bt\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*[,)]/", $src, $m)) {
            foreach ($m[1] as $k) { $k = stripslashes($k); if (trim($k) !== '') $keys[$k] = true; }
        }
        if (preg_match_all('/\bt\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*[,)]/', $src, $m)) {
            foreach ($m[1] as $k) { $k = stripslashes($k); if (trim($k) !== '') $keys[$k] = true; }
        }
    }
    return array_keys($keys);
}

/** Map case-insensitive: lower(key) => lang => [nilai non-kosong...]. */
function i18nDbValueMap(): array {
    $map = [];
    foreach (db()->query("SELECT `key`, lang, value FROM translations")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $lk = mb_strtolower(trim($r['key']));
        if (trim((string)$r['value']) !== '') $map[$lk][$r['lang']][] = $r['value'];
    }
    return $map;
}

function testEveryCodeKeyHasEnAndZh(): void {
    $map = i18nDbValueMap();
    $allow = array_map('mb_strtolower', i18nBrandKeyAllowlist());
    $missingEn = [];
    $missingZh = [];
    foreach (i18nScanCodeKeys() as $k) {
        $lk = mb_strtolower(trim($k));
        if (in_array($lk, $allow, true)) continue;
        if (empty($map[$lk]['en'])) $missingEn[] = $k;
        if (empty($map[$lk]['zh'])) $missingZh[] = $k;
    }
    sort($missingEn); sort($missingZh);
    assertSame([], $missingEn, 'Key t() tanpa terjemahan en');
    assertSame([], $missingZh, 'Key t() tanpa terjemahan zh');
}

function testPaymentLabelsLocalized(): void {
    // Label pembayaran di booking-success.php (dulu hardcoded).
    foreach (['Virtual Account', 'Biaya'] as $k) {
        $_SESSION['lang'] = 'zh'; $_COOKIE['lang'] = 'zh';
        $zh = t($k);
        assertTrue($zh !== $k && preg_match('/[\x{4E00}-\x{9FFF}]/u', $zh), "zh '$k' tidak diterjemahkan (got '$zh')");
        $_SESSION['lang'] = 'en'; $_COOKIE['lang'] = 'en';
        assertTrue(t($k) !== '', "en '$k' kosong");
    }
    $_SESSION['lang'] = 'id';
}

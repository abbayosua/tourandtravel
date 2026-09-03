<?php
/**
 * Audit Translations — scan semua t('...') di codebase vs tabel translations
 * Output:
 *   scripts/out/all_keys.txt        — semua unique key t() di codebase
 *   scripts/out/missing_keys.txt    — key t() yang belum ada di DB (lang=en)
 *   scripts/out/keys_without_t.txt  — teks Indonesia hardcoded (bukan t())
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$outDir = __DIR__ . '/out';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

// 1. Scan semua t('...') literal di file PHP
$dirs = [__DIR__ . '/..', __DIR__ . '/../includes', __DIR__ . '/../admin', __DIR__ . '/../database'];
$allKeys = [];
$files = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/..', FilesystemIterator::SKIP_DOTS),
        function ($file) {
            if ($file->isDir()) return !in_array($file->getFilename(), ['.git', 'node_modules', 'test-results', 'scripts', 'vendor', 'uploads']);
            return preg_match('/\.php$/', $file->getFilename());
        }
    )
);
foreach ($files as $f) {
    $src = file_get_contents($f->getPathname());
    if (preg_match_all("/\bt\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*[,)]/", $src, $m)) {
        foreach ($m[1] as $key) {
            $key = stripslashes($key);
            if (trim($key) !== '') $allKeys[$key] = true;
        }
    }
    if (preg_match_all('/\bt\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*[,)]/', $src, $m)) {
        foreach ($m[1] as $key) {
            $key = stripslashes($key);
            if (trim($key) !== '') $allKeys[$key] = true;
        }
    }
}
$allKeys = array_keys($allKeys);
sort($allKeys);
file_put_contents("$outDir/all_keys.txt", implode("\n", $allKeys) . "\n");
echo "Total unique t() keys: " . count($allKeys) . "\n";

// 2. Bandingkan dengan DB (lang=en) — collation MySQL case-insensitive, normalize lowercase
$stmt = db()->query("SELECT `key` FROM translations WHERE lang = 'en'");
$dbKeys = [];
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $k) $dbKeys[mb_strtolower(trim($k))] = true;

$missing = array_values(array_filter($allKeys, fn($k) => !isset($dbKeys[mb_strtolower(trim($k))])));
file_put_contents("$outDir/missing_keys.txt", implode("\n", $missing) . "\n");
echo "Missing en di DB: " . count($missing) . "\n";

// 3. Scan teks Indonesia hardcoded (huruf kapital awal + spasi, di luar t())
//    Heuristik: tag HTML berisi teks yang dimulai huruf kapital/angka, bukan tag/attr
$hardcoded = [];
foreach ($files as $f) {
    $src = file_get_contents($f->getPathname());
    // Ambil blok HTML (di luar tag PHP — pindahkan ke file terpisah agar tidak konflik parser)
    $blocks = preg_split('/<\?php.*?\?>/s', $src);
    foreach ($blocks as $bi => $block) {
        if (trim($block) === '') continue;
        // Ambil teks di antara tag >...<
        if (preg_match_all('/>([^<>]{3,80})</', $block, $m)) {
            foreach ($m[1] as $txt) {
                $txt = trim(html_entity_decode(strip_tags($txt)));
                if ($txt === '' || strlen($txt) < 3) continue;
                // Skip kalau sudah pakai t()/e()/PHP
                if (preg_match('/<\?php|\$\w|t\(|e\(|formatRupiah|formatCurrency|BASE_URL|urlencode|str_repeat|nl2br|ucfirst|date\(|number_format|substr|implode|array_/', $txt)) continue;
                // Skip jika tidak mengandung huruf
                if (!preg_match('/[A-Za-zÀ-ÿ]/', $txt)) continue;
                // Hanya teks yang punya huruf kapital di awal kata (kandidat label ID/EN)
                if (!preg_match('/^[A-Z0-9À-ÿ]/', $txt)) continue;
                // Skip angka murni, simbol, placeholder image URL
                if (preg_match('/^[0-9\s.,%()+\-]+$/', $txt)) continue;
                if (preg_match('/\.(jpg|jpeg|png|webp|svg|gif|ico)/i', $txt)) continue;
                $rel = str_replace(__DIR__ . '/..', '', $f->getPathname());
                if (!isset($hardcoded[$txt])) $hardcoded[$txt] = $rel;
            }
        }
    }
}
$hardcodedKeys = array_keys($hardcoded);
sort($hardcodedKeys);
file_put_contents("$outDir/keys_without_t.txt", implode("\n", $hardcodedKeys) . "\n");
echo "Hardcoded text candidates: " . count($hardcodedKeys) . "\n";

echo "\nOutput files:\n";
echo "  $outDir/all_keys.txt\n";
echo "  $outDir/missing_keys.txt\n";
echo "  $outDir/keys_without_t.txt\n";

<?php
/**
 * Audit Translations — scan semua t('...') di codebase vs tabel translations
 * Output:
 *   scripts/out/all_keys.txt                   — semua unique key t() di codebase
 *   scripts/out/missing_keys.txt               — key t() yang belum ada di DB (lang=en)
 *   scripts/out/keys_without_t.txt             — teks Indonesia hardcoded (gabungan semua cluster)
 *   scripts/out/keys_without_t_<cluster>.txt   — pecah per cluster:
 *       public (root *.php) | includes | ajax (*-ajax.php + ajax/) | admin | js (assets/js)
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

// 4. Pecah kandidat hardcoded per cluster (berdasarkan file lokasi pertama ditemukan)
$clusters = ['public' => [], 'includes' => [], 'ajax' => [], 'admin' => [], 'js' => [], 'other' => []];
foreach ($hardcoded as $txt => $rel) {
    $clusters[classifyCluster($rel)][$txt] = $rel;
}

// 5. Scan string user-visible di assets/js/*.js (context: textContent/innerHTML/confirm/alert/placeholder/title)
$jsDir = __DIR__ . '/../assets/js';
foreach (glob($jsDir . '/*.js') ?: [] as $jsFile) {
    $rel = str_replace(__DIR__ . '/..', '', $jsFile);
    $lines = file($jsFile) ?: [];
    foreach ($lines as $line) {
        if (!preg_match('/textContent|innerHTML|confirm\s*\(|alert\s*\(|placeholder|\.title/', $line)) continue;
        if (preg_match_all('/[\'"]([^\'"\n]{3,120})[\'"]/', $line, $m)) {
            foreach ($m[1] as $s) {
                $s = trim($s);
                if ($s === '' || strlen($s) < 3) continue;
                if (!preg_match('/[A-Za-z\xC0-\xFF]/', $s)) continue;      // harus ada huruf
                if (preg_match('/^(https?:|\/|\.|#|[A-Z_]+$)/', $s)) continue; // url/selector/konstanta
                if (preg_match('/^[0-9\s.,%()+\-Rp]+$/', $s)) continue;   // angka/mata uang polos
                if (preg_match('/^:?[a-z]+(-[a-z]+)*$/', $s) && !preg_match('/\s/', $s) && !in_array($s, ['malam', 'kamar', 'paket'])) continue; // css-class/param-like
                $clusters['js'][$s] = $rel;
                if (!isset($hardcoded[$s])) $hardcoded[$s] = $rel;
            }
        }
    }
}
// Regenerate gabungan (kini termasuk JS)
$hardcodedKeys = array_keys($hardcoded);
sort($hardcodedKeys);
file_put_contents("$outDir/keys_without_t.txt", implode("\n", $hardcodedKeys) . "\n");

foreach ($clusters as $name => $items) {
    if ($name === 'other' && !$items) continue;
    $keys = array_keys($items);
    sort($keys);
    $lines = "";
    foreach ($keys as $k) $lines .= $k . "    [" . $items[$k] . "]\n";
    file_put_contents("$outDir/keys_without_t_$name.txt", $lines);
    echo "  cluster $name: " . count($keys) . " kandidat\n";
}

echo "\nOutput files:\n";
echo "  $outDir/all_keys.txt\n";
echo "  $outDir/missing_keys.txt\n";
echo "  $outDir/keys_without_t.txt (+ per-cluster: _public _includes _ajax _admin _js)\n";

function classifyCluster(string $rel): string {
    if (preg_match('#/admin/#', $rel)) return 'admin';
    if (preg_match('#/includes/#', $rel)) return 'includes';
    if (preg_match('#/ajax/#', $rel) || preg_match('#-ajax\.php$#', $rel)) return 'ajax';
    if (preg_match('#/assets/js/#', $rel)) return 'js';
    if (preg_match('#^/[a-z0-9\-]+\.php$#', $rel)) return 'public';
    return 'other';
}

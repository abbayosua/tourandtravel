<?php
/**
 * Audit Admin Translations — scan literal text di admin/*.php
 * Output: scripts/out/admin_keys.txt
 */

$outDir = __DIR__ . '/out';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);

$keys = [];
$files = glob(__DIR__ . '/../admin/*.php');

foreach ($files as $f) {
    $src = file_get_contents($f);
    $rel = basename($f);

    // 1. pageTitle = '...'
    if (preg_match_all("/pageTitle\s*=\s*'((?:[^'\\\\]|\\\\.)*)'/", $src, $m)) {
        foreach ($m[1] as $k) if (trim($k) !== '') $keys[$k] = true;
    }
    // 2. Flash messages (match ... 'Berhasil ...')
    if (preg_match_all("/'((?:Berhasil|Gagal|Pilih|Silakan|Data|Harap|Kurs|Perbarui|Hapus|Simpan|Tambah|Edit|Tidak)[^']*)'/", $src, $m)) {
        foreach ($m[1] as $k) if (trim($k) !== '') $keys[$k] = true;
    }
    // 3. HTML text nodes >Text<
    if (preg_match_all('/>([^<>]{3,80})</', $src, $m)) {
        foreach ($m[1] as $txt) {
            $txt = trim(html_entity_decode(strip_tags($txt)));
            if ($txt === '' || strlen($txt) < 3) continue;
            if (preg_match('/<\?php|\$\w|t\(|e\(|formatRupiah|BASE_URL|urlencode|str_repeat|ucfirst|date\(|number_format|substr|implode|array_/', $txt)) continue;
            if (!preg_match('/[A-Za-zÀ-ÿ]/', $txt)) continue;
            if (!preg_match('/^[A-Z0-9À-ÿ]/', $txt)) continue;
            if (preg_match('/\.(jpg|jpeg|png|webp|svg|gif|ico)/i', $txt)) continue;
            $keys[$txt] = true;
        }
    }
}

$keys = array_keys($keys);
sort($keys);
file_put_contents("$outDir/admin_keys.txt", implode("\n", $keys) . "\n");
echo "Admin literal keys: " . count($keys) . "\n";
echo "Output: $outDir/admin_keys.txt\n";

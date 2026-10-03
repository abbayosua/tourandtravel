<?php
/**
 * I18nHardcodedTest — guard terhadap atribut user-facing yang hardcoded
 * (placeholder/aria-label/title/alt) berisi teks Indonesia, yang tidak
 * tertangkap audit >text< maupun sweep (karena berada di atribut).
 */

/** Kata penanda khas Indonesia untuk nilai atribut. */
function i18nAttrMarkers(): string {
    return '/\b(' . implode('|', [
        'contoh', 'nama', 'nomor', 'alamat', 'telepon', 'sandi', 'depan', 'belakang',
        'pilih', 'cari', 'tanggal', 'jumlah', 'harga', 'kota', 'tiket', 'pesan',
        'kamar', 'malam', 'orang', 'kirim', 'simpan', 'hapus', 'tambah', 'lihat',
        'yang', 'untuk', 'dengan', 'tidak', 'dari',
    ]) . ')\b/i';
}

function testNoHardcodedIndonesianAttributes(): void {
    $root = dirname(__DIR__, 2);
    $skip = ['.git', 'node_modules', 'test-results', 'scripts', 'vendor', 'uploads', '.serena', 'tests'];
    $re = i18nAttrMarkers();
    $attrs = ['placeholder', 'aria-label', 'title', 'alt'];
    $bad = [];

    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            function ($f) use ($skip) {
                if ($f->isDir()) return !in_array($f->getFilename(), $skip);
                return preg_match('/\.php$/', $f->getFilename()) === 1;
            }
        )
    );
    foreach ($it as $f) {
        $src = @file_get_contents($f->getPathname());
        if ($src === false) continue;
        foreach ($attrs as $a) {
            if (preg_match_all('/' . $a . '="([^"]+)"/', $src, $m)) {
                foreach ($m[1] as $v) {
                    if (strpos($v, '<?') !== false || strpos($v, '$') !== false) continue;
                    // Lewati nilai yang dibangun PHP (concatenation/e()/t()).
                    if (strpos($v, "'") !== false || strpos($v, 't(') !== false || strpos($v, 'e(') !== false) continue;
                    if (preg_match($re, $v)) {
                        $bad[] = basename($f->getPathname()) . ': ' . $a . '="' . $v . '"';
                    }
                }
            }
        }
    }
    sort($bad);
    assertSame([], $bad, 'Atribut user-facing hardcoded berbahasa Indonesia');
}

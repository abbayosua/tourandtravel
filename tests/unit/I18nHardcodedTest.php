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

/** Kata penanda Indonesia untuk teks node (di luar tag). */
function i18nTextMarkers(): string {
    return '/\b(' . implode('|', [
        'yang', 'untuk', 'dengan', 'tidak', 'dari', 'atau', 'nama', 'nomor', 'alamat',
        'pilih', 'cari', 'lihat', 'kirim', 'simpan', 'hapus', 'tambah', 'tanggal',
        'jumlah', 'harga', 'kota', 'tiket', 'pesan', 'kamar', 'malam', 'orang',
        'depan', 'belakang', 'telepon', 'sandi', 'masuk', 'daftar', 'keluar', 'kembali',
        'lanjut', 'penting', 'catatan', 'ulasan', 'diskon', 'kupon', 'poin', 'saldo',
        'rute', 'jadwal', 'bandara', 'penumpang', 'penerbangan', 'keberangkatan',
        'stasiun', 'pelabuhan', 'perjalanan', 'wisata', 'destinasi', 'tentang',
        'ketentuan', 'kebijakan', 'bantuan', 'hubungi', 'beranda', 'favorit',
        'notifikasi', 'profil', 'reseller',
    ]) . ')\b/i';
}

function testNoHardcodedIndonesianTextNodes(): void {
    $root = dirname(__DIR__, 2);
    $skip = ['.git', 'node_modules', 'test-results', 'scripts', 'vendor', 'uploads', '.serena', 'tests'];
    $re = i18nTextMarkers();
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
        // Buang blok script/style (kode JS/CSS, bukan teks HTML).
        $src = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $src);
        $src = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $src);
        $src = preg_replace('/<\?(?:php|=).*?(?:\?>|$)/s', '', $src);
        $blocks = [$src];
        foreach ($blocks as $block) {
            if (preg_match_all('/>([^<>]{3,80})</', $block, $m)) {
                foreach ($m[1] as $txt) {
                    $txt = trim(html_entity_decode(strip_tags($txt)));
                    if ($txt === '' || strlen($txt) < 3) continue;
                    if (preg_match('/<\?php|$\w|t\(|e\(|BASE_URL|urlencode|formatRupiah|formatCurrency|nl2br/', $txt)) continue;
                    if (!preg_match($re, $txt)) continue;
                    $bad[] = basename($f->getPathname()) . ': "' . $txt . '"';
                }
            }
        }
    }
    sort($bad);
    assertSame([], $bad, 'Teks node user-facing hardcoded berbahasa Indonesia');
}

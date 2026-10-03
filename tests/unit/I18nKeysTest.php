<?php
/**
 * I18nKeysTest — regresi kelengkapan terjemahan untuk semua key t() di kode.
 *
 * Menjamin setiap key t() literal di codebase punya terjemahan en DAN zh
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

/**
 * Key yang sah bernilai en == key meski memuat kata Indonesia (kata serapan
 * yang juga dipakai bahasa Inggris / brand).
 */
function i18nIdentityEnAllowlist(): array {
    return ['reseller', 'reseller booking'];
}

/** Kata penanda khas Indonesia (bukan kata serapan Inggris). */
function i18nIndonesianMarkers(): string {
    return '/\b(' . implode('|', [
        'yang', 'untuk', 'dengan', 'tidak', 'belum', 'sudah', 'akan', 'dari', 'atau', 'juga',
        'bisa', 'dapat', 'anda', 'kami', 'kita', 'pada', 'oleh', 'agar', 'saat', 'setiap',
        'lebih', 'paling', 'cari', 'pilih', 'lihat', 'buka', 'tutup', 'masuk', 'daftar',
        'tanggal', 'jumlah', 'orang', 'anak', 'dewasa', 'malam', 'tiket', 'kursi', 'kamar',
        'pesawat', 'kereta', 'kapal', 'perjalanan', 'wisata', 'destinasi', 'ulasan', 'bayar',
        'pembayaran', 'berhasil', 'gagal', 'minimal', 'wajib', 'harus', 'silakan', 'mohon',
        'terima', 'maaf', 'harga', 'paket', 'pesan', 'nama', 'kota', 'bandara', 'penumpang',
        'penerbangan', 'pengiriman', 'saldo', 'poin', 'fasilitas', 'ketersediaan',
        'keberangkatan', 'jadwal', 'stasiun', 'pelabuhan', 'rute', 'bantuan', 'lacak',
        'profil', 'notifikasi', 'tentang', 'ketentuan', 'kebijakan', 'hubungi', 'beranda',
        'reseller', 'favorit', 'mitra', 'terpercaya', 'diskon', 'segera', 'segala',
        'kuota', 'seluruh', 'khusus', 'selama', 'dimuat', 'peta', 'kembali', 'lanjut',
        'simpan', 'kirim', 'penting', 'catatan', 'kupon', 'kartu', 'tersisa', 'menyetujui',
        'syarat', 'afiliasi', 'aktivitas', 'deskripsi', 'gunakan', 'diterapkan', 'nominal',
        'persentase', 'rincian', 'pembelian', 'pulang', 'pergi', 'sekali',
        'tambah', 'ubah', 'hapus', 'batal', 'selesai', 'berikut', 'sebelum', 'lainnya',
        'utama', 'jenis', 'segmen',
    ]) . ')\b/i';
}

/** Kumpulkan semua key t() literal dari file PHP aplikasi. */
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

/**
 * Kumpulkan semua key I18N.t() literal dari file PHP + assets/js.
 * Key ini harus terdaftar di getJsI18nKeys() agar masuk window.I18N.strings;
 * jika tidak, I18N.t() jatuh ke key (Indonesia) di semua bahasa.
 */
function i18nScanJsCallKeys(): array {
    $root = dirname(__DIR__, 2);
    $skip = ['.git', 'node_modules', 'test-results', 'scripts', 'vendor', 'uploads', '.serena'];
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            function ($f) use ($skip) {
                if ($f->isDir()) return !in_array($f->getFilename(), $skip);
                return preg_match('/\.(php|js)$/', $f->getFilename()) === 1;
            }
        )
    );
    $keys = [];
    foreach ($it as $f) {
        $src = @file_get_contents($f->getPathname());
        if ($src === false) continue;
        if (preg_match_all("/I18N\.t\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*[,)]/", $src, $m)) {
            foreach ($m[1] as $k) { $k = stripslashes($k); if (trim($k) !== '') $keys[$k] = true; }
        }
        if (preg_match_all('/I18N\.t\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*[,)]/', $src, $m)) {
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

/**
 * Key dengan nilai `en` identity (en == key) yang memuat kata Indonesia = bocor:
 * saat bahasa aktif EN, label tetap tampil bahasa Indonesia.
 */
function testNoIdentityEnIndonesianKey(): void {
    $map = i18nDbValueMap();
    $allow = array_map('mb_strtolower', array_merge(i18nBrandKeyAllowlist(), i18nIdentityEnAllowlist()));
    $re = i18nIndonesianMarkers();
    $bad = [];
    foreach (i18nScanCodeKeys() as $k) {
        $lk = mb_strtolower(trim($k));
        if (in_array($lk, $allow, true)) continue;
        foreach ($map[$lk]['en'] ?? [] as $en) {
            if (trim($en) === $k && preg_match($re, $k)) { $bad[] = $k; break; }
        }
    }
    sort($bad);
    assertSame([], $bad, 'Key t() dengan en identity berbahasa Indonesia');
}

/**
 * Semua key I18N.t() yang dipakai di JS/PHP harus terdaftar di getJsI18nKeys()
 * (kalau tidak, I18N.t() selalu mengembalikan key = teks Indonesia).
 */
function testJsI18nKeysAreRegistered(): void {
    $registered = array_flip(getJsI18nKeys());
    $allow = ['ada :n paket']; // contoh placeholder di komentar assets/js/i18n.js
    $missing = [];
    foreach (i18nScanJsCallKeys() as $k) {
        if (isset($registered[$k]) || in_array($k, $allow, true)) continue;
        $missing[] = $k;
    }
    sort($missing);
    assertSame([], $missing, 'Key I18N.t() yang belum terdaftar di getJsI18nKeys()');
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

/**
 * Tidak boleh ada grup key di `translations` yang hanya berbeda huruf besar/kecil
 * (collation ai_ci membuatnya duplikat lookup). Cegah regresi setelah dedupe.
 */
function testNoCaseDuplicateTranslationKeys(): void {
    $rows = db()->query("SELECT `key` FROM translations")->fetchAll(PDO::FETCH_COLUMN);
    $groups = [];
    foreach ($rows as $k) $groups[mb_strtolower($k)][$k] = true;
    $bad = [];
    foreach ($groups as $low => $variants) {
        if (count($variants) > 1) $bad[] = implode(' / ', array_keys($variants));
    }
    sort($bad);
    assertSame([], $bad, 'Grup key translations hanya beda huruf besar/kecil');
}

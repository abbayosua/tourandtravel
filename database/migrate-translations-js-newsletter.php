<?php
/**
 * migrate-translations-js-newsletter.php
 *
 * Dua key dipakai via I18N.t() di footer-shared.php (form newsletter, tampil di
 * SEMUA halaman) tetapi TIDAK terdaftar di getJsI18nKeys(), sehingga
 * window.I18N.strings tidak memuatnya dan I18N.t() selalu jatuh ke key
 * (Indonesia) di semua bahasa:
 *   - 'Gagal'                       (fallback pesan newsletter gagal)
 *   - 'Terjadi kesalahan. Coba lagi.' (catch network newsletter)
 * Key sudah ditambahkan ke getJsI18nKeys(); migrasi ini memastikan nilai en
 * benar (zh sudah ada).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-js-newsletter.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Gagal' => 'Failed',
        'Terjadi kesalahan. Coba lagi.' => 'Something went wrong. Try again.',
    ],
    'zh' => [
        'Gagal' => '失败',
        'Terjadi kesalahan. Coba lagi.' => '发生错误。请重试。',
    ],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count JS newsletter translation rows.\n";

<?php
/**
 * repair-mojibake.php — perbaiki terjemahan double-encoded (mojibake) di tabel
 * `translations`. Terjadi saat seed .sql di-import lewat client dengan default
 * connection charset latin1/CP1252: "首页" masuk DB sebagai "é¦–é¡µ".
 *
 * Idempotent & aman: round-trip iconv UTF-8 → CP1252 → UTF-8 hanya mengubah
 * baris yang benar-benar korup; baris normal tidak disentuh.
 *
 * Jalankan di server: php database/repair-mojibake.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = db();
$rows = $db->query("SELECT id, `key`, lang, value FROM translations")->fetchAll(PDO::FETCH_ASSOC);

$fixed = 0;
$skip = 0;
$update = $db->prepare("UPDATE translations SET value = ? WHERE id = ?");

foreach ($rows as $r) {
    $v = $r['value'];
    $re = @iconv('UTF-8', 'CP1252', $v);
    if ($re === false || $re === $v || !mb_check_encoding($re, 'UTF-8')) {
        $skip++;
        continue;
    }
    $update->execute([$re, $r['id']]);
    $fixed++;
    echo "FIX  [{$r['lang']}] {$r['key']}: " . mb_substr($v, 0, 30) . " → " . mb_substr($re, 0, 30) . "\n";
}

echo "\nSelesai: {$fixed} baris diperbaiki, {$skip} baris normal.\n";

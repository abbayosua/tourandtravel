<?php
/**
 * migrate-translations-dedupe-case.php
 *
 * Tabel `translations` punya 26 grup key yang hanya beda HURUF BESAR/KECIL
 * (mis. 'Diskon' vs 'diskon'). Karena collation `key` = utf8mb4_0900_ai_ci
 * (case-insensitive), keduanya cocok untuk lookup t() yang sama — jadi baris
 * duplikat ini hanya menambah entri. Tidak ada konflik nilai per-bahasa,
 * sehingga aman digabung ke satu key kanonik (yang dipakai kode, bila ada).
 *
 * Hanya menggabungkan varian CASE (bukan spasi — collation NO PAD membedakan
 * spasi akhir, jadi itu key berbeda).
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-dedupe-case.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Key yang dipakai kode (untuk memilih casing kanonik).
$codeKeys = [];
foreach (['scripts/out/all_keys.txt'] as $f) {
    $path = __DIR__ . '/../' . $f;
    if (is_file($path)) {
        foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
            $k = trim($line);
            if ($k !== '') $codeKeys[mb_strtolower($k)] = $k;
        }
    }
}

$rows = db()->query("SELECT id, `key`, lang FROM translations ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups = [];
foreach ($rows as $r) {
    $groups[mb_strtolower($r['key'])][] = $r;
}

$upd = db()->prepare("UPDATE translations SET `key` = ? WHERE id = ?");
$del = db()->prepare("DELETE FROM translations WHERE id = ?");
$moved = 0; $deleted = 0;

db()->beginTransaction();
try {
    foreach ($groups as $low => $list) {
        $distinct = [];
        foreach ($list as $r) $distinct[$r['key']] = true;
        if (count($distinct) < 2) continue;

        // Pilih kanonik: casing yang dipakai kode, else casing pertama.
        $canonical = $codeKeys[$low] ?? $list[0]['key'];
        // Pastikan canonical ada di antara varian (kalau tidak, pakai varian pertama).
        if (!isset($distinct[$canonical])) $canonical = $list[0]['key'];

        // Bahasa yang sudah ada di canonical.
        $canonLangs = [];
        foreach ($list as $r) if ($r['key'] === $canonical) $canonLangs[$r['lang']] = true;

        foreach ($list as $r) {
            if ($r['key'] === $canonical) continue;
            if (isset($canonLangs[$r['lang']])) {
                // Seharusnya tidak terjadi (tak ada konflik) — hapus agar tak unik-bentrok.
                $del->execute([$r['id']]);
                $deleted++;
            } else {
                $upd->execute([$canonical, $r['id']]);
                $canonLangs[$r['lang']] = true;
                $moved++;
            }
        }
    }
    db()->commit();
} catch (Throwable $e) {
    db()->rollBack();
    echo "ROLLBACK: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Moved $moved rows to canonical keys, deleted $deleted duplicate rows.\n";

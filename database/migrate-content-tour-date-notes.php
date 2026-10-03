<?php
/**
 * migrate-content-tour-date-notes.php
 *
 * tour_dates.note ("Low Season", "Imlek", "Liburan Natal", ...) dirender di
 * brosur PDF via tContentLang($dp, 'note', $lang). Untuk tour aktif, note_en/zh
 * belum lengkap sehingga versi en/zh menampilkan teks Indonesia. Isi yang
 * kosong saja (pertahankan nilai yang sudah ada).
 *
 * Idempotent.
 * Jalankan: php database/migrate-content-tour-date-notes.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'Golden Week'        => ['Golden Week', '黄金周'],
    'Imlek'              => ['Chinese New Year', '春节'],
    'Lebaran'            => ['Eid Holiday', '开斋节'],
    'Libur Natal'        => ['Christmas Holiday', '圣诞假期'],
    'Liburan Natal'      => ['Christmas Holiday', '圣诞假期'],
    'Low Season'         => ['Low Season', '淡季'],
    'Promo Akhir Tahun'  => ['Year-End Promo', '年终促销'],
];

$stmt = db()->prepare(
    "UPDATE tour_dates
        SET note_en = COALESCE(NULLIF(note_en, ''), ?),
            note_zh = COALESCE(NULLIF(note_zh, ''), ?)
      WHERE note = ?"
);
$count = 0;
foreach ($dict as $note => $t) {
    $stmt->execute([$t[0], $t[1], $note]);
    $count += $stmt->rowCount();
}
echo "Updated $count tour_dates rows.\n";

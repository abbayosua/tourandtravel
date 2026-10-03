<?php
/**
 * migrate-translations-identity-en.php
 *
 * Beberapa key punya nilai `en` yang masih sama dengan key Indonesia (identity),
 * sehingga saat bahasa aktif EN, label tetap tampil dalam bahasa Indonesia.
 * Key ini dirender di halaman booking ferry/PELNI, detail tour/hotel, my-points,
 * dan deskripsi admin appearance. Perbaiki nilai en-nya (zh sudah benar).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-identity-en.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Pesan Ferry' => 'Book Ferry',
        'Total Bayar' => 'Total Payment',
        'Cari Ferry Lagi' => 'Search Ferry Again',
        'Kode booking Anda:' => 'Your booking code:',
        'Kode bayar Tripay' => 'Tripay payment code',
        'Gunakan Profil Tersimpan' => 'Use Saved Profile',
        'Poin' => 'Points',
        'Homepage menonjolkan paket tour: flash deals, destinasi populer, rekomendasi tur.'
            => 'Homepage highlights tour packages: flash deals, popular destinations, recommended tours.',
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
echo "Upserted $count identity-en translation rows.\n";

<?php
/**
 * migrate-translations-identity-en-batch3.php
 *
 * Lanjutan audit key `en` identity (nilai en == key Indonesia) yang masih
 * menampilkan bahasa Indonesia pada versi EN. Dirender di:
 *   - tour-detail.php          : 'Gunakan' (points), 'diterapkan' (diskon korporat)
 *   - homepage transport-search: 'Pulang Pergi', 'Sekali Jalan', 'Tambah leg'
 *   - flight-detail.php        : 'Rincian segmen'
 *   - admin/currency-settings  : 'Mata Uang Default'
 *   - admin/promo-codes        : 'Min. Pembelian', 'Nominal (Rp)', 'Persentase (%)'
 *   - admin/appearance         : deskripsi urutan section & hero
 *   - error rate-limit / generic JS alert
 * (nilai zh sudah benar)
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-identity-en-batch3.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Gunakan' => 'Use',
        'diterapkan' => 'applied',
        'Pulang Pergi' => 'Round Trip',
        'Sekali Jalan' => 'One Way',
        'Rincian segmen' => 'Segment details',
        'Tambah leg' => 'Add leg',
        'Terjadi kesalahan. Coba lagi.' => 'Something went wrong. Try again.',
        'Terlalu banyak permintaan. Coba lagi dalam satu menit.' => 'Too many requests. Try again in a minute.',
        'Mata Uang Default' => 'Default Currency',
        'Min. Pembelian' => 'Min. Purchase',
        'Nominal (Rp)' => 'Amount (Rp)',
        'Persentase (%)' => 'Percentage (%)',
        'Urutan & jenis section homepage (produk utama di atas, lainnya sebagai pendukung).'
            => 'Order & type of homepage sections (main products first, others as supporting).',
        'Hero utama + slide hero sesuai fokus (dikelola di menu Hero Slides).'
            => 'Main hero + focus-based hero slides (managed in the Hero Slides menu).',
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
echo "Upserted $count identity-en (batch 3) translation rows.\n";

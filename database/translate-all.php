<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$strings = [
    'Your World of Joy',
    'Temukan paket tour impian Anda dari ratusan destinasi',
    'Cari destinasi atau aktivitas...',
    'Cari',
    'Semua',
    'Paket Tour',
    'Pelanggan',
    'Destinasi',
    'Tahun',
    'Kategori Wisata',
    'paket',
    'Flash Deals',
    'Lihat Semua',
    'HOT',
    'Promo',
    'Hubungi Kami',
    'Destinasi Populer',
    'Rekomendasi Paket Tour',
    'Pilihan terbaik untuk liburan Anda',
    'Segera',
    'org',
    'Pesan',
    'Rating Pelanggan',
    'Dari 2.000+ ulasan',
    'Pelanggan Puas',
    'Tersebar di 12+ destinasi',
    'Kepuasan',
    'Pelanggan merekomendasikan kami',
    'Apa Kata Mereka?',
    'Pengalaman pelanggan yang sudah traveling bersama kami',
    'Blog',
    'Cek blog TourAndTravel',
    'Ikuti tren travel, itinerary ideas, dan tips traveling terbaru.',
    'Baca selengkapnya',
    'Reward',
    'Dapatkan TourCash',
    'Setiap booking dapat poin reward. Tukarkan untuk diskon tour berikutnya!',
    'Pelajari',
    'Referral',
    'Ajak Teman, Dapat Diskon',
    'Ajak teman daftar & booking, kamu dan teman dapat diskon Rp100.000!',
    'Bagikan',
    'Kenapa Pilih',
    'Harga Transparan',
    'Tidak ada biaya tersembunyi',
    'Terpercaya',
    'tahun melayani pelanggan',
    'Siap bantu kapan saja',
    'Mudah Booking',
    'Proses cepat & praktis',
    'Siap Liburan?',
    'Dapatkan promo spesial untuk pendaftaran hari ini',
    'Mulai Sekarang',
    'Beranda',
    'Tour Tidak Ditemukan',
    'Tour tidak ditemukan',
    'Kembali ke Catalog',
    'Max',
    'peserta',
    'ulasan',
    'Galeri Foto',
    'Fasilitas Termasuk',
    'Lokasi',
    'Itinerary',
    'Hari',
    'Ulasan berhasil dikirim, terima kasih!',
    'dari',
    'Belum ada ulasan untuk tour ini.',
    'Tulis Ulasan',
    'Rating',
    'Bagikan pengalaman Anda...',
    'Kirim Ulasan',
    'orang',
    'Jadwal Keberangkatan',
    'slot',
    'Penuh',
    'Booking Sekarang',
    'Memproses...',
    'Pilih Tanggal',
    '-- Pilih Tanggal --',
    'Nama Lengkap',
    'Email (opsional)',
    'No. WhatsApp',
    'Jumlah Peserta',
    'Upload Foto Paspor',
    'Format JPG/PNG/WebP, max 2MB',
    'Catatan (opsional)',
    'Pesan Sekarang',
    'Belum ada jadwal keberangkatan tersedia',
    'Nama harus diisi',
    'No. WhatsApp harus diisi',
    'Jumlah peserta minimal 1',
    'Maaf, sisa slot hanya',
    'kursi',
    'Tanggal keberangkatan tidak valid',
    'Foto paspor wajib diupload',
    '3-5 Hari',
    '6-8 Hari',
    '9+ Hari',
    '< Rp 5 Juta',
    'Rp 5-10 Juta',
    'Rp 10-20 Juta',
    '> Rp 20 Juta',
    'Termurah',
    'Termahal',
    'Rating Tertinggi',
    'Terpopuler',
    'tour ditemukan',
    'Reset',
    'Semua Kategori',
    'Durasi',
    'Harga',
    'Urutkan',
    'Cari...',
    'Detail',
    'Tidak ada tour ditemukan',
    'Reset Filter',
    'Layanan',
    'Profil',
    'Booking Saya',
    'Wishlist',
    'Keluar',
    'Masuk',
    'Kontak',
    'Ikuti Kami',
    'Jam Operasional',
    'All rights reserved.',
    'Partner perjalanan terpercaya Anda. Kami menyediakan paket wisata domestik & internasional dengan harga terbaik.',
    'Rental Mobil',
    'Cari destinasi...',
    'Paket Tour Populer',
    'Tour',
    'Pesawat',
    'Ferry',
    'Rental',
    'Daftar',
    '/ orang',
    '/orang',
];

$translated = 0;
$skipped = 0;
$failed = 0;

foreach ($strings as $key) {
    $existing = '';
    try {
        $stmt = db()->prepare("SELECT value FROM translations WHERE `key` = ? AND lang = 'en' LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row) {
            $skipped++;
            continue;
        }
    } catch (Throwable $e) {}

    // Tanpa API — seed id dulu, lalu en = manual mapping bila ada (fallback key)
    saveTranslation($key, 'id', $key);
    saveTranslation($key, 'en', $key);
    $translated++;
    echo "✓ {$key} → {$key} (fallback, tanpa API)\n";
}

echo "\n=== Summary ===\n";
echo "Translated: {$translated}\n";
echo "Skipped (existing): {$skipped}\n";
echo "Failed: {$failed}\n";

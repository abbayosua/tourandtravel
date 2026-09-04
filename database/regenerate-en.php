<?php
/**
 * regenerate-en.php — kumpulkan SEMUA key t() dari codebase → banding DB →
 * insert EN translation untuk key baru.
 *
 * Sumber terjemahan (berurutan):
 *   1. Kamus manual di file ini ($manual) — EN berkualitas untuk key umum
 *   2. Terjemahan dari bahasa lain yang sudah ada (jika suatu saat ada >2 bahasa)
 *   3. Identity (key = value) sebagai fallback aman — tampil apa adanya, EN
 *      bisa disempurnakan kemudian tanpa mengubah kode
 *
 * Idempotent: INSERT IGNORE (key sudah ada tidak disentuh).
 * Jalankan: php database/regenerate-en.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ---------- 1. Scan semua key t() literal dari file PHP ----------
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/..', FilesystemIterator::SKIP_DOTS),
        function ($file) {
            if ($file->isDir()) {
                return !in_array($file->getFilename(), ['.git', 'node_modules', 'test-results', 'vendor', 'uploads', 'cache']);
            }
            return preg_match('/\.php$/', $file->getFilename());
        }
    )
);

$codeKeys = [];
foreach ($iterator as $f) {
    $src = file_get_contents($f->getPathname());
    foreach ([["/\bt\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*[,)]/", 1], ["/\bt\(\s*\"((?:[^\"\\\\]|\\\\.)*)\"\s*[,)]/", 1]] as [$re]) {
        if (preg_match_all($re, $src, $m)) {
            foreach ($m[1] as $key) {
                $key = stripslashes($key);
                if (trim($key) !== '') $codeKeys[$key] = true;
            }
        }
    }
}
$codeKeys = array_keys($codeKeys);
sort($codeKeys);
echo "Key t() di codebase: " . count($codeKeys) . "\n";

// ---------- 2. Kamus manual EN (key umum; identity otomatis untuk sisanya) ----------
$manual = [
    // navigasi & umum
    'Beranda' => 'Home', 'Masuk' => 'Sign In', 'Daftar' => 'Sign Up', 'Keluar' => 'Logout',
    'Cari' => 'Search', 'Filter' => 'Filter', 'Reset' => 'Reset', 'Reset Filter' => 'Reset Filters',
    'Lihat' => 'View', 'Detail' => 'Details', 'Simpan' => 'Save', 'Batal' => 'Cancel',
    'Tambah' => 'Add', 'Edit' => 'Edit', 'Hapus' => 'Delete', 'Salin' => 'Copy',
    'Berikutnya' => 'Next', 'Sebelumnya' => 'Previous', 'Kembali' => 'Back',
    // layanan
    'Paket Tour' => 'Tour Packages', 'Pesawat' => 'Flights', 'Ferry' => 'Ferry',
    'Rental Mobil' => 'Car Rental', 'Rental' => 'Rental', 'Kereta' => 'Trains',
    'Atraksi' => 'Attractions', 'Transfer' => 'Transfers', 'Hotel' => 'Hotels',
    'Layanan' => 'Services', 'eSIM' => 'eSIM',
    // halaman akun
    'Profil Saya' => 'My Profile', 'Profil' => 'Profile', 'Booking Saya' => 'My Bookings',
    'Riwayat Booking' => 'Booking History', 'Wishlist Saya' => 'My Wishlist',
    'KlookCash Saya' => 'My KlookCash', 'Referral Saya' => 'My Referrals',
    'Ganti Password' => 'Change Password', 'Simpan Perubahan' => 'Save Changes',
    // status
    'Pending' => 'Pending', 'Dikonfirmasi' => 'Confirmed', 'Dibatalkan' => 'Cancelled',
    'Menunggu Konfirmasi' => 'Awaiting Confirmation', 'Selesai' => 'Completed',
    'Diterima' => 'Received', 'Konfirmasi' => 'Confirmation', 'Pembayaran' => 'Payment',
    // booking
    'Pesan Sekarang' => 'Book Now', 'Pesan' => 'Book Now', 'Booking Sekarang' => 'Book Now',
    'Pesan Penerbangan' => 'Book Flight', 'Sewa Sekarang' => 'Rent Now',
    'Booking Berhasil!' => 'Booking Successful!', 'Kode Booking' => 'Booking Code',
    'Detail Booking' => 'Booking Details', 'Detail Pesanan' => 'Order Details',
    'Tracking Booking' => 'Track Booking', 'Cari Booking' => 'Find Booking',
    // unit & waktu
    'orang' => 'guests', 'malam' => 'night', 'kamar' => 'room(s)', 'hari' => 'day',
    'kursi' => 'seats', 'tiket' => 'tickets', 'pax' => 'pax', 'pcs' => 'pcs',
    'org' => 'person', 'Tamu' => 'Guest(s)', 'Penumpang' => 'Passenger(s)',
    // form
    'Email' => 'Email', 'Password' => 'Password', 'Nama' => 'Name', 'Nama Lengkap' => 'Full Name',
    'No. Telepon' => 'Phone Number', 'Kota' => 'City', 'Tanggal' => 'Date', 'Durasi' => 'Duration',
    'Harga' => 'Price', 'Rating' => 'Rating', 'Kategori' => 'Category', 'Status' => 'Status',
    'Total' => 'Total', 'Jumlah' => 'Amount', 'Tipe' => 'Type', 'Deskripsi' => 'Description',
    'Check-in' => 'Check-in', 'Check-out' => 'Check-out', 'Min' => 'Min', 'Max' => 'Max',
    // empty states
    'Belum ada pemesanan.' => 'No bookings yet.',
    'Belum ada transaksi.' => 'No transactions yet.',
    'Tidak ada tour ditemukan' => 'No tours found',
    'Tidak ada hotel ditemukan.' => 'No hotels found.',
    'Tidak ada jadwal keberangkatan tersedia' => 'No departure dates available',
    'Segera' => 'Coming Soon',
];

// ---------- 3. Banding & insert ----------
$stmtKeys = db()->query("SELECT `key` FROM translations WHERE lang = 'en'");
$dbKeys = [];
foreach ($stmtKeys->fetchAll(PDO::FETCH_COLUMN) as $k) {
    $dbKeys[mb_strtolower(trim($k))] = true;
}

$insert = db()->prepare("INSERT IGNORE INTO translations (`key`, lang, value) VALUES (?, 'en', ?)");
$newFromManual = 0;
$newIdentity = 0;
foreach ($codeKeys as $key) {
    if (isset($dbKeys[mb_strtolower(trim($key))])) continue;
    $manualKey = $manual[$key] ?? null;
    if ($manualKey !== null) {
        $insert->execute([$key, $manualKey]);
        $newFromManual++;
    } else {
        $insert->execute([$key, $key]);
        $newIdentity++;
    }
}
echo "Baru dari kamus manual: $newFromManual\n";
echo "Baru identity (fallback): $newIdentity\n";

// ---------- 4. Verifikasi: regenerate missing_keys.txt ----------
$stmtKeys = db()->query("SELECT `key` FROM translations WHERE lang = 'en'");
$dbKeys = [];
foreach ($stmtKeys->fetchAll(PDO::FETCH_COLUMN) as $k) {
    $dbKeys[mb_strtolower(trim($k))] = true;
}
$missing = array_values(array_filter($codeKeys, fn($k) => !isset($dbKeys[mb_strtolower(trim($k))])));
$outDir = __DIR__ . '/../scripts/out';
if (!is_dir($outDir)) mkdir($outDir, 0777, true);
file_put_contents("$outDir/missing_keys.txt", implode("\n", $missing) . (count($missing) ? "\n" : ""));
echo "Missing en tersisa: " . count($missing) . "\n";

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
    // hero booking
    'Jelajahi Lebih Banyak,' => 'Explore More,', 'Nikmati Perjalanannya.' => 'Enjoy the Journey.',
    'Pesan tiket pesawat, ferry, dan kereta api dalam satu tempat.' => 'Book flights, ferries, and train tickets in one place.',
    'Pesan tiket ferry, pesawat, dan kereta api dalam satu tempat.' => 'Book ferry, flight, and train tickets in one place.',
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
    // form booking
    'Pulang Pergi' => 'Round Trip', 'Sekali Jalan' => 'One Way', 'Multi-Kota' => 'Multi-City',
    'Semua Kelas' => 'All Classes', 'Ekonomi' => 'Economy', 'Bisnis' => 'Business', 'First Class' => 'First Class',
    'Dari' => 'From', 'Ke' => 'To', 'Tanggal Pergi' => 'Departure Date', 'Tanggal Pulang' => 'Return Date',
    'Tambah leg' => 'Add Leg', 'Maksimal 6 leg.' => 'Maximum 6 legs.',
    'Kota atau bandara' => 'City or airport', 'Kota atau码头' => 'City or port',
    'Penumpang' => 'Passengers', 'Cari' => 'Search', 'Cari destinasi...' => 'Search Destinations...',
    'Kota atau nama hotel...' => 'City or hotel name...', 'Kota...' => 'City...',
    'Out of' => 'From', 'Harga Termurah' => 'Lowest Price',
    'Total' => 'Total', 'Jumlah' => 'Amount', 'Tipe' => 'Type', 'Deskripsi' => 'Description',
    'Check-in' => 'Check-in', 'Check-out' => 'Check-out', 'Min' => 'Min', 'Max' => 'Max',
    // empty states
    'Belum ada pemesanan.' => 'No bookings yet.',
    'Belum ada transaksi.' => 'No transactions yet.',
    'Tidak ada tour ditemukan' => 'No tours found',
    'Tidak ada hotel ditemukan.' => 'No hotels found.',
    'Tidak ada jadwal keberangkatan tersedia' => 'No departure dates available',
    'Segera' => 'Coming Soon',

    // === homepage-templates: fokus & preset ===
    'Tampilan Homepage' => 'Homepage Appearance',
    'Fokus Website' => 'Website Focus',
    'Pilih produk utama yang dijual website ini. Halaman utama akan tersusun otomatis mengikuti pilihan.' => 'Pick the main product this site sells. The homepage will rearrange automatically.',
    'Paket Tour (Klook-style)' => 'Tour Packages (Klook-style)',
    'Hotel (Agoda-style)' => 'Hotels (Agoda-style)',
    'Tiket Pesawat (Tiket.com-style)' => 'Flights (Tiket.com-style)',
    'Homepage menonjolkan paket tour: flash deals, destinasi populer, rekomendasi tur.' => 'Homepage highlights tour packages: flash deals, popular destinations, recommended tours.',
    'Homepage menonjolkan hotel: pencarian menginap dominan, deal hotel terbaik, hotel per kota.' => 'Homepage highlights hotels: dominant stay search, best hotel deals, hotels by city.',
    'Homepage menonjolkan penerbangan: form cari tiket dominan, promo & rute populer.' => 'Homepage highlights flights: dominant flight search, deals & popular routes.',
    'Fokus tidak valid' => 'Invalid focus',
    'Fokus website berhasil disimpan.' => 'Website focus saved successfully.',
    'Yang berubah saat fokus diganti' => 'What changes when the focus changes',
    'Hero utama + slide hero sesuai fokus (dikelola di menu Hero Slides).' => 'Main hero + hero slides per focus (managed in Hero Slides menu).',
    'Urutan & jenis section homepage (produk utama di atas, lainnya sebagai pendukung).' => 'Homepage section order & type (main product on top, others as supporting).',
    'Halaman lain (tour/hotel/flight detail) tidak berubah.' => 'Other pages (tour/hotel/flight detail) stay unchanged.',
    'Fokus' => 'Focus', 'Semua Fokus' => 'All Focuses',
    'Hero Slides' => 'Hero Slides', 'Tambah Slide' => 'Add Slide', 'Edit Slide' => 'Edit Slide',
    'Subjudul' => 'Subtitle', 'Teks CTA' => 'CTA Text', 'Link CTA' => 'CTA Link',
    'Gambar slide wajib diupload' => 'Slide image is required',
    'JPG/PNG/WebP, maks 2MB. Rekomendasi 1920px lebar.' => 'JPG/PNG/WebP, max 2MB. Recommended 1920px wide.',
    'Slide tampil di homepage dengan fokus ini (atau semua).' => 'Slide appears on homepage with this focus (or all).',
    'Link CTA harus nama file .php, path internal (diawali /), atau URL' => 'CTA link must be a .php filename, internal path (starting with /), or URL',
    'Hapus slide ini?' => 'Delete this slide?',
    'Belum ada slide.' => 'No slides yet.',

    // === admin dashboard operational system (ADMINPRD.md) ===
    'Sales Report' => 'Sales Report', 'Accounting' => 'Accounting',
    'Profit & Loss' => 'Profit & Loss', 'Laba Rugi' => 'Profit & Loss',
    'COGS' => 'COGS', 'Harga Pokok Penjualan' => 'Cost of Goods Sold',
    'Total HPP' => 'Total COGS',
    'Gross Profit' => 'Gross Profit', 'Laba Kotor' => 'Gross Profit',
    'Net Profit' => 'Net Profit', 'Laba Bersih' => 'Net Profit',
    'Expenses' => 'Expenses', 'Pengeluaran' => 'Expenses',
    'Tambah Pengeluaran' => 'Add Expense', 'Edit Pengeluaran' => 'Edit Expense',
    'Hapus Pengeluaran' => 'Delete Expense', 'Hapus pengeluaran ini?' => 'Delete this expense?',
    'Daftar Pengeluaran' => 'Expense List', 'Breakdown Pengeluaran' => 'Expense Breakdown',
    'Export CSV' => 'Export CSV', 'Perbandingan Bulanan' => 'Monthly Comparison',
    'Revenue Breakdown' => 'Revenue Breakdown', 'Breakdown Pendapatan' => 'Revenue Breakdown',
    'Avg Order Value' => 'Avg Order Value', 'Rata-rata per Transaksi' => 'Average per Transaction',
    'Conversion Rate' => 'Conversion Rate', 'Tingkat Konversi' => 'Conversion Rate',
    'Total Pendapatan' => 'Total Revenue', 'Total Pengeluaran' => 'Total Expenses',
    'Pendapatan' => 'Revenue', 'Revenue' => 'Revenue',
    'Tren Pendapatan' => 'Revenue Trend', '30 hari terakhir' => 'Last 30 days',
    'Booking per Vertikal' => 'Bookings per Vertical',
    'Aktivitas Terakhir' => 'Recent Activity', 'Booking baru' => 'New booking',
    'Aksi Cepat' => 'Quick Actions', 'Tambah Tour' => 'Add Tour',
    'Vertikal' => 'Vertical', 'Semua Tipe' => 'All Types',
    'Detail Transaksi' => 'Transaction Details', 'transaksi' => 'transactions',
    'Pelanggan' => 'Customer', 'Qty' => 'Qty', 'Metode' => 'Method',
    'Vertikal Teratas' => 'Top Vertical',
    'Belum ada transaksi pada periode ini' => 'No transactions in this period',
    'Menampilkan 200 dari' => 'Showing 200 of',
    'gunakan filter atau Export CSV untuk data lengkap' => 'use filters or Export CSV for full data',
    'Periode' => 'Period', 'Biaya' => 'Costs',
    'dari pendapatan' => 'of revenue',
    'Kategori' => 'Category', 'Deskripsi' => 'Description',
    'Pengeluaran berhasil ditambahkan' => 'Expense added successfully',
    'Pengeluaran berhasil diperbarui' => 'Expense updated successfully',
    'Pengeluaran berhasil dihapus' => 'Expense deleted successfully',
    'Deskripsi dan jumlah wajib diisi (jumlah > 0)' => 'Description and amount are required (amount > 0)',
    'Belum ada pengeluaran pada periode ini' => 'No expenses in this period',
    'Simpan COGS' => 'Save COGS',
    'bulan' => 'months', 'Bulan' => 'Month',
    'Overview' => 'Overview', 'Inventory' => 'Inventory', 'Finance' => 'Finance',
    'Content' => 'Content', 'Settings' => 'Settings', 'Eksternal' => 'External',
    'Marketing' => 'Marketing', 'Operasional' => 'Operations', 'Gaji' => 'Salaries',
    'Sewa' => 'Rent', 'Utilitas' => 'Utilities', 'Lainnya' => 'Others',

    // === preset Hotel (Agoda-style) ===
    'Menginap Nyaman, Harga Terbaik' => 'Comfortable Stays, Best Rates',
    'Dari budget sampai bintang 5 — bandingkan dan pesan sekarang.' => 'From budget to 5-star — compare and book now.',
    'Kota / Hotel' => 'City / Hotel',
    'Cari kota atau nama hotel...' => 'Search city or hotel name...',
    'Cari Hotel' => 'Search Hotels', 'Semua Hotel' => 'All Hotels',
    'Deal Hotel Terbaik' => 'Best Hotel Deals',
    'Harga termurah untuk menginap di kota favoritmu' => 'Cheapest rates to stay in your favorite cities',
    'Kota Populer' => 'Popular Cities',
    'Harga mulai dari — per malam' => 'Rates from — per night',
    'Belum ada hotel tersedia saat ini.' => 'No hotels available right now.',
    'Jelajahi Paket Tour' => 'Explore Tour Packages',
    'Belum ada kota dengan data hotel.' => 'No cities with hotel data yet.',
    'hotel' => 'hotels',
    'Bintang' => 'Star(s)',

    // === preset Flight (Tiket.com-style) ===
    'One Way' => 'One Way', 'Round Trip' => 'Round Trip',
    'Pergi' => 'Depart', 'Pulang' => 'Return',
    'Cari Penerbangan' => 'Search Flights',
    'Semua Penerbangan' => 'All Flights',
    'Terbang ke Kota Impian' => 'Fly to Your Dream City',
    'Tiket pesawat murah ke ratusan kota, setiap hari.' => 'Cheap flights to hundreds of cities, every day.',
    'Promo Tiket Setiap Hari' => 'Daily Flight Deals',
    'Harga spesial untuk rute favorit — kuota terbatas, pesan sekarang.' => 'Special fares on favorite routes — limited seats, book now.',
    'Lihat Semua Promo' => 'View All Deals',
    'Rute Populer' => 'Popular Routes',
    'Harga mulai dari — per penumpang, one way' => 'Fares from — per passenger, one way',
    'jadwal' => 'schedules', 'harga mulai' => 'fare from',
    'Belum ada jadwal penerbangan tersedia.' => 'No flight schedules available yet.',

    // === trust & cross-sell ===
    'Kenapa Booking Hotel di' => 'Why Book Hotels with',
    'Kenapa Pesan Tiket di' => 'Why Book Flights with',
    'Jelajahi Juga: Paket Tour' => 'Also Explore: Tour Packages',
    'Aktivitas Menarik' => 'Fun Activities',
    'Lengkapi Perjalanan: Hotel' => 'Complete Your Trip: Hotels',
    'Paket Tour Populer' => 'Popular Tour Packages',
    'Butuh Menginap?' => 'Need a Stay?',
    'Aktivitas Seru' => 'Exciting Activities',
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

// ---------- 3b. Key dinamis (t($var) — tidak tertangkap scanner) ----------
$dynamicKeys = [
    ["Gross Profit", "Gross Profit"], ["Hapus Pengeluaran", "Delete Expense"],
    ["Operasional", "Operations"], ["Gaji", "Salaries"],
    ["Utilitas", "Utilities"], ["Lainnya", "Others"],
];
$newDynamic = 0;
foreach ($dynamicKeys as [$dk, $dv]) {
    if (isset($dbKeys[mb_strtolower($dk)])) continue;
    $insert->execute([$dk, $dv]);
    $newDynamic++;
}
echo "Baru dinamis (t(\$var)): $newDynamic
";

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

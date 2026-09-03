<?php
/**
 * Translate Migrate Admin — seed terjemahan id/en untuk semua key admin
 * Mapping terpusat (nav, dashboard, CRUD list/form, settings, flash msgs)
 * Run: php scripts/translate-migrate-admin.php  (idempotent)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$map = [
    // ===== Sidebar nav =====
    'Dashboard' => 'Dashboard', 'Admin Panel' => 'Admin Panel',
    'Kelola Tour' => 'Manage Tours', 'Kelola Hotel' => 'Manage Hotels',
    'Kelola Pesawat' => 'Manage Flights', 'Kelola Ferry' => 'Manage Ferries',
    'Kelola Rental' => 'Manage Rentals', 'Kelola Booking' => 'Manage Bookings',
    'Kelola Atraksi' => 'Manage Attractions', 'Kelola Transfer' => 'Manage Transfers',
    'Kelola Kereta' => 'Manage Trains', 'Kelola eSIM' => 'Manage eSIM',
    'Kode Promo' => 'Promo Codes', 'Koleksi' => 'Collections',
    'Kelola FAQ' => 'Manage FAQ', 'Loyalty Settings' => 'Loyalty Settings',
    'Pengaturan WA' => 'WhatsApp Settings', 'Mata Uang' => 'Currency',
    'Lihat Website' => 'View Website',

    // ===== Flash messages =====
    'Berhasil ditambahkan' => 'Successfully added',
    'Berhasil diperbarui' => 'Successfully updated',
    'Berhasil dihapus' => 'Successfully deleted',
    'Pilih minimal 1 tour' => 'Select at least 1 tour',
    'Status booking berhasil diperbarui' => 'Booking status updated successfully',
    'Booking berhasil dihapus' => 'Booking deleted successfully',
    'Pengaturan WhatsApp berhasil disimpan' => 'WhatsApp settings saved successfully',
    'Pengaturan loyalty berhasil disimpan' => 'Loyalty settings saved successfully',
    'Kode promo wajib diisi' => 'Promo code is required',
    'Kode promo sudah ada' => 'Promo code already exists',

    // ===== Login =====
    'Login Admin' => 'Admin Login', 'Username' => 'Username', 'Password' => 'Password',
    'Masuk' => 'Sign In', 'Kembali ke Website' => 'Back to Website',
    'Username atau password salah' => 'Invalid username or password',

    // ===== Dashboard =====
    'Selamat datang' => 'Welcome', 'Tour Aktif' => 'Active Tours', 'Aktif' => 'Active',
    'Total Booking' => 'Total Bookings', 'Semua status' => 'All statuses',
    'Pending' => 'Pending', 'Menunggu' => 'Waiting', 'Confirmed' => 'Confirmed',
    'Terkonfirmasi' => 'Confirmed', 'Revenue' => 'Revenue', 'Pendapatan' => 'Earnings',
    'Booking Terbaru' => 'Recent Bookings', 'Belum ada booking' => 'No bookings yet',

    // ===== CRUD list =====
    'Nama' => 'Name', 'Kota' => 'City', 'Bintang' => 'Stars', 'Harga' => 'Price',
    'Aksi' => 'Actions', 'Gambar' => 'Image', 'Judul' => 'Title',
    'Kategori' => 'Category', 'Max Peserta' => 'Max Participants', 'Status' => 'Status',
    'Tambah' => 'Add', 'Tambah Tour' => 'Add Tour', '/malam' => '/night',
    'Maskapai' => 'Airline', 'No.' => 'No.', 'Rute' => 'Route', 'Jam' => 'Time',
    'Kelas' => 'Class', 'Perusahaan' => 'Company', 'Berangkat' => 'Depart',
    'Tiba' => 'Arrive', 'Tipe' => 'Type', 'Harga/Hari' => 'Price/Day',
    'Transmisi' => 'Transmission', 'Kursi' => 'Seats', 'Pesawat' => 'Flights',
    'Ferry' => 'Ferry', 'Rental Mobil' => 'Car Rental', 'Hotel' => 'Hotel',
    'Kendaraan' => 'Vehicle', 'Max Pax' => 'Max Pax', 'Jadwal' => 'Schedule',
    'Durasi' => 'Duration', 'Negara' => 'Country', 'Kuota' => 'Quota',
    'Best Seller' => 'Best Seller', 'Tiket Tempat Wisata' => 'Attraction Tickets',
    'Transfer Bandara' => 'Airport Transfer', 'Kereta Api' => 'Trains',
    'eSIM & Connectivity' => 'eSIM & Connectivity', 'Kategori FAQ' => 'FAQ Categories',
    'Jumlah FAQ' => 'Total FAQ', 'Urutan' => 'Sort Order', 'Pertanyaan' => 'Question',
    'FAQ' => 'FAQ', 'Koleksi Tour' => 'Tour Collections', 'Tambah Koleksi' => 'Add Collection',
    'Kode' => 'Code', 'Nilai' => 'Value', 'Min. Beli' => 'Min. Purchase',
    'Max. Diskon' => 'Max. Discount', 'Pemakaian' => 'Usage', 'Berlaku' => 'Valid',
    'Tipe Diskon' => 'Discount Type', 'Ya' => 'Yes', 'Tidak' => 'No',
    'Simpan' => 'Save', 'Batal' => 'Cancel', 'Hapus?' => 'Delete?',
    'Item' => 'Item', 'Qty' => 'Qty', 'Kontak' => 'Contact', 'Semua' => 'All',
    'Semua Tipe' => 'All Types', 'Slug' => 'Slug', 'Edit' => 'Edit',
    'Nonaktif' => 'Inactive', 'Tanggal' => 'Date', 'Total' => 'Total',

    // ===== CRUD forms =====
    'Judul Tour' => 'Tour Title', 'Deskripsi' => 'Description', 'Gambar Cover' => 'Cover Image',
    'Max 2MB. Format: JPG, PNG, WebP' => 'Max 2MB. Format: JPG, PNG, WebP',
    'Bahasa Konten' => 'Content Language',
    'Konten akan otomatis diterjemahkan ke bahasa lain' => 'Content will be automatically translated to the other language',
    'Simpan Tour' => 'Save Tour', 'Update Tour' => 'Update Tour',
    'Kosongkan jika tidak ingin mengubah gambar' => 'Leave empty if you do not want to change the image',
    'Tanggal Berangkat' => 'Departure Date', 'Tanggal Kembali' => 'Return Date',
    'Slot' => 'Slots', 'Hari' => 'Day', 'Makan' => 'Meals', 'Akomodasi' => 'Accommodation',
    'Pilih Gambar (bisa banyak)' => 'Select Image (multiple allowed)',
    'Edit Hotel' => 'Edit Hotel', 'Nama Hotel' => 'Hotel Name', 'Harga/Malam (Rp)' => 'Price/Night (Rp)',
    'Edit Pesawat' => 'Edit Flights', 'No. Penerbangan' => 'Flight No.', 'Dari' => 'From',
    'Ke' => 'To', 'Harga (Rp)' => 'Price (Rp)', 'Edit Ferry' => 'Edit Ferry',
    'Kapal' => 'Vessel', 'Nama & kota wajib diisi' => 'Name & city are required',
    'Kategori harus diisi' => 'Category is required', 'Harga harus diisi' => 'Price is required',
    'Tambah Tour' => 'Add Tour', 'Edit Tour' => 'Edit Tour',
    'Tambah FAQ' => 'Add FAQ', 'Edit FAQ' => 'Edit FAQ', 'Jawaban' => 'Answer',
    'Tambah Kategori' => 'Add Category', 'Edit Kategori' => 'Edit Category',
    'Nama Kategori' => 'Category Name',
    'Pertanyaan, jawaban, dan kategori wajib diisi' => 'Question, answer, and category are required',
    'Nama kategori wajib diisi' => 'Category name is required',
    'Nama Transfer' => 'Transfer Name', 'Tipe Asal' => 'Origin Type', 'Tipe Tujuan' => 'Destination Type',
    'Tipe Kendaraan' => 'Vehicle Type', 'Max Penumpang' => 'Max Passengers',
    'Tambah Transfer' => 'Add Transfer', 'Edit Transfer' => 'Edit Transfer',
    'Nama Kereta' => 'Train Name', 'Durasi (contoh: 5j 30m)' => 'Duration (e.g. 5h 30m)',
    'Stasiun Asal' => 'Origin Station', 'Stasiun Tujuan' => 'Destination Station',
    'Keberangkatan' => 'Departure', 'Tambah Kereta' => 'Add Train', 'Edit Kereta' => 'Edit Train',
    'Kuota Data' => 'Data Quota', 'Durasi (hari)' => 'Duration (days)',
    'Tambah eSIM' => 'Add eSIM', 'Edit eSIM' => 'Edit eSIM',
    'Nama Tiket' => 'Ticket Name', 'Cover Image (Upload)' => 'Cover Image (Upload)',
    'Nama Mobil' => 'Car Name', 'Harga/Hari (Rp)' => 'Price/Day (Rp)', 'Kapasitas' => 'Capacity',
    'Edit Rental Mobil' => 'Edit Car Rental', 'Isi semua field' => 'Fill in all fields',
    'Konfirmasi Instan' => 'Instant Confirmation', 'Batal Gratis' => 'Free Cancellation',

    // ===== Settings =====
    'Pengaturan Mata Uang' => 'Currency Settings', 'Pengaturan WhatsApp' => 'WhatsApp Settings',
    'Pengaturan Loyalty' => 'Loyalty Settings',
    'Gagal mengambil kurs dari Frankfurter API' => 'Failed to fetch rates from Frankfurter API',
    'Gagal menyimpan file config' => 'Failed to save config file',
    'Kurs Terkini (EUR Base)' => 'Latest Rates (EUR Base)',
    'Mata uang yang ditampilkan ke pengunjung' => 'Currency shown to visitors',
    'Pasangan' => 'Pair', 'Rate' => 'Rate', 'Sumber' => 'Source',
    'update setiap 24 jam' => 'updates every 24 hours', 'Refresh' => 'Refresh',
    'Contoh Konversi dari IDR 10.000.000' => 'Example conversion from IDR 10,000,000',
    'Belum ada data kurs. Klik "Refresh" untuk mengambil data terbaru.' => 'No rate data yet. Click "Refresh" to fetch the latest data.',
    'Koneksi Pengirim WA' => 'WA Sender Connection',
    'Nomor WhatsApp yang digunakan untuk mengirim notifikasi ke supplier.' => 'WhatsApp number used to send notifications to the supplier.',
    'Memuat status...' => 'Loading status...', 'Server Terhubung' => 'Server Connected',
    'Server Tidak Terhubung' => 'Server Not Connected', 'Simpan Pengaturan' => 'Save Settings',
    'Konfigurasi' => 'Configuration', 'Nomor WA Admin/Supplier' => 'WA Admin/Supplier Number',
    'Nomor tujuan notifikasi booking baru. Diawali 62, tanpa + atau spasi.' => 'Destination number for new booking notifications. Starts with 62, no + or spaces.',
    'Token Akun WUZAPI' => 'WUZAPI Account Token',
    'Token akun pengirim WA. Kosongkan jika tidak diubah.' => 'Sender WA account token. Leave empty if unchanged.',
    'Server URL' => 'Server URL', 'Kosongkan jika tidak diubah.' => 'Leave empty if unchanged.',
    'Test Kirim WA' => 'Test Send WA', 'Nomor Tujuan' => 'Destination Number',
    'Kirim Test' => 'Send Test', 'Informasi Notifikasi' => 'Notification Info',
    'Saat ada booking baru, notifikasi otomatis dikirim ke nomor WA Admin/Supplier:' => 'When there is a new booking, notifications are automatically sent to the Admin/Supplier WA number:',
    'Log Webhook' => 'Webhook Log', 'Belum ada aktivitas webhook.' => 'No webhook activity yet.',
    'Pengaturan Loyalty & KlookCash' => 'Loyalty & KlookCash Settings',
    'Tier Threshold (Jumlah Booking)' => 'Tier Threshold (Number of Bookings)',
    'Explorer — minimal booking' => 'Explorer — minimum bookings',
    'Silver — minimal booking' => 'Silver — minimum bookings',
    'Gold — minimal booking' => 'Gold — minimum bookings',
    'Joy+ — minimal booking' => 'Joy+ — minimum bookings',
    'Earning Rate KlookCash' => 'KlookCash Earning Rate',
    'Persentase dari total booking yang menjadi KlookCash reward.' => 'Percentage of total booking that becomes KlookCash reward.',
    'Explorer (%)' => 'Explorer (%)', 'Silver (%)' => 'Silver (%)',
    'Gold (%)' => 'Gold (%)', 'Joy+ (%)' => 'Joy+ (%)', 'Kembali' => 'Back',
];

$insId = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, 'id', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$insEn = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, 'en', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");

$count = 0;
foreach ($map as $id => $en) {
    $insId->execute([$id, $id]);
    $insEn->execute([$id, $en]);
    $count++;
}

$enTotal = db()->query("SELECT COUNT(*) FROM translations WHERE lang='en'")->fetchColumn();
$idTotal = db()->query("SELECT COUNT(*) FROM translations WHERE lang='id'")->fetchColumn();
echo "Admin keys seeded: $count\n";
echo "Total en rows: $enTotal\n";
echo "Total id rows: $idTotal\n";

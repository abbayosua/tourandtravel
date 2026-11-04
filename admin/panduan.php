<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once 'includes/admin-access.php';
requireAdminPage();

$pageTitle = t('Panduan Branding');
require_once 'includes/admin-header.php';
$img = fn(string $f): string => '../assets/img/panduan/' . $f;
$cap = t('Tampilan menu dalam Bahasa Indonesia — posisi tombol sama di semua bahasa.');
?>
<style>
.panduan-step{display:flex;gap:.75rem;margin-bottom:1rem}
.panduan-num{flex:0 0 32px;width:32px;height:32px;border-radius:50%;background:#0d6efd;color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center}
.panduan-img{border:1px solid #dee2e6;border-radius:.5rem;max-width:100%;height:auto;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.panduan-cap{font-size:.8rem;color:#6c757d;margin-top:.25rem}
.panduan-tip{background:#fff8e1;border-left:4px solid #ffc107;padding:.6rem .9rem;border-radius:.25rem;margin:.6rem 0}
.panduan-warn{background:#fdecea;border-left:4px solid #dc3545;padding:.6rem .9rem;border-radius:.25rem;margin:.6rem 0}
</style>

<h4 class="fw-bold mb-1"><i class="bi bi-book me-2"></i><?= t('Panduan Admin Panel') ?></h4>
<p class="text-muted"><?= t('Petunjuk langkah demi langkah memakai seluruh menu admin — tanpa perlu bisa coding. Mulai dari Bab 0, lalu ke bab yang kamu butuhkan.') ?></p>

<div class="card border-0 shadow-sm mb-3" id="bab-0"><div class="card-body">
<h5 class="fw-bold"><?= t('Bab 0 — Mulai di sini (3 menit)') ?></h5>
<p class="mb-1"><b>1.</b> <?= sprintf(t('Buka halaman admin: %s lalu login dengan akunmu. Di web latihan lokal, user %s password %s.'), '<code>/admin/login.php</code>', '<code>admin</code>', '<code>admin123</code>') ?></p>
<p class="mb-1"><b>2.</b> <?= t('Kenali peta sidebar (9 bagian, dari atas ke bawah):') ?></p>
<ul class="mb-1">
<li><b><?= t('Overview') ?></b> — <?= t('Dashboard + Analytics: ringkasan baca saja.') ?></li>
<li><b><?= t('Inventory') ?></b> — <?= t('Kelola Tour, Hotel, Pesawat, Ferry, Rental, Atraksi, Transfer, Kereta, eSIM (Bab 2).') ?></li>
<li><b><?= t('Bookings') ?></b> — <?= t('Kelola Booking + Pembayaran (Bab 3).') ?></li>
<li><b><?= t('Marketing') ?></b> — <?= t('Flash Sale, Kode Promo, Price Alert, Push, Corporate, Koleksi (Bab 4).') ?></li>
<li><b><?= t('Finance') ?></b> — <?= t('Sales Report, Accounting, Loyalty (Bab 5).') ?></li>
<li><b><?= t('Reseller') ?></b> — <?= t('Kelola Reseller, Topup, Harga (Bab 6).') ?></li>
<li><b><?= t('Content') ?></b> — <?= t('Blog, Ulasan, FAQ, Menu, Hero, Brand & Logo (Bab 1 + Bab 7).') ?></li>
<li><b><?= t('Settings') ?> ⚠️</b> — <?= t('WA, Chat, Email, Mata Uang, Hotel/Flight API (Bab 8, hati-hati).') ?></li>
<li><b><?= t('Tim') ?></b> — <?= t('Kelola Admin, khusus superadmin (Bab 9).') ?></li>
</ul>
<p class="mb-1"><b>3.</b> <?= t('Aturan aman:') ?></p>
<ul class="mb-0">
<li>🟢 <?= t('Bebas: isi produk, blog, FAQ, ulasan, laporan baca, branding (Bab 1).') ?></li>
<li>🟡 <?= t('Hati-hati: promo/diskon, harga reseller, ambang loyalty, status booking — catat nilai lama sebelum ubah.') ?></li>
<li>🔴 <?= sprintf(t('Jangan sentuh bila tak paham: API key (%s), pengaturan pembayaran, hapus massal, akun admin orang lain.'), 'WA/email/FCM/flight/hotel') ?></li>
</ul>
</div></div>

<h4 class="fw-bold mb-1" id="bab-1"><i class="bi bi-award me-2"></i><?= t('Bab 1 — Ganti Branding Website') ?></h4>

<div class="alert alert-info py-2">
<b><?= t('Di mana menunya?') ?></b> <?= sprintf(t('Di sidebar kiri, bagian %s, klik %s.'), '<b>' . t('Content') . '</b>', '<b>' . t('Brand & Logo') . '</b>') ?>
<a href="brand-settings.php" class="btn btn-sm btn-primary ms-2"><i class="bi bi-arrow-right-circle"></i> <?= t('Buka Brand & Logo') ?></a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Langkah 1 — Masuk ke admin') ?></h5>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka halaman login admin, isi %s dan %s, lalu klik %s.'), '<b>' . t('Username') . '</b>', '<b>' . t('Password') . '</b>', '<b>' . t('Masuk') . '</b>') ?></p>
<img src="<?= e($img('panduan-brand-01-login.png')) ?>" alt="<?= e(t('Halaman login admin')) ?>" class="panduan-img" style="max-width:420px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= sprintf(t('Kalau salah password, muncul tulisan merah %s — ketik ulang perlahan.'), '"' . t('Username atau password salah') . '"') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Langkah 2 — Ganti nama travel + tagline') ?></h5>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Di halaman %s, bagian paling atas ada %s (wajib, maks 60 huruf) dan %s (boleh kosong, maks 120 huruf). Nama ini muncul di menu atas web, judul tab browser, email, notifikasi WA, dan PDF.'), '<b>' . t('Brand & Logo') . '</b>', '<b>' . t('Nama travel') . '</b>', '<b>' . t('Tagline') . '</b>') ?></p>
<img src="<?= e($img('panduan-brand-02-form-atas.png')) ?>" alt="<?= e(t('Kolom nama travel dan tagline')) ?>" class="panduan-img" style="max-width:560px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-warn"><?= sprintf(t('Nama tidak boleh kosong. Kalau kosong, muncul error %s dan tidak tersimpan.'), '"' . t('Nama travel wajib diisi') . '"') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Langkah 3 — Ganti link sosmed (boleh kosong)') ?></h5>
<div class="panduan-step"><div class="panduan-num">3</div><div>
<p class="mb-1"><?= sprintf(t('Isi 4 kolom %s dengan link lengkap (diawali %s). %s baris yang tidak punya akun — ikonnya otomatis hilang dari footer.'), '<b>' . t('Media Sosial') . '</b>', '<code>https://</code>', '<b>' . t('Kosongkan') . '</b>') ?></p>
<img src="<?= e($img('panduan-brand-03-sosmed.png')) ?>" alt="<?= e(t('Kolom media sosial')) ?>" class="panduan-img" style="max-width:560px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= sprintf(t('Contoh benar: %s. Contoh salah: %s (tanpa https://) — tidak tersimpan sebagai link.'), '<code>https://instagram.com/royaldream</code>', '<code>royaldream</code>') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Langkah 4 — Ganti alamat, telepon, WA, email, jam (tampil di footer)') ?></h5>
<div class="panduan-step"><div class="panduan-num">4</div><div>
<p class="mb-1"><?= sprintf(t('Bagian %s tampil di footer bawah setiap halaman web. Nomor %s cukup angka saja, tanpa spasi/tanda plus — contoh benar %s, salah %s.'), '<b>' . t('Kontak & Alamat') . '</b>', '<b>WhatsApp</b>', '<code>6281234567890</code>', '<code>0812 3456 7890</code>') ?></p>
<img src="<?= e($img('panduan-brand-04-kontak.png')) ?>" alt="<?= e(t('Kolom kontak dan alamat')) ?>" class="panduan-img" style="max-width:560px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= t('Setiap kolom maks 160 huruf. Tombol telepon, email, dan WA di footer otomatis bisa diklik pengunjung.') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Langkah 5 — Ganti logo, favicon, dan gambar share') ?></h5>
<div class="panduan-step"><div class="panduan-num">5</div><div>
<p class="mb-1"><?= sprintf(t('Bagian %s: klik %s untuk logo, favicon (icon kecil di tab browser, maks 512KB), dan OG image (gambar saat link di-share ke WA/FB, maks 2MB). Centang %s bila ingin menghilangkan gambar lama.'), '<b>' . t('Logo (JPG/PNG/WebP, maks 2MB)') . '</b>', '<b>Choose File</b>', '<b>' . t('Hapus') . '</b>') ?></p>
<img src="<?= e($img('panduan-brand-05-logo.png')) ?>" alt="<?= e(t('Kolom upload logo')) ?>" class="panduan-img" style="max-width:560px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-warn"><?= t('Jangan upload file selain gambar (mis. PDF/Word) — muncul error dan gambar tidak tersimpan.') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Langkah 6 — Klik Simpan, tunggu tulisan hijau') ?></h5>
<div class="panduan-step"><div class="panduan-num">6</div><div>
<p class="mb-1"><?= sprintf(t('Klik tombol biru %s di bawah form. Tunggu sampai muncul tulisan hijau %s — itu tandanya sudah masuk database. Jangan tutup halaman sebelum tulisan hijau muncul.'), '<b>' . t('Simpan') . '</b>', '<b>"' . t('Brand & logo tersimpan') . '"</b>') ?></p>
<img src="<?= e($img('panduan-brand-06-simpan.png')) ?>" alt="<?= e(t('Tulisan sukses setelah simpan')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-warn"><?= sprintf(t('Bagian %s di bawah kontak JANGAN diubah kalau tidak paham API key — biarkan apa adanya, web tetap jalan.'), '<b>' . t('Email pengirim') . '</b>') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Langkah 7 — Cek hasil di web') ?></h5>
<div class="panduan-step"><div class="panduan-num">7</div><div>
<p class="mb-1"><?= t('Buka halaman depan web, scroll ke bawah (footer). Pastikan nama, alamat, telepon, WA, email, dan jam sudah berubah seperti yang diisi.') ?></p>
<img src="<?= e($img('panduan-brand-07-hasil-footer.png')) ?>" alt="<?= e(t('Footer web setelah ganti branding')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= sprintf(t('Klik ikon telepon/email/WA di footer untuk memastikan link-nya jalan. Kalau belum berubah, tekan %s (refresh bersih) sekali.'), '<b>Ctrl+Shift+R</b>') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('Kalau gagal — cari pesan error-nya di sini') ?></h5>
<table class="table table-sm">
<thead><tr><th><?= t('Pesan yang muncul') ?></th><th><?= t('Artinya') ?></th><th><?= t('Solusi') ?></th></tr></thead>
<tbody>
<tr><td><?= t('Nama travel wajib diisi') ?></td><td><?= t('Kolom nama kosong') ?></td><td><?= t('Isi nama minimal 1 huruf, klik Simpan lagi') ?></td></tr>
<tr><td><?= t('Nama travel maksimal 60 karakter') ?></td><td><?= t('Nama kepanjangan') ?></td><td><?= t('Pendekkan nama, singkatan boleh') ?></td></tr>
<tr><td><?= t('Alamat email pengirim tidak valid') ?></td><td><?= t('Email salah ketik') ?></td><td><?= sprintf(t('Pakai format %s, atau kembalikan ke bawaan'), '<code>nama@domain.com</code>') ?></td></tr>
<tr><td><?= t('Tipe favicon harus … / Ukuran favicon maksimal 512KB') ?></td><td><?= t('File favicon salah') ?></td><td><?= t('Pakai ICO/PNG/JPG/WebP/SVG di bawah 512KB') ?></td></tr>
<tr><td><?= t('File gambar / Gagal menyimpan file') ?></td><td><?= t('File rusak / folder penuh') ?></td><td><?= t('Coba file lain di bawah 2MB, ulangi Simpan') ?></td></tr>
</tbody>
</table>
</div></div>

<h4 class="fw-bold mb-1" id="bab-2"><i class="bi bi-map me-2"></i><?= t('Bab 2 — Kelola Produk (Inventory)') ?></h4>
<p class="text-muted"><?= t('Satu pola untuk semua produk: Daftar → Tambah/Edit → isi harga & slot → foto → aktifkan → cek di web.') ?></p>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('2.0 Pola umum (Tour, Hotel, Pesawat, Ferry, Rental, Atraksi, Transfer, Kereta, eSIM)') ?></h5>
<p class="mb-1"><b>1.</b> <?= sprintf(t('Buka menu %s di sidebar, klik %s untuk produk baru atau ikon edit untuk mengubah.'), '<b>' . t('Inventory') . '</b>', '<b>' . t('Tambah') . '</b>') ?></p>
<p class="mb-1"><b>2.</b> <?= t('Isi kolom wajib, lalu harga dan slot/tanggal. Kolom Inggris/Cina boleh kosong — otomatis memakai versi Indonesia.') ?></p>
<p class="mb-1"><b>3.</b> <?= sprintf(t('Upload foto (JPG/PNG/WebP, maks 2MB). Klik %s, tunggu tulisan sukses, lalu buka halaman web produknya.'), '<b>' . t('Simpan') . '</b>') ?></p>
<div class="panduan-tip"><?= t('Harga 0 artinya gratis — hanya isi 0 bila memang gratis. Tanggal lewat hari ini tidak bisa dipesan pengunjung.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('2.1 Tour: manual vs AI AUTO (pakai Meta AI)') ?></h5>
<p class="mb-1"><?= sprintf(t('Di %s ada 2 mode. %s: isi 3 bahasa sendiri. %s: tulis 1 bahasa, klik %s untuk pratinjau, lalu %s — sisanya diisi AI otomatis.'), '<b>' . t('Kelola Tour → Tambah') . '</b>', '<b>Manual</b>', '<b>AI AUTO</b>', '<b>' . t('Terjemahkan Otomatis (pratinjau)') . '</b>', '<b>' . t('Simpan') . '</b>') ?></p>
<p class="mb-1"><?= sprintf(t('Wajib diisi: %s, %s, %s. Mata uang: IDR/SGD/USD. Tanggal + itinerary + foto diisi lewat %s setelah tersimpan.'), '<b>' . t('Judul tour') . '</b>', '<b>' . t('Kategori') . '</b>', '<b>' . t('Harga') . '</b>', '<b>' . t('Edit Tour') . '</b>') ?></p>
<div class="panduan-warn"><?= t('Kalau pratinjau AI gagal (kuota/jaringan), isi manual saja — tour tetap tersimpan. Jangan klik Simpan dua kali.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('2.2 Hotel + kamar (wajib nama + kota)') ?></h5>
<ul class="mb-1">
<li><b><?= t('Hotel') ?></b> — <?= t('wajib nama + kota; bintang 1–5; harga per malam. Lalu Kelola kamar: tipe bed, tamu 1–10, harga>0, stok 0–999.') ?></li>
<li><?= sprintf(t('Isi %s + %s dulu (keduanya wajib), lalu bintang 1–5 dan harga per malam. Kolom Inggris/Cina boleh kosong.'), '<b>' . t('Nama Hotel') . '</b>', '<b>' . t('Kota') . '</b>') ?></li>
<li><?= sprintf(t('Lalu klik %s: tiap tipe kamar wajib nama + harga>0; bed single/double/twin/king/suite; tamu 1–10; stok 0–999.'), '<b>' . t('Kelola') . '</b>') ?></li>
</ul>
<div class="panduan-warn"><?= sprintf(t('Error %s artinya nama/kota kosong; error %s artinya harga kamar 0 — perbaiki lalu Simpan ulang.'), '"' . t('Nama & kota wajib diisi') . '"', '"' . t('Nama & harga wajib diisi') . '"') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('2.3 Pesawat, Ferry, Kereta, Transfer, Rental (jadwal + rute)') ?></h5>
<ul class="mb-1">
<li><b><?= t('Pesawat') ?></b> — <?= t('wajib maskapai + nomor + asal + tujuan + jam berangkat/tiba + durasi + harga>0; kelas ekonomi/bisnis/first.') ?></li>
<li><b><?= t('Ferry') ?></b> — <?= t('wajib perusahaan + asal + tujuan + jam + harga>0; nama kapal boleh kosong.') ?></li>
<li><b><?= t('Kereta') ?></b> — <?= t('wajib nama + asal + tujuan; jam kosong otomatis jadi 00:00; harga boleh 0.') ?></li>
<li><b><?= t('Transfer') ?></b> — <?= t('wajib nama + asal + tujuan; pilih tipe titik (bandara/kota/pelabuhan/hotel); penumpang min 1; harga boleh 0.') ?></li>
<li><b><?= t('Rental') ?></b> — <?= t('wajib nama + tipe + kota + harga>0; transmisi auto/manual; kursi 2–20.') ?></li>
</ul>
<div class="panduan-tip"><?= t('Jam pakai format 24 jam (mis. 14:30). Tulis kota persis sama seperti di web agar pencarian ketemu.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('2.4 Atraksi & eSIM (harga boleh 0)') ?></h5>
<ul class="mb-1">
<li><b><?= t('Atraksi') ?></b> — <?= t('wajib nama + kota; kategori/durasi/deskripsi boleh kosong; harga boleh 0 (=gratis).') ?></li>
<li><b><?= t('eSIM') ?></b> — <?= t('wajib nama + negara + kuota; tipe esim/sim/wifi; durasi min 1 hari; harga boleh 0.') ?></li>
</ul>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('2.5 Live (API) vs manual — jangan salah paham') ?></h5>
<p class="mb-1"><?= t('Hotel/Pesawat punya 2 sumber: LIVE (NusaTrip/OYO/Duffel — harga & stok ikut penyedia, berubah sendiri) vs MANUAL (kamu yang isi & atur stok).') ?></p>
<div class="panduan-warn"><?= t('Aturan: jangan jual slot live yang sudah habis di penyedia; jangan ubah harga live manual kecuali disuruh; kalau data live aneh, cek menu Hotel/Flight API (Bab 8) dulu.') ?></div>

<h4 class="fw-bold mb-1" id="bab-3"><i class="bi bi-ticket-perforated me-2"></i><?= t('Bab 3 — Pesanan & Pembayaran') ?></h4>
<p class="text-muted"><?= t('Baca daftar booking, proses status, setujui refund, dan cek pembayaran. Jangan tandai lunas tanpa bukti.') ?></p>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('3.1 Baca daftar booking (9 jenis)') ?></h5>
<p class="mb-1"><?= t('Menu Kelola Booking menampung: tour, atraksi, transfer, kereta, eSIM, hotel, pesawat, ferry. Status: pending, confirmed, cancelled, paid, refunded.') ?></p>
<p class="mb-1"><?= t('Cari pakai kode booking atau filter tanggal. Klik baris untuk detail: nama, kontak, jadwal, harga, catatan admin.') ?></p>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('3.2 Proses: konfirmasi, batal, refund') ?></h5>
<ul class="mb-1">
<li><?= t('Konfirmasi: ubah status ke confirmed/paid. Pembeli otomatis dapat notif WA/email/push + poin loyalty bila paid.') ?></li>
<li><?= t('Batal: ubah ke cancelled + tulis alasan di catatan admin. Saldo reseller balik otomatis bila booking reseller.') ?></li>
<li><?= t('Refund tour: pakai tombol approve/reject refund (bukan ubah status manual). Dana masuk wallet TravelPoints.') ?></li>
</ul>
<div class="panduan-warn"><?= t('JANGAN tandai paid bila uang belum masuk. Salah status = laporan dan poin ikut salah.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('3.3 Pembayaran: gateway & manual') ?></h5>
<p class="mb-1"><?= t('Menu Pembayaran: mode manual vs instant; gateway Midtrans/Tripay/SingaPay/Xendit (sandbox vs production); tiap gateway punya server key + client key + merchant/code sendiri. Status pembayaran: pending, paid, failed, expired, challenge.') ?></p>
<p class="mb-1"><?= t('Manual: isi nama bank + nomor + pemilik + catatan per bahasa. Nota manual tampil di halaman bayar pengunjung.') ?></p>
<div class="panduan-warn"><?= t('Kunci API = rahasia. Jangan foto/share. Sandbox untuk latihan, production untuk uang asli — salah tempel = bayar gagal.') ?></div>
</div></div>

<h4 class="fw-bold mb-1" id="bab-4"><i class="bi bi-tag me-2"></i><?= t('Bab 4 — Marketing: Promo, Flash Sale, Notif') ?></h4>
<p class="text-muted"><?= t('Bikin diskon, kirim notifikasi, atur harga korporat, susun koleksi. Prioritas: jangan dobel diskon yang bikin rugi.') ?></p>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('4.1 Flash sale & kode promo') ?></h5>
<ul class="mb-1">
<li><?= t('Flash sale: wajib pilih produk + tanggal mulai & selesai (selesai harus setelah mulai) + diskon 5–70%. Stok kosongkan = tanpa batas.') ?></li>
<li><?= t('Kode promo: wajib kode (otomatis kapital) + nilai > 0; tipe persen/nominal; opsional min. belanja, maks. potongan, kuota, periode (bawaan: hari ini s.d. +1 tahun).') ?></li>
</ul>
<div class="panduan-warn"><?= t('JANGAN jalankan flash sale + kode promo + harga reseller + corporate barengan untuk produk sama — diskon menumpuk. Cek prioritas diskon di checkout dulu.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('4.2 Price alert & push (notifikasi otomatis)') ?></h5>
<p class="mb-1"><?= t('Price alert read-only: klik cek untuk notifikasi user bila harga turun ke target; hapus bila spam. Push: pilih semua / per bahasa (id/en/zh) / per user; judul maks 150, isi maks 500 huruf; judul+isi Indonesia wajib, EN/Cina boleh kosong.') ?></p>
<div class="panduan-tip"><?= t('Price alert dicek berkala (throttle ~1/jam). Push butuh FCM key (Bab 8) — tanpa itu tombol kirim gagal.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('4.3 Corporate & koleksi') ?></h5>
<p class="mb-1"><?= t('Corporate: buat perusahaan + diskon 0–100%; kaitkan email user (kosongkan = lepas). Koleksi: wajib nama; centang tur aktif; atur urutan tampil di web.') ?></p>
</div></div>

<h4 class="fw-bold mb-1" id="bab-5"><i class="bi bi-graph-up me-2"></i><?= t('Bab 5 — Keuangan: Laporan & Loyalty') ?></h4>
<p class="text-muted"><?= t('Baca omzet, catat pengeluaran + COGS, atur poin loyalty. Angka di sini operasional — bukan laporan pajak resmi.') ?></p>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('5.1 Sales Report: omzet per vertikal') ?></h5>
<p class="mb-1"><?= t('Filter tanggal dari–sampai + tipe produk + status. Baca: total transaksi, omzet (batal tidak dihitung), rata-rata per transaksi, vertikal teratas. Klik Export CSV untuk unduh.') ?></p>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('5.2 Accounting: pengeluaran + COGS') ?></h5>
<ul class="mb-1">
<li><?= t('Tambah pengeluaran: wajib kategori + deskripsi + jumlah > 0 + tanggal (bawaan hari ini).') ?></li>
<li><?= t('COGS: isi harga pokok per booking agar laba = omzet − COGS − pengeluaran.') ?></li>
</ul>
<div class="panduan-warn"><?= t('Deskripsi dan jumlah wajib diisi (jumlah > 0) — tanpa itu tidak tersimpan.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('5.3 Loyalty: ambang & persen poin') ?></h5>
<p class="mb-1"><?= t('Atur ambang (bawaan: silver 2, gold 5, joyplus 10) + persen poin per tier. Poin terbit otomatis saat booking paid/confirmed.') ?></p>
<div class="panduan-warn"><?= t('JANGAN ubah ambang/persen saat promo jalan — member bisa naik/turun tier tiba-tiba. Catat nilai lama dulu.') ?></div>
</div></div>

<h4 class="fw-bold mb-1" id="bab-6"><i class="bi bi-shop me-2"></i><?= t('Bab 6 — Reseller: Daftar, Topup, Harga') ?></h4>
<p class="text-muted"><?= t('Kelola agen reseller: lihat saldo, setujui topup, atur harga khusus. Jangan kasih harga lebih murah dari flash sale.') ?></p>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('6.1 Daftar reseller: cari + filter saldo') ?></h5>
<p class="mb-1"><?= t('Cari nama/email/telepon; filter Semua / Ada Saldo / Saldo Kosong. Kolom: saldo, jumlah booking, total belanja, tanggal daftar. Klik ikon jam untuk riwayat topup.') ?></p>
<img src="<?= e($img('panduan-bab6-01-daftar.png')) ?>" alt="<?= e(t('Daftar reseller dengan saldo')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('6.2 Topup: cek bukti, Approve/Reject') ?></h5>
<ul class="mb-1">
<li><?= t('Filter pending/approved/rejected. Klik Lihat Bukti, cocokkan nominal + tanggal dengan mutasi bank.') ?></li>
<li><?= t('Approve: saldo otomatis bertambah + email + notif ke reseller. Reject: wajib tulis alasan, saldo tidak berubah.') ?></li>
</ul>
<img src="<?= e($img('panduan-bab6-02-topup.png')) ?>" alt="<?= e(t('Daftar pengajuan topup')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
</ul>
<div class="panduan-warn"><?= t('Setujui topup ini dan tambahkan saldo reseller? — hanya klik Approve bila uang benar masuk. Salah klik = kasih saldo gratis.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('6.3 Harga reseller per tur') ?></h5>
<p class="mb-1"><?= t('Pilih tur (aktif saja) + harga reseller > 0 + min pax + centang Aktif. Satu tur hanya satu baris harga; pakai Edit untuk ubah, Toggle untuk nonaktifkan, Hapus bila salah.') ?></p>
<img src="<?= e($img('panduan-bab6-03-harga.png')) ?>" alt="<?= e(t('Harga reseller per tur')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<img src="<?= e($img('panduan-bab6-04-hasil.png')) ?>" alt="<?= e(t('Dashboard reseller setelah topup')) ?>" class="panduan-img mt-2" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-warn"><?= t('Tour dan harga wajib diisi. Tour yang sudah ada harganya tidak bisa ditambah lagi — edit yang sudah ada.') ?></div>
</div></div>

BAB7暂时


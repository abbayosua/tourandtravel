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
</div></div>

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

<div class="alert alert-info py-2">
<b><?= t('Di mana menunya?') ?></b> <?= sprintf(t('Di sidebar kiri, bagian %s, ada 3 menu: %s, %s, %s.'), '<b>' . t('Finance') . '</b>', '<b>' . t('Sales Report') . '</b>', '<b>' . t('Accounting') . '</b>', '<b>' . t('Loyalty') . '</b>') ?>
<a href="sales-report.php" class="btn btn-sm btn-primary ms-2"><i class="bi bi-arrow-right-circle"></i> <?= t('Buka Sales Report') ?></a>
<a href="accounting.php" class="btn btn-sm btn-outline-primary ms-1"><?= t('Buka Accounting') ?></a>
<a href="loyalty-settings.php" class="btn btn-sm btn-outline-primary ms-1"><?= t('Buka Loyalty') ?></a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('5.1 Sales Report: baca omzet per vertikal') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('tahu berapa transaksi + omzet masuk periode ini, vertikal mana paling laris, dan unduh datanya.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s. Isi filter %s (tanggal mulai) + %s (tanggal akhir) + %s (Tour/Hotel/Pesawat/Atraksi/Transfer/Kereta/eSIM/Ferry) + %s (pending/confirmed/cancelled/refunded).'), '<b>' . t('Sales Report') . '</b>', '<b>' . t('Dari') . '</b>', '<b>' . t('Sampai') . '</b>', '<b>' . t('Tipe') . '</b>', '<b>' . t('Status') . '</b>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= t('Baca 4 angka di atas tabel: total transaksi, omzet (booking batal tidak dihitung), rata-rata per transaksi, dan vertikal teratas. Di bawahnya ada rincian per vertikal + daftar booking (kode, nama, item, qty, total, status, metode bayar).') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">3</div><div>
<p class="mb-1"><?= sprintf(t('Klik tombol hijau %s untuk unduh CSV (nama file otomatis %s). Buka di Excel/Spreadsheet untuk rekap.'), '<b>' . t('Export CSV') . '</b>', '<code>sales-report-TANGGAL-TANGGAL.csv</code>') ?></p>
<div class="panduan-tip"><?= sprintf(t('Contoh baca: %s berarti bulan ini ada 40 transaksi lunas, omzet Rp120 juta, rata-rata Rp3 juta per transaksi, tur paling laris.'), '<code>40 transaksi — Rp120.000.000 — Tour</code>') ?></div>
</div></div>
<img src="<?= e($img('panduan-bab5-01-sales.png')) ?>" alt="<?= e(t('Laporan omzet per vertikal')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= t('ubah filter tanggal 1 hari saja — daftar harus menyusut. Klik Export CSV — file harus terunduh dan bisa dibuka.') ?></p>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('5.2 Accounting: catat pengeluaran + COGS agar laba benar') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= sprintf(t('tahu laba bersih = %s. Tanpa COGS + pengeluaran, omzet terlihat besar padahal belum dikurangi modal.'), '<code>' . t('omzet − COGS − pengeluaran') . '</code>') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s. Di atas ada ringkasan: omzet, COGS, pengeluaran, laba kotor, laba bersih, margin%%, grafik 6 bulan, dan rincian per vertikal.'), '<b>' . t('Accounting') . '</b>') ?></p>
<img src="<?= e($img('panduan-bab5-02-accounting.png')) ?>" alt="<?= e(t('Ringkasan laba dan daftar pengeluaran')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Tambah pengeluaran: pilih %s (%s), isi %s (mis. %s), isi %s (angka > 0, mis. %s), tanggal bawaan hari ini — lalu Simpan.'), '<b>' . t('Kategori') . '</b>', t('Marketing, Operasional, Gaji, Sewa, Utilitas, Lainnya'), '<b>' . t('Deskripsi') . '</b>', '"Bayar iklan Instagram"', '<b>' . t('Jumlah') . '</b>', '<code>500000</code>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">3</div><div>
<p class="mb-1"><?= sprintf(t('Isi COGS (harga pokok) lewat menu %s: cari booking-nya, isi kolom %s (angka ≥ 0, boleh desimal), Simpan. Laba otomatis = omzet − COGS − pengeluaran.'), '<b>' . t('Kelola Booking') . '</b>', '<b>COGS</b>') ?></p>
<div class="panduan-tip"><?= sprintf(t('Contoh: tur terjual %s, COGS (bayar hotel+bus) %s, iklan %s → laba = Rp3.000.000 − Rp2.000.000 − Rp200.000 = Rp800.000.'), '<code>Rp3.000.000</code>', '<code>Rp2.000.000</code>', '<code>Rp200.000</code>') ?></div>
</div></div>
<img src="<?= e($img('panduan-bab5-04-cogs.png')) ?>" alt="<?= e(t('Kolom COGS di Kelola Booking')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('setelah tambah pengeluaran, tulisan hijau %s muncul dan baris baru tampil di tabel. Setelah isi COGS, angka laba di Accounting berubah.'), '"<b>' . t('Pengeluaran berhasil ditambahkan') . '</b>"') ?></p>
<div class="panduan-warn"><?= sprintf(t('%s — deskripsi kosong atau jumlah 0 tidak tersimpan.'), '"' . t('Deskripsi dan jumlah wajib diisi (jumlah > 0)') . '"') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('5.3 Loyalty: ambang tier + persen poin TravelPoints') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('atur kapan member naik tier dan berapa persen poin yang ia dapat tiap belanja lunas.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s. Ada 2 kelompok: %s (bawaan: explorer 0, silver 2, gold 5, joyplus 10 booking) dan %s per tier (bawaan: 5%%, 6%%, 7%%, 10%% dari total booking).'), '<b>' . t('Pengaturan Loyalty') . '</b>', '<b>' . t('Tier Threshold (Jumlah Booking)') . '</b>', '<b>' . t('persen poin') . '</b>') ?></p>
<img src="<?= e($img('panduan-bab5-03-loyalty.png')) ?>" alt="<?= e(t('Ambang tier dan persen poin loyalty')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= t('Ubah angka ambang/persen, klik Simpan. Poin terbit otomatis saat booking lunas (paid/confirmed) — tidak perlu input manual.') ?></p>
<div class="panduan-tip"><?= sprintf(t('Contoh: gold butuh 5 booking + 7%%. Member gold belanja %s dapat %s poin otomatis.'), '<code>Rp1.000.000</code>', '<code>70.000</code>') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('muncul tulisan hijau %s. Minta 1 member melakukan 1 booking lunas — poinnya harus bertambah sesuai persen tier-nya.'), '"<b>' . t('Pengaturan loyalty berhasil disimpan') . '</b>"') ?></p>
<div class="panduan-warn"><?= t('JANGAN ubah ambang/persen saat promo jalan — member bisa naik/turun tier tiba-tiba. Catat nilai lama dulu (foto layar).') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('5.9 Kalau gagal — cari pesan error-nya di sini') ?></h5>
<table class="table table-sm">
<thead><tr><th><?= t('Pesan yang muncul') ?></th><th><?= t('Artinya') ?></th><th><?= t('Solusi') ?></th></tr></thead>
<tbody>
<tr><td><?= t('Deskripsi dan jumlah wajib diisi (jumlah > 0)') ?></td><td><?= t('Kolom deskripsi kosong / jumlah 0') ?></td><td><?= t('Isi deskripsi + jumlah lebih dari 0, Simpan lagi') ?></td></tr>
<tr><td><?= t('Export CSV kosong / hanya judul') ?></td><td><?= t('Filter tanggal/tipe/status tidak cocok — 0 baris') ?></td><td><?= t('Lebarkan tanggal, kosongkan tipe + status, Export lagi') ?></td></tr>
<tr><td><?= t('Laba terlihat salah / minus') ?></td><td><?= t('COGS belum diisi di booking') ?></td><td><?= sprintf(t('Isi kolom %s di Kelola Booking dulu, lalu cek Accounting lagi'), '<b>COGS</b>') ?></td></tr>
<tr><td><?= t('Poin member tidak bertambah') ?></td><td><?= t('Booking belum lunas (masih pending) / persen 0') ?></td><td><?= t('Pastikan status paid/confirmed dan persen tier > 0') ?></td></tr>
<tr><td><?= t('Pengeluaran berhasil ditambahkan') ?></td><td><?= t('Sukses — bukan error') ?></td><td><?= t('Cek baris baru di tabel + angka laba berubah') ?></td></tr>
</tbody>
</table>
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

<h4 class="fw-bold mb-1" id="bab-7"><i class="bi bi-file-text me-2"></i><?= t('Bab 7 — Konten: Blog, Ulasan, FAQ, Menu & Hero') ?></h4>
<p class="text-muted"><?= t('Isi tulisan web: artikel blog, balas ulasan tamu, Q&A, menu atas, dan gambar besar depan. Semua tampil langsung di web setelah Publish.') ?></p>

<div class="alert alert-info py-2">
<b><?= t('Di mana menunya?') ?></b> <?= sprintf(t('Di sidebar kiri, bagian %s: %s, %s, %s, %s, %s.'), '<b>' . t('Content') . '</b>', '<b>' . t('Blog') . '</b>', '<b>' . t('Ulasan') . '</b>', '<b>' . t('FAQ') . '</b>', '<b>' . t('Menu Navigasi') . '</b>', '<b>' . t('Hero Slides') . '</b>') ?>
<a href="posts.php" class="btn btn-sm btn-primary ms-2"><?= t('Buka Blog') ?></a>
<a href="reviews.php" class="btn btn-sm btn-outline-primary ms-1"><?= t('Buka Ulasan') ?></a>
<a href="faq.php" class="btn btn-sm btn-outline-primary ms-1"><?= t('Buka FAQ') ?></a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('7.1 Blog: tulis artikel yang dibaca Google') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('terbitkan artikel (mis. “5 Pantai Terbaik”) agar web ramai dari pencarian Google.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s, klik tombol %s. Isi %s (wajib — kalau kosong muncul %s), lalu %s (ringkasan 1–2 kalimat) + %s (isi lengkap) + %s (foto sampul, masuk folder %s) + %s (mis. Tips) + %s (draft = belum tampil, published = tampil + tanggal terbit otomatis).'), '<b>' . t('Blog') . '</b>', '<b>' . t('Tambah') . '</b>', '<b>' . t('Judul') . '</b>', '"<b>' . t('Judul wajib diisi') . '</b>"', '<b>' . t('Excerpt') . '</b>', '<b>' . t('Body') . '</b>', '<b>' . t('Cover') . '</b>', '<code>uploads/blog/</code>', '<b>' . t('Kategori') . '</b>', '<b>' . t('Status') . '</b>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= t('Kalau web 3 bahasa, isi juga kolom EN + ZH (judul/ringkasan/isi). Slug (alamat link) dibuat otomatis dari judul — tidak perlu diketik manual.') ?></p>
<img src="<?= e($img('panduan-bab7-01-posts.png')) ?>" alt="<?= e(t('Daftar artikel blog')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= sprintf(t('Contoh: judul %s → link otomatis %s. Ganti judul = link ikut berubah, jadi jangan ganti judul artikel yang sudah disebar.'), '"<code>Promo Liburan Batam</code>"', '<code>promo-liburan-batam</code>') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('muncul tulisan hijau %s, baris baru tampil di daftar (judul + kategori + status + tanggal). Klik %s untuk sembunyikan/tampilkan tanpa hapus. Buka halaman blog web — artikel harus ada.'), '"<b>' . t('Berhasil diperbarui') . '</b>"', '<b>' . t('Draft') . '/' . t('Publish') . '</b>') ?></p>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('7.2 Ulasan: balas bintang tamu, hapus yang spam') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('tamu yang dibalas merasa dihargai; spam/junk dibuang agar rating bersih.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s. Tampil 100 ulasan terbaru: nama tamu + bintang + nama tur/hotel + isi komentar + rating per aspek (%s).'), '<b>' . t('Ulasan') . '</b>', t('Kebersihan, Lokasi, Staff, Nilai, Fasilitas, Kenyamanan')) ?></p>
<img src="<?= e($img('panduan-bab7-02-reviews.png')) ?>" alt="<?= e(t('Daftar ulasan tamu')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= t('Balas: ketik balasan di kolom balasan ulasan itu, Simpan — tanggal balasan terisi otomatis. Hapus: klik Hapus (muncul tanya “Hapus ulasan ini?”) — foto ulasan ikut terhapus dan tidak bisa dikembalikan.') ?></p>
<div class="panduan-tip"><?= sprintf(t('Contoh balasan baik: %s. Singkat, sebut nama tamu, janji diperbaiki bila ada komplain.'), '"<code>Terima kasih Kak Rina! Senang tur Batam-nya berkesan — ditunggu liburan berikutnya!</code>"') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('muncul tulisan hijau %s dan balasan tampil di bawah komentar. Ulasan yang dihapus hilang dari daftar + halaman web.') , '"<b>' . t('Berhasil diperbarui') . '</b>"') ?></p>
<div class="panduan-warn"><?= t('JANGAN hapus ulasan bintang kecil hanya karena malu — balas dulu dengan sopan. Hapus hanya untuk spam/iklan/judi/kata kasar.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('7.3 FAQ: jawab pertanyaan yang ditanya berulang-ulang') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('tamu tidak perlu WA hanya untuk tanya “bayar pakai apa?” — jawabannya sudah ada di web.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s, klik %s dulu untuk buat kelompok (mis. Pembayaran, Refund). Baru klik %s: isi pertanyaan + jawaban + pilih kategori + centang %s.'), '<b>' . t('FAQ') . '</b>', '<b>' . t('Kategori') . '</b>', '<b>' . t('Tambah') . '</b>', '<b>' . t('Aktif') . '</b>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Cari dengan kolom %s (ketik kata, mis. %s). Edit pakai ikon pensil, hapus pakai ikon tong sampah (muncul tanya %s).'), '<b>' . t('Cari') . '</b>', '"<code>refund</code>"', '"<b>' . t('Hapus?') . '</b>"') ?></p>
<img src="<?= e($img('panduan-bab7-03-faq.png')) ?>" alt="<?= e(t('Daftar FAQ per kategori')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= sprintf(t('Contoh Q&A bagus: Q: %s A: %s'), '"<code>Apakah bisa refund?</code>"', '"<code>Bisa H-3, potong 20%% admin. Klik Kelola Booking → Refund.</code>"') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('muncul %s / %s / %s sesuai aksi. Buka halaman FAQ web — pertanyaan baru harus tampil di kategori yang benar.'), '"<b>' . t('Berhasil ditambahkan') . '</b>"', '"<b>' . t('Berhasil diperbarui') . '</b>"', '"<b>' . t('Berhasil dihapus') . '</b>"') ?></p>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('7.4 Menu navigasi + gambar depan (hero)') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('atur tombol menu atas web + gambar besar halaman depan agar tamu mudah jalan-jalan di web.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Menu: buka %s. Isi %s + %s (wajib — kosong muncul %s), pilih %s (ikon kecil), %s (kata kunci halaman aktif), centang %s / %s, isi %s (0 = paling kiri), centang %s.'), '<b>' . t('Menu Navigasi') . '</b>', '<b>' . t('Label') . '</b>', '<b>URL</b>', '"<b>' . t('Label dan URL wajib diisi') . '</b>"', '<b>' . t('Ikon') . '</b>', '<b>' . t('Match key') . '</b>', '<b>' . t('Tampil di tab') . '</b>', '<b>' . t('Tampil di menu') . '</b>', '<b>' . t('Urutan') . '</b>', '<b>' . t('Aktif') . '</b>') ?></p>
<img src="<?= e($img('panduan-bab7-04-nav.png')) ?>" alt="<?= e(t('Daftar menu navigasi')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Hero: buka %s, klik %s. Pilih %s (%s — mis. gambar tur hanya tampil di halaman tur), upload gambar, isi %s, centang %s.'), '<b>' . t('Hero Slides') . '</b>', '<b>' . t('Tambah') . '</b>', '<b>' . t('Fokus') . '</b>', t('Tour, Hotel, Pesawat, Semua Fokus'), '<b>' . t('Urutan') . '</b>', '<b>' . t('Aktif') . '</b>') ?></p>
<img src="<?= e($img('panduan-bab7-05-hero.png')) ?>" alt="<?= e(t('Daftar hero slides')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= sprintf(t('Contoh: menu %s → %s urutan 1 tampil paling kiri. Hero fokus %s tampil hanya di halaman tur.'), '"<code>Promo</code>"', '<code>/promo.php</code>', '<b>' . t('Tour') . '</b>') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('muncul %s / %s. Klik %s untuk mati/nyalakan cepat tanpa hapus. Buka web — menu + gambar depan harus berubah (kalau belum, tekan %s sekali).'), '"<b>' . t('Berhasil ditambahkan') . '</b>"', '"<b>' . t('Status diubah') . '</b>"', '<b>Toggle</b>', '<b>Ctrl+Shift+R</b>') ?></p>
<div class="panduan-warn"><?= t('JANGAN hapus menu Home/Beranda atau matikan semua hero — web jadi terlihat rusak. Nonaktifkan 1 dulu, cek web, baru lanjut.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('7.9 Kalau gagal — cari pesan error-nya di sini') ?></h5>
<table class="table table-sm">
<thead><tr><th><?= t('Pesan yang muncul') ?></th><th><?= t('Artinya') ?></th><th><?= t('Solusi') ?></th></tr></thead>
<tbody>
<tr><td><?= t('Judul wajib diisi') ?></td><td><?= t('Judul artikel kosong') ?></td><td><?= t('Isi judul minimal 1 huruf, Simpan lagi') ?></td></tr>
<tr><td><?= t('Label dan URL wajib diisi') ?></td><td><?= t('Label / URL menu kosong') ?></td><td><?= t('Isi label (mis. Promo) + URL (mis. /promo.php)') ?></td></tr>
<tr><td><?= t('Hapus artikel ini? / Hapus ulasan ini? / Hapus?') ?></td><td><?= t('Konfirmasi hapus — bukan error') ?></td><td><?= t('Klik OK bila yakin, Batal bila salah klik') ?></td></tr>
<tr><td><?= t('Belum ada artikel. / Belum ada ulasan. / Belum ada data.') ?></td><td><?= t('Tabel masih kosong / pencarian tidak cocok') ?></td><td><?= t('Klik Tambah dulu; kalau habis Cari, kosongkan pencarian') ?></td></tr>
<tr><td><?= t('Berhasil ditambahkan / diperbarui / dihapus') ?></td><td><?= t('Sukses — bukan error') ?></td><td><?= t('Cek baris baru/hilang di daftar + cek halaman web') ?></td></tr>
</tbody>
</table>
</div></div>

<h4 class="fw-bold mb-1" id="bab-8"><i class="bi bi-gear me-2"></i><?= t('Bab 8 — Pengaturan Teknis: WA, Chat, Email, Kurs, API') ?></h4>
<p class="text-muted"><?= t('Kabel-kabel web: notif WA, chat tamu, log email, kurs asing, dan stok live hotel/pesawat. Salah isi di sini bisa bikin web diam atau harga salah.') ?></p>

<div class="alert alert-danger py-2">
<b><?= t('Prinsip Bab 8: foto dulu sebelum ubah!') ?></b> <?= t('Semua menu di sini sensitif. Sebelum klik Simpan, foto layar nilai lama (HP). Kalau web rusak setelah ubah, kembalikan persis seperti foto — jangan tebak-tebak.') ?>
</div>

<div class="alert alert-info py-2">
<b><?= t('Di mana menunya?') ?></b> <?= sprintf(t('Di sidebar kiri, bagian %s: %s, %s, %s, %s, %s, %s.'), '<b>' . t('Settings') . '</b>', '<b>' . t('Pengaturan WA') . '</b>', '<b>' . t('Live Chat') . '</b>', '<b>' . t('Log Email') . '</b>', '<b>' . t('Mata Uang') . '</b>', '<b>' . t('Hotel API') . '</b>', '<b>' . t('Flight API') . '</b>') ?>
<a href="wa-settings.php" class="btn btn-sm btn-primary ms-2"><?= t('Buka WA') ?></a>
<a href="chat-settings.php" class="btn btn-sm btn-outline-primary ms-1"><?= t('Buka Chat') ?></a>
<a href="email-log.php" class="btn btn-sm btn-outline-primary ms-1"><?= t('Buka Email') ?></a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('8.1 WA: nomor admin + token + tombol Test') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('setiap booking lunas/batal, HP kamu bunyi WA otomatis.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s. Isi %s (wajib, format %s — tanpa spasi/titik, tanpa +), %s, dan %s server. Klik Simpan.'), '<b>' . t('Pengaturan WA') . '</b>', '<b>' . t('Nomor WA admin') . '</b>', '<code>628xxxx</code>', '<b>Token</b>', '<b>URL</b>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Klik tombol %s (file %s): HP yang diisi harus dapat pesan %s dari nama travel-mu. Kalau dapat, kabel WA beres.'), '<b>Test</b>', '<code>wa-test.php</code>', '"✅ <b>' . t('Test Notifikasi') . '</b>"') ?></p>
<div class="panduan-tip"><?= sprintf(t('Contoh benar: nomor %s + token %s. Nomor diawali 62 (kode Indonesia), 8–15 digit setelahnya.'), '<code>6285174488415</code>', '<code>abbayosua</code>') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('muncul %s. Test terkirim muncul %s; gagal muncul %s.'), '"<b>' . t('Pengaturan WhatsApp berhasil disimpan') . '</b>"', '"<b>' . t('Test WA berhasil dikirim ke') . ' ...</b>"', '"<b>' . t('Gagal kirim WA ke') . ' ...</b>"') ?></p>
<div class="panduan-warn"><?= sprintf(t('%s / %s — perbaiki nomor lalu Simpan ulang.'), '"' . t('Nomor WA admin harus diisi') . '"', '"' . t('Nomor WA harus diawali 62 (contoh: 6285174488415)') . '"') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('8.2 Live chat + log email: sapaan tamu & bukti terkirim') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('tamu bisa chat langsung di web; kamu bisa cek email mana yang gagal terkirim dan kirim ulang.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Chat: buka %s. Isi %s (24 huruf/angka dari dashboard %s → Administration) + %s (umumnya %s). Kosongkan keduanya = widget mati. Klik Simpan.'), '<b>' . t('Live Chat') . '</b>', '<b>Property ID</b>', '<code>tawk.to</code>', '<b>Widget ID</b>', '<code>default</code>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Email: buka %s. Filter %s (%s) + %s (booking lunas, refund, dsb — 200 terbaru). Baris merah = gagal. Klik %s untuk kirim ulang isi generik dengan subject sama.'), '<b>' . t('Log Email') . '</b>', '<b>' . t('Status') . '</b>', t('sent/failed'), '<b>' . t('Event') . '</b>', '<b>Resend</b>') ?></p>
<div class="panduan-tip"><?= t('Contoh: filter Status=failed + Event=refund → ketemu 3 email gagal → Resend satu-satu, cek status jadi sent.') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('chat tersimpan muncul %s; buka web — ikon chat melayang harus ada. Email resent sukses balik dengan %s.'), '"<b>' . t('Pengaturan live chat tersimpan') . '</b>"', '<code>?msg=resent</code>') ?></p>
<div class="panduan-warn"><?= sprintf(t('%s / %s — salin ID dari dashboard tawk.to, jangan ketik manual.'), '"' . t('Property ID tawk.to harus 24 karakter hex (contoh: 5f1a2b3c4d5e6f7a8b9c0d1e)') . '"', '"' . t('Widget ID tawk.to hanya alfanumerik maks 10 karakter') . '"') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('8.3 Kurs + API live: harga asing & stok hotel/pesawat') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('harga USD/SGD tampil benar; stok hotel/pesawat ikut penyedia live (NusaTrip/Duffel) atau dimatikan jadi manual.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Kurs: buka %s. Pilih %s (bawaan IDR), isi %s (mis. 2%% = harga asing dinaikkan 2%% buat jaga-jaga naik), klik %s untuk tarik kurs baru dari Frankfurter API.'), '<b>' . t('Pengaturan Mata Uang') . '</b>', '<b>' . t('Mata uang default') . '</b>', '<b>FX buffer</b>', '<b>' . t('Refresh') . '</b>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('API: buka %s / %s. Centang %s + modul penyedia, isi %s bila diminta, tentukan %s (mis. %s). Simpan → cache dibersihkan otomatis.'), '<b>' . t('Hotel API') . '</b>', '<b>' . t('Flight API') . '</b>', '<b>' . t('Live enabled') . '</b>', '<b>rkey</b>', '<b>' . t('urutan sumber') . '</b>', '<code>nusatrip,lokal</code>') ?></p>
<div class="panduan-tip"><?= sprintf(t('Contoh: hotel live mati = tamu hanya lihat hotel ketikanmu. Urutan %s = cari ke NusaTrip dulu, gagal baru ke data lokal.'), '<code>nusatrip,lokal</code>') ?></div>
</div></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= sprintf(t('kurs muncul %s; gagal muncul %s. API tersimpan muncul %s / %s. Buka halaman hotel/pesawat web — harga & stok harus berubah.'), '"<b>' . t('Kurs berhasil diperbarui: IDR=') . '...</b>"', '"<b>' . t('Gagal mengambil kurs dari Frankfurter API') . '</b>"', '"<b>' . t('Pengaturan Hotel API tersimpan') . '</b>"', '"<b>' . t('Pengaturan Flight API tersimpan') . '</b>"') ?></p>
<div class="panduan-warn"><?= t('JANGAN matikan Live / ganti urutan sumber saat jam ramai tanpa foto nilai lama. Salah urutan (mis. ketik oyo) = error dan stok kosong. Modul OYO sudah sunset — paksa mati, jangan dihidupkan.') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('8.9 Kalau gagal — cari pesan error-nya di sini') ?></h5>
<table class="table table-sm">
<thead><tr><th><?= t('Pesan yang muncul') ?></th><th><?= t('Artinya') ?></th><th><?= t('Solusi') ?></th></tr></thead>
<tbody>
<tr><td><?= t('Nomor WA admin harus diisi') ?></td><td><?= t('Kolom nomor kosong') ?></td><td><?= t('Isi nomor format 628xxx, Simpan ulang') ?></td></tr>
<tr><td><?= t('Nomor WA harus diawali 62 (contoh: 6285174488415)') ?></td><td><?= t('Format nomor salah (pakai 08 / +62 / spasi)') ?></td><td><?= t('Buang 0/+/spasi, awali 62, 8–15 digit') ?></td></tr>
<tr><td><?= t('Gagal kirim WA ke ... Cek server log.') ?></td><td><?= t('Server WA mati / token salah') ?></td><td><?= t('Cek URL server + token, Test ulang') ?></td></tr>
<tr><td><?= t('Property ID tawk.to harus 24 karakter hex (contoh: 5f1a2b3c4d5e6f7a8b9c0d1e)') ?></td><td><?= t('Property ID salah salin') ?></td><td><?= t('Salin ulang dari dashboard tawk.to') ?></td></tr>
<tr><td><?= t('Gagal mengambil kurs dari Frankfurter API') ?></td><td><?= t('Internet/API luar mati sesaat') ?></td><td><?= t('Tunggu 5 menit, klik Refresh lagi') ?></td></tr>
<tr><td><?= t('Sumber live tidak valid / Urutan sumber hanya boleh') ?></td><td><?= t('Ketik sumber/urutan yang tidak ada') ?></td><td><?= t('Ikuti contoh: nusatrip,lokal (hotel) / nusatrip,duffel,flightlist,lokal (pesawat)') ?></td></tr>
<tr><td><?= t('rkey NusaTrip harus hex 32–160 karakter') ?></td><td><?= t('rkey salah / kependekan') ?></td><td><?= t('Salin rkey penuh dari NusaTrip, jangan diketik') ?></td></tr>
<tr><td><?= t('Pengaturan WhatsApp berhasil disimpan / live chat tersimpan / Hotel/Flight API tersimpan') ?></td><td><?= t('Sukses — bukan error') ?></td><td><?= t('Lanjut Test + cek halaman web') ?></td></tr>
</tbody>
</table>
</div></div>

<h4 class="fw-bold mb-1" id="bab-9"><i class="bi bi-people me-2"></i><?= t('Bab 9 — Tim: Dashboard, Analitik & Kelola Admin') ?></h4>
<p class="text-muted"><?= t('Pantau kesehatan web tiap pagi + atur siapa boleh buka menu apa. Bagian Kelola Admin hanya untuk superadmin.') ?></p>

<div class="alert alert-warning py-2">
<b><?= t('Khusus superadmin!') ?></b> <?= t('Menu Kelola Admin (bagian Tim) hanya tampil untuk superadmin. Admin biasa (staff) tidak melihatnya — itu normal, bukan error. Minta superadmin untuk menambah/mengubah akun.') ?>
</div>

<div class="alert alert-info py-2">
<b><?= t('Di mana menunya?') ?></b> <?= sprintf(t('Di sidebar kiri, bagian %s: %s, %s. Bagian %s: %s (superadmin saja).'), '<b>' . t('Overview') . '</b>', '<b>' . t('Dashboard') . '</b>', '<b>' . t('Analytics') . '</b>', '<b>' . t('Tim') . '</b>', '<b>' . t('Kelola Admin') . '</b>') ?>
<a href="dashboard.php" class="btn btn-sm btn-primary ms-2"><?= t('Buka Dashboard') ?></a>
<a href="analytics.php" class="btn btn-sm btn-outline-primary ms-1"><?= t('Buka Analytics') ?></a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('9.1 Dashboard: cek kesehatan web 2 menit tiap pagi') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('tahu sekilas: berapa booking, pending berapa, omzet, laba bersih, tur aktif.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s. Baca kartu KPI: %s (8 tabel booking), %s, %s (batal tidak dihitung), %s (= omzet − COGS − pengeluaran), %s, plus grafik 7 hari + 5 booking terbaru + 10 aktivitas terakhir.'), '<b>' . t('Dashboard') . '</b>', '<b>' . t('total booking') . '</b>', '<b>' . t('pending/sukses/batal') . '</b>', '<b>' . t('omzet') . '</b>', '<b>' . t('laba bersih') . '</b>', '<b>' . t('tur aktif') . '</b>') ?></p>
</div></div>
<img src="<?= e($img('panduan-bab9-01-dashboard.png')) ?>" alt="<?= e(t('KPI dan aktivitas dashboard')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Buka %s untuk detail: filter tanggal + vertikal, grafik tren, tabel per vertikal, top tur. Angka sama dengan Dashboard — bedanya bisa dibedah per tanggal/produk.'), '<b>' . t('Analytics') . '</b>') ?></p>
</div></div>
<img src="<?= e($img('panduan-bab9-02-analytics.png')) ?>" alt="<?= e(t('Grafik dan tabel analytics')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<div class="panduan-tip"><?= t('Rutinitas pagi: pending naik tiba-tiba → buka Kelola Booking proses. Laba minus → cek COGS di Bab 5. Booking sepi 3 hari → cek flash sale/promo di Bab 4.') ?></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= t('ganti filter tanggal — grafik + tabel harus berubah. Angka omzet Dashboard = angka Analytics periode sama.') ?></p>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('9.2 Kelola admin: tambah akun + centang menu (superadmin saja)') ?></h5>
<p class="mb-1"><b><?= t('Tujuan:') ?></b> <?= t('karyawan baru bisa kerja tanpa bisa utak-atik gaji/pengaturan; yang keluar langsung dikunci.') ?></p>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= sprintf(t('Sebagai superadmin, buka %s. Klik Tambah: isi %s (3–50 karakter: huruf/angka/_ . @ -), %s (baru: minimal 6; edit: kosongkan = tidak ganti), pilih %s (%s = semua menu, %s = pilih manual).'), '<b>' . t('Kelola Admin') . '</b>', '<b>' . t('Username') . '</b>', '<b>' . t('Password') . '</b>', '<b>' . t('Role') . '</b>', '<b>superadmin</b>', '<b>staff</b>') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= sprintf(t('Untuk staff, centang %s (minimal 1 — mis. Booking saja untuk CS). Simpan. Edit pakai ikon pensil, hapus pakai %s, %s untuk nyalakan/matikan cepat.'), '<b>' . t('hak akses halaman') . '</b>', '<b>' . t('Hapus') . '</b>', '<b>Toggle</b>') ?></p>
</div></div>
<img src="<?= e($img('panduan-bab9-03-admins.png')) ?>" alt="<?= e(t('Daftar admin dan hak akses')) ?>" class="panduan-img" style="max-width:640px">
<div class="panduan-cap"><?= e($cap) ?></div>
<p class="mb-1"><b><?= t('Cek hasil:') ?></b> <?= t('login sebagai akun staff baru — menu yang tidak dicentang harus hilang. Staff buka Kelola Admin langsung ditolak (403).') ?></p>
<div class="panduan-warn"><?= sprintf(t('%s / %s / %s / %s — ikuti aturannya, jangan akali.') , '"' . t('Username 3-50 karakter (huruf, angka, _ . @ -).') . '"', '"' . t('Password minimal 6 karakter.') . '"', '"' . t('Pilih minimal satu halaman.') . '"', '"' . t('Username sudah dipakai.') . '"') ?></div>
<div class="panduan-warn"><?= sprintf(t('%s / %s — minimal 1 superadmin harus hidup. Jangan hapus/cabut akun sendiri bila kamu satu-satunya superadmin.') , '"' . t('Tidak dapat menurunkan superadmin terakhir.') . '"', '"' . t('Tidak dapat menurunkan peran akun sendiri.') . '"') ?></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('9.9 Kalau gagal — cari pesan error-nya di sini') ?></h5>
<table class="table table-sm">
<thead><tr><th><?= t('Pesan yang muncul') ?></th><th><?= t('Artinya') ?></th><th><?= t('Solusi') ?></th></tr></thead>
<tbody>
<tr><td><?= t('Menu Kelola Admin tidak ada') ?></td><td><?= t('Kamu login sebagai staff, bukan superadmin') ?></td><td><?= t('Minta superadmin yang menambah/mengubah akun') ?></td></tr>
<tr><td><?= t('403 / akses ditolak buka admins.php') ?></td><td><?= t('Halaman superadmin-only') ?></td><td><?= t('Login dengan akun superadmin') ?></td></tr>
<tr><td><?= t('Username 3-50 karakter (huruf, angka, _ . @ -).') ?></td><td><?= t('Username kosong / ada spasi / terlalu pendek') ?></td><td><?= t('Isi mis. cs_rina (tanpa spasi)') ?></td></tr>
<tr><td><?= t('Password minimal 6 karakter.') ?></td><td><?= t('Password terlalu pendek') ?></td><td><?= t('Isi minimal 6 karakter') ?></td></tr>
<tr><td><?= t('Pilih minimal satu halaman.') ?></td><td><?= t('Staff tanpa centang halaman apa pun') ?></td><td><?= t('Centang minimal 1 halaman, Simpan lagi') ?></td></tr>
<tr><td><?= t('Tidak dapat menurunkan superadmin terakhir.') ?></td><td><?= t('Proteksi: web harus punya 1 superadmin') ?></td><td><?= t('Angkat 1 superadmin baru dulu, baru turunkan yang lama') ?></td></tr>
</tbody>
</table>
</div></div>

<h4 class="fw-bold mb-1" id="bab-10"><i class="bi bi-life-preserver me-2"></i><?= t('Bab 10 — Darurat: web error / salah ubah / minta tolong') ?></h4>
<p class="text-muted"><?= t('Jangan panik. Ikuti urutan ini: foto error → jangan ubah apa-apa lagi → coba 3 langkah aman → baru minta tolong dengan info lengkap.') ?></p>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('10.1 Web blank/500 setelah kamu ubah sesuatu') ?></h5>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1"><?= t('FOTO DULU layar + catat jam + menu apa yang terakhir diubah (mis. “jam 10:15 ubah Hotel API”). Jangan klik Simpan di menu lain — 1 masalah jangan ditambah masalah baru.') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1"><?= t('Kembalikan nilai seperti foto HP (Bab 8: foto-sebelum-ubah). Kalau tidak ada foto, kembalikan ke bawaan aman: Live enabled MATI (manual), urutan sumber “nusatrip,lokal” (hotel) / “nusatrip,duffel,flightlist,lokal” (pesawat), mata uang IDR.') ?></p>
</div></div>
<div class="panduan-step"><div class="panduan-num">3</div><div>
<p class="mb-1"><?= t('Coba 3 langkah aman: (a) tekan Ctrl+Shift+R (buang cache), (b) logout lalu login lagi, (c) buka browser lain/HP. Kalau 1 browser error tapi browser lain normal = masalah cache, bukan data.') ?></p>
<div class="panduan-tip"><?= t('Contoh: setelah ubah urutan sumber jadi “oyo”, halaman hotel kosong. Kembalikan ke “nusatrip,lokal”, Simpan, Ctrl+Shift+R — hotel muncul lagi.') ?></div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('10.2 Salah hapus / salah klik Approve / salah ubah harga') ?></h5>
<ul class="mb-1">
<li><?= t('Hapus (blog/FAQ/menu/admin/ulasan): TIDAK bisa dikembalikan. Jangan buat ulang asal — cek dulu halaman web, catat yang hilang, baru buat ulang pelan-pelan.') ?></li>
<li><?= t('Salah Approve topup (kasih saldo gratis): jangan kurangi manual diam-diam. Catat username + nominal + jam, hubungi pemilik untuk potong/lebih-bayar resmi lewat menu Topup.') ?></li>
<li><?= t('Salah ubah harga/promo: nonaktifkan dulu yang salah (Toggle/Aktif mati), baru perbaiki. Salah yang tampil = rugi; salah yang mati = aman.') ?></li>
<li><?= t('Salah ubah ambang loyalty saat promo jalan: kembalikan ke nilai foto, jangan ubah lagi sampai promo selesai.') ?></li>
</ul>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold"><?= t('10.3 Minta tolong teknis: kirim info lengkap biar cepat dibalas') ?></h5>
<p class="mb-1"><?= t('Kirim 5 info ini sekaligus (jangan dicicil):') ?></p>
<ol class="mb-1">
<li><?= t('Foto layar error (full layar, termasuk alamat web di atas).') ?></li>
<li><?= t('Jam kejadian + menu terakhir diubah + nilai lama (dari foto Bab 8) + nilai baru.') ?></li>
<li><?= t('Akun yang dipakai (username admin) + browser/HP apa.') ?></li>
<li><?= t('Sudah coba apa: Ctrl+Shift+R? logout-login? browser lain? hasilnya apa?') ?></li>
<li><?= t('Dampak: tamu tidak bisa bayar / admin tidak bisa login / hanya tampilan rusak?') ?></li>
</ol>
<div class="panduan-tip"><?= sprintf(t('Contoh pesan bagus: %s. Teknisi langsung tahu harus ke mana tanpa tanya balik 5x.'), '"<code>Jam 10:15 ubah Hotel API urutan jadi “oyo” (lama: “nusatrip,lokal”), halaman hotel kosong, akun admin, Chrome laptop. Sudah Ctrl+Shift+R + browser lain tetap kosong. Tamu tidak bisa cari hotel.</code>"') ?></div>
</div></div>

<?php require_once 'includes/admin-footer.php'; ?>


<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once 'includes/admin-access.php';
requireAdminPage();

$pageTitle = 'Panduan Branding';
require_once 'includes/admin-header.php';
$img = fn(string $f): string => '../assets/img/panduan/' . $f;
?>
<style>
.panduan-step{display:flex;gap:.75rem;margin-bottom:1rem}
.panduan-num{flex:0 0 32px;width:32px;height:32px;border-radius:50%;background:#0d6efd;color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center}
.panduan-img{border:1px solid #dee2e6;border-radius:.5rem;max-width:100%;height:auto;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.panduan-tip{background:#fff8e1;border-left:4px solid #ffc107;padding:.6rem .9rem;border-radius:.25rem;margin:.6rem 0}
.panduan-warn{background:#fdecea;border-left:4px solid #dc3545;padding:.6rem .9rem;border-radius:.25rem;margin:.6rem 0}
</style>

<h4 class="fw-bold mb-1"><i class="bi bi-book me-2"></i>Panduan: Ganti Branding Website</h4>
<p class="text-muted">Pengganti nama travel, logo, kontak footer, dan sosmed — tanpa perlu bisa coding. Ikuti langkah 1 sampai 7 berurutan.</p>

<div class="alert alert-info py-2">
<b>Di mana menunya?</b> Di sidebar kiri, bagian <b>Content</b>, klik <b>Brand &amp; Logo</b>.
<a href="brand-settings.php" class="btn btn-sm btn-primary ms-2"><i class="bi bi-arrow-right-circle"></i> Buka Brand &amp; Logo</a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Langkah 1 — Masuk ke admin</h5>
<div class="panduan-step"><div class="panduan-num">1</div><div>
<p class="mb-1">Buka halaman login admin, isi <b>Username</b> dan <b>Password</b>, lalu klik <b>Masuk</b>.</p>
<img src="<?= e($img('panduan-brand-01-login.png')) ?>" alt="Halaman login admin" class="panduan-img" style="max-width:420px">
<div class="panduan-tip">Kalau salah password, muncul tulisan merah "Username atau password salah" — ketik ulang perlahan.</div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Langkah 2 — Ganti nama travel + tagline</h5>
<div class="panduan-step"><div class="panduan-num">2</div><div>
<p class="mb-1">Di halaman <b>Brand &amp; Logo</b>, bagian paling atas ada <b>Nama travel</b> (wajib, maks 60 huruf) dan <b>Tagline</b> (boleh kosong, maks 120 huruf). Nama ini muncul di menu atas web, judul tab browser, email, notifikasi WA, dan PDF.</p>
<img src="<?= e($img('panduan-brand-02-form-atas.png')) ?>" alt="Kolom nama travel dan tagline" class="panduan-img" style="max-width:560px">
<div class="panduan-warn">Nama tidak boleh kosong. Kalau kosong, muncul error "Nama travel wajib diisi" dan tidak tersimpan.</div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Langkah 3 — Ganti link sosmed (boleh kosong)</h5>
<div class="panduan-step"><div class="panduan-num">3</div><div>
<p class="mb-1">Isi 4 kolom <b>Media Sosial</b> dengan link lengkap (diawali <code>https://</code>). <b>Kosongkan</b> baris yang tidak punya akun — ikonnya otomatis hilang dari footer.</p>
<img src="<?= e($img('panduan-brand-03-sosmed.png')) ?>" alt="Kolom media sosial" class="panduan-img" style="max-width:560px">
<div class="panduan-tip">Contoh benar: <code>https://instagram.com/royaldream</code>. Contoh salah: <code>royaldream</code> (tanpa https://) — tidak tersimpan sebagai link.</div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Langkah 4 — Ganti alamat, telepon, WA, email, jam (tampil di footer)</h5>
<div class="panduan-step"><div class="panduan-num">4</div><div>
<p class="mb-1">Bagian <b>Kontak &amp; Alamat</b> tampil di footer bawah setiap halaman web. Nomor <b>WhatsApp</b> cukup angka saja, tanpa spasi/tanda plus — contoh benar <code>6281234567890</code>, salah <code>0812 3456 7890</code>.</p>
<img src="<?= e($img('panduan-brand-04-kontak.png')) ?>" alt="Kolom kontak dan alamat" class="panduan-img" style="max-width:560px">
<div class="panduan-tip">Setiap kolom maks 160 huruf. Tombol telepon, email, dan WA di footer otomatis bisa diklik pengunjung.</div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Langkah 5 — Ganti logo, favicon, dan gambar share</h5>
<div class="panduan-step"><div class="panduan-num">5</div><div>
<p class="mb-1">Bagian <b>Logo (JPG/PNG/WebP, maks 2MB)</b>: klik <b>Choose File</b> untuk logo, favicon (icon kecil di tab browser, maks 512KB), dan OG image (gambar saat link di-share ke WA/FB, maks 2MB). Centang <b>Hapus</b> bila ingin menghilangkan gambar lama.</p>
<img src="<?= e($img('panduan-brand-05-logo.png')) ?>" alt="Kolom upload logo" class="panduan-img" style="max-width:560px">
<div class="panduan-warn">Jangan upload file selain gambar (mis. PDF/Word) — muncul error dan gambar tidak tersimpan.</div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Langkah 6 — Klik Simpan, tunggu tulisan hijau</h5>
<div class="panduan-step"><div class="panduan-num">6</div><div>
<p class="mb-1">Klik tombol biru <b>Simpan</b> di bawah form. Tunggu sampai muncul tulisan hijau <b>"Brand &amp; logo tersimpan"</b> — itu tandanya sudah masuk database. Jangan tutup halaman sebelum tulisan hijau muncul.</p>
<img src="<?= e($img('panduan-brand-06-simpan.png')) ?>" alt="Tulisan sukses setelah simpan" class="panduan-img" style="max-width:640px">
<div class="panduan-warn">Bagian <b>Email pengirim</b> di bawah kontak JANGAN diubah kalau tidak paham API key — biarkan apa adanya, web tetap jalan.</div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Langkah 7 — Cek hasil di web</h5>
<div class="panduan-step"><div class="panduan-num">7</div><div>
<p class="mb-1">Buka halaman depan web, scroll ke bawah (footer). Pastikan nama, alamat, telepon, WA, email, dan jam sudah berubah seperti yang diisi.</p>
<img src="<?= e($img('panduan-brand-07-hasil-footer.png')) ?>" alt="Footer web setelah ganti branding" class="panduan-img" style="max-width:640px">
<div class="panduan-tip">Klik ikon telepon/email/WA di footer untuk memastikan link-nya jalan. Kalau belum berubah, tekan <b>Ctrl+Shift+R</b> (refresh bersih) sekali.</div>
</div></div>
</div></div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
<h5 class="fw-bold">Kalau gagal — cari pesan error-nya di sini</h5>
<table class="table table-sm">
<thead><tr><th>Pesan yang muncul</th><th>Artinya</th><th>Solusi</th></tr></thead>
<tbody>
<tr><td>Nama travel wajib diisi</td><td>Kolom nama kosong</td><td>Isi nama minimal 1 huruf, klik Simpan lagi</td></tr>
<tr><td>Nama travel maksimal 60 karakter</td><td>Nama kepanjangan</td><td>Pendekkan nama, singkatan boleh</td></tr>
<tr><td>Alamat email pengirim tidak valid</td><td>Email salah ketik</td><td>Pakai format <code>nama@domain.com</code>, atau kembalikan ke bawaan</td></tr>
<tr><td>Tipe favicon harus … / Ukuran favicon maksimal 512KB</td><td>File favicon salah</td><td>Pakai ICO/PNG/JPG/WebP/SVG di bawah 512KB</td></tr>
<tr><td>File gambar / Gagal menyimpan file</td><td>File rusak / folder penuh</td><td>Coba file lain di bawah 2MB, ulangi Simpan</td></tr>
</tbody>
</table>
</div></div>

<?php require_once 'includes/admin-footer.php'; ?>

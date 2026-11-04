# TULISDOCS.md — Planning Panduan Pengguna Admin Panel (Awam)

> Status: PLANNING — obrolan dulu, eksekusi belakangan.
> Fokus iterasi 1: **Cara mengganti branding** (Royal Dream) + screenshot via CDP.
> File panduan akhir: `admin/panduan.php` (HTML, butuh login admin).

## 1. Tujuan

- Pengguna awam (pemilik travel, bukan IT) bisa ganti sendiri: nama travel, logo, kontak footer, sosmed, warna, tanpa takut merusak web.
- Tiap langkah ada **screenshot asli dari CDP** (bukan mockup), nama tombol persis seperti di layar, dan contoh benar/salah.
- Bahasa Indonesia santai, nol istilah teknis tanpa penjelasan.

## 2. Keputusan yang sudah disepakati

1. Format = **satu file HTML di dalam admin** (`admin/panduan.php`), bukan PDF terpisah. Alasan:
   - Selalu ikut versi kode terbaru (git).
   - Bisa link langsung ke halaman admin (`brand-settings.php`, dll).
   - Bisa embed screenshot lokal `assets/img/panduan/`.
2. Mulai dari **bab branding dulu**, bab lain menyusul (Tur → Pesanan → Konten → Teknis).
3. Screenshot diambil via **CDP / Playwright Chromium** (headless), bukan screenshot manual. Alasan: konsisten, bisa diulang, resolusi sama.
4. Data screenshot = **data Royal Dream asli** (server lokal), bukan data E2E `Royal Dream E2E xxx` yang bikin bingung.

## 3. Sumber fakta (jangan diasumsi, sudah dibaca)

File acuan: `admin/brand-settings.php:1-147`

- URL: `/admin/brand-settings.php`, guard `requireAdminPage()`, sidebar label **Brand & Logo** (section Content).
- Field + batas (wajib ditulis di panduan):
  - `site_name` (Nama travel): wajib, maks 60 karakter. Error bila kosong: "Nama travel wajib diisi".
  - `site_tagline` (Tagline opsional): maks 120 karakter.
  - `social_instagram|facebook|youtube|tiktok`: type=url, kosongkan = sembunyikan.
  - `contact_address|phone|wa|email|hours_weekday|hours_sunday`: maks 160 karakter, tampil di footer.
  - `email_from`: harus format email valid. `email_driver`: log (dev) / api (Resend). `email_api_key` (password), `email_api_endpoint` (default `https://api.resend.com/emails`).
  - `site_logo`: JPG/PNG/WebP maks 2MB (`uploadGambar`). `site_favicon`: ICO/PNG/JPG/WebP/SVG maks 512KB. `site_og_image`: JPG/PNG/WebP maks 2MB.
  - Checkbox hapus: `remove_logo`, `remove_favicon`, `remove_og_image`.
  - Tombol: **Simpan** (`data-testid=brand-save`), **Batal** (ke dashboard). Sukses: alert hijau `data-testid=brand-saved` "Brand & logo tersimpan".
  - Pratinjau kanan: logo 32px + nama + tagline.
- Efek ganti (wajib dijelaskan ke user):
  - Nama → navbar, footer, judul tab, email, notifikasi WA, PDF brosur/itinerary.
  - Kontak → footer publik (`includes/footer-shared.php`), link klik `tel:`, `mailto:`, `https://wa.me/`.
  - Logo → navbar + kop PDF. Favicon → icon tab browser. OG image → preview share WA/FB.
  - Warna tema saat ini: `theme_color=#7A0C1A` (bila ada di DB; kalau belum, tetap sebut default).
- Kredensial demo Royal Dream (sudah diverifikasi live):
  - User `admin`, pass `admin123` (bawaan schema, DB royal hasil clone situs utama).
  - URL royal: `https://royaldream.tourandtravel.web.id/admin/login.php`.
  - URL lokal panduan/screenshot: `http://localhost/tourandtravel/admin/`.

## 4. Struktur panduan (HTML) — iterasi 1 cukup Bab 0-1

```
admin/panduan.php
├── Bab 0: Mulai di sini (3 menit)
│   ├── Cara buka: URL admin + login admin/admin123
│   ├── Peta sidebar: kamu hanya perlu menu "Brand & Logo" dulu
│   └── Aturan aman: yang boleh diubah vs jangan disentuh
├── Bab 1: Ganti branding (inti iterasi 1)
│   ├── 1.1 Ganti nama + tagline
│   ├── 1.2 Ganti logo (upload, hapus, pratinjau)
│   ├── 1.3 Ganti favicon + OG image (icon tab + preview share)
│   ├── 1.4 Ganti alamat/kontak/jam (footer)
│   ├── 1.5 Ganti sosmed (link Instagram/FB/YT/TikTok)
│   ├── 1.6 Ganti email pengirim (peringatan: jangan ubah API key kalau tidak paham)
│   ├── 1.7 Klik Simpan + cek hasil di web (link langsung)
│   └── 1.8 Kalau gagal (tabel error umum: nama kosong, email salah, file kebesaran)
└── (Next: Bab 2 Tur, Bab 3 Pesanan — cukup judul + "segera hadir")
```

Format tiap sub-bab (konsisten):
1. Tujuan 1 kalimat ("supaya nama di web jadi Royal Dream").
2. Langkah bernomor: "Klik X → isi Y → klik Simpan" (nama tombol persis).
3. Screenshot tiap langkah kritis (lihat §5).
4. Contoh benar/salah (mis. WA `62812...` benar vs `0812...` + spasi salah).
5. "Cek hasil": buka homepage, lihat bagian yang berubah.
6. "Kalau error": pesan error persis + solusi 1 baris.

## 5. Rencana screenshot via CDP (bagian diminta user)

### 5.1 Tooling (pakai yang sudah ada, jangan bikin baru)

- Playwright `@playwright/test ^1.62.1`, Chromium headless, viewport 1280x720 (desktop) + 360x800 (1 contoh mobile footer).
- Pola login tiru `tests/e2e/brand-contact-footer.spec.ts:23-29` (admin/tmpcheck123 lokal; global-setup menyiapkan hash, teardown restore).
- Output: `assets/img/panduan/` (baru, di-commit). Nama: `panduan-brand-01-login.png`, `panduan-brand-02-form-atas.png`, dst. Jangan taruh di `/tmp` (hilang) dan jangan pakai snapshot e2e (`-snapshots/`) yang flaky.

### 5.2 Daftar screenshot Bab 1 (7 file, cukup, tidak berlebihan)

| # | File | Halaman / aksi CDP | Area |
|---|------|--------------------|------|
| 1 | `panduan-brand-01-login.png` | `admin/login.php`, isi user+pass (jangan submit) | full card login |
| 2 | `panduan-brand-02-form-atas.png` | `admin/brand-settings.php`, scroll atas | nama + tagline + pratinjau |
| 3 | `panduan-brand-03-sosmed.png` | sama, scroll ke Media Sosial | 4 input sosmed |
| 4 | `panduan-brand-04-kontak.png` | sama, scroll ke Kontak & Alamat | 6 input kontak |
| 5 | `panduan-brand-05-logo.png` | sama, scroll ke Logo + preview | 3 input file + preview |
| 6 | `panduan-brand-06-simpan.png` | klik Simpan, tunggu `[data-testid=brand-saved]` | alert hijau + form |
| 7 | `panduan-brand-07-hasil-footer.png` | `index.php`, scroll ke `footer#kontak` | footer Royal Dream live |

Catatan: halaman brand-settings panjang → ambil per-seksi (full-page sekaligus teksnya kekecilan buat awam). Selector jangkar: `[data-testid=brand-name]`, `[data-testid=brand-contact-address]`, `[data-testid=brand-logo]`, `[data-testid=brand-saved]`, `footer#kontak`.

### 5.3 Skrip CDP (baru, sekali pakai tapi disimpan)

- File: `tests/e2e/panduan-brand-screenshots.spec.ts` (tidak assert visual, hanya `page.screenshot({path})`).
- Alur: global-setup login prep → goto login → screenshot 01 → login → goto brand-settings → screenshot 02-05 → fill dummy? **TIDAK** — jangan ubah data Royal Dream asli; cukup screenshot nilai asli + 1x klik Simpan tanpa ubah (aman, idempotent) untuk dapat 06 → goto index → screenshot 07.
- Verifikasi wajib tiap run: file ada + size > 20KB + tidak blank (cek via `ls -l`).
- Bahasa Indonesia di screenshot: tambahkan `?lang=id`.

### 5.4 Risiko screenshot (dicatat biar tidak flaky seperti e2e lama)

- Baseline e2e lama flaky karena data `E2E+timestamp` mengubah lebar/tinggi. Mitigasi panduan: pakai data Royal Dream final, JANGAN isi timestamp.
- Form panjang → jangan `toHaveScreenshot` locator kecil; pakai `page.screenshot` full/section.
- Upload file tidak perlu difoto (file picker OS beda-beda); cukup foto input + preview yang sudah ada.

## 6. Acceptance criteria iterasi 1 (cek sebelum bilang jadi)

- [ ] `admin/panduan.php` bisa dibuka setelah login, ada di sidebar (atau link dari brand-settings).
- [ ] 7 screenshot ada di `assets/img/panduan/`, tampil di panduan, terbaca di 1280px dan HP.
- [ ] Semua nama tombol/label di tulisan = persis di layar (cek ulang `brand-settings.php`).
- [ ] Batas/validasi ditulis: 60/120/160 char, tipe file, maks size, format email/WA.
- [ ] Ada bagian "cek hasil" dengan link langsung ke homepage + footer.
- [ ] Ada bagian "kalau gagal" untuk 4 error: nama kosong, nama >60, email invalid, file kebesaran/salah tipe.
- [ ] `php -l admin/panduan.php` bersih, tidak merusak E2E lama.

## 7. Next steps (urun, belum dikerjakan)

1. ACC planning ini dari pemilik (atau revisi).
2. Tulis skrip `panduan-brand-screenshots.spec.ts`, run lokal, verifikasi 7 PNG.
3. Tulis `admin/panduan.php` + daftarkan di sidebar (`admin-access.php` `adminPages()` bila perlu) + link "Butuh bantuan? Baca panduan" di `brand-settings.php`.
4. Review bahasa dengan mata awam (minta 1 orang non-IT baca, catat yang bingung).
5. Baru lanjut Bab 2 (Kelola Tur).

## 8. Yang SENGAJA tidak dikerjakan di iterasi 1

- Bab Tur/Pesanan/Pembayaran/API — hanya placeholder judul.
- Versi cetak/PDF panduan — diputuskan setelah HTML dipakai (user belum minta).
- Translate panduan (id/en/zh) — panduan cukup Indonesia dulu.
- Ubah `brand-settings.php` itu sendiri — panduan hanya membaca, tidak refactor.

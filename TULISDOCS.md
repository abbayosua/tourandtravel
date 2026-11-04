# TULISDOCS.md — Planning Panduan Pengguna Admin Panel (Awam)

> Status: PLANNING PENUH — mencakup SELURUH fitur admin panel. Bab 1 (branding) SUDAH LIVE; Bab 2-10 direncanakan di bawah.
> Fokus iterasi 1: **Cara mengganti branding** (Royal Dream) + screenshot via CDP.
> File panduan akhir: `admin/panduan.php` (HTML, butuh login admin).

## 1. Tujuan (diperluas: seluruh admin panel)

- Pemilik travel awam (bukan IT) bisa menjalankan SEMUA tugas admin harian sendiri tanpa takut merusak web: branding, produk, pesanan, promo, keuangan, reseller, konten, pengaturan teknis, tim, dan darurat.
- Cakupan = SELURUH menu sidebar admin (sumber: `adminPages()` di `admin/includes/admin-access.php`): Overview (Dashboard, Analytics), Inventory (Tour, Hotel+Ruang, Pesawat, Ferry, Rental, Atraksi, Transfer, Kereta, eSIM), Bookings (Booking, Pembayaran), Marketing (Flash Sale, Promo, Price Alert, Push, Corporate, Koleksi), Finance (Sales Report, Accounting, Loyalty), Reseller (Kelola, Topup, Harga), Content (Blog, Ulasan, FAQ, Menu, Hero Slides, Brand & Logo, Panduan), Settings (WA, Chat, Email Log, Mata Uang, Hotel/Flight API), Tim (Kelola Admin). Dikecualikan: `appearance.php` (hidden tech-debt), `ab-tests.php` (hidden).
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

## 4. Struktur panduan (HTML) — SELURUH fitur admin panel (Bab 0–10; peta menu dari `adminPages()` di `admin/includes/admin-access.php`, status: Bab 1 SUDAH LIVE ✅)

```
admin/panduan.php (+ anchor #bab-N per bab, sidebar panduan = daftar isi)
├── Bab 0: Mulai di sini (3 menit) ✅ SUDAH ADA (ringkas, di atas Bab 1)
│   ├── Cara buka: URL admin + login (admin/admin123 lokal; akun masing2 di produksi)
│   ├── Peta sidebar 9 section: Overview, Inventory, Bookings, Marketing, Finance, Reseller, Content, Settings, Tim
│   └── Aturan aman: hijau (bebas) vs kuning (hati2) vs merah (jangan sentuh: API key, pembayaran, hapus massal)
├── Bab 1: Ganti branding ✅ SUDAH LIVE (`brand-settings.php`; 7 langkah + 7 PNG + tabel error; multilingual id/en/zh)
│   ├── 1.1 Nama + tagline (wajib, maks 60/120)
│   ├── 1.2–1.3 Logo/favicon/OG (tipe + size) 
│   ├── 1.4 Kontak footer (alamat/telp/WA/email/jam; format WA angka saja)
│   ├── 1.5 Sosmed (URL penuh, kosongkan = sembunyikan)
│   ├── 1.6 Email pengirim (⚠️ jangan ubah API key bila tak paham)
│   ├── 1.7 Simpan + cek hasil (alert hijau, refresh bersih)
│   └── 1.8 Kalau gagal (5 baris error → solusi)
├── Bab 2: Kelola produk/Inventory (tours, hotels+rooms, flights, ferries, rental-cars, attractions, transfers, trains, esim)
│   ├── 2.0 Pola umum semua inventori: Daftar → Tambah/Edit → harga & slot/tanggal → foto → aktif/nonaktif → cek di web
│   ├── 2.1 Tour (`tours.php` → `tour-add/edit.php`): judul 3 bahasa ATAU mode AI AUTO (tulis 1 bahasa → pratinjau → simpan; Meta LLM) + tanggal keberangkatan + itinerary + harga + cover
│   ├── 2.2 Hotel (`hotels` + `hotel-rooms`): data hotel → tipe kamar + harga/malam → sumber live (NusaTrip/OYO) vs inventori lokal
│   ├── 2.3 Pesawat/Ferry/Kereta/Transfer/Rental/Atraksi/eSIM: jadwal/rute/harga per file `-edit.php` masing2; bedakan yg API-live vs manual
│   └── 2.9 Kalau gagal: validasi wajib isi, harga 0, tanggal lewat, foto kebesaran
├── Bab 3: Pesanan & Pembayaran (bookings, payments)
│   ├── 3.1 Baca daftar booking: status (pending/paid/cancelled/dll), cari kode, filter tanggal
│   ├── 3.2 Proses: konfirmasi manual, batal + refund per kebijakan, kirim ulang e-tiket/WA
│   ├── 3.3 Pembayaran (`payments.php`): cek status Midtrans/Tripay/Xendit, cocokkan VA, jangan tandai lunas tanpa bukti
│   └── 3.9 Kalau gagal: booking tak muncul, status macet, double-bayar → solusi + siapa dihubungi
├── Bab 4: Marketing (flash-sales, promo-codes, price-alerts, push-notifications, corporate-rates, collections, ab-tests[hidden])
│   ├── 4.1 Flash sale & kode promo: syarat, periode, kuota, prioritas diskon (jangan dobel dengan reseller)
│   ├── 4.2 Price alert & push: kapan notif terkirim (throttle 1/jam), cara kirim push FCM
│   ├── 4.3 Corporate & koleksi: kaitkan user perusahaan, susun koleksi homepage
│   └── 4.9 Kalau gagal: promo tak jalan (cek tanggal/kuota/syarat), push tak sampai (cek FCM key)
├── Bab 5: Keuangan (sales-report, accounting, loyalty-settings)
│   ├── 5.1 Baca laporan: omzet per vertikal, tren, AOV, konversi; filter periode
│   ├── 5.2 Accounting: catat pengeluaran + COGS per booking; baca laba rugi
│   ├── 5.3 Loyalty: ambang silver/gold, % poin, tukarkan poin (jangan ubah ambang saat promo jalan)
│   └── 5.9 Kalau gagal: angka tak cocok (cek filter/timezone), poin tak masuk (cek earning rate)
├── Bab 6: Reseller (resellers, reseller-topups, reseller-pricing)
│   ├── 6.1 Daftarkan reseller + atur peran; 6.2 Setujui/tolak topup saldo (cek bukti transfer)
│   ├── 6.3 Harga khusus reseller per tur (jangan lebih murah dari flash sale)
│   └── 6.9 Kalau gagal: saldo tak masuk, harga tak berlaku → cek status/role
├── Bab 7: Konten web (posts, reviews, faq, nav-menus, hero-slides, brand-settings→Bab1, appearance[hidden])
│   ├── 7.1 Blog (`posts`): tulis → terbit/draf → cek di /blog
│   ├── 7.2 Ulasan (`reviews`): moderasi tampil/sembunyi; balas ulasan
│   ├── 7.3 FAQ + kategori: urutan tampil di /faq; jangan hapus kategori berisi FAQ
│   ├── 7.4 Menu navigasi + hero slides: tambah tautan, atur urutan, fokus homepage
│   └── 7.9 Kalau gagal: konten tak tampil (cek status/draf, cache, bahasa aktif)
├── Bab 8: Pengaturan teknis ⚠️ (wa-settings+test, chat-settings, email-log, currency-settings, hotel/flight-api-settings)
│   ├── 8.0 Prinsip: baca saja dulu; ubah hanya bila dipandu; catat nilai lama sebelum ubah
│   ├── 8.1 WA (`wa-settings/wa-test`): nomor admin, template notif, tombol tes kirim
│   ├── 8.2 Chat (tawk.to IDs), Email (driver log/api + endpoint/key + email_from; cek `email-log`)
│   ├── 8.3 Mata uang (default + kurs), Hotel/Flight API (pilih sumber, key, toggle live)
│   └── 8.9 Kalau gagal: notif tak terkirim (cek key/endpoint/log), kurs aneh (cek sumber)
├── Bab 9: Tim & akses (dashboard, analytics, admins) — superadmin saja
│   ├── 9.1 Dashboard/analytics: baca ringkasan (jangan jadikan laporan pajak resmi)
│   ├── 9.2 Kelola admin: buat akun staff + centang halaman yg boleh dibuka; superadmin vs staff
│   └── 9.9 Kalau gagal: staff tak bisa buka (cek grant), lupa password (reset via superadmin)
└── Bab 10: Darurat & pemulihan (boleh dibaca semua)
    ├── Web error/500: jangan panik, catat jam + halaman + screenshot; cek `logs/` via panduan SSH (atau hubungi teknis)
    ├── Salah ubah: kembalikan manual (panduan tiap bab mencatat nilai bawaan) 
    └── Minta tolong teknis: info wajib (URL, akun, langkah terakhir, pesan error persis)
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

## 7. Next steps (berurutan: Bab 0, 2–10)

1. ACC planning penuh ini dari pemilik (atau revisi).
2. Bab 0: tulis + screenshot dashboard/sidebar (1 spec, 3 PNG).
3. Bab 2 Inventory: tulis + screenshot per pola umum + AI AUTO tour (1–2 spec, ±10 PNG).
4. Bab 3 Bookings: tulis + screenshot daftar/proses (1 spec, ±5 PNG).
5. Bab 4 Marketing: tulis + screenshot promo/flash (1 spec, ±5 PNG).
6. Bab 5 Finance: tulis + screenshot laporan (1 spec, ±4 PNG).
7. Bab 6 Reseller: tulis + screenshot topup/pricing (1 spec, ±4 PNG).
8. Bab 7 Konten: tulis + screenshot blog/FAQ/ulasan/menu (1 spec, ±6 PNG).
9. Bab 8 Settings: tulis + screenshot (TANPA mengubah key produksi; 1 spec, ±5 PNG).
10. Bab 9 Tim: tulis + screenshot grant (1 spec, ±3 PNG, akun uji dihapus sesudahnya).
11. Bab 10 Darurat: tulis (tanpa screenshot rusak2; 0 spec).
12. Multilingual-kan tiap bab baru mengikuti Bab 9 (bungkus `t()` → seed Meta → audit 0 → E2E `panduan-i18n` diperluas).
13. Review awam 1 orang non-IT per bab.
14. Commit + push + deploy Royal + utama per bab (pola stash-config).

## 8. Yang tidak dikerjakan (bukan penundaan bab)

- Versi cetak/PDF panduan — diputuskan setelah HTML dipakai (user belum minta).
- Ubah halaman admin itu sendiri — panduan hanya membaca, tidak refactor.
- Foto file-picker OS / QRIS / bukti transfer asli — diganti ilustrasi/sketsa agar tidak bocor data.
- `appearance.php` + `ab-tests.php` (hidden tech-debt) — tidak didokumentasikan sampai diaktifkan kembali.

## 9. Plan: Panduan Multilingual (id/en/zh) — DISETUJUI

### 9.1 Fakta awal (terverifikasi, bukan asumsi)

- `admin/panduan.php` saat ini NOL `t()` — sekitar 40 string hardcoded Indonesia (judul, langkah, tips, warning, tabel error).
- Pola i18n situs (lihat `MULTILINGUAL.md`): string UI via `t('...')` → tabel `translations` (UNIQUE key+lang). Bahasa aktif via `getCurrentLang()` (session → cookie → Accept-Language → default id). Header admin sudah ada switcher `?lang=` otomatis dari `getSupportedLanguages()`.
- Screenshot panduan tetap 1 set (Indonesia) — tidak perlu 3x foto; gambar UI tidak diterjemahkan per-bahasa. Yang multilingual = TEKS panduan.

### 9.2 Desain (keputusan)

1. Bungkus SEMUA string user-facing di `panduan.php` dengan `t('...')` — judul, intro, langkah, tips, warning, header tabel, isi tabel error. Kecuali: nama brand/contoh (`Royal Dream`, `62812...`, `https://...`, nama tombol bila tombolnya sendiri belum diterjemahkan).
2. Seed en+zh via Meta LLM (sudah hidup: `muse-spark-1.3`, key di `.env`) memakai pola `database/regenerate-en.php` + kamus manual bernada awam — JANGAN translate mentah tanpa review (mis. "klik Simpan" harus konsisten dengan label tombol terjemahan).
3. Verifikasi per MULTILINGUAL.md: `php scripts/audit-translations.php` → missing en/zh untuk key panduan = 0; buka `panduan.php?lang=en` dan `?lang=zh`, pastikan tak ada string Indonesia nyasar (kecuali brand/contoh).
4. Screenshot: tetap 7 PNG Indonesia. Tambah caption di bawah tiap gambar: "Tampilan menu dalam Bahasa Indonesia — posisi tombol sama di semua bahasa." (juga via `t()`).
5. E2E: tambah spec ringan `panduan-i18n.spec.ts` — login, buka `?lang=en` assert judul EN, `?lang=zh` assert judul ZH, assert 7 `.panduan-img` naturalWidth > 0. Tanpa snapshot visual (hindari flaky).

### 9.3 Daftar string (ringkas; key = teks Indonesia persis)

| Kelompok | Jumlah | Contoh key |
|---|---|---|
| Judul + intro + alert menu | 4 | `Panduan: Ganti Branding Website`, `Di mana menunya?` |
| Judul langkah 1-7 + tabel | 8 | `Langkah 1 — Masuk ke admin`, `Kalau gagal — cari pesan error-nya di sini` |
| Isi langkah + tips + warning | ~20 | `Buka halaman login admin, isi...`, `Nama tidak boleh kosong...` |
| Header + isi tabel error (5x3) | 16 | `Pesan yang muncul`, `Nama travel wajib diisi` (pakai ulang key validasi brand-settings yang SUDAH ADA — jangan duplikasi) |
| Alt gambar (7) + caption bahasa | 8 | `Halaman login admin`, `Tampilan menu dalam Bahasa Indonesia...` |

Total ~55 key baru di `translations`. Key validasi yang sudah ada dipakai ulang.

### 9.4 Langkah eksekusi (berurutan)

1. Bungkus `panduan.php` dengan `t()` (satu commit, `php -l` bersih).
2. `php scripts/audit-translations.php` → catat missing keys panduan.
3. Seed en: `regenerate-en.php` (identity) → isi kamus en via Meta + review nada awam.
4. Seed zh: pola sama → review.
5. Verifikasi: audit 0 missing; buka `?lang=id/en/zh`; screenshot EN+ZH 1 halaman penuh sebagai bukti (`/tmp` saja, TIDAK commit).
6. Tulis `tests/e2e/panduan-i18n.spec.ts`, run hijau.
7. Commit + push + deploy Royal + situs utama (pola stash-config biasa).

### 9.5 Kriteria selesai

- [ ] `panduan.php?lang=id/en/zh` ketiganya penuh tanpa string nyasar.
- [ ] `audit-translations.php`: missing en/zh key panduan = 0.
- [ ] E2E `panduan-i18n.spec.ts` hijau; E2E lama tak ada merah baru.
- [ ] Caption bahasa tampil di bawah tiap screenshot.

---

## 10. Cakupan Bab 2–10 (detail per bab: file acuan, screenshot, estimasi)

Standar tiap bab baru: baca file admin dulu (catat field/batas/error persis) → tulis dengan format §4 → screenshot via CDP (pola §5: skrip `panduan-<bab>-screenshots.spec.ts`, data Royal Dream final, `?lang=id`, verifikasi size>20KB) → multilingual-kan (pola Bab 9) → E2E ringan → commit+deploy.

| Bab | File acuan | Screenshot (±) | Estimasi | Catatan |
|---|---|---|---|---|
| 0 Mulai | `admin-access.php`, `login.php`, `dashboard.php` | 3: login, sidebar 9 section, dashboard | S | Sudah ada ringkas; lengkapi peta + aturan aman |
| 2 Inventory | `tour-add/edit`, `hotel-edit`, `hotel-rooms`, `flight-edit`, `ferry-edit`, `rental-car-edit`, `attraction-edit`, `transfer-edit`, `train-edit`, `esim-edit` | 10: daftar, form, AI AUTO, tanggal/harga, foto, hasil | L | Tekankan pola umum 2.0 + AI AUTO Meta + live-vs-manual |
| 3 Bookings | `bookings.php`, `payments.php` | 5: daftar+filter, detail, konfirmasi, status gateway | M | JANGAN lunas tanpa bukti; refund ikut kebijakan |
| 4 Marketing | `flash-sales`, `promo-codes`, `price-alerts`, `push-notifications`, `corporate-rates`, `collections` | 5: form promo, periode/kuota, push, corporate | M | Prioritas diskon; throttle 1/jam; FCM sensitif |
| 5 Finance | `sales-report`, `accounting`, `loyalty-settings` | 4: filter, expense/COGS, ambang loyalty | M | Operasional saja, bukan laporan pajak |
| 6 Reseller | `resellers`, `reseller-topups`, `reseller-pricing` | 4: daftar, topup, harga per tur | S | Cek bukti; harga vs flash sale |
| 7 Konten | `posts/post-edit`, `reviews`, `faq+category`, `nav-menus`, `hero-slides` | 6: editor, moderasi, FAQ, menu, hero | M | Status/draf + cache + bahasa |
| 8 Settings | `wa-settings/wa-test`, `chat-settings`, `email-log`, `currency-settings`, `hotel/flight-api-settings` | 5: form + tes + log | M | Catat nilai lama; JANGAN ubah key produksi |
| 9 Tim | `dashboard`, `analytics`, `admins` | 3: dashboard, form admin, grant | S | Superadmin saja; hapus akun uji |
| 10 Darurat | `logs/`, nilai bawaan tiap bab | 0 | S | Info wajib lapor teknis |

Total screenshot baru ±45 PNG (7 Bab-1 sudah ada). Tiap bab = 1 commit + deploy Royal + utama.

---

## RECAP ISI MD (jawaban "recap lagi isi md")

### TULISDOCS.md — Planning Panduan (file ini)

- **Tujuan**: pemilik travel awam bisa ganti branding sendiri (nama, logo, kontak, sosmed) tanpa IT; tiap langkah ada screenshot CDP asli + nama tombol persis + contoh benar/salah; bahasa santai.
- **Keputusan**: 1 file `admin/panduan.php` (login admin, ikut git); mulai bab branding dulu; screenshot via Playwright Chromium; data Royal Dream asli.
- **Acuan**: `brand-settings.php:1-147` — semua field, batas (60/120/160 char), tipe/size file, pesan error, efek tiap ganti, kredensial demo.
- **Struktur**: Bab 0–10 SELURUH admin panel (Bab 1 SUDAH LIVE ✅; Bab 0, 2–10 terencana di §4+§10, ±45 PNG baru). Format per sub-bab: tujuan → langkah → screenshot → contoh → cek hasil → kalau error.
- **Screenshot**: 7 PNG di `assets/img/panduan/` via `panduan-brand-screenshots.spec.ts`. Mitigasi flaky: data final, bukan timestamp.
- **ACC iterasi 1**: panduan dibuka, 7 gambar tampil, label persis, batas tertulis, ada cek hasil + tabel error, lint bersih — SEMUA SUDAH CENTANG (commit `2f639dd`, live Royal + utama).
- **Bab 9 (baru)**: panduan WAJIB multilingual id/en/zh — bungkus `t()`, seed via Meta + review, audit 0 missing, E2E `panduan-i18n`, screenshot tetap 1 set + caption.

### MULTILINGUAL.md — Pola i18n situs (acuan teknis Bab 9)

- **Arsitektur**: `getSupportedLanguages()` (registry) → `getCurrentLang()` (session/cookie/browser) → `t()` (tabel `translations`) → `tContent()` (kolom `{field}_{lang}`) → `I18N` JS → formatter locale-aware.
- **Tambah bahasa**: daftar di registry → seed via `regenerate-en.php` (salin-ganti kode) → kolom DB bila perlu → selesai (switcher + `?lang=` otomatis).
- **Aturan kode**: PHP `t('...')`, JS `I18N.t`, AJAX pakai `t()`, render DB via `tContent()`, tanggal/angka via formatter, lalu `audit-translations.php` wajib 0 missing.
- **Relevansi ke panduan**: panduan ikut aturan yang sama — tak ada mekanisme khusus; yang belum dipenuhi hanya pembungkusan `t()` + seed en/zh.


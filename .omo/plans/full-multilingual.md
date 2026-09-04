# Plan: full-multilingual

> Created: 2026-09-04 17:47:54
> **Status**: Draft

## Objective

Jadikan seluruh codebase tourandtravel 100% multilingual EN/ID hingga detail terkecil, dengan arsitektur yang memungkinkan penambahan bahasa baru secara dinamis: (a) semua string UI hardcoded dibungkus t(), (b) string JS via sistem i18n, (c) pesan AJAX via t(), (d) konten DB per-bahasa dengan fallback, (e) format tanggal/angka/mata uang locale-aware, (f) bahasa switcher preserve params + html lang dinamis + Accept-Language fallback.

## Scope

**In Scope:**
- Semua halaman publik (root *.php), includes/header-footer (kedua varian: header.php + header-klook.php, footer.php + footer-klook.php), komponen (includes/components/*)
- String di assets/js/*.js (klook.js, script.js) + inline JS di view
- Pesan AJAX (apply-promo-ajax.php, newsletter-ajax.php, ajax/currency-rates.php)
- Helper locale: formatDate(), formatNumber(), formatRupiah() locale-aware, formatCurrency() separator locale-aware
- Konten DB per-bahasa: kolom `content_language` untuk tabel layanan (hotels, flights, ferries, trains, transfers, attractions, esim) + per-language columns `_en` untuk title/description + resolusi dengan fallback
- tglIndonesia() → formatDate() locale-aware
- <html lang> dinamis, Accept-Language fallback di getCurrentLang()
- Language switcher sudah OK (preserve params) — verifikasi saja

**Out of Scope:**
- Redesign UI/UX
- Fix 13 E2E failure pre-existing (language-switch/admin-bulk-lang) — hanya dipastikan tidak bertambah
- Deploy ke produksi (terpisah)
- Email (tidak ada mail() di codebase); WA notifikasi admin (hanya ID, bukan user-facing — opsional)

## Context

- PHP vanilla, helper `t($key,$fallback,$sourceLang)` di functions.php:182-206; tabel `translations` (UNIQUE key,lang); switcher `?lang=` di includes/config.php:15-28 (set session+cookie lalu redirect, params preserved).
- getCurrentLang() = session>cookie>default 'id' (functions.php:161) — belum ada Accept-Language.
- Audit tooling sudah ada: `scripts/audit-translations.php` → `scripts/out/keys_without_t.txt` (232 kandidat hardcoded, incl. admin), `missing_keys.txt` (kosong).
- Konten DB: hanya `tours.content_language` + collections; tabel lain belum. Itineraries, FAQ items, hero_slides raw.
- E2E Playwright 314 tests; 13 failure pre-existing; global-setup wajib untuk full run.
- Produksi: stash `includes/config.php` saat git pull; jangan sentuh data tours/translations server.

## Approach

1. **Foundation dulu** (locale helpers + language registry + Accept-Language + html lang dinamis), lalu **audit tooling dipertajam** (keys_without_t.txt sebagai daftar kerja), lalu **wrap massal per-cluster file** (header/footer → index → auth pages → user pages → search/listing pages → detail pages → AJAX → JS), **translation seeding** via script (auto-translate id→en dengan kamus manual + translate-all.php pattern), **konten DB per-bahasa** (ALTER tabel layanan tambah kolom `_en`, resolusi helper `tContent($row, $field)`), lalu **E2E regression** + audit 0-missing.
2. Ekstensibilitas: bahasa didaftarkan di satu tempat (`getSupportedLanguages()` di functions.php + baris di lang switcher auto-generate); penambahan bahasa = tambah kode + seed translations + (opsional) kolom `_{lang}`.
3. Verifikasi tiap fase: `php -l` per file yang diedit, audit script per-cluster, E2E targeted per fase, full E2E di akhir.

## Tasks

| # | Task | Files | Status |
|---|------|-------|--------|
| 0 | Fase 0 — Fondasi & audit baseline | - | pending |
| 1 | Baseline: jalankan full E2E, catat failure pre-existing ke .omo/plans/baseline-e2e.txt sebagai garis dasar (HASIL: 4 failed di multilingual.spec.ts, 400/404 passed @ f9e79c9) | tests/e2e, npx playwright test | done |
| 2 | getSupportedLanguages(): registry bahasa terpusat (id,en + meta: label,flag,locale) | includes/functions.php | pending |
| 3 | getCurrentLang(): validasi kode vs registry + Accept-Language fallback | includes/functions.php | pending |
| 4 | html lang dinamis di kedua header | includes/header.php, includes/header-klook.php | pending |
| 5 | Locale helpers baru: formatDate() (menggantikan pemanggilan tglIndonesia bertahap), formatNumber() | includes/functions.php | pending |
| 6 | formatRupiah/formatCurrency separator sesuai locale bahasa aktif | includes/functions.php | pending |
| 7 | i18n untuk JS: expose window.I18N (dari PHP, semua key JS) + i18n.js helper t() sisi JS | includes/header*.php, assets/js/i18n.js | pending |
| 8 | Refactor tglIndonesia() → formatDate() internal; ganti semua pemanggil | includes/functions.php + semua pemanggil | pending |
| 9 | Baseline audit: regenerate keys_without_t.txt sebagai daftar kerja; pecah per-cluster | scripts/audit-translations.php | pending |
| 10 | Fase 1 — Chrome & navigasi | - | pending |
| 11 | Wrap sisa string header.php (dropdown nav, placeholder search) | includes/header.php | pending |
| 12 | Wrap footer.php (alamat/telepon/jam ops) | includes/footer.php | pending |
| 13 | Wrap header-klook.php + footer-klook.php (termasuk payment badges) | includes/header-klook.php, includes/footer-klook.php | pending |
| 14 | Wrap komponen: includes/components/* (tour-card dsb) | includes/components/*.php | pending |
| 15 | Verifikasi Fase 1: php -l + audit cluster + E2E nav-crawl | - | pending |
| 16 | Fase 2 — Auth & akun user | - | pending |
| 17 | Wrap login.php (8 string) | login.php | pending |
| 18 | Wrap register.php (9 string) | register.php | pending |
| 19 | Wrap profile.php (13: Ganti Password, Simpan Perubahan, dsb) | profile.php | pending |
| 20 | Wrap my-bookings.php (7: Riwayat Booking, confirm dialog, empty state) | my-bookings.php | pending |
| 21 | Wrap wishlist.php (4) | wishlist.php | pending |
| 22 | Wrap wallet.php (6: KlookCash, header tabel) | wallet.php | pending |
| 23 | Wrap referral.php (5) | referral.php | pending |
| 24 | Wrap booking-success.php (15: step labels, detail rows) | booking-success.php | pending |
| 25 | Wrap track.php (18: step labels, status, detail rows) | track.php | pending |
| 26 | Wrap review-submit.php + logout.php bila ada output | review-submit.php, logout.php | pending |
| 27 | Verifikasi Fase 2: php -l + audit + E2E local-auth, user-pages, booking-success, track | - | pending |
| 28 | Fase 3 — Listing & search | - | pending |
| 29 | Wrap index.php sisa (sr-only carousel, 'paket') | index.php | pending |
| 30 | Wrap tours.php filter arrays (durasi/harga/sort labels) + label filter | tours.php | pending |
| 31 | Wrap destinasi.php (judul dinamis "Paket Tour ke X", count, empty state) | destinasi.php | pending |
| 32 | Wrap hotels.php (placeholder, Check-in/out, Min/Max, amenity placeholder) | hotels.php | pending |
| 33 | Wrap flights.php (placeholder kota, jumlah penerbangan) | flights.php | pending |
| 34 | Wrap ferries.php (placeholder terminal, pax) | ferries.php | pending |
| 35 | Wrap trains.php + transfers.php + attractions.php + esim.php (badge/pax/alt) | trains.php, transfers.php, attractions.php, esim.php | pending |
| 36 | Wrap collection.php + faq.php sisa | collection.php, faq.php | pending |
| 37 | Verifikasi Fase 3: php -l + audit + E2E tours-filter, destinasi, hotels, flights, ferries, rental-cars | - | pending |
| 38 | Fase 4 — Detail pages | - | pending |
| 39 | Wrap tour-detail.php sisa (error paspor, alt peta) + itineraries via tContent() | tour-detail.php | pending |
| 40 | Wrap hotel-detail.php (13: Fasilitas, amenity array, Lokasi, Hotel Lain) | hotel-detail.php | pending |
| 41 | Wrap flight-detail.php (20+: error pesan, tombol, label kelas) | flight-detail.php | pending |
| 42 | Wrap rental-car-detail.php, train-detail.php, transfer-detail.php, attraction-detail.php, esim-detail.php sisa | rental-car-detail.php, train-detail.php, transfer-detail.php, attraction-detail.php, esim-detail.php | pending |
| 43 | tContent($row,$field): resolver konten DB per-bahasa + fallback | includes/functions.php | pending |
| 44 | Verifikasi Fase 4: php -l + audit + E2E tour-detail, hotel-detail, rental-car-detail | - | pending |
| 45 | Fase 5 — AJAX & JS strings | - | pending |
| 46 | Wrap apply-promo-ajax.php (10 pesan) | apply-promo-ajax.php | pending |
| 47 | Wrap newsletter-ajax.php (5) | newsletter-ajax.php | pending |
| 48 | Wrap ajax/currency-rates.php + admin/wa-ajax.php pesan | ajax/currency-rates.php, admin/wa-ajax.php | pending |
| 49 | JS: ganti string klook.js & script.js dengan I18N.t() (window.I18N dari PHP) | assets/js/klook.js, assets/js/script.js | pending |
| 50 | Inline JS: confirm() my-bookings, admin wa-settings messages → I18N | my-bookings.php, admin/wa-settings.php | pending |
| 51 | Number formatting JS: Intl.NumberFormat pakai locale dari window.I18N.locale | assets/js/klook.js, assets/js/currency.js | pending |
| 52 | Verifikasi Fase 5: audit + E2E review, smoke, manual promo/newsletter | - | pending |
| 53 | Fase 6 — Admin sisa | - | pending |
| 54 | Wrap pesan flash admin (promo-codes, faq, collections, dsb per admin_keys.txt) | admin/*.php | pending |
| 55 | Verifikasi Fase 6: php -l + E2E admin-crud, admin-settings | - | pending |
| 56 | Fase 7 — Konten DB per-bahasa | - | pending |
| 57 | Migration: kolom `content_language` untuk tabel layanan (hotels, flights, ferries, rental_cars, trains, transfers, attractions, esim, itineraries, faq_items, hero_slides) | database/migrate-content-lang.sql | pending |
| 58 | Migration: kolom `title_en`/`description_en` (+name_en dsb) untuk tabel layanan | database/migrate-content-lang.sql | pending |
| 59 | Seed terjemahan EN untuk konten layanan + itineraries + FAQ (script dari data lokal) | database/seed-content-en.php | pending |
| 60 | Integrasikan tContent() di semua render: cards + detail pages | komponen + detail pages | pending |
| 61 | Admin: input field EN di form edit layanan (bila sempat; minimal title/desc) | admin/*-edit.php | pending |
| 62 | Verifikasi Fase 7: mysql lokal run migration, render EN/ID dicek manual + E2E destinasi | - | pending |
| 63 | Fase 8 — Seeding translations & ekstensibilitas | - | pending |
| 64 | Regenerate daftar semua key t() baru → buat EN translations untuk semua key baru (pola translate-all.php) | database/translate-all.php / script baru | pending |
| 65 | Audit script: wajib 0 missing (missing_keys.txt kosong) + 0 keys_without_t (target) | scripts/audit-translations.php | pending |
| 66 | Dokumentasi cara tambah bahasa baru (registry, kolom _en pattern, seed, switcher otomatis) | docs atau komentar di functions.php / MULTILINGUAL.md | pending |
| 67 | Fase 9 — Regression & rilis lokal | - | pending |
| 68 | Full E2E run (global-setup), bandingkan vs baseline: HASIL --workers=1 → 408 tests, 308 passed, 0 failed (baseline 4 failure = multilingual.spec.ts menarget server remote; race DB saat multi-worker terdokumentasi di baseline-e2e-final.txt) | npx playwright test | done |
| 69 | Update E2E language-switch spec untuk assertion baru (html lang, format tanggal, JS i18n) | tests/e2e/language-switch.spec.ts | pending |
| 70 | Smoke manual: switch ID↔EN di semua halaman utama, cek tidak ada string terlewat | browser | pending |
| 71 | git commit per fase, push origin/main | git | pending |

## Risks & Mitigations

- **t() key collision / salah fallback**: key = string Indonesia persis; verifikasi dengan audit script per-cluster sebelum lanjut fase.
- **Regresi E2E**: banyak test assert teks ID; wrap t() dengan fallback ID yang sama persis tidak mengubah output default (lang=id) — jalankan E2E per fase.
- **Perf**: t() mem-query DB per key miss; tambah preload cache per-request (static, ambil semua baris lang aktif sekaligus).
- **DB schema drift produksi**: migration idempotent (ADD COLUMN IF NOT EXISTS pattern/procedure); server config.php di-stash saat pull.
- **Key JS**: pastikan window.I18N di-inject sebelum klook.js/script.js (urutan di header/footer).
- **Accept-Language**: hanya fallback ketika tidak ada session/cookie — jangan override pilihan eksplisit.

## Verification

- [ ] All tasks completed
- [ ] Tests pass
- [ ] Edge cases handled
- [ ] Audit 0 missing keys, 0 hardcoded user-visible strings (keys_without_t.txt kosong)
- [ ] Full E2E: tidak ada regresi baru vs baseline 4 failure (multilingual.spec.ts) — lihat .omo/plans/baseline-e2e.txt

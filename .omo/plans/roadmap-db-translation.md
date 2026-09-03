# Roadmap: Remove MyMemory API — Full DB Translation (100% coverage)

> Status: Draft
> Git: `26535dc`

## Problem

Fungsi `translateMyMemory()` di `includes/functions.php` memanggil API eksternal (MyMemory) setiap kali terjemahan tidak ditemukan di DB. Ini:
- Lambat (HTTP request 10s timeout)
- Tidak stabil (API rate limit, down)
- Boros (setiap request user bisa trigger translate)
- Source key jadi target terjemahan (contoh: "Nama Lengkap" dikirim ke API, tidak aman)

## Audit Saat Ini (masalah "setengah2")

```
Unique t() keys di codebase : 329
DB translations en          : 158  ← hanya 48% tercover!
DB translations id          :  66
Missing en                  : 265 keys bocor tetap Indonesia
id keys tanpa en            :  17
```

**Yang bocor (ditemukan via curl lang=en):**
- Navbar: `Tour`, `Hotel`, `Pesawat`, `Ferry`, `Rental`, `Kereta`, `Atraksi`, `Transfer`, `eSIM`, `Beranda`
- Komponen: `Destinasi Populer`, `Rekomendasi Paket Tour`, `Paket Tour`, `Kategori Wisata`
- Halaman fitur baru (Fase 7-9): attractions/transfers/trains/esim/faq/wallet/referral — hampir semua teks tidak di-DB

## Target

- **100% key** `t()` di codebase punya entri `translations` untuk `id` DAN `en`
- Navbar & teks hardcoded lain di-wraplah pakai `t()` + di-DB-kan
- Hapus total `translateMyMemory()` / `translateAndCache()`
- Verifikasi: `lang=en` tidak ada satu pun kata Indonesia yang bocor

## Langkah

### 1. [x] Commit & push (`26535dc`)

### 2. Audit script: `scripts/audit-translations.php`

Scan:
- Semua `t('...')` literal di `*.php` + `includes/` + `admin/`
- Teks hardcoded berbahasa Indonesia (regex huruf + spasi) di file view
- Bandingkan dengan DB → output missing keys per file

### 3. Fix hardcoded → t() wrapping

File yang punya teks Indonesia langsung (bukan t()):
- `includes/header-klook.php` — navbar, search placeholder, dropdown labels
- `includes/footer-klook.php` — Metode Pembayaran, partner teks
- `includes/components/*.php` — Best Seller, badge labels
- `index.php`, `flights.php`, `hotels.php`, `ferries.php` (baris baru Traveloka/Agoda/Easybook: "Pesawat/Kereta/Ferry/Rental" tabs, "Hemat", "e-ticket", "Termurah/Tercepat/Terpopuler")
- `attractions.php`, `transfers.php`, `trains.php`, `esim.php`, `faq.php`, `wallet.php`, `referral.php`, `collection.php`
- `admin/*.php` — labels admin

### 4. Generate terjemahan: `scripts/translate-migrate.php`

- Ambil semua key unik dari step 2
- `id` value = key itu sendiri (karena key sudah bahasa Indonesia)
- `en` value = terjemahan manual/crowdsource yang di-embed dalam script
  (array `'Beranda' => 'Home', 'Tour' => 'Tours', ...`)
- Insert via `INSERT ... ON DUPLICATE KEY UPDATE`
- Report: berapa inserted/updated/skip

### 5. Hapus API MyMemory

- `translateMyMemory()` — hapus
- `translateAndCache()` — hapus
- Callers: cek via `grep -rn "translateMyMemory\|translateAndCache"`

### 6. E2E — pastikan SEMUA bahasa terganti

Baru (khusus verifikasi kelengkapan):
- `tests/e2e/translation-completeness.spec.ts`:
  - Test: `lang=en` di halaman kunci (index, tours, hotels, flights, ferries,
    trains, esim, faq, wallet, referral, attractions, transfers, collection)
  - Assert: body TIDAK mengandung kata kunci Indonesia:
    `Beranda|Pesawat|Kereta|Atraksi|Transfer|Destinasi Populer|Rekomendasi
    Paket Tour|Kategori Wisata|Batal Gratis|Konfirmasi Instan|Lihat Semua|Cari`
  - Assert: mengandung setara EN (`Home|Flights|Trains|View All|Search...`)

Lama (regresi):
- `language-switch.spec.ts` (8), `language-switcher.spec.ts` (2),
  `tour-content-lang-switch.spec.ts` (3), `multilingual.spec.ts`,
  `tour-content-translation.spec.ts`

### 7. Full suite + commit + push

```bash
npx playwright test --workers=1   # ≥ 382 passed
git add -A && git commit -m "feat: 100% DB translation tanpa MyMemory API"
git push
```

## Files changed

| File | Perubahan |
|---|---|
| `includes/functions.php` | Hapus `translateMyMemory()`, `translateAndCache()` |
| `includes/header-klook.php` | t() wrapping navbar/placeholder |
| `includes/footer-klook.php` | t() wrapping |
| `includes/components/*.php` | t() wrapping badge/labels |
| `index.php`, `flights.php`, `hotels.php`, `ferries.php` | t() wrapping teks baru |
| `attractions.php`, `transfers.php`, `trains.php`, `esim.php`, `faq.php`, `wallet.php`, `referral.php`, `collection.php` | t() wrapping |
| `admin/*.php` | t() wrapping (opsional, admin bisa id-only) |
| `scripts/audit-translations.php` | Baru |
| `scripts/translate-migrate.php` | Baru |
| `tests/e2e/translation-completeness.spec.ts` | Baru |

## Verification (checklist)

- [ ] `php scripts/audit-translations.php` → 0 missing en key
- [ ] `php scripts/translate-migrate.php` → insert ~265 baris en
- [ ] `php -l includes/functions.php` → OK
- [ ] Semua halaman HTTP 200, tidak ada `Call to undefined function`
- [ ] `translation-completeness.spec.ts` → hijau (0 kata Indonesia bocor)
- [ ] 8+2+3 language spec lama → hijau
- [ ] Full suite ≥ 382 passed
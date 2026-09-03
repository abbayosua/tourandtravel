# Roadmap: Admin Multilingual — English + Indonesia + Extensible

> Status: Draft
> Git: `49e263f` (100% DB translation public pages selesai)

## Problem

Admin panel saat ini **100% hardcoded Indonesia** — 24 `pageTitle` literal, 40+ label CRUD,
sidebar, tombol, tabel header, flash messages, dashboard KPI. Tidak ada satupun yang
pakai `t()`. Admin tidak bisa beralih ke English.

## Tujuan

- Admin bisa pakai **English** atau **Indonesia** (via lang switch di navbar admin)
- Admin bisa nambah bahasa lain nanti (tinggal seed `translations` + set `lang` param)
- Minimal impact: hanya `t()` wrapping, tanpa ubah logika/struktur

## Approach

### 1. Lang switch di admin

Admin login page (`admin/login.php`) dan admin header (`admin/includes/admin-header.php`)
sudah punya akses ke session. Tambah lang toggle di admin sidebar/footer:

- `admin/includes/admin-header.php` — tambah dropdown Language di sidebar atau navbar atas
- `admin/includes/admin-footer.php` — persist `$_GET['lang']` di setiap link admin

### 2. Audit admin keys

Scan semua text hardcoded di `admin/*.php`:

```
Grep patterns:
- pageTitle = '...'           → 24 hits
- match(...) msg string       → 10+ hits (Berhasil ditambahkan, dll)
- label/form teks             → 40+ hits
- sidebar nav labels          → 20+ hits
- tabel header, tombol        → 30+ hits
- flash messages, errors      → 15+ hits
```

### 3. t() wrapping + seed

- Wrap semua literal → `t('...')`
- Seed EN values via `scripts/translate-migrate-admin.php` (atau tambah mapping di `translate-migrate.php`)
- Target: **0 hardcoded Indonesia** di admin saat `lang=en`

### 4. Files berubah

| File | Perubahan |
|---|---|
| `admin/includes/admin-header.php` | Sidebar nav labels → `t()`, tambah lang dropdown |
| `admin/includes/admin-footer.php` | Lang persist |
| `admin/login.php` | Form labels → `t()` |
| `admin/dashboard.php` | KPI labels, stat cards → `t()` |
| `admin/bookings.php` | Filters, table headers, status badges → `t()` |
| `admin/tours.php` | Bulk lang, table, tombol → `t()` |
| `admin/hotels.php`, `admin/flights.php`, `admin/ferries.php`, `admin/rental-cars.php` | Table headers → `t()` |
| `admin/attractions.php`, `admin/transfers.php`, `admin/trains.php`, `admin/esim.php` | Table headers → `t()` |
| `admin/faq.php`, `admin/faq-category.php`, `admin/promo-codes.php`, `admin/collections.php` | Table headers → `t()` |
| `admin/*-edit.php` (CRUD forms) | Form labels, buttons, validation → `t()` |
| `admin/loyalty-settings.php`, `admin/currency-settings.php`, `admin/wa-settings.php` | Labels → `t()` |
| `scripts/translate-migrate-admin.php` | Baru: seed mapping admin keys |

### 5. E2E

- Update `tests/e2e/translation-completeness.spec.ts` — tambah halaman admin (login, dashboard, tours, hotels, bookings, settings)
- `tests/e2e/admin-guard.spec.ts` — cek masih hijau
- `tests/e2e/admin-crud.spec.ts` — cek masih hijau
- `tests/e2e/admin-dashboard.spec.ts` — cek masih hijau

### 6. Ekstensi bahasa lain (masa depan)

- Tinggal insert baris baru ke `translations` dengan `lang = 'zh'` / `'ja'` / `'ko'`
- `t()` function sudah support `getCurrentLang()` dari session/cookie
- Admin lang dropdown tinggal tambah opsi

## Langkah Eksekusi

1. Audit admin keys: `grep -rn "pageTitle\s*='\|'[A-Z][a-z]" admin/*.php | wc -l`
2. `t()` wrapping admin sidebar + navbar lang dropdown
3. `t()` wrapping dashboard + booking + CRUD list
4. `t()` wrapping semua form edit
5. `t()` wrapping settings pages
6. Seed mapping admin keys ke DB
7. Update `translation-completeness.spec.ts` — include admin pages
8. `npx playwright test tests/e2e/admin-*.spec.ts tests/e2e/translation-completeness.spec.ts --workers=1`
9. Full suite + commit + push

## Verification

- [ ] Admin login page: `lang=en` → labels English
- [ ] Admin dashboard: KPI, sidebar, stat cards English
- [ ] Admin CRUD list: table headers, filter, tombol English
- [ ] Admin CRUD form: labels, validation, save button English
- [ ] Admin booking: status filter, dropdown, table English
- [ ] Admin settings: currency, WA, loyalty labels English
- [ ] Lang switch di admin sidebar: toggle id/en → semua berubah
- [ ] `translation-completeness.spec.ts` include 5+ admin pages, 0 bocor
- [ ] Full suite ≥ 383 passed
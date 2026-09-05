# Plan: homepage-templates

> Created: 2026-09-05 09:32:09
> **Status**: Ready

## Objective

Implementasi HOMEPAGE-TEMPLATES.md: admin pilih fokus website (Tour/Hotel/Flight) via dropdown (tabel settings), homepage tersusun modular per preset — Tour = Klook-style regresi nol, Hotel = Agoda-style (search hotel dominan, deal hotel, per kota, trust, cross-sell), Flight = Tiket.com-style (search penerbangan one-way/round-trip, promo, rute murah, cross-sell); CRUD hero slides per fokus dengan fallback; semua string baru multilingual (t()/I18N); test E2E untuk 3 preset + CRUD hero; smoke manual id/en.

## Scope

**In Scope:**
- Setting `site_focus` (tour/hotel/flight) via tabel settings + halaman admin "Tampilan Homepage"
- Refaktor index.php → pool section modular + include per preset
- 3 preset render: Tour (status quo), Hotel (Agoda-style), Flight (Tiket.com-style)
- CRUD admin hero_slides (tambah kolom `focus`) + fallback saat kosong
- Komponen baru bila perlu: hotel-card, deal-hotel section, flight search hero, rute populer, cross-sell
- String baru 100% via t()/I18N + seed EN (regenerate-en.php)
- E2E: homepage-templates.spec.ts (3 preset), hero-slides.spec.ts (CRUD), regresi penuh --workers=1

**Out of Scope:**
- Perubahan halaman selain homepage; nav/footer/tagline dinamis
- Drag-and-drop editor; fokus lain (ferries/trains/dst.) — cukup arsitektur siap ekstensi
- Modul produk baru

## Context

- Tabel `settings` (key-value) ada + pola admin/currency-settings.php; `hero_slides` ada (image_url, title, subtitle, cta_text, cta_link, sort_order, is_active) belum dipakai.
- index.php: 17 section Klook hardcoded, double-header (header-klook.php:64 + header.php:227) — dirapikan.
- i18n: t()/getSupportedLanguages()/I18N — semua string baru wajib lewat ini; audit scripts/audit-translations.php missing=0.
- E2E: Playwright, SELALU --workers=1 (race DB). Baseline: 408 tests, 0 failed.

## Approach

Fase 0 baseline & infrastruktur (setting helper + migration hero_slides.focus) → Fase 1 admin (halaman fokus + CRUD hero) → Fase 2 refaktor index.php modular (Tour preset = pindahan section, regresi nol) → Fase 3 preset Hotel (komponen Agoda-style) → Fase 4 preset Flight (komponen Tiket-style) → Fase 5 i18n seeding + audit → Fase 6 E2E baru + regresi penuh → Fase 7 smoke manual id/en + commit.

## Tasks

| # | Task | Files | Status |
|---|------|-------|--------|
| 0 | Fase 0 — Baseline & infrastruktur | - | pending |
| 1 | Baseline E2E penuh (--workers=1) → catat hasil ke .omo/plans/homepage-baseline.txt (HASIL: 408 tests, 308 passed, 4 failed = multilingual.spec.ts remote; @ f6ecd15) | npx playwright test | done |
| 2 | Helper setting: getSetting($key,$default)/setSetting($key,$value) di functions.php (pola default_currency existing) | includes/functions.php | pending |
| 3 | Migration idempotent: kolom `focus` VARCHAR(10) DEFAULT 'all' + index di hero_slides; seed 3 slide tour default | database/migrate-hero-focus.sql | pending |
| 4 | Jalankan migration lokal + verifikasi SHOW COLUMNS & re-run idempotent | mysql | pending |
| 5 | Fase 1 — Admin: halaman fokus | - | pending |
| 6 | admin/appearance.php: dropdown site_focus (tour/hotel/flight + deskripsi), simpan ke settings, flash ?msg=updated, guard cekLogin() | admin/appearance.php | pending |
| 7 | Tambah link sidebar "Tampilan Homepage" di admin-header | admin/includes/admin-header.php | pending |
| 8 | Verifikasi: pilih & simpan tiap opsi, nilai terbaca via getSetting; php -l | - | pending |
| 9 | Fase 1b — Admin: CRUD hero slides | - | pending |
| 10 | admin/hero-slides.php: tabel daftar (thumbnail, judul, fokus, urutan, status) + aksi | admin/hero-slides.php | pending |
| 11 | admin/hero-slide-edit.php: form tambah/edit (upload gambar reuse konvensi, judul, subtitle, CTA text/link, fokus dropdown, urutan, aktif) + hapus dengan konfirmasi | admin/hero-slide-edit.php | pending |
| 12 | String admin baru dibungkus t(); php -l semua file admin baru | - | pending |
| 13 | Verifikasi CRUD via browser (add/edit/sort/deactivate/delete) + E2E admin-guard tidak rusak | - | pending |
| 14 | Fase 2 — index.php modular (preservasi Tour) | - | pending |
| 15 | Refaktor index.php: baca getSetting('site_focus','tour'), pisahkan section Klook ke includes/homepage/tour-*.php (pindahan persis), struktur switch per preset; rapikan double-header | index.php, includes/homepage/ | pending |
| 16 | Loader hero per fokus: query hero_slides (focus = site_focus OR 'all', is_active, order sort_order) + fallback gradient bila kosong — gantikan array hardcoded | includes/homepage/hero.php | pending |
| 17 | Verifikasi regresi: homepage Tour identik (visual + curl diff struktur section), E2E smoke-public/nav-crawl hijau | - | pending |
| 18 | Fase 3 — Preset Hotel (Agoda-style) | - | pending |
| 19 | Komponen hotel-card.php (nama, bintang, kota, harga/malam, badge Best Seller/Batal Gratis/Konfirmasi Instan) — konsisten design tokens | includes/components/hotel-card.php | pending |
| 20 | includes/homepage/hotel-hero.php: search bar hotel dominan (kota, check-in/out, tamu) submit ke hotels.php | includes/homepage/hotel-hero.php | pending |
| 21 | includes/homepage/hotel-deals.php: grid deal hotel terbaik (query hotels is_active, harga termurah dulu) + empty state | includes/homepage/hotel-deals.php | pending |
| 22 | includes/homepage/hotel-cities.php: kartu kota populer + "harga mulai dari" (GROUP BY city MIN price) | includes/homepage/hotel-cities.php | pending |
| 23 | includes/homepage/trust.php + cross-sell.php (reusable: trust badges + baris kartu kecil vertikal lain) | includes/homepage/ | pending |
| 24 | Susun preset hotel di index.php switch; semua string via t() | index.php | pending |
| 25 | Verifikasi visual id/en + empty state (matikan hotels aktif sementara) + link 200 | - | pending |
| 26 | Fase 4 — Preset Flight (Tiket.com-style) | - | pending |
| 27 | includes/homepage/flight-hero.php: search form penerbangan (one-way/round-trip radio, asal-tujuan autocomplete existing, tanggal, penumpang) submit ke flights.php; gradasi warna khas | includes/homepage/flight-hero.php | pending |
| 28 | includes/homepage/flight-promo.php: banner promo + rute populer dengan harga mulai (dari flight_schedules) + empty state | includes/homepage/ | pending |
| 29 | Susun preset flight di index.php (trust + cross-sell reuse dari fase 3) | index.php | pending |
| 30 | Verifikasi visual id/en + empty state + link 200 | - | pending |
| 31 | Fase 5 — i18n & audit | - | pending |
| 32 | Seed terjemahan EN semua string baru via regenerate-en.php (perluas kamus manual untuk key penting) + update getJsI18nKeys bila ada string JS baru | database/regenerate-en.php | pending |
| 33 | Audit: php scripts/audit-translations.php wajib Missing=0 & tanpa kandidat hardcoded baru | scripts/audit-translations.php | pending |
| 34 | Verifikasi format angka/tanggal & currency switcher bekerja di ketiga preset | - | pending |
| 35 | Fase 6 — E2E | - | pending |
| 36 | tests/e2e/homepage-templates.spec.ts: per preset — render kunci (hero style, section urutan), switch fokus via settings DB helper, idempotent, empty state, i18n en/id, link section 200 | tests/e2e/ | pending |
| 37 | tests/e2e/hero-slides.spec.ts: admin CRUD (login admin, add/edit/sort/deactivate/delete) + tampil di publik per fokus + fallback | tests/e2e/ | pending |
| 38 | Jalankan E2E baru sampai hijau; perbaiki defect yang ditemukan (loop) | - | pending |
| 39 | Full regresi --workers=1: HASIL 322 passed, 0 failed (308 baseline + 14 baru) ✓ | npx playwright test | done |
| 40 | Fase 7 — Smoke & rilis | - | pending |
| 41 | Smoke manual browser: 3 preset × (id, en) — navigasi, switch bahasa, currency, form search submit benar; catat di .omo/plans/homepage-smoke.md | - | pending |
| 42 | Update plan status + commit per fase (feat: homepage phase N - ...) & push origin/main; git status bersih | git | pending |

## Risks & Mitigations

- **Regresi homepage Tour**: preset Tour = pindahan section persis (bukan tulis ulang) + E2E existing sebagai pagar.
- **Race DB E2E**: selalu --workers=1; E2E switch fokus via DB langsung dengan cleanup.
- **Bocor string ID di EN**: semua string baru t(); audit wajib lulus sebelum fase selesai.
- **hero_slides kosong di produksi**: fallback gradient + headline; slide seed diberikan.
- **Komponen kartu baru melenceng desain**: ikuti design-tokens.css + pola klook card existing.
- **Double-header index**: dirapikan di fase 2; diverifikasi visual & E2E nav-crawl.

## Verification

- [ ] All tasks completed
- [ ] Tests pass
- [ ] Edge cases handled
- [ ] Audit translations missing=0
- [ ] Full E2E --workers=1 tanpa failure baru
- [ ] Smoke 3 preset × id/en tercatat

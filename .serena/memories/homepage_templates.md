# Homepage Templates (site focus) — SELESAI

## Arsitektur
- Setting `site_focus` (tour|hotel|flight) via tabel settings; helper getSetting/setSetting di functions.php.
- admin/appearance.php: dropdown fokus; admin/hero-slides.php + hero-slide-edit.php: CRUD slide per fokus (kolom focus di hero_slides, migration idempotent database/migrate-hero-focus.sql).
- index.php: switch $siteFocus → include modular di includes/homepage/ (17 section):
  tour-* (7, pindahan persis Klook), hotel-hero/deals/cities, flight-hero/promo, trust, testimonials, cross-sell, cta-banner.
- Komponen: hero-loader.php (getHeroSlides per fokus + fallback), hotel-card.php (renderHotelCard).
- PRD: HOMEPAGE-TEMPLATES.md. Regresi nol preset tour.

## Verifikasi
- E2E baru: homepage-templates.spec.ts (14) + hero-slides.spec.ts (8) — semua lulus 2x beruntun.
- Full regresi --workers=1: 322 passed, 0 failed (308 baseline + 14 baru).
- Audit translations: missing 0; kamus regenerate-en.php diperluas ~70 key.
- Smoke 3 preset × id/en: ALL_SMOKE_OK (render, submit form, currency switcher).

## Commit
4 commit `feat/docs: homepage phase 1..4` (c95c68f..83c7de8) di origin/main.

## Catatan deploy produksi
- Jalankan database/migrate-hero-focus.sql di server (idempotent) + php database/regenerate-en.php.
- Default site_focus = tour → produksi tak berubah sampai admin memilih fokus lain.
- Slide hero sekarang dari DB (hero_slides) — kelola via admin; fallback aman bila kosong.

## Follow-up potensial
- Fokus lain (ferries/trains/dst.) tinggal ikuti pola preset (switch + section + kamus EN).
- Input field EN untuk slide title/subtitle bila konten slide perlu bilingual.

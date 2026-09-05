# Plan: prd-20260906-roadmap

> Created: 2026-09-06 01:40:04
> **Status**: Ready

## Objective

Implementasi seluruh PRD-20260906-0117.md: I-1 Payment Gateway Midtrans Snap (backend+admin), I-2 Email transaksional (abstraksi sendEmail, log, 5 template, admin resend), I-3 Reset password, I-4 SEO dasar (sitemap/robots/meta/OG/JSON-LD/canonical), I-5 Blog, I-6 Notifikasi user, I-7 Analytics dashboard, I-8 Review kaya, I-9 Kupon & referral upgrade, I-10 UI/UX polish (dark mode, PWA, perf, editor EN, recently viewed, a11y) — semuanya dengan E2E Playwright + unit test (runner sederhana) untuk logika inti, tetap PHP vanilla + Bootstrap, multilingual wajib di semua string baru.

## Scope

**In Scope:** 10 inisiatif PRD (I-1..I-10) + infra unit test + E2E per fitur + regresi penuh.
**Out of Scope:** ganti framework, CMS blog penuh, redesign visual global, vertikal produk baru.

## Context

- Baseline regresi: 322 passed / 0 failed (`--workers=1` — WAJIB).
- Belum ada unit test framework → buat runner sederhana `tests/unit/run.php` (assert helper, tanpa composer).
- Midtrans tanpa composer: Snap via curl POST → redirect URL; webhook verifikasi `sha512(order_id+status_code+gross_amount+serverKey)`; sandbox toggle di settings.
- Email: driver API (resend-style) atau SMTP-lite via curl; tabel `email_log`; pola setting = wa-config/settings.
- Booking 5 tabel vertikal; payment utama = bookings (tour) dulu, tabel lain menyusul via helper generik.
- i18n wajib: semua string baru via t()/tContent/I18N; audit missing=0 tiap fase.

## Approach

Per inisiatif PRD (urutan PRD: I-1→I-2→I-3→I-4→I-5→I-6→I-7→I-8→I-9→I-10), setiap inisiatif: migration → implementasi → unit test → E2E → i18n audit → commit. Fase 0 = infra (unit test runner + baseline). Regresi penuh di akhir + per inisiatif besar.

## Tasks

| # | Task | Files | Status |
|---|------|-------|--------|
| **FASE 0 — Infrastruktur test & baseline** | | | |
| 0.1 | Buat unit test runner sederhana (tanpa composer): assert helper, bootstrap config+db, output lulus/gagal per kasus | tests/unit/run.php, tests/unit/README.md | pending |
| 0.2 | Unit test contoh (getSetting/setSetting, formatRupiah EN/ID, formatDate) sebagai validasi runner | tests/unit/*Test.php | pending |
| 0.3 | Jalankan runner + jalankan E2E smoke sebagai baseline; catat | tests/unit/run.php, .omo/plans/prd-baseline.txt | pending |
| 0.4 | Commit fase 0 (`feat: prd phase 0 - unit test infra`) | git | pending |
| **FASE 1 — I-1 Payment Gateway (Midtrans Snap)** | | | |
| 1.1 | Migration: tabel `payments` (id, booking_type, booking_id, order_id uniq, gross_amount, status, payment_type, raw_payload JSON, created_at, paid_at) — idempotent | database/migrate-payments.sql | pending |
| 1.2 | Jalankan migration lokal + verifikasi SHOW COLUMNS + re-run idempotent; update schema-klook.sql | mysql | pending |
| 1.3 | Unit test: verifySignature() (sha512 benar/salah/tampered), idempotensi handler (panggil 2x), status mapping (capture/settlement=paid, expire=expired, deny=failed) | tests/unit/MidtransTest.php | pending |
| 1.4 | includes/payments.php: createSnapTransaction($bookingType,$bookingId) via curl ke Midtrans Snap API (sandbox/prod dari settings), verifySignature(), handleNotification() idempotent, getPayments | includes/payments.php | pending |
| 1.5 | booking-success.php: tombol "Bayar Sekarang" bila status pending & payment aktif; halaman pending menunggu (poll status via ajax) | booking-success.php | pending |
| 1.6 | ajax/payment-status.php: cek status by order_id (dipakai polling) | ajax/payment-status.php | pending |
| 1.7 | webhook-midtrans.php (publik, tanpa session): baca notif, verifikasi signature, update payments + bookings status, idempotent, log payload | webhook-midtrans.php | pending |
| 1.8 | admin/payments.php: daftar pembayaran (filter status), tombol "Mark as expired"/lihat raw payload; admin/payments-settings? gabung di settings (midtrans_server_key, midtrans_client_key, midtrans_env=sandbox|production) | admin/payments.php | pending |
| 1.9 | i18n: semua string baru t() + seed EN via regenerate (perluas kamus); audit missing=0 | database/regenerate-en.php | pending |
| 1.10 | E2E payments.spec.ts: buat booking (tour guest flow) → tombol bayar ada; endpoint webhook: signature valid+status capture → bookings status paid & payments row; duplikat → tetap 1 row; signature salah → 403; expire → expired; admin list render; sandbox toggle tersimpan | tests/e2e/payments.spec.ts | pending |
| 1.11 | Unit test lulus + E2E hijau (loop perbaikan) + full regresi cepat (smoke+accounting) | npx playwright test | pending |
| 1.12 | Commit fase 1 (`feat: prd phase 1 - midtrans payment`) | git | pending |
| **FASE 2 — I-2 Email transaksional** | | | |
| 2.1 | Migration: tabel `email_log` (id, to_email, subject, body_hash, status, error, retry_count, event, created_at) — idempotent; settings: email_driver, email_api_key, email_from | database/migrate-email.sql | pending |
| 2.2 | includes/email.php: sendEmail($to,$subject,$html,$opts) — driver 'log'(default dev) & 'api' (curl POST), selalu tulis email_log, gagal tidak throw | includes/email.php | pending |
| 2.3 | Unit test: driver log menulis email_log; driver api memanggil curl (stub via setting driver=log); retry tidak duplikat log | tests/unit/EmailTest.php | pending |
| 2.4 | Template: includes/email-templates/ (booking-created, booking-status, invoice, reset-password, welcome) — HTML brand sederhana + fungsi render per template bilingual | includes/email-templates/*.php | pending |
| 2.5 | Wire event: booking dibuat (tour dulu), status berubah (webhook payment + admin bookings), invoice saat paid | booking-success.php, webhook-midtrans.php, admin/bookings.php | pending |
| 2.6 | admin/email-log.php: tabel log + filter status + tombol resend (panggil sendEmail ulang) + link sidebar | admin/email-log.php, admin/includes/admin-header.php | pending |
| 2.7 | i18n audit missing=0 | scripts/audit-translations.php | pending |
| 2.8 | E2E email.spec.ts: booking dibuat → email_log baris event booking_created; webhook paid → email_log booking_status; resend admin duplikat tidak error; template EN/ID sesuai lang user | tests/e2e/email.spec.ts | pending |
| 2.9 | Unit + E2E hijau; commit fase 2 (`feat: prd phase 2 - transactional email`) | git | pending |
| **FASE 3 — I-3 Reset password** | | | |
| 3.1 | Migration: tabel `password_resets` (email, token_hash, expires_at, used_at) — idempotent | database/migrate-password-resets.sql | pending |
| 3.2 | Unit test: token hash unik, expiry 1 jam, sekali pakai, rate limit 1x/menit | tests/unit/PasswordResetTest.php | pending |
| 3.3 | forgot-password.php: form email → rate limit → token → sendEmail template reset | forgot-password.php | pending |
| 3.4 | reset-password.php: validasi token (hash, belum expired, belum used) → form password baru → update users → token used | reset-password.php | pending |
| 3.5 | login.php: link "Lupa password?"; i18n semua string | login.php | pending |
| 3.6 | E2E password-reset.spec.ts: minta token (baca DB) → set password baru → login sukses; token kedua kali ditolak; token expired ditolak; rate limit menolak spam | tests/e2e/password-reset.spec.ts | pending |
| 3.7 | Commit fase 3 (`feat: prd phase 3 - password reset`) | git | pending |
| **FASE 4 — I-4 SEO dasar** | | | |
| 4.1 | includes/seo.php: renderMeta($pageTitle,$desc,$ogImage,$canonical) + renderJsonLd(array) helper | includes/seo.php | pending |
| 4.2 | header-klook.php & header.php: pakai renderMeta (meta description dinamis dari $metaDesc default per halaman, OG tags, canonical) | includes/header*.php | pending |
| 4.3 | JSON-LD: index (Organization+WebSite), tour-detail (TouristTrip/Product), hotel-detail (Hotel), blog-detail (Article) | detail pages | pending |
| 4.4 | sitemap.php: XML dinamis (index, semua tours/hotels/attractions/transfers/trains/esim aktif, blog, statis) + robots.txt | sitemap.php, robots.txt | pending |
| 4.5 | E2E seo.spec.ts: meta description ada di 3 halaman kunci; JSON-LD valid di tour-detail & hotel-detail; sitemap.xml content-type & berisi URL tours; robots.txt ada | tests/e2e/seo.spec.ts | pending |
| 4.6 | i18n (meta desc bilingual via tContent) + audit; commit fase 4 (`feat: prd phase 4 - seo basics`) | git | pending |
| **FASE 5 — I-5 Blog** | | | |
| 5.1 | Migration: tabel `posts` (id, title, slug uniq, excerpt, body, cover_image, category, tags, status enum draft/published, published_at, content_language, title_en, excerpt_en, body_en, timestamps) — idempotent + seed 3 artikel | database/migrate-blog.sql | pending |
| 5.2 | admin/posts.php + admin/post-edit.php: CRUD lengkap (pola hero-slides: upload cover, slug otomatis, status draft/published) | admin/ | pending |
| 5.3 | blog.php (list + pagination + filter kategori) & blog-detail.php (render body, related tours, meta/OG/JSON-LD Article, sitemap ikut) | blog.php, blog-detail.php | pending |
| 5.4 | Sidebar admin link Blog; i18n semua label; tContent() untuk konten | admin, blog pages | pending |
| 5.5 | E2E blog.spec.ts: admin CRUD (add/edit/publish/unpublish/delete), publik list render, detail render, draft tidak tampil, bilingual EN/ID, sitemap berisi post | tests/e2e/blog.spec.ts | pending |
| 5.6 | Commit fase 5 (`feat: prd phase 5 - blog module`) | git | pending |
| **FASE 6 — I-6 Notifikasi user** | | | |
| 6.1 | Migration: tabel `notifications` (id, user_id, type, title, body, link, read_at, created_at) + index user_id | database/migrate-notifications.sql | pending |
| 6.2 | includes/notifications.php: addNotification($userId,$type,$title,$body,$link) + wire event: booking dibuat, paid (webhook), status berubah (admin bookings) | includes/notifications.php, admin/bookings.php | pending |
| 6.3 | ajax/notifications.php: list unread, mark-read (satu & semua); ikon lonceng + badge di header-klook & header; halaman notifications.php | ajax/notifications.php, includes/header*.php, notifications.php | pending |
| 6.4 | Email ringkasan saat event (reuse I-2, hanya user ber-email valid) | includes/notifications.php | pending |
| 6.5 | E2E notifications.spec.ts: booking dibuat → notif user; mark-read; badge count; notif user lain tidak bocor | tests/e2e/notifications.spec.ts | pending |
| 6.6 | Commit fase 6 (`feat: prd phase 6 - user notifications`) | git | pending |
| **FASE 7 — I-7 Analytics dashboard** | | | |
| 7.1 | includes/analytics.php: query 30-hari bookings/day, revenue per vertikal, top 5 tours/hotels, funnel status, subscriber growth; filter date range (GET) | includes/analytics.php | pending |
| 7.2 | admin/analytics.php: kartu KPI + grafik SVG inline (tanpa dependensi) + tabel top items; link sidebar | admin/analytics.php, admin-header | pending |
| 7.3 | Unit test: query agregasi (booking per hari, sum revenue) dengan data fixture sementara | tests/unit/AnalyticsTest.php | pending |
| 7.4 | E2E analytics.spec.ts: dashboard render KPI & grafik; filter date mengubah angka; guard login | tests/e2e/analytics.spec.ts | pending |
| 7.5 | Commit fase 7 (`feat: prd phase 7 - analytics dashboard`) | git | pending |
| **FASE 8 — I-8 Review kaya** | | | |
| 8.1 | Migration: kolom review (reply_text, reply_at) + tabel `review_images` (review_id, path) — idempotent | database/migrate-reviews.sql | pending |
| 8.2 | review-submit.php: terima hingga 3 foto (uploadGambar konvensi); tour-detail: galeri foto review, distribusi bintang (bar 5..1), sort terbaru/tertinggi (GET sort), filter bintang | review-submit.php, tour-detail.php | pending |
| 8.3 | admin/reviews.php: daftar review + form balasan (reply_text) → tampil di tour-detail dengan gaya balasan | admin/reviews.php, tour-detail.php | pending |
| 8.4 | E2E reviews.spec.ts: submit review + 1 foto → tampil; balasan admin tampil; sort & filter bekerja; distribusi bintang render; XSS comment tetap aman (regresi) | tests/e2e/reviews.spec.ts | pending |
| 8.5 | Commit fase 8 (`feat: prd phase 8 - rich reviews`) | git | pending |
| **FASE 9 — I-9 Kupon & referral upgrade** | | | |
| 9.1 | my-coupons.php: promo aktif untuk user (dari apply history/expiry), terpakai, kadaluarsa; link dari profile | my-coupons.php | pending |
| 9.2 | referral.php upgrade: leaderboard top referrer + status reward jelas (pending/completed) | referral.php | pending |
| 9.3 | E2E coupons.spec.ts: halaman render, kupon tampil sesuai status, leaderboard terurut | tests/e2e/coupons.spec.ts | pending |
| 9.4 | Commit fase 9 (`feat: prd phase 9 - coupons & referral`) | git | pending |
| **FASE 10 — I-10 UI/UX Polish** | | | |
| 10.1 | Dark mode: variabel [data-theme=dark] di design-tokens.css + toggle di header (localStorage) + perbaikan kontras komponen utama | assets/css/design-tokens.css, header*.php | pending |
| 10.2 | PWA ringan: manifest.json (nama, ikon, theme_color), service worker sw.js (cache shell), registrasi JS; meta theme-color | manifest.json, sw.js, header | pending |
| 10.3 | Perf: preload hero image pertama, fetchpriority=high, verifikasi Lighthouse target ≥85 (manual), defer script non-kritis | includes/homepage/*, header | pending |
| 10.4 | Editor EN untuk transfers/trains/esim (title_en/description_en sudah ada di migration lama): admin form field EN + tContent di listing/detail (pola hotel-edit) | admin/transfer-edit.php, admin/train-edit.php, admin/esim-edit.php, pages | pending |
| 10.5 | Recently viewed: localStorage + baris kartu di tour-detail/hotel-detail (JS render dari history) | assets/js, detail pages | pending |
| 10.6 | A11y: skip-link, aria-label konsisten, focus-visible, kontras; jalankan audit cepat | header*.php, css | pending |
| 10.7 | E2E polish.spec.ts: dark mode persist + toggle, manifest reachable, recently viewed muncul, editor EN tersimpan & render | tests/e2e/polish.spec.ts | pending |
| 10.8 | Commit fase 10 (`feat: prd phase 10 - ux polish`) | git | pending |
| **FASE 11 — Finalisasi** | | | |
| 11.1 | Regenerate + audit i18n final: Missing en = 0; keys_without_t tanpa kandidat nyata baru | scripts/audit-translations.php | pending |
| 11.2 | Unit test: seluruh tests/unit lulus (run.php) | tests/unit/run.php | pending |
| 11.3 | Full E2E regresi --workers=1: 0 failure baru vs baseline 322+barunya | npx playwright test | pending |
| 11.4 | Smoke manual id/en untuk alur baru (bayar sandbox simulasi, reset password, blog, notif, analytics, dark mode) → catat .omo/plans/prd-smoke.md | - | pending |
| 11.5 | Commit sisa + push origin/main; git status bersih; update plan status & memori | git | pending |

## Risks & Mitigations

- **Midtrans webhook publik**: verifikasi signature wajib + tolak tanpa signature + log payload + idempoten (unique order_id); E2E khusus penolakan.
- **Email deliverability**: driver default 'log' agar dev/E2E tidak kirim sungguhan; API key via settings; dokumentasikan SPF/DKIM.
- **Unit test tanpa composer**: runner PHP murni dengan assert helper; test DB pakai data fixture yang di-cleanup.
- **Race DB E2E**: SELALU --workers=1; data fixture dibuat/di-clean per test.
- **Scope 10 inisiatif**: disiplin fase — satu inisiatif selesai (unit+E2E+audit+commit) baru lanjut; regresi cepat tiap fase.
- **i18n bocor**: audit wajib per fase sebelum commit.

## Verification

- [ ] Semua task selesai (10 inisiatif)
- [ ] tests/unit/run.php lulus penuh
- [ ] Full E2E --workers=1: 0 failure baru vs baseline
- [ ] Audit translations missing=0
- [ ] Smoke id/en tercatat (.omo/plans/prd-smoke.md)
- [ ] git status bersih, push origin/main

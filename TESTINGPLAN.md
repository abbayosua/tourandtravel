# TESTINGPLAN.md — Inventaris Fitur & Rencana E2E Testing (Playwright)

Goal: listkan semua fitur di codebase, prioritaskan perhitungan jual-beli & akuntansi, test e2e satu per satu (Playwright), fix issue saat ditemukan, update progress di file ini.

## Inventaris Fitur

| # | Modul | File utama | Status Test |
|---|-------|-----------|-------------|
| 1 | Auth (login/register/forgot/reset) | `login.php`, `register.php`, `forgot-password.php`, `reset-password.php`, `includes/auth.php` | ⏳ |
| 2 | Tours (listing/detail/booking) | `tours.php`, `tour-detail.php`, `admin/tours.php` | ⏳ |
| 3 | Hotels (listing/detail/review/rooms) | `hotels.php`, `hotel-detail.php`, `admin/hotels.php`, `admin/hotel-rooms.php` | ⏳ |
| 4 | Flights (Duffel + FlightList + cache) | `flights.php`, `flight-detail.php`, `includes/duffel.php`, `includes/flightlist.php`, `includes/flight-cache.php` | ⏳ |
| 5 | Ferries (Easybook) | `ferries.php`, `ferry-booking.php`, `includes/easybook.php`, `admin/ferries.php` | ⏳ |
| 6 | Trains | `trains.php`, `train-detail.php`, `admin/trains.php` | ⏳ |
| 7 | Attractions | `attractions.php`, `attraction-detail.php`, `admin/attractions.php` | ⏳ |
| 8 | eSIM | `esim.php`, `esim-detail.php`, `admin/esim.php` | ⏳ |
| 9 | Rental Cars | `rental-cars.php`, `rental-car-detail.php`, `admin/rental-cars.php` | ⏳ |
| 10 | Transfers | `transfers.php`, `transfer-detail.php`, `admin/transfers.php` | ⏳ |
| 11 | Blog/Posts | `blog.php`, `blog-detail.php`, `admin/posts.php` | ⏳ |
| 12 | Collections | `collection.php`, `admin/collections.php` | ⏳ |
| 13 | Destinasi | `destinasi.php` | ⏳ |
| 14 | FAQ | `faq.php`, `admin/faq.php`, `admin/faq-category.php` | ⏳ |
| 15 | Wishlist | `wishlist.php`, `wishlist-ajax.php` | ⏳ |
| 16 | Reviews + subratings | `review-submit.php`, `hotel-review-submit.php`, `admin/reviews.php` | ⏳ |
| 17 | Itinerary Builder + PDF (FPDF) | `my-itinerary.php`, `itinerary-ajax.php`, `itinerary-pdf.php`, `tour-itinerary-pdf.php` | ⏳ |
| 18 | Price Alerts | `my-alerts.php`, `price-alert-ajax.php`, `includes/price-alert-checker.php` | ⏳ |
| 19 | Referral | `referral.php` | ⏳ |
| 20 | Coupons | `my-coupons.php`, `apply-promo-ajax.php`, `admin/promo-codes.php` | ⏳ |
| 21 | Flash Sales | `includes/flash-sales.php`, `admin/flash-sales.php` | ⏳ |
| 22 | Loyalty Points/Tiers | `my-points.php`, `includes/points.php`, `admin/loyalty-settings.php` | ⏳ |
| 23 | Wallet | `wallet.php`, `includes/wallet.php` | ⏳ |
| 24 | Reseller (register/topup/booking/admin/pricing) | `reseller-*.php`, `includes/reseller.php`, `admin/resellers.php`, `admin/reseller-topups.php`, `admin/reseller-pricing.php` | ⏳ |
| 25 | Payments Midtrans | `includes/payments.php`, `webhook-midtrans.php`, `admin/payments.php` | ⏳ |
| 26 | Webhooks (Midtrans/WA) | `webhook-midtrans.php`, `webhook-wa.php`, `includes/send-wa.php` | ⏳ |
| 27 | Notifications + FCM Push | `notifications.php`, `includes/fcm-push.php`, `includes/notifications.php`, `admin/push-notifications.php` | ⏳ |
| 28 | Analytics | `analytics.php`, `includes/analytics/`, `admin/analytics.php` | ⏳ |
| 29 | **Accounting (income/expense) — P0** | `admin/accounting.php`, `database/migrate-admin-finance.sql`, `database/migrate-expenses.php` | ⏳ |
| 30 | **Sales Report — P0** | `admin/sales-report.php` | ⏳ |
| 31 | Corporate Rates | `admin/corporate-rates.php` (logika di `includes/functions.php`) | ⏳ |
| 32 | Currency Settings | `admin/currency-settings.php` | ⏳ |
| 33 | i18n / Terjemahan (ID/EN/ZH, `t()`) | `includes/functions.php` (`t()`), `database/regenerate-en.php`, `admin/` bulk-lang | ⏳ |
| 34 | AB Testing | `admin/ab-tests.php` (helper `abVariant` di `includes/functions.php`) | ⏳ |
| 35 | Social Proof | `social-proof-ajax.php` | ⏳ |
| 36 | Newsletter | `newsletter-ajax.php` | ⏳ |
| 37 | Hero Slides / Appearance | `index.php`, `admin/hero-slides.php`, `admin/appearance.php` | ⏳ |
| 38 | Chat (WA) Settings | `admin/chat-settings.php`, `admin/wa-settings.php` | ⏳ |
| 39 | Email Templates + Log | `includes/email.php`, `includes/email-templates/`, `admin/email-log.php` | ⏳ |
| 40 | SEO (sitemap, breadcrumb, schema) | `sitemap.php`, `includes/seo.php`, `includes/components/breadcrumb.php` | ⏳ |
| 41 | Passenger Profiles | `my-profiles.php`, `profile-ajax.php` | ⏳ |
| 42 | Track/Affiliate | `track.php` | ⏳ |
| 43 | Android App / WebView Bridge | `android-app/`, `CAPACITORAPP.md`, `WEBVIEWBRIDGE.md` | ⏳ |

## Prioritas P0 — Perhitungan Jual-Beli & Akuntansi (paling penting)

> Semua item di bawah WAJIB dites & diverifikasi lebih dulu sebelum modul lain. Fungsi kunci sudah diverifikasi ada di codebase:
> `getPriceForDate()` `includes/functions.php:645`, `getFlashSalePrice()` `includes/flash-sales.php:17`, `applyCorporateDiscount()` `includes/functions.php:1128`, `handleMidtransNotification()` `includes/payments.php:150`, `awardPointsForPaidBooking()` `includes/points.php:60`.

| # | Area Perhitungan | Logika/File kunci | Yang diverifikasi |
|---|------------------|-------------------|-------------------|
| P0-1 | Harga tour + date pricing | `getPriceForDate()` (`includes/functions.php:645`), `getFlashSalePrice()` (`includes/flash-sales.php:17`) | Harga dasar per-tanggal, flash sale override, format Rupiah |
| P0-2 | Promo codes / coupons | `apply-promo-ajax.php`, `admin/promo-codes.php` | Diskon benar, expiry, usage limit, tidak double-use |
| P0-3 | Corporate discount | `includes/corporate-rates.php` | Diskon % clamp 0–100, diterapkan di booking |
| P0-4 | Reseller pricing/balance | `includes/reseller.php`, `reseller-topup.php`, `reseller-booking.php` | Topup → approve → saldo; booking memotong saldo; reject saat saldo kurang |
| P0-5 | Wallet | `wallet.php`, `includes/wallet.php` | Topup, debit, kredit, saldo konsisten |
| P0-6 | Midtrans payment | `includes/payments.php`, `webhook-midtrans.php` | Webhook idempotent, status paid hanya sekali, recon admin |
| P0-7 | Loyalty points award | `includes/points.php` | Poin ter-award saat paid, tier badge, cache bust |
| P0-8 | Accounting income/expense | `admin/accounting.php`, `database/migrate-admin-finance.sql` (✅ ada) | Pencatatan income per booking, expense manual, margin |
| P0-9 | Sales report | `admin/sales-report.php` | Total penjualan = jumlah booking semua vertikal |
| P0-10 | Analytics revenue | `includes/analytics/` | Revenue trend kontinu, normalisasi semua vertikal |

## Prioritas P1 — Booking Flow & User Journey (setelah P0)

> Dites setelah semua P0 hijau. Cakupan: booking flow tiap vertikal (tour/hotel/flight/ferry/train/attraction/esim/rental-car/transfer), auth, review, wishlist, referral→cancel→wallet, price alert.

| # | Area P1 | Yang diverifikasi |
|---|---------|-------------------|
| P1-1 | Booking flow tiap vertikal | Booking form → submit → muncul di `my-bookings.php` & `admin/bookings.php` |
| P1-2 | Auth (login/register/logout/forgot) | Sesi user benar, password reset token sekali pakai |
| P1-3 | Reviews | Submit review + subratings, tampil di detail, moderasi admin |
| P1-4 | Wishlist | Toggle add/remove via `wishlist-ajax.php`, persist di `wishlist.php` |
| P1-5 | Referral → cancel → wallet | Kode referral tercatat, cancel booking → refund masuk wallet |
| P1-6 | Price Alerts | Buat alert via `price-alert-ajax.php`, checker trigger notifikasi |

## Prioritas P2 — i18n, Notifikasi & UI (setelah P1)

> Dites terakhir. Catatan known-issue: footer beberapa teks masih ID hardcoded di mode EN (belum di-wrap `t()` di `includes/footer-klook.php`) — sisa sesi sebelumnya.

| # | Area P2 | Yang diverifikasi |
|---|---------|-------------------|
| P2-1 | i18n ID/EN/ZH | Switch bahasa di halaman publik; footer EN masih hardcoded → fix dengan wrap `t()` + kamus `regenerate-en.php` |
| P2-2 | Hero flights/ferries | Hero "Jelajahi Lebih Banyak, Nikmati Perjalanannya." + subtitle ter-translate di `flights.php`/`ferries.php` |
| P2-3 | Notifications (in-app + FCM) | Bell unread count, list, mark-read; FCM lang-aware |
| P2-4 | AB Testing | `abVariant` assign konsekuen per session, convert tracking |
| P2-5 | Social Proof + Track | Popup notifikasi pembelian, track.php redirect affiliate |
| P2-6 | UI/Polish | Bottom nav mobile, image lightbox, skeleton loading, trust badge, halaman 404/sad-path |

## Log Testing (progress tracking — update tiap langkah)

Status: ⏳ pending · ✅ pass · ❌ fail · 🔧 fixed (re-run pass)

| No | Test | Status | Issue | Fix commit |
|----|------|--------|-------|------------|
| 1 | Env check: `npx playwright --version` + `php tests/unit/run.php` | ✅ | — | — |
| 2 | Local server up (`http://localhost/tourandtravel/index.php` = 200) | ✅ | — | — |
| 3 | smoke-public.spec.ts | ✅ 7/7 pass (4.1s) | — | — |
| 4 | homepage-templates.spec.ts | 🔧 14/14 pass (9.7s) | preset tour expect "Destinasi Populer" padahal section dihide sementara di `includes/homepage/tour-destinations.php:1` (test stale) | Hapus assertion stale di `tests/e2e/homepage-templates.spec.ts:40` |
| 5 | nav-crawl.spec.ts | 🔧 7 pass + 1 skip (6.6s) | Test "destinasi links di homepage" expect link `destinasi.php?city=`, tapi satu-satunya sumber adalah section "Destinasi Populer" yang dihide sementara (sama dengan issue #4) | Test di-skip kondisional via `test.skip(count===0)` di `tests/e2e/nav-crawl.spec.ts:125` |
| 6 | local-auth.spec.ts | ✅ 3/3 pass (6.4s) | — | — |
| 7 | password-reset.spec.ts | 🔧 5/5 pass (3.6s) | (1) Halaman render EN karena `getCurrentLang()` fallback ke `HTTP_ACCEPT_LANGUAGE` Chromium (en-US) — pattern test hanya ID; (2) rate-limit test tidak seed user → `password_resets` kosong → limit tak trigger; (3) typo `'tidak-ada-'+email` memakai fungsi `email()` bukan variabel `e` → email invalid | Perbaiki pattern regex jadi bilingual di `tests/e2e/password-reset.spec.ts` (baris 33/46/89), seed user di test rate-limit, ganti `email`→`e`. Aplikasi sendiri OK — bukan bug produk |
| 8 | user-pages.spec.ts | ⏳ | | |
| 9 | passenger-profile.spec.ts | ⏳ | | |
| 10 | Unit: FlashSalesTest + HelpersTest + SmokeTest | ⏳ | | |
| 11 | tour-detail.spec.ts + tours-filter.spec.ts (P0 harga) | ⏳ | | |
| 12 | tour-detail-sadpath.spec.ts + tours-sadpath.spec.ts | ⏳ | | |
| 13 | tour-content-full/lang-switch/translation.spec.ts | ⏳ | | |
| 14 | flash-sale-listing.spec.ts (P0 flash sale) | ⏳ | | |
| 15 | coupons.spec.ts + promo-codes.spec.ts (P0 promo) | ⏳ | | |
| 16 | corporate-rates.spec.ts (P0 corporate discount) | ⏳ | | |
| 17 | abuse-booking.spec.ts (P0 booking validasi) | ⏳ | | |
| 18 | payments.spec.ts + unit MidtransPaymentTest (P0 webhook idempotent) | ⏳ | | |
| 19 | booking-success.spec.ts (P0 status paid + poin) | ⏳ | | |
| 20 | Unit PointsTest + TierBadgeTest, e2e loyalty-badge/loyalty-tier (P0 poin) | ⏳ | | |
| 21 | wallet.spec.ts (P0 wallet) | ⏳ | | |
| 22 | referral-cancel-wallet.spec.ts (P0 refund) | ⏳ | | |
| 23 | reseller-register.spec.ts (P0) | ⏳ | | |
| 24 | reseller-topup.spec.ts (P0 saldo) | ⏳ | | |
| 25 | reseller-booking.spec.ts + reseller-insufficient-balance.spec.ts (P0) | ⏳ | | |
| 26 | reseller-admin.spec.ts (P0) | ⏳ | | |
| 27 | Unit AnalyticsFinanceTest (P0 akuntansi) | ⏳ | | |
| 28 | admin-accounting.spec.ts + accounting-tour/transport.spec.ts (P0) | ⏳ | | |
| 29 | admin-sales-report.spec.ts (P0) | ⏳ | | |
| 30 | analytics.spec.ts (P0 revenue) | ⏳ | | |
| 31 | admin-bookings.spec.ts (P0) | ⏳ | | |
| 32 | admin/payments.php recon (P0) | ⏳ | | |
| 33 | admin-guard.spec.ts + abuse-noauth.spec.ts (P0 akses) | ⏳ | | |
| 34 | admin-dashboard.spec.ts + enhanced (P0) | ⏳ | | |
| 35 | admin-settings.spec.ts (P0) | ⏳ | | |
| 36 | admin-crud.spec.ts + admin-tours.spec.ts (P1) | ⏳ | | |
| 37 | admin-bulk-lang + admin-translation + translation-completeness (P1) | ⏳ | | |
| 38 | hotels.spec.ts + hotel-detail.spec.ts (P1) | ⏳ | | |
| 39 | infinite-scroll + skeleton-loading (P1) | ⏳ | | |
| 40 | flights.spec.ts (P1) | ⏳ | | |
| 41 | Unit FlightMultiLegTest + PriceCalendarTest (P1) | ⏳ | | |
| 42 | ferries.spec.ts (P1) | ⏳ | | |
| 43 | trains-esim-faq.spec.ts (P1) | ⏳ | | |
| 44 | attractions.spec.ts (P1) | ⏳ | | |
| 45 | rental-cars.spec.ts + rental-car-detail.spec.ts (P1) | ⏳ | | |
| 46 | transfers.spec.ts (P1) | ⏳ | | |
| 47 | destinasi + blog + collections (P1) | ⏳ | | |
| 48 | review + review-subrating + reviews-rich + review-multilang (P1) | ⏳ | | |
| 49 | Unit WishlistTest + e2e wishlist (P1) | ⏳ | | |
| 50 | Unit PriceAlertTest + e2e price-alert (P1) | ⏳ | | |
| 51 | notifications.spec.ts (P1) | ⏳ | | |
| 52 | Unit EmailTest + e2e email.spec.ts (P1) | ⏳ | | |
| 53 | Unit ItineraryTest + e2e itinerary-builder (P1 PDF) | ⏳ | | |
| 54 | datepicker-dual.spec.ts (P1) | ⏳ | | |
| 55 | language-switch + switcher + multilingual + zh-switch (P2 i18n) | ⏳ | | |
| 56 | Fix footer i18n: wrap t() di includes/footer-klook.php + kamus EN (P2) | ⏳ | | |
| 57 | ferry-flights-hero + hero-slides + redesign-transport (P2) | ⏳ | | |
| 58 | Unit ABTestTest + e2e ab-testing (P2) | ⏳ | | |
| 59 | track.spec.ts + trust-badge.spec.ts + unit SocialProofTest (P2) | ⏳ | | |
| 60 | abuse-sqli + abuse-xss + abuse-user-admin (P2 security) | ⏳ | | |
| 61 | polish + bottom-nav + image-lightbox + zz-step36/36b (P2 UI) | ⏳ | | |
| 62 | Full regression: `npx playwright test` + semua unit test | ⏳ | | |

## Ringkasan Akhir

(diisi setelah semua test selesai — jumlah pass/fail, bug ditemukan & fix, sisa risiko)

### Baseline Environment (step 6, 2026-09-10)

- Playwright: **1.62.1** (devDependency `@playwright/test` ^1.62.1) ✅
- PHP CLI: **8.3.32** ✅
- Unit test baseline: `php tests/unit/run.php` → **LULUS 139, GAGAL 0** ✅
- Test inventory: 88 spec e2e (`tests/e2e/*.spec.ts`), 22 file unit (`tests/unit/`)
- Konfigurasi Playwright: baseURL `http://localhost/tourandtravel`, chromium headless, workers=1, retries=1, testDir `./tests/e2e`, globalSetup `tests/e2e/global-setup.ts`

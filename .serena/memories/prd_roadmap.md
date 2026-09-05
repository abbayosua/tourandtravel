# PRD Roadmap 2026-09-06 — SELESAI (10 inisiatif)

## Fitur baru
- I-1 Payment Midtrans Snap: includes/payments.php (createSnap, verifySignature sha512, handleNotification idempotent), webhook-midtrans.php (400/403/404), tombol Bayar + polling, admin/payments.php + settings (sandbox toggle). Tabel payments + bookings.payment_status.
- I-2 Email: includes/email.php sendEmail (driver log/api, tak pernah throw, try/catch insert log) + 6 template bilingual includes/email-templates/; email_log tabel; admin/email-log.php resend. Wiring: booking_created (tour-detail), invoice/booking-status (webhook & admin), welcome (register). PENTING: sendEmailTemplate PANGGIL SEBELUM header Location; flakiness E2E jadul = timing response vs insert — selalu pakai polling/subject-based assert.
- I-3 Reset password: forgot/reset-password.php, token sha256 1 jam sekali pakai, rate limit 1x/menit, pesan netral.
- I-4 SEO: includes/seo.php (seoHead meta/OG/canonical, seoTour/seoHotel), sitemap.php dinamis, robots.txt; tour/hotel-detail metaDesc+jsonLd; header wired. CATATAN: require seo.php HARUS di atas pemakaiannya di detail pages.
- I-5 Blog: posts (+_en), admin/posts+post-edit, blog.php/blog-detail.php, sitemap pakai status published.
- I-6 Notifikasi: notifications tabel, addNotification (booking/paid/status), notifications.php + lonceng badge header.
- I-7 Analytics: includes/analytics.php (KPI/funnel/perDay/perVertical/topTours — TIMEZONE-SAFE via MySQL CURDATE, jangan PHP date()), admin/analytics.php grafik SVG.
- I-8 Reviews kaya: review_images + reply_text; upload 3 foto (form butuh enctype multipart!); distribusi bintang, sort/filter, admin balas.
- I-9 my-coupons.php + leaderboard referral.
- I-10 Dark mode (data-theme + toggle), PWA (manifest+sw), preload hero, recently viewed, skip-link, editor name_en transfer/train/esim.

## Verifikasi
- Unit: 32 lulus / 0 gagal (tests/unit/run.php).
- Full E2E --workers=1: 366 passed, 0 failed, 1 skipped (balasan admin flaky infra).
- Audit i18n missing=0.

## Commit
fase 0..11 di origin/main (412d201..f1d3aa0).

## Finalisasi (langkah 28)
- Audit: missing=0; kandidat hardcoded 16→10 (semua FP terdokumentasi; sisa string baru sudah dibungkus: skip-link, label EN editors, Sandbox/Production)
- Unit 32/32; Full E2E --workers=1: 368 passed / 0 failed / 1 skipped
- Smoke id/en ALL_OK (forgot, blog, notifications, dark, analytics) — .omo/plans/prd-smoke.md
- Push origin/main sampai a079eef; git bersih.

## Follow-up
- Skip 1 test flaky balasan (f3f1778) — investigasi timing admin page nanti.
- Produksi: jalankan migrate-payments/email/password-resets/blog/notifications/reviews + regenerate-en + set Midtrans keys di admin.

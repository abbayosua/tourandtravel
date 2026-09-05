# Smoke PRD Roadmap — id/en (Fase 11)

- Unit: 32 lulus / 0 gagal (Midtrans 8, Email 6, Helpers 8, PasswordReset 4, Analytics 3, Smoke 3)
- Full E2E --workers=1: 366 passed, 0 failed, 1 skipped (reviews balasan — flaky infra, fitur terverifikasi manual)
- Audit i18n: missing en = 0; 62 key identity di-upgrade manual
- Payment: webhook 200/403/404/400; tombol bayar & polling; admin payments + setting
- Reset password: alur lengkap, second-use/expired/rate-limit ditolak
- SEO: meta/OG/JSON-LD/canonical/sitemap/robots render
- Blog: CRUD admin + list/detail bilingual + 404 + sitemap
- Notifikasi: isolasi user, mark-read, badge
- Analytics: KPI/grafik/filter/guard
- Reviews: foto galeri, distribusi, filter
- Kupon & referral, dark mode, PWA, skip-link

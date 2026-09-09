# Plan: admin-dashboard-overhaul

> Created: 2026-09-09 16:09:59
> **Status**: Draft

## Objective

 overhaul admin dashboard menjadi operational system lengkap ala Traveloka/Klook: (1) Enhanced Dashboard dengan real-time KPI cards, revenue trend chart, booking funnel per vertikal, recent activity feed, conversion metrics; (2) Sales Report page — filterable table per vertikal/hotel/flight/attraction/transfer/train/esim/ferry, date range, status, total per kategori, export CSV; (3) Accounting/Finance page — profit & loss statement, COGS tracking, expense management (tabel expenses), net profit calculation, monthly comparison chart; (4) Reorganize admin sidebar dengan section grouping (Overview, Inventory, Bookings, Marketing, Finance, Settings). Gunakan Chart.js (CDN) untuk grafik interaktif. Semua bilingual (t()).

## Scope

**In Scope:**
- Enhanced dashboard dengan KPI real-time (revenue, booking count, profit, conversion rate, AOV)
- Revenue trend chart (harian/mingguan/bulanan) via Chart.js
- Booking funnel per vertikal dengan visualisasi
- Sales report — tabel filterable per vertikal, tanggal, status + ringkasan per kategori + export CSV
- Accounting page — P&L statement, expense tracking (CRUD), COGS per booking, net profit
- Migration SQL: `expenses` table + `bookings.cogs` column
- Sidebar reorganized dengan section grouping

**Out of Scope:**
- Multi-currency accounting (sudah ada currency-settings, fokus RUP sahaja)
- Invoice PDF generation (bisa fase berikutnya)
- Multi-admin role/permission

## Context

PHP vanilla + MySQL + Bootstrap 5.3. Existing: dashboard.php (basic 5 cards + recent bookings), analytics.php (booking/day, revenue/vertical, funnel), payments.php (Midtrans list). DB tables: bookings (tour), hotel_bookings, flight_bookings, attraction_bookings, transfer_bookings, train_bookings, connectivity_bookings, payments. Tech: no composer, PDO, session auth cekLogin(). Basis: http://localhost/tourandtravel/admin/

## Acceptance Criteria

1. Dashboard utama menampilkan semua KPI real-time (revenue, bookings, profit, conversion rate)
2. Sales report bisa filter per vertikal, tanggal, dan status
3. Accounting page lengkap (COGS, profit, expense tracking, P&L)
4. Export CSV/Excel tersedia
5. Sidebar terorganisir dengan section grouping
6. Semua query performant (indexed, no N+1)

## Approach

Fase berurutan: (1) DB migration dulu, (2) Enhanced Dashboard, (3) Sales Report, (4) Accounting, (5) Sidebar reorganize. Setiap fase test manual via browser. Chart.js via CDN (sudah pattern project — Bootstrap CDN). Semua query pakai UNION atau loop vertikal seperti pattern `bookings.php` yang sudah ada.

## Tasks

| # | Task | Files | Status |
|---|------|-------|--------|
| 1 | **DB Migration** — Buat `database/migrate-admin-finance.sql`: tabel `expenses` (id, category, description, amount, booking_type nullable, booking_id nullable, created_at); tambah kolom `cogs` ke `bookings` (all booking tables via ALTER). Buat `database/migrate-expenses.php` runner. | `database/migrate-admin-finance.sql`, `database/migrate-expenses.php` | pending |
| 2 | **Enhanced Dashboard** — Rewrite `admin/dashboard.php`: (a) 8 KPI cards (Tour Aktif, Total Booking, Pending, Confirmed, Revenue, Profit, Avg Order Value, Conversion Rate); (b) Revenue trend line chart 30 hari terakhir (Chart.js); (c) Booking per vertikal horizontal bar chart; (d) Recent 10 activity feed (status changes); (e) Quick actions panel (link ke bookings, sales, accounting). Query helper di `includes/analytics.php` tambah fungsi baru. | `admin/dashboard.php`, `includes/analytics.php` | pending |
| 3 | **Sales Report Page** — Buat `admin/sales-report.php`: (a) Date range picker; (b) Filter per vertikal (tour/hotel/flight/attraction/transfer/train/esim/ferry); (c) Tabel detail transaksi (tanggal, kode, pelanggan, item, tipe, qty, gross, disc/promo, net, status, payment_method); (d) Summary cards per kategori (total transaksi, total revenue, avg per transaksi); (e) Export CSV button; (f) Revenue breakdown pie chart. Query: UNION semua booking tables + payments. | `admin/sales-report.php` | pending |
| 4 | **Accounting Page** — Buat `admin/accounting.php`: (a) P&L statement: Revenue section (per vertikal), COGS section (from cogs column), Gross Profit, Expenses section (from expenses table), Net Profit; (b) Date range filter; (c) Expense CRUD (add/edit/delete via modal); (d) Monthly comparison chart (revenue vs expense vs profit); (e) Export CSV. Helper functions: `accountingPnL()`, `accountingExpenses()` di `includes/analytics.php`. | `admin/accounting.php`, `admin/includes/admin-header.php` | pending |
| 5 | **Sidebar Reorganize** — Group navigation di `admin/includes/admin-header.php`: **Overview** (Dashboard, Analytics), **Inventory** (Tour, Hotel, Pesawat, Ferry, Rental, Atraksi, Transfer, Kereta, eSIM), **Bookings** (Kelola Booking, Pembayaran), **Marketing** (Flash Sale, Kode Promo, Price Alerts, Corporate Rates, Koleksi), **Finance** (Sales Report ← NEW, Accounting ← NEW, Loyalty), **Content** (Blog, Ulasan, FAQ, Tampilan), **Settings** (WA, Live Chat, Email Log, Mata Uang), **External** (Lihat Website). Tambah section headers (small text-secondary). | `admin/includes/admin-header.php` | pending |
| 6 | **Analytics Enhancement** — Update `admin/analytics.php`: tambah filter per vertikal (tabs), pie chart revenue per vertikal (Chart.js), bar chart top 10 produk (bukan hanya tour). Update `includes/analytics.php` dengan fungsi baru: `analyticsRevenuePerProduct()`. | `admin/analytics.php`, `includes/analytics.php` | pending |
| 7 | **Translation Keys** — Tambah translation keys baru di `database/regenerate-en.php` untuk semua string baru (Sales Report, Accounting, P&L, COGS, Expenses, Export, Gross Profit, Net Profit, dll). | `database/regenerate-en.php` | pending |
| 8 | **Smoke Test** — Jalankan migration, akses semua halaman baru via browser, pasti tidak error, chart render, filter/export berfungsi. | - | pending |

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Query performance UNION 7+ booking tables | Tambah index `created_at` + `status` di semua booking tables; pakai `LIMIT` di subquery; materialize ke tabel ringkas jika lambat |
| COGS belum ada di tabel booking lama | Migration ALTER TABLE `cogs DECIMAL(12,2) DEFAULT 0`; set default 0 agar existing rows aman |
| Chart.js CDN down | Fallback SVG static seperti pattern `analytics.php` yang sudah ada |
| Bahasa Indonesia & English sync | Pakai `t()` wrapper, regenerate translation keys setelah semua page selesai |

## Verification

- [ ] All tasks completed
- [ ] Tests pass
- [ ] Edge cases handled

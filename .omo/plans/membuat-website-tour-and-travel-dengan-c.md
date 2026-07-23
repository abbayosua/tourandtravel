# Plan: membuat-website-tour-and-travel-dengan-c

> Created: 2026-07-23 18:25:57
> **Status**: Completed ✅

## Objective

Membuat website tour and travel dengan catalog tour, itinerary, jadwal keberangkatan, harga, dan sistem booking

## Scope

**In Scope:**
- Landing page hero, featured tours, newsletter
- Catalog tour dengan filter kategori & pencarian
- Detail tour (itinerary per hari, jadwal keberangkatan, harga)
- Sistem booking/pemesanan via form
- Admin panel (login, CRUD tour, manage bookings)
- Database MySQL dengan PDO
- Responsive mobile-first design
- Upload gambar tour

**Out of Scope:**
- Pembayaran online (midtrans/dll) — masih manual konfirmasi
- Multi-language
- SEO advanced
- CMS user management (hanya 1 admin)

## Context

Working directory: /Users/user/www/tourandtravel (kosong). Target: website statis dengan admin panel untuk manage konten. Bahasa Indonesia.

## Acceptance Criteria

1. Halaman landing/homepage menarik. 2. Catalog tour dengan filter/kategori. 3. Detail tour berisi itinerary, harga, jadwal keberangkatan. 4. Sistem booking/pemesanan. 5. Admin panel untuk CRUD tour. 6. Responsive mobile.

## Approach

**Tech Stack:** PHP 8 + MySQL (PDO) + Bootstrap 5
**Design:** Mobile-first responsive, warna biru/hijau tematik travel
**Auth:** Session-based login untuk admin panel

**Struktur Folder:**
```
/
├── index.php              # Landing page
├── tours.php              # Catalog tour
├── tour-detail.php        # Detail tour + booking form
├── booking-success.php    # Konfirmasi booking
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── tours.php          # Manage tours
│   ├── tour-add.php
│   ├── tour-edit.php
│   ├── bookings.php       # Manage bookings
├── includes/
│   ├── config.php         # DB config
│   ├── db.php             # PDO connection
│   ├── header.php
│   ├── footer.php
│   ├── auth.php           # Session auth check
│   ├── functions.php      # Helper functions
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/
├── uploads/               # Tour images
├── database/
│   └── schema.sql
```

**Database Tables:**
- `tours` — id, title, slug, category, description, price, max_participants, cover_image, is_active, created_at
- `tour_dates` — id, tour_id, departure_date, return_date, available_slots
- `itineraries` — id, tour_id, day_number, title, description, meals, accommodation
- `bookings` — id, tour_id, tour_date_id, name, email, phone, participants, total_price, status, notes, created_at
- `admins` — id, username, password_hash

## Tasks

| # | Task | Files | Status |
|---|------|-------|--------|
| 1 | Buat database schema & SQL | `database/schema.sql` | pending |
| 2 | Buat includes (config, db, header, footer, functions, auth) | `includes/` | pending |
| 3 | Landing page (Hero, Featured Tours, Newsletter) | `index.php` | pending |
| 4 | Catalog tour (grid + filter kategori + search) | `tours.php` | pending |
| 5 | Detail tour (itinerary, jadwal, harga, form booking) | `tour-detail.php` | pending |
| 6 | Booking confirmation page | `booking-success.php` | pending |
| 7 | Admin auth (login/logout session) | `admin/login.php`, `admin/logout.php` | pending |
| 8 | Admin dashboard | `admin/dashboard.php` | pending |
| 9 | Admin CRUD tours + upload gambar | `admin/tours.php`, `admin/tour-add.php`, `admin/tour-edit.php` | pending |
| 10 | Admin manage bookings | `admin/bookings.php` | pending |
| 11 | CSS styling & responsive | `assets/css/style.css` | pending |
| 12 | Seed data SQL & testing | `database/seed.sql` | pending |

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Gambar tour ukuran besar | Batasi upload max 2MB, kompres dengan GD |
| SQL injection | Gunakan prepared statements PDO |
| Booking double untuk slot yang sama | Validasi stok sebelum insert |
| Admin session tidak aman | Session timeout, regenerate session ID

## Verification

- [ ] All tasks completed
- [ ] Tests pass
- [ ] Edge cases handled

# DEPLOY.md — Checklist Produksi (FOLLOW-20260909-104850)

## 1. Urutan Eksekusi Migrasi SQL

Jalankan dari root project, urut alfabet aman (semua file idempotent — aman diulang):

```bash
for f in database/migrate-*.sql; do
  echo "== $f =="; mysql -u $DB_USER -p $DB_NAME < "$f" || echo "FAILED: $f"
done
```

Daftar lengkap (36 file):

| # | File | Tujuan |
|---|------|--------|
| 1 | migrate-ab-tests.sql | A/B testing (ab_tests, ab_variants, ab_impressions) |
| 2 | migrate-admin-bookings-phase5.sql | Admin bookings |
| 3 | migrate-blog.sql | Blog |
| 4 | migrate-content-lang.sql | Konten multilingual (title_en/description_en) |
| 5 | migrate-corporate-rates.sql | Corporate rates + users.corporate_company_id |
| 6 | migrate-email.sql | Email log + templates |
| 7 | migrate-fcm.sql | FCM tokens |
| 8 | migrate-ferry-bookings.sql | Ferry bookings (local processing) |
| 9 | migrate-flight-cache.sql | Flight API cache (FlightList/Duffel/Ferry) |
| 10 | migrate-flash-sales.sql | Flash sales |
| 11 | migrate-hero-focus.sql | Hero focus |
| 12 | migrate-hotel-bookings-room.sql | room_id di hotel_bookings |
| 13 | migrate-hotel-bookings-status.sql | Status hotel_bookings |
| 14 | migrate-hotel-rooms.sql | Hotel rooms |
| 15 | migrate-hotels-amenities.sql | Amenities hotels |
| 16 | migrate-itinerary.sql | Itinerary builder (user_itineraries/days/items) |
| 17 | migrate-latlng.sql | lat/lng |
| 18 | migrate-notifications.sql | Notifikasi in-app |
| 19 | migrate-passenger-profiles.sql | Profil penumpang |
| 20 | migrate-password-resets.sql | Reset password |
| 21 | migrate-payments.sql | Payments |
| 22 | migrate-points.sql | Points ledger |
| 23 | migrate-price-alerts.sql | Price alerts |
| 24 | migrate-price-calendar.sql | Price calendar |
| 25 | migrate-price-calendar-hotel-flight.sql | Price calendar hotel/flight |
| 26 | migrate-review-hotel-id.sql | reviews.hotel_id (tour_id jadi nullable) |
| 27 | migrate-review-lang.sql | reviews.lang (multi-lang UGC) + tour_id nullable |
| 28 | migrate-review-subratings.sql | Review sub-ratings |
| 29 | migrate-reviews.sql | Reviews |
| 30 | migrate-user-tiers.sql | Loyalty tiers (users.tier + user_tiers) |
| 31 | migrate-wishlists-drop-fk.sql | Wishlist (lepas FK) |
| 32 | migrate-wishlists-polymorphic.sql | Wishlist polymorphic |
| 33 | migrate-reseller.sql | Reseller role + balance (users.role, users.reseller_balance) |
| 34 | migrate-reseller-topups.sql | Reseller topup requests (reseller_topups) |
| 35 | migrate-reseller-pricing.sql | Reseller tour pricing (reseller_tour_prices) |
| 36 | migrate-reseller-bookings.sql | Reseller booking tracking (bookings.booking_source, bookings.reseller_id) |

Catatan khusus:
- `migrate-review-lang.sql` — juga membuat `reviews.tour_id` nullable (wajib untuk review hotel).
- `migrate-fcm.php` — skrip PHP alternatif migrasi FCM (opsional).
- `database/schema.sql` / `schema-klook.sql` — hanya untuk instalasi fresh, bukan migrasi.
- `database/seeder.php` / `seed.sql` — data demo, JANGAN di produksi.

## 2. Environment Variables / Credentials

### A. Environment variables (includes/config.php + includes/duffel.php membaca via getenv)

| Variabel | Default | Keterangan |
|----------|---------|-----------|
| `DB_HOST` | localhost | Host database |
| `DB_NAME` | tourandtravel | Nama database |
| `DB_USER` | root | User database |
| `DB_PASS` | (kosong) | Password database |
| `BASE_URL` | https://tourandtravel.web.id | URL situs |
| `FCM_SERVER_KEY` | (kosong) | Server key Firebase (push notif admin) |
| `DUFFEL_TOKEN` | test token di duffel.php | Ganti ke `duffel_live_…` untuk flight live |

Setup lokal: `cp .env.example .env` lalu `export $(grep -v '^#' .env | xargs)`.
Produksi: systemd `Environment=` / docker env / panel hosting. `.env` sudah di-gitignore.

### B. Hardcoded fallback (jika env tidak diset)

| Konstanta | File | Produksi |
|-----------|------|----------|
| `FCM_SERVER_KEY` | includes/config.php:19-21 | Kosong = push dinonaktifkan |
| `DUFFEL_TOKEN` | includes/duffel.php:3-5 | Test token — WAJIB ganti/live sebelum go-live |

### B. Settings via DB (tabel `settings` — diisi lewat /admin)

| Key | Keterangan |
|-----|-----------|
| `midtrans_server_key` | Server key Midtrans |
| `midtrans_client_key` | Client key Midtrans (Snap JS) |
| `midtrans_env` | `sandbox` / `production` |
| `payment_enabled` | `1` aktifkan pembayaran |
| `email_driver` | `api` / `log` |
| `email_api_endpoint` | Endpoint email API |
| `email_api_key` | API key email |
| `email_from` | Alamat pengirim |
| `points_earning_rate` | % poin per booking (default 1) |
| `loyalty_silver_threshold` / `loyalty_gold_threshold` / `loyalty_joyplus_threshold` | Ambang tier (2/5/10) |
| `tawk_property_id` / `tawk_widget_id` | Widget chat Tawk |
| `default_currency` | Mata uang default |

## 3. Checklist Pre-Deploy

- [ ] Backup DB produksi (`mysqldump`)
- [ ] Jalankan semua 32 migrasi SQL (loop di atas), cek output `FAILED`
- [ ] Set env vars (`DB_*`, `BASE_URL`, `FCM_SERVER_KEY`, `DUFFEL_TOKEN`) — lihat tabel di atas
- [ ] Isi settings Midtrans + email via `/admin` (atau SQL INSERT IGNORE ke settings)
- [ ] `php -l` semua file PHP yang berubah
- [ ] Upload `assets/img/ferry/` (logo ferry: sindo.png, horizon.png, batam.jpg, majestic.png)
- [ ] Upload `assets/css/hero-uifactory.css` (hero UI style)
- [ ] Verifikasi halaman kunci: `/index.php`, `/tours.php`, `/hotels.php`, `/flights.php`, `/ferries.php`, `/tour-detail.php?slug=…`, `/hotel-detail.php?slug=…`
- [ ] Test checkout 1 tour end-to-end (booking → Midtrans sandbox → paid → poin terbit)
- [ ] Test booking ferry: `/ferries.php` → cari → klik "Pesan" → isi data → submit
- [ ] Verifikasi cron/hook price alert checker (includes/price-alert-checker.php, trigger per jam di config)
- [ ] Pastikan `uploads/` writable (passports, reviews, blog)

## 4. Verifikasi Fitur Baru (post-deploy)

- [ ] Header: badge tier + poin muncul untuk user login (loyalty-badge)
- [ ] `/tour-detail.php` → tombol "Simpan ke Itinerary" → modal → buat/tambah/hapus item
- [ ] `/my-itinerary.php` → daftar itinerary
- [ ] `/hotels.php` + `/flights.php` → scroll bawah → konten bertambah tanpa reload
- [ ] CTA tour-detail menampilkan salah satu varian A/B (Pesan Sekarang / Booking Sekarang)
- [ ] `/admin/ab-tests.php` → impresi tercatat per varian
- [ ] Review dengan dropdown bahasa → tersimpan `lang`, tampil hanya di halaman bahasa sama
- [ ] `/admin/corporate-rates.php` → tambah perusahaan + tautkan user → checkout berdiskon
- [ ] `/flights.php` + `/ferries.php` → hero UI tiket.com style (gradient background, white card)
- [ ] `/flights.php` → filter maskapai dinamis dari hasil search
- [ ] `/flights.php` → logo maskapai dari Kiwi CDN
- [ ] `/ferries.php` → logo ferry lokal (Sindo, Horizon, Batam Fast, Majestic)
- [ ] `/ferries.php` → booking lokal (bukan redirect ke Easybook)
- [ ] Flight API cache aktif (flight_cache table terisi, query kedua lebih cepat)

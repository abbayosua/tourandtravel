# Plan: Klook 1:1 Redesign — TourAndTravel

> Created: 2026-09-02 17:12:32
> **Status**: Draft

## Objective

Redesign TourAndTravel menjadi **1:1 ala Klook** — layout, komponen, UX booking flow — dengan **nuansa warna brand saat ini** (Bootstrap blue `#0d6efd` & turunannya), **bukan** sunset orange Klook.

Pertahankan seluruh layer PHP/session/DB. Hanya rombak **UI layer** (HTML+CSS+JS) + tambah komponen reusable. Struktur file/routing/nama tabel/helper tetap.

---

## 1. Perbandingan Kondisi Codebase Saat Ini vs Target Klook

### 1.1 Identitas & Branding

| Aspek | Current (TourAndTravel) | Target (Klook 1:1 + brand blue) |
|---|---|---|
| Warna utama | Bootstrap `#0d6efd` (navbar `bg-primary`, CTA `btn-primary`, gradient `#0d6efd→#6610f2`) | **Dipertahankan** — hanya diperkaya: `#0d6efd` primary, `#0b5ed7` dark, `#6ea8fe` light, `#f0f4ff` bg tint |
| Font | `system-ui` stack | Ganti ke **Inter / Plus Jakarta Sans** (CDN Google Fonts) + fallback system |
| Radius | `8/12/16px` | Naikkan konsisten: `12/16/24px`, pill `9999px` untuk chip |
| Shadow | custom 4 level | Pertahankan token, rafinasi elevasi hover |
| Ikon | bootstrap-icons 1.11.3 | **Pertahankan** (gaya Klook pakai ikon outline solid, bootstrap-icons cukup dekat) |

### 1.2 Header / Navigasi

| Aspek | Current (`includes/header.php`) | Target |
|---|---|---|
| Struktur | 2 navbar bertumpuk di `div.sticky-top` | **1 navbar** saja: putih bersih, sticky dengan shadow saat scroll (gaya Klook) |
| Logo | `<i bi-airplane-engines-fill> SITE_NAME`, biru di `bg-primary` | Logo di kiri, teks **brand biru** di atas putih; tagline kecil `hidden md:block` |
| Search | `.nav-search-wrapper` **hidden**, muncul saat scroll, max-width 400px | **Selalu terlihat** di tengah, pill `rounded-full bg-light`, icon search kiri |
| Menu utama | Dropdown "Layanan" (Tour/Pesawat/Ferry/Rental) + baris ke-2 link Beranda/Tour/Pesawat/Ferry/Rental | **Flat links** di baris navbar: Beranda · Tour · Hotel · Pesawat · Ferry · Rental (+ kategori ekspandable "Lainnya") — **hapus navbar baris 2** |
| Link Hotel | Tidak ada di navbar (hanya via dropdown Layanan? tdk ada — hotel hanya dari index/footer) | **Tambahkan** item Hotel di menu |
| Wishlist | Icon heart (wishlist.php) | Pertahankan, badge count (JS) bila login |
| User | Guest: link "Masuk". Login: dropdown Profil/Booking Saya/Wishlist/Keluar | Guest: tombol **Daftar** (outline) + **Masuk** (solid blue pill). Login: avatar circle inisial + dropdown |
| Currency | Dropdown icon + label (IDR/SGD/USD) | Compact: **"IDR ▾"** single dropdown |
| Language | Dropdown icon translate + ID/EN | Compact: **"🌐 ID ▾"** |
| Mobile | `navbar-toggler` collapse biasa | **Off-canvas** menu + search bar di baris ke-2 mobile |

**File**: `includes/header.php`, `assets/css/style.css`, `assets/js/script.js`
**⚠️ E2E yang menyentuh struktur navbar**: `nav-crawl.spec.ts` (8), `smoke-public.spec.ts` (7), `language-switch/language-switcher/multilingual/tour-content-*` (~24) — selector lang/currency di navbar harus tetap ada (`#navbarNav`, `.dropdown-toggle`, `i.bi-translate`), sebagian besar aman jika id/class lama dipertahankan.

### 1.3 Hero (index.php)

| Aspek | Current | Target |
|---|---|---|
| Tinggi | `min-height: 80vh` | `60–65vh` lebih kompak |
| Media | Video `backgroundvideo.mp4` fade + fallback Unsplash | **Carousel gambar** (Bootstrap carousel-fade, 3 slide dari tabel/array `hero_slides`) + overlay gradient gelap kiri→kanan |
| Headline | `Your World of Joy` (sudah ala Klook) | Pertahankan + subhead; alignment kiri |
| Search box | Kotak putih `rounded-4 p-2 shadow-lg`, max-width 600px, 1 input + tombol Cari | **Kotak putih besar full-kontainer** (max-w 720px): row 3 kolom = input destinasi (dgn autocomplete existing) · date range (2 date input atau label "Pilih Tanggal") · tombol **Search pill biru** |
| Quick category chips | Pills outline putih | Pertahankan, lebih kecil |
| Stats bar (150+ paket dst) | Section terpisah `bg-white border-bottom` | **Dipertahankan** tapi dirapikan (hidden di mobile) |

**File**: `index.php`, `assets/css/style.css`, `assets/js/script.js`, DB: tabel baru `hero_slides`

### 1.4 Kartu Aktivitas/Tour (blok terkecil, dipakai berulang)

| Aspek | Current (`.tour-card-klook` di index/tours/destinasi/wishlist) | Target |
|---|---|---|
| Image | `height:180px` fixed | `aspect-ratio: 4/3` (atau 3/2), zoom subtle saat hover (`img scale 1.05`), skeleton bg |
| Badge | Diskon `bg-danger` + kategori `bg-white text-dark` (duplikat 2x di index!) | Badge Klook: **Best Seller** (biru), **Promo −X%** (merah), **Instant Confirmation** (hijau), **Free Cancellation** (biru muda) — 1 posisi kiri-atas |
| Wishlist | `.like-btn` opacity 0 → muncul hover | **Selalu tampil** lingkaran putih di kanan-atas; filled `text-danger` bila wishlisted |
| Rating | `renderStars()` + `(count)` | Baris: `★ 4.8 (2.147)` — bold angka, muted reviews |
| Judul | `text-truncate` 1 baris | **2 baris line-clamp** (CSS `-webkit-line-clamp`) |
| Lokasi/durasi | 1 icon clock + tanggal | Icon `geo-alt` kota + icon `clock` durasi hari |
| Harga | `formatRupiah(price)` + `/org` + coret original | **"Dari Rp…"** bold + strikethrough original + `/orang` muted |
| CTA | Tombol kecil `btn-primary rounded-pill "Pesan"` | Hapus tombol (seluruh kartu `clickable` → detail) — Klook tidak pakai tombol di kartu; `$` harga jadi anchor |
| Link | Hanya tombol yang link | **Seluruh kartu** `<a>` stretch (`stretched-link`) |

**File**: `index.php` + `tours.php` + `wishlist.php` + `destinasi.php` (pindah ke komponen `renderTourCard()`), `assets/css/style.css`
**⚠️ E2E**: `tours-filter.spec.ts` (12), `hotels.spec.ts` (15, selector `span.fw-bold.text-primary.fs-5`), `rental-cars/ferries/flights/destinasi` specs mengandalkan `.tour-card-klook` + `.btn-primary` "Pesan" → **DAFTAR TEST PERLU UPDATE SELECTOR** (±40).

### 1.5 Halaman Katalog (tours/hotels/flights/ferries/rental-cars)

| Aspek | Current | Target |
|---|---|---|
| Header halaman | Judul + desc | Judul + breadcrumb + count "X aktivitas" |
| Filter | Form row select horizontal (kategori/harga/durasi/rating/sort) | **Sidebar kiri sticky** (desktop): checkbox kategori, rentang harga (slider/select), durasi, rating bintang; **topbar** untuk sort + result count. Mobile: collapse |
| Pencarian | Input search + dropdown autocomplete | Pertahankan di topbar |
| Grid | `col-md-6 col-lg-4` | `col-md-6 col-lg-4` dipertahankan (3 kolom nyaman utk konten lokal) |
| Pagination | `ul.pagination` | Pertahankan, + "menampilkan X–Y dari Z" |
| Empty state | Icon + text + link | Icon ilustrasi + text + tombol **Reset Filter** |

**File**: `tours.php`, `hotels.php`, `flights.php`, `ferries.php`, `rental-cars.php`, `destinasi.php`
**⚠️ E2E**: `tours-sadpath.spec.ts` (empty state & pagination clamp) — selector form `select.form-select` akan berubah posisi; perlu update ±30 selector test.

### 1.6 Halaman Detail (tour/hotel/flight/rental/ferry)

| Aspek | Current | Target |
|---|---|---|
| Galeri tour | 1 cover + thumbnail + modal carousel | **Grid galeri 1 besar + 3 kecil** di kiri, klik → lightbox (modal existing dipertahankan id `galleryModal`) |
| Breadcrumb | ada | Pertahankan + tombol share |
| Konten | deskripsi/fasilitas/map/itinerary timeline/review list + form | **Tab atau seksi rapi**: deskripsi · itinerary **accordion** · fasilitas icon-grid · **map iframe Google** · **review ringkasan** (bintang besar + bar distribusi 5→1) + form review |
| Sidebar booking (tour) | sticky card: harga, pilih tanggal (`.date-item`), form isi data, total | **Sidebar putih ber-shadow**, harga besar + "Dari", pilih tanggal, jumlah peserta stepper (− 1 +), total **update real-time JS**, tombol **"Pesan Sekarang"** besar pill, catatan "Konfirmasi instan · Pembatalan gratis" |
| Form field id | `bookingSubmitBtn`, `tourDateId`, dsb | **Pertahankan semua id/name** (E2E & handler PHP tergantung) |
| Hotel | gallery single + sidebar form checkin/checkout/rooms + total JS `updateTotal()` | Layout ala Klook: header foto besar, info + fasilitas grid, **peta**, sidebar sticky "Pilih tanggal & kamar", total real-time; id/name form dipertahankan |
| Review | list + form | Ringkasan rating + filter bintang + kartu review |

**File**: `tour-detail.php`, `hotel-detail.php`, `flight-detail.php`, `rental-car-detail.php` (+ `ferries.php` link), CSS/JS
**⚠️ E2E**: `tour-detail.spec.ts` (9), `tour-detail-sadpath.spec.ts` (7), `hotel-detail.spec.ts` (17), `rental-car-detail.spec.ts` (11), `review.spec.ts` (8) — **pertahankan id form & alur POST** agar body handler PHP tidak berubah; hanya tata letak yang berubah → banyak selector layout berubah, logika test booking tetap valid.

### 1.7 Halaman Pengguna (profile/my-bookings/wishlist/track/booking-success)

| Aspek | Current | Target |
|---|---|---|
| booking-success | card check hijau + tabel detail | **Animasi confetti ringan** (CSS/JS) + panel booking code biru besar + tombol "Lacak" + detail; id `.tracking-code` dipertahankan |
| track | stepper 4 langkah + tabel | Timeline stepper dipercantik (connector aktif biru) + status badge; struktur `.rounded-circle.bg-*` disempurnakan tapi text "pending/confirmed/cancelled" tetap |
| my-bookings | kartu booking | Kartu dengan **foto tour mini** (join tours), status badge warna, tombol lacak |
| profile | form 1 kolom | Form 2 kolom + avatar + ringkasan statistik (jumlah booking) |

**File**: `booking-success.php`, `track.php`, `my-bookings.php`, `wishlist.php`, `profile.php`, `login.php`, `register.php`
**⚠️ E2E**: `booking-success.spec.ts` (9), `track.spec.ts` (9), `user-pages.spec.ts` (13), `local-auth.spec.ts` (3) — teks kunci (`booking_code`, label sukses) dipertahankan.

### 1.8 Footer

| Aspek | Current | Target |
|---|---|---|
| Kolom | 3 kolom (brand/kontak/sosmed) | **4 kolom**: brand+deskripsi+sosmed · Layanan (link cepat) · Bantuan (FAQ/Kontak/Track) · Kontak. Latar tetap gelap/primary gelap |
| Newsletter | ✗ | Baris **subscribe email** di atas kolom (input + tombol) — simpan ke tabel `newsletter_subscribers` via `ajax/newsletter-ajax.php` |
| Badges | ✗ | Baris metode bayar (icons text: Visa/MC/Transfer) + "Powered by" |

**File**: `includes/footer.php`, `assets/css/style.css`, `assets/js/script.js`, DB tabel `newsletter_subscribers`

### 1.9 Admin (tetap fungsional, fresh look)

| Aspek | Current | Target |
|---|---|---|
| Sidebar | `#adminSidebar.bg-dark.sidebar.p-3` static | **Collapsible** (toggle collapse lebar 56px icon-only) + responsive off-canvas mobile (CSS sudah ada sebagian) |
| Dashboard | Stat cards + tabel recent | KPI cards dengan icon + **trend arrow**; tetap 1 tabel |
| Tabel CRUD | Bootstrap table | `table` dirapikan: sticky header, badge status, tombol action icon |
| Form | Bootstrap default | Card + konsistensi spacing, tetap name field sama |

**File**: `admin/includes/admin-header.php`, `admin/includes/admin-footer.php`, `admin/dashboard.php`, admin CRUD (`tours.php`, `tour-add.php`, `tour-edit.php`, dst)
**⚠️ E2E**: `admin-guard.spec.ts` (20) & `admin-*.spec.ts` (~58) — **jaga id/name form, href, struktur tabel** agar minimal update.

---

## 2. Feature Gap Analysis: Klook vs TourAndTravel

### 2.1 Product & Service Categories

| Feature | Klook | TourAndTravel | Status |
|---|---|---|---|
| **Tours & day trips** | ✅ Guided, private, group, adventure, workshop, spa, dining | ✅ `tours` table + itinerary + booking | **Ada** |
| **Attraction tickets** | ✅ Theme parks, museums, shows, Klook Pass bundles | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Hotels / Stays** | ✅ Hotel booking + staycation packages | ✅ `hotels` + `hotel_bookings` | **Ada** (tanpa staycation) |
| **Flights** | ✅ Domestic & international | ✅ `flights` + Duffel API | **Ada** |
| **Trains / Rail** | ✅ Rail passes (JR Pass) + point-to-point tickets | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Car rental** | ✅ Local & global agencies | ✅ `rental_cars` + booking | **Ada** |
| **Airport transfers** | ✅ Private/shared airport transfer, point-to-point car | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Ferries & Cruises** | ✅ Regional ferry + cruise | ✅ `ferries` + Easybook API | **Ada** (tanpa cruise) |
| **eSIM / SIM / WiFi** | ✅ eSIM, physical SIM, portable WiFi rental | ❌ Tidak ada | **HARUS DITAMBAH** |

### 2.2 Search & Discovery

| Feature | Klook | TourAndTravel | Status |
|---|---|---|---|
| **Keyword search** | ✅ Auto-suggest + recent history | ✅ `search-ajax.php` autocomplete | **Ada** |
| **Filter by price/category/duration/rating** | ✅ | ✅ `getTours()` params | **Ada** |
| **Filter by amenities** | ✅ Free cancellation, instant confirmation | ❌ Tidak ada kolom | **HARUS DITAMBAH** (kolom badge) |
| **Curated collections** | ✅ "Best Sellers", "Hidden Gems" | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Personalized recommendations** | ✅ Based on browsing/past bookings | ❌ Tidak ada | **Nice to have** |
| **Price comparison** | ✅ Original vs discounted | ✅ `getDiskonPersen()` + `original_price` | **Ada** |

### 2.3 Booking & Checkout Flow

| Feature | Klook | TourAndTravel | Status |
|---|---|---|---|
| **Instant confirmation** | ✅ After payment | ❌ Tidak ada kolom/sistem | **HARUS DITAMBAH** |
| **Open-dated vouchers** | ✅ Flexible date validity | ❌ Tidak ada | **Nice to have** |
| **Date/time selection** | ✅ Real-time availability calendar | ✅ `.date-item` + `tour_dates` | **Ada** |
| **Package options** | ✅ Multiple tiers (Basic/Premium) | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Traveler details form** | ✅ Passport, pickup location | ✅ `tour-detail.php` (name/email/phone/participants) | **Ada** (minimal) |
| **Guest checkout** | ✅ Book without account | ❌ Guard `isLoggedIn()` pada booking | **HARUS DITAMBAH** (opsional) |
| **Multi-currency PAYMENT** | ✅ 40+ currencies | ❌ Hanya display (IDR/SGD/USD), bayar di IDR | **HARUS DITAMBAH** |
| **Promo codes / vouchers** | ✅ Discount code field at checkout | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Add-on services** | ✅ Insurance, extra baggage | ❌ Tidak ada | **Nice to have** |

### 2.4 User Account & Loyalty

| Feature | Klook | TourAndTravel | Status |
|---|---|---|---|
| **User registration** | ✅ Email/social login | ✅ `register.php` + `users` table | **Ada** |
| **Booking history** | ✅ Current/past/cancelled | ✅ `my-bookings.php` | **Ada** |
| **Wishlist / Saved** | ✅ Heart icon + dedicated page | ✅ `wishlist.php` + `wishlists` table | **Ada** |
| **KlookCash / Wallet** | ✅ Earn credits from bookings, use as discount | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Loyalty program / tiers** | ✅ Explorer (Standard) + Joy+ (Gold) | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Referral program** | ✅ Refer friend → both get credits | ❌ Hanya placeholder text di index.php | **HARUS DITAMBAH** |
| **Voucher management** | ✅ QR code, offline access, digital redemption | ❌ Tidak ada (booking_code saja) | **HARUS DITAMBAH** |
| **Self-service cancellation** | ✅ Manage refunds directly | ❌ Tidak ada (hanya admin yg cancel) | **HARUS DITAMBAH** |
| **Rescheduling** | ✅ Change dates for eligible bookings | ❌ Tidak ada | **Nice to have** |
| **Review system** | ✅ Star + photo/video + earn credits | ✅ `review-submit.php` + `canReview()` | **Ada** (tanpa foto/earn) |

### 2.5 Post-Booking & Support

| Feature | Klook | TourAndTravel | Status |
|---|---|---|---|
| **Booking success page** | ✅ Confetti + booking code + voucher | ✅ `booking-success.php` | **Ada** |
| **Track booking** | ✅ Timeline status | ✅ `track.php` stepper | **Ada** |
| **Voucher QR code** | ✅ Digital redemption QR | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Email notification** | ✅ Booking confirmation + voucher | ❌ Tidak ada (WA only via `sendWA()`) | **HARUS DITAMBAH** |
| **24/7 Live chat** | ✅ AI + human support | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Help Center / FAQ** | ✅ Extensive FAQ database | ❌ Tidak ada | **HARUS DITAMBAH** |

### 2.6 Technical & Mobile

| Feature | Klook | TourAndTravel | Status |
|---|---|---|---|
| **Multi-language** | ✅ 10+ languages | ✅ `id`/`en` via `translations` table | **Ada** (terbatas) |
| **Multi-currency display** | ✅ 40+ currencies | ✅ IDR/SGD/USD via `getExchangeRates()` | **Ada** (terbatas) |
| **Search autocomplete** | ✅ | ✅ `search-ajax.php` + `initSearchAutocomplete()` | **Ada** |
| **Newsletter signup** | ✅ Email subscription | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Price alerts** | ✅ Notifikasi harga turun | ❌ Tidak ada | **Nice to have** |
| **Trip planner** | ✅ Organize bookings into daily itinerary | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Apple/Google Wallet** | ✅ Add tickets to mobile wallet | ❌ Tidak ada | **Nice to have** |
| **Maps & navigation** | ✅ Integrated map: "Activities Near Me" | ✅ Static map di tour-detail | **Ada** (minimal) |
| **PWA / Mobile app** | ✅ Native app iOS + Android + PWA | ❌ Tidak ada | **Nice to have** |

### 2.7 Admin / Operations

| Feature | Klook | TourAndTravel | Status |
|---|---|---|---|
| **Admin dashboard** | ✅ KPI + bookings | ✅ `admin/dashboard.php` | **Ada** |
| **CRUD produk** | ✅ Tours, hotels, etc | ✅ Semua admin CRUD | **Ada** |
| **Booking management** | ✅ Filter status, update, cancel | ✅ `admin/bookings.php` | **Ada** |
| **Promo code management** | ✅ Create/discount codes | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Loyalty/points management** | ✅ Configure earning rates | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Newsletter management** | ✅ Broadcast email | ❌ Tidak ada | **HARUS DITAMBAH** |
| **Currency settings** | ✅ | ✅ `admin/currency-settings.php` | **Ada** |
| **WA notification** | ✅ | ✅ `admin/wa-settings.php` | **Ada** |

---

## 3. Fitur Baru yang Harus Ditambahkan (Prioritas)

Berdasarkan gap analysis di atas, berikut fitur yang harus ditambahkan ke kodebase, diurutkan berdasarkan prioritas:

### 🟥 P0 — Core (harus ada, bagian dari MVP Klook-like)
| # | Fitur | Tabel/Komponen Baru | File |
|---|---|---|---|
| P0.1 | **Attraction tickets** (tiket tempat wisata) | `attractions` table, `attraction_bookings` | `attractions.php`, `attraction-detail.php`, `admin/attractions.php`, `admin/attraction-edit.php` |
| P0.2 | **Airport transfers** | `transfers` table, `transfer_bookings` | `transfers.php`, `transfer-detail.php`, `admin/transfers.php`, `admin/transfer-edit.php` |
| P0.3 | **Guest checkout** (booking tanpa login) | Opsional field `user_id` nullable di bookings (sudah), ubah guard `isLoggedIn()` jadi warning bukan block | `tour-detail.php`, `hotel-detail.php`, `rental-car-detail.php` |
| P0.4 | **Promo codes / vouchers** | `promo_codes` table, `booking_discounts` | `checkout-apply-promo.php`, `admin/promo-codes.php` |
| P0.5 | **Instant confirmation + Free cancellation badges** | Kolom `instant_confirmation`, `free_cancellation` di `tours`/`hotels` | Semua kartu + detail |
| P0.6 | **Newsletter signup** (footer) | `newsletter_subscribers` table | `includes/footer.php`, `newsletter-ajax.php` |

### 🟧 P1 — Loyalty & Engagement
| # | Fitur | Tabel/Komponen Baru | File |
|---|---|---|---|
| P1.1 | **KlookCash / Wallet system** | `wallet_transactions` table, `user_wallet` | `profile.php`, `my-bookings.php`, `wallet.php` |
| P1.2 | **Loyalty tiers** | `user_tiers` (kolom di users), `tier_benefits` | `profile.php`, `admin/loyalty-settings.php` |
| P1.3 | **Referral program** | `referrals` table, referral code di users | `register.php`, `referral.php`, `profile.php` |
| P1.4 | **Self-service cancellation** | `cancellation_requests` table | `my-bookings.php`, `cancel-booking.php` |
| P1.5 | **Curated collections** | `collections` table, `collection_items` | index.php (section baru), `collection.php` |

### 🟨 P2 — Transport & Connectivity
| # | Fitur | Tabel/Komponen Baru | File |
|---|---|---|---|
| P2.1 | **Trains / Kereta api** | `trains` table, `train_bookings` | `trains.php`, `train-detail.php`, `admin/trains.php` |
| P2.2 | **eSIM / SIM / WiFi rental** | `connectivity_products` table | `esim.php`, `admin/esim.php` |

### 🟩 P3 — Support & Post-Booking
| # | Fitur | Tabel/Komponen Baru | File |
|---|---|---|---|
| P3.1 | **Help Center / FAQ** | `faq_categories`, `faq_items` | `faq.php`, `admin/faq.php` |
| P3.2 | **Live chat widget** | Third-party (Tawk.to / WhatsApp Business API) | `includes/footer.php` |
| P3.3 | **Email notification** | PHPMailer / mail() | `includes/send-email.php` |
| P3.4 | **Voucher QR code** | QR code generator (phpqrcode library) | `booking-success.php`, `my-bookings.php` |
| P3.5 | **Trip planner** | `trip_plans` table, `trip_plan_items` | `my-trip.php`, `my-bookings.php` |

### 🟦 P4 — Monetization & Admin
| # | Fitur | Tabel/Komponen Baru | File |
|---|---|---|---|
| P4.1 | **Admin promo codes management** | CRUD untuk promo_codes | `admin/promo-codes.php` |
| P4.2 | **Admin loyalty settings** | Tier earning rates, benefits | `admin/loyalty-settings.php` |
| P4.3 | **Admin newsletter broadcast** | Broadcast email ke subscribers | `admin/newsletter.php` |
| P4.4 | **Admin collections management** | CRUD collections | `admin/collections.php` |

---

## 4. Perubahan Database / Schema (additive, aman) — DIPERBARUI

```sql
-- database/schema-klook.sql (dijalankan manual/php seeder)
-- 1) Kolom badge & informasi tambahan tours
ALTER TABLE tours
  ADD COLUMN IF NOT EXISTS duration_days INT DEFAULT NULL AFTER max_participants,
  ADD COLUMN IF NOT EXISTS duration_nights INT DEFAULT NULL AFTER duration_days,
  ADD COLUMN IF NOT EXISTS location_city VARCHAR(100) DEFAULT NULL AFTER category,
  ADD COLUMN IF NOT EXISTS instant_confirmation TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS free_cancellation TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS best_seller TINYINT(1) NOT NULL DEFAULT 0;

-- 2) Hotel: peta & amenities ringkas
ALTER TABLE hotels
  ADD COLUMN IF NOT EXISTS lat DECIMAL(10,7) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS lng DECIMAL(10,7) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS amenities VARCHAR(500) DEFAULT NULL;   -- comma list

-- 3) Hero slides untuk carousel landing
CREATE TABLE IF NOT EXISTS hero_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  image_url VARCHAR(255) NOT NULL,
  title VARCHAR(200) DEFAULT NULL,
  subtitle VARCHAR(200) DEFAULT NULL,
  cta_text VARCHAR(50) DEFAULT NULL,
  cta_link VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Newsletter
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Attractions / Tiket tempat wisata (P0)
CREATE TABLE IF NOT EXISTS attractions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  city VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(12,2) NOT NULL,
  price_currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  cover_image VARCHAR(255) DEFAULT NULL,
  category VARCHAR(100) DEFAULT NULL,
  duration VARCHAR(50) DEFAULT NULL,
  instant_confirmation TINYINT(1) NOT NULL DEFAULT 1,
  free_cancellation TINYINT(1) NOT NULL DEFAULT 0,
  best_seller TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attraction_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attraction_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  visit_date DATE NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  total_price DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  booking_code VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attraction_id) REFERENCES attractions(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Airport Transfers (P0)
CREATE TABLE IF NOT EXISTS transfers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  from_city VARCHAR(100) NOT NULL,
  to_city VARCHAR(100) NOT NULL,
  from_type ENUM('airport','city','port','hotel') NOT NULL DEFAULT 'airport',
  to_type ENUM('airport','city','port','hotel') NOT NULL DEFAULT 'city',
  price DECIMAL(12,2) NOT NULL,
  price_currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
  vehicle_type VARCHAR(50) DEFAULT NULL,
  max_passengers INT NOT NULL DEFAULT 4,
  description TEXT,
  instant_confirmation TINYINT(1) NOT NULL DEFAULT 1,
  free_cancellation TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transfer_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transfer_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  pickup_date DATE NOT NULL,
  pickup_time TIME NOT NULL,
  pickup_location VARCHAR(255) NOT NULL,
  dropoff_location VARCHAR(255) DEFAULT NULL,
  flight_number VARCHAR(20) DEFAULT NULL,
  passengers INT NOT NULL DEFAULT 1,
  total_price DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  booking_code VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (transfer_id) REFERENCES transfers(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7) Promo Codes (P0)
CREATE TABLE IF NOT EXISTS promo_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  discount_value DECIMAL(12,2) NOT NULL,
  min_purchase DECIMAL(12,2) DEFAULT NULL,
  max_discount DECIMAL(12,2) DEFAULT NULL,
  usage_limit INT DEFAULT NULL,
  used_count INT NOT NULL DEFAULT 0,
  valid_from DATE NOT NULL,
  valid_until DATE NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8) Wallet / Loyalty (P1)
CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  type ENUM('earn','spend','refund','bonus') NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  reference_type VARCHAR(50) DEFAULT NULL,
  reference_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9) Referrals (P1)
ALTER TABLE users ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) DEFAULT NULL UNIQUE AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS referred_by INT DEFAULT NULL AFTER referral_code;

CREATE TABLE IF NOT EXISTS referrals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  referrer_id INT NOT NULL,
  referred_email VARCHAR(150) NOT NULL,
  referred_user_id INT DEFAULT NULL,
  status ENUM('pending','completed','rewarded') NOT NULL DEFAULT 'pending',
  reward_amount DECIMAL(12,2) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10) Collections (P1)
CREATE TABLE IF NOT EXISTS collections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  description TEXT,
  cover_image VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  collection_id INT NOT NULL,
  item_type ENUM('tour','attraction','hotel','transfer') NOT NULL,
  item_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11) FAQ (P3)
CREATE TABLE IF NOT EXISTS faq_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS faq_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (category_id) REFERENCES faq_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Semua kolom **nullable/ber-DEFAULT** → data lama tetap jalan; PHP pakai `?? fallback`. (`IF NOT EXISTS` utk kolom butuh MySQL 8 / MariaDB 10.4+; bila XAMPP MariaDB lebih lama → jalankan ALTER manual + tangani error duplicate column.)

---

## 5. Strategi Migrasi — Tidak Memutus 314 E2E Tests

**Prinsip**: additive dulu, swap belakangan, `id`/`name`/teks-kunci dipertahankan; setiap fase = commit + `npx playwright test` hijau sebelum lanjut.

### Fase 0 — Fondasi (0 test break)
- Design tokens + font + komponen system.
- **Test**: NONE.

### Fase 1 — Komponen reusable (0 test break)
- `includes/components/` : `tour-card.php` (`renderTourCard($tour,$wishlist)`), `hero-search.php`, `category-grid.php`, `dest-card.php`, `pagination.php`, `rating-stars.php`, `price.php`, `breadcrumb.php`, `badge.php` (best-seller/instant/free-cancel).
- Mulai dipakai di halaman BARU/layout baru sambil halaman lama tetap utuh (dual-run sementara jika perlu).
- **Test**: NONE.

### Fase 2 — Header & landing (≈40 test update)
- Swap `includes/header.php` + `includes/footer.php` (konten dipertahankan, markup baru), hero carousel, kartu komponen.
- **Perlu ubah selector test**: `nav-crawl` (urutan link), `smoke-public` (heading), `language-*` (posisi dropdown, id dipertahankan → banyak tetap lolos), `.tour-card-klook` selector di index → jika komponen memakai class baru, update spec `tours-filter/hotels` dll. Alternatif: **komponen tetap menyertakan class lama** `.tour-card-klook` + tambah class baru → banyak test lolos tanpa edit.
- **Verifikasi**: jalankan subset + update test.

### Fase 3 — Katalog & filter sidebar (≈40 test update)
- Grid 3 kolom + sidebar filter (desktop). **Pertahankan** seluruh `name` select existing (`category/search/price/duration/rating/sort/page`) supaya `getTours()` PHP & query param tidak berubah → test sadpath & filter *logic* tetap hijau; hanya selector DOM yang disesuaikan.

### Fase 4 — Halaman detail (≈50 test update)
- Layout baru; **wajib pertahankan**: id form booking (`bookingSubmitBtn`, input name), `.date-item`, `.tracking-code`, pesan error PHP (`$bookingError`), alur POST → handler PHP tidak tersentuh.

### Fase 5 — Halaman pengguna & auth (≈25 test update)
- Card layout baru; pertahankan teks & href.

### Fase 6 — Admin (≈60 test update)
- Refresh layout; pertahankan `name` form admin, href, struktur `<table>`/badge status.

### Fase 7 — Polish + Regression
- Responsive (breakpoint), aksesibilitas, micro-animasi, skeleton.
- Jalankan **seluruh** 314 + spec baru (hero-carousel, newsletter) → target **semua hijau**.

**Aturan emas agar E2E tetap hijau**: (a) jangan ubah `name`/`id` form & query param; (b) pertahankan class CSS lama di samping class baru (`tour-card-klook` tetap ada); (c) teks kunci tetap; (d) jalankan `npx playwright test` per fase.

---

## 6. Tugas per Fase (files) — DIPERBARUI (dengan fitur baru)

### Fase 0: Fondasi (0 test break)

| # | Task | Files |
|---|------|-------|
| 0.1 | Design tokens: CSS variables + Google Fonts (Inter) | `assets/css/design-tokens.css` (baru), `assets/css/style.css`, `includes/header.php` |

### Fase 1: Komponen Reusable (0 test break)

| # | Task | Files |
|---|------|-------|
| 1.1 | Folder `includes/components/` + helper render functions (tour-card, hero-search, category-grid, dest-card, pagination, rating-stars, price, breadcrumb, badge) | `includes/components/*.php` (baru) |

### Fase 2: Header + Footer + Landing Page (≈40 test update)

| # | Task | Files |
|---|------|-------|
| 2.1 | Header navbar 1-baris Klook | `includes/header.php` |
| 2.2 | Hero carousel + search panel | `index.php`, `assets/js/klook.js` (baru) |
| 2.3 | Category grid horizontal + komponen kartu | `index.php` |
| 2.4 | Footer 4 kolom + **newsletter signup** | `includes/footer.php`, `newsletter-ajax.php` (baru) |
| 2.5 | DB: schema-klook.sql + seeder | `database/schema-klook.sql`, `database/seed-klook-ui.php` (baru) |
| 2.6 | Update test fase 2 | `tests/e2e/{smoke-public,nav-crawl,language-*}.spec.ts` |

### Fase 3: Katalog Pages (≈40 test update)

| # | Task | Files |
|---|------|-------|
| 3.1 | Sidebar filter + grid tours | `tours.php` |
| 3.2 | Hotels, flights, ferries, rental-cars, destinasi | `hotels.php`, `flights.php`, `ferries.php`, `rental-cars.php`, `destinasi.php` |
| 3.3 | Update test fase 3 | `tests/e2e/{tours-filter,tours-sadpath,hotels,flights,ferries,rental-cars,destinasi}.spec.ts` |

### Fase 4: Detail Pages (≈50 test update)

| # | Task | Files |
|---|------|-------|
| 4.1 | Tour detail: gallery grid, accordion itinerary, review summary, sidebar total JS | `tour-detail.php` |
| 4.2 | Hotel detail: map, fasilitas, sidebar sticky | `hotel-detail.php` |
| 4.3 | Flight/rental detail layout | `flight-detail.php`, `rental-car-detail.php` |
| 4.4 | Update test fase 4 | `tests/e2e/{tour-detail,tour-detail-sadpath,hotel-detail,rental-car-detail,review}.spec.ts` |

### Fase 5: User Pages (≈25 test update)

| # | Task | Files |
|---|------|-------|
| 5.1 | booking-success + track polish | `booking-success.php`, `track.php` |
| 5.2 | my-bookings/wishlist/profile/login/register | `my-bookings.php`, `wishlist.php`, `profile.php`, `login.php`, `register.php` |
| 5.3 | Update test fase 5 | `tests/e2e/{booking-success,track,user-pages,local-auth}.spec.ts` |

### Fase 6: Admin Panel (≈60 test update)

| # | Task | Files |
|---|------|-------|
| 6.1 | Admin header/sidebar + dashboard KPI | `admin/includes/admin-header.php`, `admin/dashboard.php` |
| 6.2 | Tabel & form admin styling | All admin CRUD files |
| 6.3 | **Admin promo codes + collections + loyalty settings** | `admin/promo-codes.php` (baru), `admin/collections.php` (baru), `admin/loyalty-settings.php` (baru) |
| 6.4 | **Admin newsletter management** | `admin/newsletter.php` (baru) |
| 6.5 | Update test fase 6 | `tests/e2e/admin-*.spec.ts` |

### Fase 7: Fitur Baru P0 (Attractions, Transfers, Promo, Guest Checkout)

| # | Task | Files |
|---|------|-------|
| 7.1 | **Attractions** (tiket tempat wisata) — CRUD + catalog + detail + booking | `attractions.php` (baru), `attraction-detail.php` (baru), `admin/attractions.php` (baru), `admin/attraction-edit.php` (baru), `includes/functions.php` |
| 7.2 | **Airport transfers** — CRUD + catalog + detail + booking | `transfers.php` (baru), `transfer-detail.php` (baru), `admin/transfers.php` (baru), `admin/transfer-edit.php` (baru) |
| 7.3 | **Promo codes** — checkout integrasi dengan diskon | `apply-promo-ajax.php` (baru), `tour-detail.php`, `hotel-detail.php`, `rental-car-detail.php` |
| 7.4 | **Guest checkout** — ubah guard `isLoggedIn()` jadi warning, izinkan booking tanpa login | `tour-detail.php`, `hotel-detail.php`, `rental-car-detail.php` |
| 7.5 | **Instant confirmation + Free cancellation badges** di kartu | `includes/components/tour-card.php`, `tours.php`, `hotels.php`, dll |
| 7.6 | **Curated collections** (Best Sellers, Flash Deals) | `collection.php` (baru), `index.php` |
| 7.7 | E2E tests untuk fitur baru | `tests/e2e/attractions.spec.ts`, `tests/e2e/transfers.spec.ts`, `tests/e2e/promo-codes.spec.ts`, `tests/e2e/collections.spec.ts` |

### Fase 8: Fitur Baru P1 (Wallet, Loyalty, Referral, Cancellation)

| # | Task | Files |
|---|------|-------|
| 8.1 | **Wallet / KlookCash** — earn from bookings, spend at checkout | `wallet.php` (baru), `includes/wallet.php` (baru), `profile.php`, `my-bookings.php` |
| 8.2 | **Loyalty tiers** — Explorer + Joy+, earning rates | `admin/loyalty-settings.php` (baru), `includes/functions.php` |
| 8.3 | **Referral program** — referral code, pending/rewarded | `referral.php` (baru), `register.php`, `profile.php` |
| 8.4 | **Self-service cancellation** — cancel booking from my-bookings | `cancel-booking.php` (baru), `my-bookings.php` |
| 8.5 | E2E tests | `tests/e2e/wallet.spec.ts`, `tests/e2e/referral.spec.ts`, `tests/e2e/cancellation.spec.ts` |

### Fase 9: Fitur Baru P2 (Trains, eSIM, FAQ)

| # | Task | Files |
|---|------|-------|
| 9.1 | **Trains** — jadwal + booking | `trains.php` (baru), `train-detail.php` (baru), `admin/trains.php` (baru), `admin/train-edit.php` (baru) |
| 9.2 | **eSIM / WiFi** — connectivity products | `esim.php` (baru), `admin/esim.php` (baru) |
| 9.3 | **FAQ / Help Center** — categories + items | `faq.php` (baru), `admin/faq.php` (baru) |
| 9.4 | **Live chat widget** — integrasi | `includes/footer.php` |
| 9.5 | E2E tests | `tests/e2e/trains.spec.ts`, `tests/e2e/esim.spec.ts`, `tests/e2e/faq.spec.ts` |

### Fase 10: Final Polish & Regression

| # | Task | Files |
|---|------|-------|
| 10.1 | Responsive + micro-animations + a11y | `assets/css/*`, `assets/js/*` |
| 10.2 | Full regression + spec baru | semua spec + `tests/e2e/klook-home.spec.ts` (baru) |
| 10.3 | `npx playwright test` → **all hijau** | — |

---

## 7. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| E2E selector massal berubah | 100+ test merah sementara | Migrasi bertahap + retain class lama + update test per fase, bukan sekali jalan |
| Video hero (backgroundvideo.mp4, 10MB+ di repo) | Load lambat | Ganti carousel gambar; file mp4 bisa dihapus dari repo bila disetujui |
| Sidebar filter ubah perilaku query | Sad-path/akuntansi test merah | **Pertahankan nama query param** — hanya posisi DOM berubah |
| Override Bootstrap bisa konflik CDN | Warna/komponen aneh | Override lewat `:root`/CSS variables & `!important` hemat; test visual manual |
| Komponen baru belum dipakai semua halaman | Duplikasi markup sementara | Refactor per halaman saat fase masing-masing; hapus markup inline |
| Gambar unsplash/placehold sering 403 | Hero & kartu kosong | Ganti ke URL picsum/upload lokal (`uploads/wiki` ada) + `onerror` fallback |
| MariaDB lama tanpa `ADD COLUMN IF NOT EXISTS` | Migrasi SQL error | Seeder PHP (`seed-klook-ui.php`) cek `information_schema` lalu ALTER manual |

---

## 8. Komponen & Aset Baru (ringkas)

```
includes/components/{tour-card,hero-search,category-grid,dest-card,pagination,rating-stars,price,breadcrumb,badge}.php
assets/css/design-tokens.css, klook.css (komponen baru, opsional gabung style.css)
assets/js/klook.js            (carousel hero, total JS, confetti sukses, newsletter)
database/schema-klook.sql, database/seed-klook-ui.php
newsletter-ajax.php
tests/e2e/klook-home.spec.ts  (test baru hero carousel + newsletter)
```

---

## 9. Kriteria Selesai (Acceptance) — DIPERBARUI

- [ ] Layout & komponen tiap halaman mendekati 1:1 Klook (struktur, spacing, kartu, galeri, sidebar booking, footer)
- [ ] Warna tetap nuansa brand biru `#0d6efd` (bukan orange Klook)
- [ ] Semua fungsi lama tetap: booking tour/hotel/rental, filter, wishlist, review, lang switch, currency, admin CRUD
- [ ] **Fitur baru P0**: Attractions, Transfers, Promo Codes, Guest Checkout, Instant Confirmation badges, Newsletter, Collections — **semua ada & berfungsi**
- [ ] **Fitur baru P1**: Wallet/KlookCash, Loyalty tiers, Referral, Self-service cancellation — **semua ada & berfungsi**
- [ ] **Fitur baru P2**: Trains, eSIM/WiFi, FAQ/Help Center, Live chat — **semua ada & berfungsi**
- [ ] **Total fitur Klook yang diimplementasi**: minimal 20 dari 30+ fitur yang diidentifikasi di bagian 2
- [ ] `npx playwright test` → seluruh suite **hijau** (314 existing + spec baru untuk fitur baru)
- [ ] Tidak ada PHP error/warning; migrasi DB additive & idempotent
- [ ] Plan ini dijadikan acuan eksekusi bertahap; tiap fase di-commit terpisah

## Catatan

- Mulai eksekusi di **Fase 0** bila disetujui — tidak menyentuh test sama sekali.
- Prioritas visual tertinggi (quick win): header 1-baris + kartu tour + hero → langsung terasa "Klook".
- **Prioritas fitur (bukan sekadar UI)**: fitur P0 (attraction ticket, transfer, promo code, guest checkout) adalah pembeda fungsional yang membuat aplikasi benar-benar "ala Klook", bukan hanya tampilan.
- Setiap fitur baru yang ditambahkan harus punya halaman public + admin CRUD + E2E test — konsisten dengan pola existing (lihat `database/seed-layanan.php`, `hotel-detail.php`).

# Roadmap: Redesign Flights → Traveloka, Hotels → Agoda, Ferries → Easybook

> Status: Draft
> Base: commit `fd3f955` (Klook 1:1 redesign selesai, 378 E2E green)

## Tujuan

Ubah tampilan 3 halaman katalog agar meniru 3 brand booking ternama,
sambil mempertahankan semua **nama form field, query params, endpoint POST,
dan 378 test E2E yang sudah ada**. Aksen warna tetap biru brand
(`--primary: #0d6efd`), hanya tata letak & interaksi yang disesuaikan.

## Prinsip & Batasan

1. **Jangan rusak test lama** — semua selector/name yang dipakai spec harus tetap ada:
   - Flights: `name="trip_type"`, `name="from"`, `name="to"`, `name="date"`,
     `name="return_date"`, `name="passengers"`, `name="class"`, `name="search"`,
     form submit → `flights.php`, hasil list dari `flights` table
   - Hotels: `select[name="rooms"]`, `checkin/checkout`, `updateTotal()`,
     `#totalDisplay`, sidebar filter city/stars, `hotel-detail.php?slug=`
   - Ferries: form rute + tanggal → `ferries.php`, list jadwal ferry
2. **Tabel sudah punya kolom pendukung** (tanpa migration):
   - `flights`: `baggage_allowance`, `refundable`, `class`, `flight_number`, `duration`
   - `hotels`: `star_rating`, `amenities`, `instant_confirmation`,
     `free_cancellation`, `best_seller`, `lat/lng`
   - `ferries`: `vessel_name`, `amenities`
3. Perubahan terbatas pada: `flights.php`, `hotels.php`, `ferries.php`
   (+ CSS vars di `assets/css/design-tokens.css` bila perlu).
   Detail pages (`flight-detail.php`? tidak ada — pakai `flights.php` list)
   — cek dulu apakah ada `hotel-detail.php` (ada) dan detail ferry (tidak ada).
4. Semua halaman tetap pakai `includes/header-klook.php` & `footer-klook.php`.

---

## Fase A — Flights ala Traveloka (prioritas 1)

### A1. Search bar Traveloka-style
- **Sekarang**: form di dalam hero, tombol Cari, layout sederhana.
- **Jadi**: komponen search bertingkat khas Traveloka:
  - Tab transportasi (Pesawat / Kereta / Ferry / Rental) di atas form
    (aktif: Pesawat; link ke halaman masing-masing)
  - Toggle **Sekali Jalan / Pulang Pergi** (sudah ada `trip_type`)
  - Field: Asal (`from`), Tujuan (`to`), Tanggal (`date`), Tanggal Pulang
    (`return_date`, hanya roundtrip), Penumpang (`passengers`), Kelas (`class`)
  - Tombol biru besar **Cari Penerbangan** (`name="search"`)
  - Efek: border-rounded besar, shadow, sticky di atas list saat scroll

### A2. Sidebar filter kiri (Traveloka)
- Maskapai (dari `SELECT DISTINCT airline`)
- Waktu berangkat (pagi/siang/sore/malam — dari `departure_time`)
- Kelas (`class`), Harga (min-max), Nonstop/Transit (dari `duration`)
- Checkbox → auto-submit via GET (pertahankan nama param lama)

### A3. Hasil pencarian ala Traveloka
- Kartu jadwal: maskapai + logo placeholder, `from → to` + jam berangkat/tiba
  besar, `duration` + label "Langsung/Transit", `baggage_allowance`,
  badge `refundable` (hijau) / `non-refundable` (abu), harga kanan + tombol
  **Pilih**
- Sort bar: **Termurah / Tercepat / Terpopuler** (dari `price`, `duration`, `rating`?)
- Hover card → border primary + shadow (micro-interaction via `assets/js/klook.js`)

### A4. Detail tambahan
- Baris info: `flight_number`, kelas, jadwal dalam format kolom tabel
  (Traveloka-style: kolom berangkat/tiba/durasi)
- Jika tidak ada hasil: empty state "Tidak ada penerbangan" + tombol reset
  (sudah ada di test `transport` spec)

---

## Fase B — Hotels ala Agoda (prioritas 2)

### B1. Search bar Agoda-style
- **Sekarang**: form sederhana di hero (city/checkin/checkout/guests).
- **Jadi**: form 4-field dalam satu baris putih:
  - Kota (autocomplete), Check-in (`checkin`), Check-out (`checkout`),
    Tamu/Kamar (`guests` + `select[name="rooms"]`)
  - Tombol **Cari** biru
- Sticky di atas daftar saat scroll (Agoda mempertahankan search bar)

### B2. Sidebar filter Agoda
- Bintang (1–5), Harga per malam, Fasilitas (`amenities` LIKE),
  **Deal/Promo** (best_seller), Batal Gratis (`free_cancellation`),
  Konfirmasi Instan (`instant_confirmation`)

### B3. List hotel ala Agoda (list view, bukan card grid)
- Tiap hotel = **satu baris penuh**:
  - Foto kiri (cover_image)
  - Nama + bintang (rating-stars component) + kota
  - Fasilitas chips (amenities dipisah koma, max 5)
  - Badge "Best Seller", "Batal Gratis", "Instan"
  - Kanan: harga per malam besar (`price_per_night`), "termasuk pajak",
    tombol **Lihat Kamar** → `hotel-detail.php?slug=`
- Sort bar: **Harga Terendah / Rating Tertinggi / Best Seller**
- `hotel-detail.php` tetap seperti sekarang (sudah klook-style, ada
  `updateTotal()`, peta Google Maps, dsb.)

### B4. Detail page (opsional polish)
- Tambah strip ala Agoda di `hotel-detail.php`: galeri kecil + rating +
  lokasi di map. **Hati-hati**: pertahankan `#totalDisplay`, `updateTotal()`,
  `select[name="rooms"]`, form POST.

---

## Fase C — Ferries ala Easybook (prioritas 3)

### C1. Search bar Easybook-style
- **Sekarang**: form rute + tanggal (ada di `ferries.php`).
- **Jadi**: layout 3 langkah khas Easybook:
  - 1) Pilih rute (Dari `route_from` → Ke `route_to`)
  - 2) Pilih tanggal keberangkatan (`date`)
  - 3) Pilih penumpang
  - Tombol **Cari Jadwal**
- Pertahankan nama param lama agar test `transport`/`destinasi` tetap lolos.

### C2. Hasil jadwal ala Easybook (timeline/table)
- **Tabel jadwal**: kolom Perusahaan (`company`), Kapal (`vessel_name`),
  Berangkat (`departure_time`), Tiba (`arrival_time`), Durasi, Harga +
  tombol **Pesan**
- Kelompokkan per hari (tanggal pilihan di header)
- Highlight baris termurah (badge "Hemat")

### C3. Filter & info
- Filter samping: perusahaan (`company`), range harga
- Banner info: "Sesampainya di pelabuhan, tunjukkan e-ticket"
- Empty state: "Tidak ada jadwal ferry" (sudah ada di test)

---

## Fase D — E2E & Verifikasi (semua fase)

1. Update/cek spec yang menyentuh 3 halaman:
   - `tests/e2e/transport.spec.ts` (flights + ferries — **berisiko tinggi**)
   - `tests/e2e/hotels.spec.ts`, `tests/e2e/hotel-detail.spec.ts`
   - `tests/e2e/destinasi.spec.ts`, `tests/e2e/nav-crawl.spec.ts`
   - `tests/e2e/admin-*.spec.ts` (CRUD tidak berubah — cuma UI list)
2. `npx playwright test tests/e2e/transport.spec.ts tests/e2e/hotels.spec.ts --workers=1`
   → target hijau sebelum lanjut
3. Full suite: `npx playwright test --workers=1` → harus tetap **378 passed**
4. Responsive pass manual di 3 ukuran (mobile/tablet/desktop)

## Daftar File yang Berubah

| File | Perubahan |
|---|---|
| `flights.php` | Rewrite layout → Traveloka (search tab, filter, list) |
| `hotels.php` | Rewrite layout → Agoda (list view, filter) |
| `ferries.php` | Rewrite layout → Easybook (tabel jadwal) |
| `assets/css/design-tokens.css` | Tambah var (jika perlu: traveloka/agoda accent override) |
| `assets/js/klook.js` | Micro-interaction baru (hover card, sticky search) |
| `includes/components/` | Mungkin tambah `search-tabs.php`, `flight-card.php`, `hotel-row.php` |
| `tests/e2e/*.spec.ts` | Update selector bila layout berubah (hati-hati) |

## Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Test lama rusak (selector berubah) | Pertahankan semua `name=`, `id=`, class penting; verifikasi tiap fase |
| Ferry/flight detail page tidak ada | Tetap pakai list sebagai "detail" (bukan buat halaman baru) |
| `hotel-detail.php` dipecah | Jangan sentuh; polish opsional dengan hati-hati |
| Perubahan layout memicu PHP error | `php -l` + curl HTTP 200 setiap file setelah edit |

## Langkah Eksekusi

1. Baca ulang `flights.php`, `hotels.php`, `ferries.php` + spec terkait (transport, hotels, hotel-detail, destinasi)
2. Fase A (flights → Traveloka) → test transport → hijau
3. Fase B (hotels → Agoda) → test hotels → hijau
4. Fase C (ferries → Easybook) → test transport → hijau
5. Full suite + responsive pass
6. Commit

# Hotel Live API (Booking.com / OYO / NusaTrip)

Integrasi live hotel (curl-only, tanpa API key) — lihat `HOTEL-ENDPOINTS.md`.

## File
- `includes/hotelapi.php` — klien live + normalisasi:
  - `hotelApiAutocomplete($q)` (Booking.com kota)
  - `hotelApiBookingPrices()` / `hotelApiBookingDetail()` / `hotelApiBookingHotel()` (per hotel, butuh cc+pagename)
  - `hotelApiOyo($city)` (daftar per kota + harga + foto + geo)
  - `hotelApiNusatrip($token)` / `hotelApiNusaAuto($q)` (butuh rkey/key)
  - `hotelApiSearch($city,$opts)` (unified) / `hotelApiFind()` / `hotelApiResolveSource()`
- `includes/hotel-cache.php` + tabel `hotel_cache` (`database/migrate-hotel-cache.sql`) — TTL booking 6j, oyo/nusatrip 4j.
- `hotel-api.php` — JSON endpoint `?action=autocomplete|prices|detail|hotel|oyo|nusatrip|nusa_auto|search|find`.
- `includes/components/live-hotel-card.php` — `renderLiveHotelCard($h,$city,$checkin,$checkout,$guests)`.
- `admin/hotel-api-settings.php` — UI admin (nav Settings → Hotel API): toggle, sumber, rkey, uji live.

## Wiring
- `hotels.php` + `hotels-ajax.php`: **live-first** saat `?city=` → render kartu live; **fallback DB** bila live gagal/kosong.
- `hotel-detail.php?live=1&src=<source>&city=&id=` → detail live (Booking.com: `&cc=&pagename=`). DB booking flow tetap utuh.

## Setting (tabel `settings`)
- `hotel_live_enabled` (default `1`)
- `hotel_live_source` (default **`nusatrip`**; opsi `auto`|`oyo`|`nusatrip`; `auto` → nusatrip bila rkey diisi)
- `nusatrip_rkey` (default = token HOTEL-ENDPOINTS.md — **sering kedaluwarsa**)

## Penting
- rkey NusaTrip **tidak bisa dibuat via curl** (search digembok reCAPTCHA di `/hotels/search` → halaman "destinasi tak dikenal"). Cara ambil: buka nusatrip.com → cari hotel → DevTools Network → `/hotels/result?...rkey=...` → copy rkey, tempel di admin.
- rkey bersifat per-lokasi (1 token ≈ 1 kota). Bila kosong/gagal → otomatis fallback OYO.
- OYO parser pakai class `listingHotelDescription__hotelName` (hindari meta `itemProp="name"`).
- Slug kota: `preg_replace('/[^a-z0-9]+/','-', strtolower($city))`.
- Live tidak menyediakan booking → tombol "Pesan" ke penyedia.

## Test
`php tests/unit/run.php HotelApi` (11 kasus, tanpa network; setting di-restore).

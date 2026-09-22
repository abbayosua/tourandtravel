# Hotel Search & Price Endpoints (curl only, tanpa browser)

Kumpulan endpoint untuk cek **harga hotel + ketersediaan** lewat curl.
Semua sudah dites jalan tanpa browser, tanpa login, tanpa API key resmi.

Ada 2 cara pakai:

1. **Wrapper PHP** (paling gampang) → `http://localhost/endpointfinder/hotels.php?action=...`
2. **Direct upstream curl** (kalau mau hit server-nya langsung)

Sumber data yang **jalan**:
- **Booking.com** — harga + ketersediaan + detail kamar **per hotel**
- **OYO** — daftar hotel **per kota** + harga
- **NusaTrip** — daftar hotel **per kota** + harga (butuh `rkey`)

---

## 0. Jalankan wrapper PHP

File: `/Users/user/www/endpointfinder/hotels.php` (dan `bookingcom.php`).
Base URL: `http://localhost/endpointfinder/hotels.php`

Semua aksi mengembalikan **JSON**.

| Aksi | Parameter | Fungsi |
|---|---|---|
| `autocomplete` | `q` | Cari kota (Booking.com) |
| `prices` | `cc`, `pagename` \| `hotel_url`, `start`, `days`, `adults`, `rooms` | Kalender harga + ketersediaan |
| `detail` | `hotel_id` | Detail hotel (nama, alamat, bintang, kamar) |
| `hotel` | `cc`, `pagename` \| `hotel_url`, `hotel_id`, `start`, `days`, `adults`, `rooms` | **Gabungan**: detail + harga + kamar + alamat + lat/lng |
| `oyo` | `q` | Daftar hotel OYO per kota + harga + foto |
| `nusa_auto` | `q` | Autocomplete kota NusaTrip |
| `nusatrip` | `key` \| `rkey`, `proxy`, `try` | Daftar hotel NusaTrip per kota + harga |

---

## 1. Booking.com — harga + ketersediaan per hotel

Booking.com pakai GraphQL internal `dml/graphql`. Operasi yang **buka tanpa CSRF**:
`AvailabilityCalendar` (harga/ketersediaan) dan `propertyDetails` (detail, by `hotelId`).

### 1a. Autocomplete kota (dapat `dest_id`, jumlah hotel)

```bash
curl -s -X POST 'https://accommodations.booking.com/autocomplete.json' \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://www.booking.com' \
  -H 'Referer: https://www.booking.com/' \
  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36' \
  -d '{"query":"Jakarta","pageview_id":"","aid":800210,"language":"en-us","size":5}'
```
→ `dest_id=-2679652`, `dest_type=city`, `nr_hotels=4140`.

Via wrapper:
```bash
curl -s 'http://localhost/endpointfinder/hotels.php?action=autocomplete&q=Jakarta'
```

### 1b. Kalender harga + ketersediaan (per hotel, per tanggal)

Butuh `cc` (country code) + `pagename` (slug hotel dari URL booking.com).
Contoh: `https://www.booking.com/hotel/id/bobobox-pods-juanda-jakarta.html` → `cc=id`, `pagename=bobobox-pods-juanda-jakarta`.

```bash
curl -s -X POST 'https://www.booking.com/dml/graphql?lang=en-gb' \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://www.booking.com' \
  -H 'Referer: https://www.booking.com/hotel/id/bobobox-pods-juanda-jakarta.html' \
  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36' \
  -d '{
    "operationName":"AvailabilityCalendar",
    "variables":{"input":{
      "travelPurpose":2,
      "pagenameDetails":{"countryCode":"id","pagename":"bobobox-pods-juanda-jakarta"},
      "searchConfig":{
        "searchConfigDate":{"startDate":"2026-09-18","amountOfDays":3},
        "nbAdults":2,"nbRooms":1
      }
    }},
    "extensions":{},
    "query":"query AvailabilityCalendar($input: AvailabilityCalendarQueryInput!) { availabilityCalendar(input: $input) { ... on AvailabilityCalendarQueryResult { hotelId days { available avgPriceFormatted checkin minLengthOfStay } } ... on AvailabilityCalendarQueryError { message } } }"
  }'
```
→ `hotelId: 6257224`, `days:[{checkin, avgPriceFormatted:"379.7K", available:true}, ...]`

Via wrapper:
```bash
curl -s 'http://localhost/endpointfinder/hotels.php?action=prices&cc=id&pagename=bobobox-pods-juanda-jakarta&start=2026-09-18&days=3&adults=2&rooms=1'

# atau langsung pakai URL hotel:
curl -s 'http://localhost/endpointfinder/hotels.php?action=prices&hotel_url=https://www.booking.com/hotel/id/bobobox-pods-juanda-jakarta.html&start=2026-09-18&days=3'
```

### 1c. Detail hotel (by hotelId)

```bash
curl -s -X POST 'https://www.booking.com/dml/graphql?lang=en-gb' \
  -H 'Content-Type: application/json' \
  -H 'Origin: https://www.booking.com' \
  -H 'Referer: https://www.booking.com/' \
  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36' \
  -d '{
    "operationName":"P",
    "variables":{"input":{"hotelId":6257224}},
    "extensions":{},
    "query":"query P($input: PropertyDetailsQueryInput!) { propertyDetails(input: $input) { ... on Property { id name accommodationType { id name } location { address city country latitude longitude } starRating { value } reviews { totalScore reviewsCount } facilities { id icon } rooms { id bedConfigurations { beds { count } } } } } }"
  }'
```

### 1d. Gabungan (detail + harga + kamar + alamat) — paling praktis

```bash
curl -s 'http://localhost/endpointfinder/hotels.php?action=hotel&cc=id&pagename=bobobox-pods-juanda-jakarta&start=2026-09-18&days=3&adults=2&rooms=1'
```
→ `{hotel_id, name, type, stars, score, reviews_count, address, city, country, lat, lng, facility_icons, rooms[], photo_ids[], booking_url, prices[]}`

> **Batasan Booking.com:** hanya per-hotel (butuh `pagename`/URL). List semua hotel 1 kota **tidak** bisa curl-only (butuh CSRF + session browser).

---

## 2. OYO — daftar hotel per kota + harga

HTML microdata (schema.org), server-rendered, tanpa WAF. Slug = nama kota lowercase, spasi → `-`.

```bash
curl -s --compressed \
  -A 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36' \
  'https://www.oyorooms.com/id/hotels-in-jakarta/'
```
Parse: `itemProp="name"`, `listingPrice__finalPrice`, `href="/id/<id>/"`, `latitude/longitude" content=`, `streetAddress" title=`, `images.oyoroomscdn.com/.../medium/...`.

Via wrapper:
```bash
curl -s 'http://localhost/endpointfinder/hotels.php?action=oyo&q=jakarta'
```
→ `{source:"oyorooms", count:30, hotels:[{name, price:"Rp172.893", url, lat, lng, address, image}]}`

---

## 3. NusaTrip — daftar hotel per kota + harga (butuh `rkey`)

Endpoint JSON-nya terbuka: `/hotels/result?rkey=<token>` (atau `key=<angka>`).
Hasil: daftar semua hotel 1 kota + harga IDR + bintang + lat/lng + alamat + foto + link detail.

### 3a. Autocomplete kota

```bash
curl -s --compressed \
  -A 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36' \
  'https://www.nusatrip.com/location/search?name=batam'
```
→ `[{"value":"Batam, Riau Islands, Indonesia","val":"89bdd3b745e6796c"}]`

### 3b. Ambil daftar hotel + harga (pakai `rkey`)

```bash
curl -s --compressed \
  -A 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36' \
  'https://www.nusatrip.com/hotels/result?rkey=3bff981561ba69f3d32f21dca1d4f9a48d18837cac9f40e370c54d523ff547867d65f7abb9f7d6618faea23dc16a2cda'
```
→ `{"hotel_list":{...74 hotel...}}`, tiap hotel:
`name, star, baseLowestRate (IDR), photo_main, latitude, longitude, address, location_desc, uri`

Foto: `https://him.nusatrip.net` + `photo_main`.
Detail link: `https://www.nusatrip.com/id/hotel` + `uri`.

Via wrapper:
```bash
curl -s 'http://localhost/endpointfinder/hotels.php?action=nusatrip&rkey=3bff98...cda'
```
→ `{source:"nusatrip", count:74, hotels:[{name, star, price, price_formatted:"Rp1.281.360", image, lat, lng, address, desc, url}]}`

### 3c. Dari mana `rkey` didapat?

`rkey` = **search token** yang dibuat server NusaTrip saat kamu submit pencarian hotel.
Cara ambil:
1. Buka `nusatrip.com` → cari hotel.
2. DevTools → tab **Network** → filter `/hotels/result`.
3. Copy `rkey` (hex 128 char) atau `key` (angka) dari URL request.

> Membuat `rkey` baru via curl **tidak bisa** (search digembok reCAPTCHA).
> Tapi sekali punya `rkey`, **bisa dipakai berulang & lintas IP** (lihat 3d).

### 3d. `rkey` bersifat IP-agnostic (portable) → bisa pakai proxy

Dites: `rkey` yang sama dari 3 egress IP berbeda → hasil identik (74 hotel Batam).

```bash
# lewat proxy
curl -s --compressed -x 'http://1.231.81.166:3128' \
  -A 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120 Safari/537.36' \
  'https://www.nusatrip.com/hotels/result?rkey=<rkey>'
```

Wrapper mendukung proxy pool: letakkan daftar proxy (satu per baris, `ip:port`) di
`/Users/user/www/endpointfinder/proxies.txt`. Kalau request langsung gagal, otomatis retry via proxy.

```bash
# pakai proxy spesifik
curl -s 'http://localhost/endpointfinder/hotels.php?action=nusatrip&rkey=<rkey>&proxy=1.231.81.166:3128&try=1'
```

> `proxies.txt` masuk `.gitignore` — jangan commit kredensial proxy.

### 3e. Generate `rkey` otomatis (per jam)

Search digembok **reCAPTCHA v3** (invisible, score-based):
sitekey `6LeFdYskAAAAACaPGGyYKH75aKme8hixg4_VRD7R`, action `hotelSearch`.

Generator: `nusatrip_rkey.php`

```bash
# pakai solver (2captcha / capsolver)
CAPTCHA_KEY=xxxx php nusatrip_rkey.php batam 2026-09-18 2026-09-19
SOLVER=capsolver CAPTCHA_KEY=xxxx php nusatrip_rkey.php batam 2026-09-18 2026-09-19

# atau pakai token yang sudah disolve manual dari browser
php nusatrip_rkey.php --token=<g-recaptcha-response> batam 2026-09-18 2026-09-19
```
→ `{"ok":true,"rkey":"...","location_id":"...","from":"20260918","until":"20260919"}`

Env: `CAPTCHA_KEY`, `SOLVER` (`2captcha`|`capsolver`), `PROXY` (opsional, `http://ip:port`).

Biaya v3 ≈ $0.5–1.5 / 1000 solve → 2x/jam ≈ **$1–2/bulan**. Bisa dijadwalkan (cron tiap 30 menit).

---

## Ringkasan cepat

| Sumber | Cakupan | Auth | Catatan |
|---|---|---|---|
| Booking.com | per hotel (harga, ketersediaan, kamar, alamat) | tidak perlu | list per kota butuh CSRF/session |
| OYO | per kota (daftar + harga + foto + geo) | tidak perlu | hanya properti OYO |
| NusaTrip | per kota (daftar + harga + foto + geo) | butuh `rkey` | `rkey` portable, bisa via proxy |

**Tidak jalan curl-only** (butuh key resmi): Expedia, Agoda, Tiket, Traveloka, Trip.com, TripAdvisor, Hotels.com, Priceline, Skyscanner, RedDoorz, MisterAladin, dll.

# E2E Testing (Playwright)

- Framework: Playwright (`@playwright/test` ^1.62), config `playwright.config.ts` (testDir `tests/e2e`, chromium headless, `globalSetup: tests/e2e/global-setup.ts`, workers=1 utk stabilitas).
- Server: XAMPP lokal, `http://localhost/tourandtravel` (BASE_URL di `includes/config.php`). Sudah jalan (curl 200).
- Run semua: `npx playwright test` (314 tests, ~3 min). Run satu: `npx playwright test tests/e2e/<file>.spec.ts`.
- Browser: chromium Playwright (`npx playwright install chromium`), ada di `~/Library/Caches/ms-playwright/`.
- **GLOBAL SETUP** (`tests/e2e/global-setup.ts`): bersihkan data non-idempotent via mysql (`hotel_bookings` >1, tours `E2E%/Test%`, booking TAT-DEL01) — WAJIB sebelum full run, jangan dihapus.
- Test lama (`multilingual.spec.ts`, `tour-content-*.spec.ts`, `admin-bulk-lang.spec.ts`) dulunya point ke URL remote/stale (`tourandtravel.web.id`, `localhost:8765`) — sudah di-fix ke localhost.
- Auth: admin `admin/password`; user register `register.php` (auto-login). Helper register tersedia di tiap spec.

## 38 Spec Files (314 tests)

### Public pages
`smoke-public` (7), `tours-filter` (12), `tours-sadpath` (7), `tour-detail` (9), `tour-detail-sadpath` (7), `language-switch` (8), `hotels` (15), `hotel-detail` (17), `flights` (8), `ferries` (11), `rental-cars` (12), `rental-car-detail` (11), `destinasi` (12), `track` (9), `booking-success` (9), `nav-crawl` (8)

### User flows
`local-auth` (3), `user-pages` (13: my-bookings/profile/wishlist), `review` (8), `wishlist` (di user-pages)

### Admin
`admin-guard` (20: 17 file redirect login), `admin-dashboard` (4), `admin-tours` (10), `admin-crud` (20: hotels/flights/ferries/rental-cars), `admin-bookings` (9), `admin-settings` (8: currency+WA), `admin-bulk-lang` (5)

### Accounting & Abuse
`accounting-tour` (7), `accounting-transport` (5), `abuse-booking` (4: double submit/overlap), `abuse-sqli` (9), `abuse-xss` (7), `abuse-user-admin` (2), `abuse-noauth` (3)

## Bug Fixes (fase testing)
1. `includes/functions.php` — sort `termurah/termahal` (dulu `price_asc/desc` mismatch); pagination clamp page>lastPage; filter harga konversi IDR→SGD.
2. `tour-detail.php`, `rental-car-detail.php`, `hotel-detail.php` — guard `isLoggedIn()` pada POST booking (sebelumnya bisa booking tanpa login).
3. `hotel-detail.php` + tabel `hotel_bookings` — booking persist + cek overlap tanggal (`? < checkout AND checkin < ?`) + error "Tanggal sudah dibooking".
4. `admin/wa-{settings,ajax,test}.php` — hapus `session_start()` ganda (config.php sudah start).
5. `profile.php` — `getUser()` dipindah setelah blok POST (form refresh setelah update).
6. `playwright.config.ts` + `global-setup.ts` — idempotensi suite (cleanup DB sebelum run).

## Data notes
- `bookings.booking_code` (TAT-FIX01..05) & `tour_dates` di-seed (dulu NULL/0 row) — JANGAN hapus, dipakai test track/booking-success.
- `hotel_bookings` seeded (id=1 Existing Guest 2026-12-01..05) — overlap test bergantung padanya.
- Tour test (admin-tours) menambah & menghapus tour "E2E Test Tour" — global setup bersihkan sisa.
- Slug rental: `toyota-avanza-jakarta` (bukan `toyota-avanza-350000`) — berubah karena regenerate slug di admin edit.

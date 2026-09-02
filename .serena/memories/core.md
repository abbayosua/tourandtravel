# Core - TourAndTravel

- PHP vanilla (no framework/composer), MySQL PDO, Bootstrap 5.3, JS vanilla. Entrypoint `index.php`, routing via `?slug/page` query params, no router.
- Struktur: `includes/{config,db,functions,auth,header,footer,send-wa}` = core; `admin/*` = panel (16 files, guard `cekLogin()`); root `*.php` = public pages (tours, tour-detail, flights, hotels, ferries, rental-cars, booking, track, wishlist); `database/{schema,seed*.php}`; `assets/{css,js}`; `uploads/{passports,wiki}`.
- DB: `tours`, `tour_dates`, `itineraries`, `bookings`, `users`, `admins`, `wishlists` (+ layanan: `hotels`, `flights`, `ferries`, `rental_cars` via seed-layanan). Singleton `Database` di `mem:tech_stack`, helper `db()` global.
- Invarian: `BASE_URL` di `includes/config.php` hardcode `http://localhost/tourandtravel`; session_start di config; semua page `require_once config+db+functions`; admin cek via `includes/auth.php:cekLogin()`.
- WA notifikasi via WUZAPI (`includes/send-wa.php` → `sendWA`/`sendBookingNotification`, config `wa-config.json` + defaults, webhook `webhook-wa.php`).
- Refs: stack detail `mem:tech_stack`, commands `mem:suggested_commands`, conventions `mem:conventions`, task done `mem:task_completion`.

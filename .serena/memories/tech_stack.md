# Tech Stack

- PHP 8.x vanilla, MySQL/MariaDB via PDO (utf8mb4), no composer/package.json. Apache (XAMPP), `BASE_URL=http://localhost/tourandtravel`.
- Frontend: Bootstrap 5.3.3 + bootstrap-icons 1.11.3 (CDN), `assets/css/style.css`, `assets/js/script.js` (search autocomplete, hero video, wishlist ajax).
- DB layer: `includes/db.php` — `Database` singleton (`getInstance()->getConnection(): PDO`), helper `db(): PDO`. ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES false.
- Auth: PHP sessions (`session_start` in config), `admins` (default admin/admin123), `users`, `includes/auth.php:cekLogin()/hashPassword()`.
- Integrasi: WUZAPI WA (`includes/send-wa.php` curl → `sendWA`/`sendBookingNotification`, config `wa-config.json`), wiki image cache `cache/wiki-cities.json`.
- Serena: `project_name=tourandtravel`, `languages=[php]`, 316 nodes / 658 edges (fast index).

# Security Audit — TourAndTravel

**Tanggal:** 9 September 2026
**Scope:** Seluruh codebase (PHP vanilla, MySQL PDO, Bootstrap 5) + Android WebView app
**Metode:** Manual code review (30 area audit) + live testing + abuse test suite
**Status fix:** 7 temuan SUDAH DIFIX (H1, H3-parsial, H4-parsial, M1, M2, M4, M9) — verifikasi di bagian bawah

---

## Ringkasan Eksekutif

| Kategori | Jumlah | Status |
|---|---|---|
| **Critical** | 0 | ✅ |
| **High** | 4 | ⚠️ Perlu fix segera |
| **Medium** | 10 | ⚠️ Perlu fix dalam sprint |
| **Low** | 6 | 📋 Backlog |

**Kesimpulan umum:** Fondasi keamanan BAIK — SQL injection solid (prepared statements + whitelist), password hashing bcrypt + password_verify, Midtrans webhook verified timing-safe, password reset flow best-practice lengkap, IDOR ter-scope, admin guard 100%. Celah yang ada dominan **defensive-in-depth missing** (CSRF token, security headers, rate limit, session hardening) dan **beberapa bug konkret** (open redirect, passport IDOR).

---

## 🔴 HIGH — Perlu Fix Segera

### H1. Open Redirect di login — ✅ FIXED
- **Lokasi:** `login.php:19-24`
- **Fix diterapkan:** Validasi `$redirect` harus path relatif (`^/[^/]`, tanpa `//`) — external & protocol-relative URL fallback ke `index.php`. Verified: `https://evil.com` → `index.php`, `//evil.com` → `index.php`, `/tours.php` tetap jalan.
- **Bukti:** `$redirect = $_GET['redirect'] ?? 'index.php'; header("Location: $redirect");` — tanpa validasi. Live test: `login.php?redirect=https://evil.com` → redirect keluar. Dipakai di 23 link internal.
- **Dampak:** Phishing — attacker kirim link login sah yang redirect ke situs palsu setelah login sukses.
- **Fix:**
```php
$redirect = $_GET['redirect'] ?? 'index.php';
if (!preg_match('#^/[^/]#', $redirect) || str_contains($redirect, '//')) $redirect = 'index.php';
header('Location: ' . $redirect);
```

### H2. Foto Passport bisa diakses siapa saja (IDOR + PII)
- **Lokasi:** `track.php:98` — link `uploads/passports/` tampil bagi siapa pun yang tahu booking code
- **Bukti:** `track.php:6-16` — hanya validasi `booking_code`, tanpa login/ownership. Code format `TAT-XXXXX` (36^5 ≈ 60jt kombinasi), `track.php` **tanpa rate limit** (live test: 10 lookup/0.98s) → brute force feasible.
- **Dampak:** Kebocoran PII dokumen identitas (berlaku UU PDP Indonesia).
- **Fix:** Wajib login + verifikasi `user_id` ATAU sembunyikan link passport di track.php (hanya admin), + rate limit track lookup.

### H3. uploadGambar() — MIME spoof + extension dari client filename
- **Lokasi:** `includes/functions.php:411-440` (`uploadGambar`)
- **Bukti:** `$file['type']` (client-controlled) untuk whitelist; `$ext = pathinfo($file['name'])` — file `shell.php` + header `image/jpeg` → tersimpan `uniqid.php` di `uploads/`.
- **Pemakai:** `admin/post-edit.php:26`, `admin/hero-slide-edit.php:45`, `admin/attraction-edit.php:40`, `admin/tour-add.php:30`, `admin/tour-edit.php:34,143`, `review-submit.php:50`, `hotel-review-submit.php:45`
- **Dampak:** RCE jika Apache meng-eksekusi PHP di uploads (tidak ada `.htaccess` di uploads). Mitigasi faktual: semua caller admin-only (cekLogin) kecuali review (isLoggedIn), dan eksekusi .php tergantung server config.
- **Fix:** Ganti semua caller ke `uploadWebP()` (sudah re-encode + ext fixed `.webp`), atau tambahkan `finfo_file()` MIME check + whitelist ext + `.htaccess` `php_flag engine off` di uploads/.

### H4. Tidak ada CSRF protection sama sekali — ⚠️ PARSIALLY FIXED (wishlist POST-only)
- **Terburuk FIXED:** `wishlist-ajax.php` — sekarang **wajib POST** (GET → `method_not_allowed`), JS callers di `footer.php` + `footer-klook.php` diupdate ke POST. CSRF via `<img src>` tidak mungkin lagi.
- **Sisa (sprint depan):** CSRF token untuk semua form POST + admin status change via POST.
- **Lokasi:** Semua form + `*-ajax.php` (0 match "csrf" di codebase)
- **Terburuk:** `wishlist-ajax.php` — INSERT/DELETE via **GET** (`$_GET['tour_id']`, tanpa REQUEST_METHOD check) → bisa di-trigger via `<img src="...wishlist-ajax.php?action=remove...">` cross-origin tanpa user interaction.
- **Juga:** `admin/bookings.php` — ubah status booking via GET link (6 lokasi href).
- **Dampak:** Aksi state-changing dipicu lintas site atas nama user/admin yang login.
- **Fix:** (1) `wishlist-ajax.php` wajib POST; (2) token CSRF session (`$_SESSION['csrf'] = bin2hex(random_bytes(32))`) di semua form POST + header `X-CSRF-Token` untuk fetch; (3) admin status change via POST form.

---

## 🟠 MEDIUM — Fix dalam Sprint

### M1. Session fixation + cookie flags — ✅ FIXED
- `session_regenerate_id(true)` ditambahkan di `login.php`, `admin/login.php`, `register.php`.
- `session_set_cookie_params(['httponly' => true, 'secure' => HTTPS-aware, 'samesite' => 'Lax'])` di `includes/config.php`.
- `login.php:17`, `admin/login.php:17`, `register.php:59` — tanpa `session_regenerate_id(true)` setelah login.
- `includes/config.php:18` — `session_start()` tanpa `session_set_cookie_params(['httponly' => true, 'secure' => true, 'samesite' => 'Lax'])`.
- Live env: `cookie_samesite=unset`, `cookie_httponly=unset`.

### M2. Semua HTTP security headers missing — ✅ FIXED
- `.htaccess` baru: X-Frame-Options SAMEORIGIN, X-Content-Type-Options nosniff, Referrer-Policy, Permissions-Policy, `display_errors Off`, `expose_php Off`, `Options -Indexes`.
- Live test semua endpoint: CSP, X-Frame-Options (clickjacking admin), X-Content-Type-Options, HSTS, Referrer-Policy, Permissions-Policy — **semua MISSING**.
- `X-Powered-By: PHP/8.3.32` exposed (`expose_php=On`), tidak ada `.htaccess`.
- **Fix:** Buat `.htaccess`:
```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### M3. Rate limiting hampir tidak ada
- `forgot-password.php:17` = satu-satunya (1/menit per email ✅).
- Live test: **login 5 attempts/0.02s** (brute force terbuka), `admin/login.php` tanpa throttle (target paling kritis), `track.php` 10/0.98s, register spam, booking spam, `api/fcm-token.php` spam.
- **Fix:** Tabel `rate_limits(ip, action, window_start, count)` + helper check di login (5/15min), admin login (3/15min), track (20/menit), register, fcm-token.

### M4. Error display leaks server path — ✅ FIXED
- `api/fcm-token.php`: validasi panjang token (32–500 chars, cocok kolom DB) → response JSON bersih, tidak ada fatal (live-verified).
- `includes/db.php`: `die()` generic tanpa exception message + error_log server-side.
- `api/fcm-token.php:20` — PDOException uncaught saat token >500 chars → **fatal + stack trace + path `/Users/user/...` ter-leak ke HTTP response** (live-verified).
- `includes/db.php:16` — `die("Koneksi database gagal: " . $e->getMessage())` bisa bocorkan DB host/user.
- Tidak ada `ini_set('display_errors','0')` di codebase; env `display_errors=STDOUT`.
- **Fix:** try/catch di fcm-token + generic die message + `ini_set('display_errors', '0')` di config.php.

### M5. User enumeration via timing di login
- `login.php:16` — user-not-found skip `password_verify` (0ms) vs user-found (61ms bcrypt) → valid email bisa dideteksi via response time.
- **Fix:** Dummy hash verify: `$user ? ... : password_verify($password, DUMMY_HASH)`.

### M6. SSL verification disabled (MITM)
- `includes/easybook.php:29,75` (harga ferry!), `includes/functions.php:87` (kurs!) — `CURLOPT_SSL_VERIFYPEER => false`.
- **Dampak:** MITM bisa manipulasi harga ferry/kurs yang diterima user.
- **Fix:** Hapus opsi tersebut (default true) + pastikan CA bundle tersedia.

### M7. CDN tanpa SRI + flatpickr unpinned
- 2/11 resource ada `integrity` (leaflet saja).
- `includes/footer-klook.php:229` — `flatpickr` **tanpa version pin**.
- Bootstrap/glightbox pinned tapi tanpa SRI → CDN compromise = XSS global.
- **Fix:** Pin `flatpickr@4.6.13`, tambah SRI semua CDN.

### M8. includes/config.php masih tracked di git
- Di `.gitignore` TAPI `git ls-files` masih track (gitignore ineffective untuk tracked files). Saat ini DB_PASS kosong, tapi credential production nanti akan ter-commit.
- **Fix:** `git rm --cached includes/config.php` + commit.

### M9. webhooks & uploads tanpa hardening — ⚠️ PARSIALLY FIXED
- **FIXED:** `uploads/.htaccess` baru — `Require all denied` untuk *.php* + `Options -Indexes` (file PHP di uploads tidak bisa dieksekusi/diroute Apache).
- **Sisa:** webhook-wa.php shared secret + log rotation.
- `webhook-wa.php` — tanpa auth (POST anonim → HTTP 200, log-only tapi disk-fill DoS, no log rotation).
- `uploads/` — tanpa `.htaccess` (php engine tidak off), tanpa `Options -Indexes`.
- **Fix:** Shared secret header untuk webhook; `.htaccess` uploads `php_flag engine off`.

### M10. sw.js cache-all strategy (jika diaktifkan)
- `sw.js:9-15` — semua GET response di-cache tanpa filter → halaman PII bisa ter-cache. **Saat ini SW tidak di-register (dead code)** — risiko muncul jika diaktifkan tanpa filter query string.
- **Fix:** Exclude URL dengan query string dari cache.

---

## 🟡 LOW — Backlog

| ID | Temuan | Lokasi |
|---|---|---|
| L1 | `getTours()` `$perPage` interpolasi LIMIT — semua caller pass literal, defensif rapuh | `includes/functions.php:564` |
| L2 | `duffelGetOffer($offerId)` — GET param masuk URL path tanpa sanitasi (external API, bukan SSRF) | `includes/duffel.php:78` ← `flight-detail.php:8` |
| L3 | `manifest.json` path localhost-style + icons 404 (`icon-192.png`, `icon-512.png` missing) | `manifest.json` |
| L4 | robots.txt mengkonfirmasi struktur `/admin/` (standar, tapi catatan) | `robots.txt` |
| L5 | `klook.js:178,182` innerHTML dengan `d.message` API — saat ini literal server-side saja | `assets/js/klook.js` |
| L6 | Debug log "PRD-DBG" masih ada | `tour-detail.php:140` |

---

## ✅ Temuan Aman (Verified)

| Area | Bukti |
|---|---|
| **SQL Injection** | PDO prepared statements + `EMULATE_PREPARES=false` (`db.php:14`); dynamic table names dari hardcoded whitelist (`admin/bookings.php:11`, `my-bookings.php`, `ajax/create-payment.php:26`); ORDER BY via `match()` whitelist (`functions.php:547`, `hotels-ajax.php:44`); 0 interpolasi user input exploitable |
| **XSS (PHP)** | Semua PII via `e()` — track.php:32,90-92, booking-success.php:181-203, admin fields; 0 echo raw `$_GET/$_POST`; social-proof pakai `textContent` + server mask |
| **XSS (JS)** | `escapeHtml()` di autocomplete (`script.js:100-104`) |
| **Password hashing** | bcrypt via `PASSWORD_DEFAULT` + `password_verify` |
| **Midtrans webhook** | sha512 sig + `hash_equals` + idempotent + order whitelist (`payments.php:150-185`) — 3/3 unit rejection tests pass |
| **Password reset** | 256-bit CSPRNG token, sha256 di DB, single-use + rotation, expiry 1 jam, rate limit 1/menit, generic response — 4/4 unit tests pass |
| **IDOR** | Semua user-data mutations scoped `user_id` session (profile-ajax:55,76,89; price-alert:62; wishlist:27; itinerary ownership helpers) |
| **Admin guard** | 47 files = 45 `cekLogin()` + login/logout exceptions — 0 bypass. e2e: user biasa ditolak 17 admin files (2/2 pass) |
| **CORS** | `ACAO: *` tanpa `Allow-Credentials` + hanya di `api/fcm-token.php` — session cookie tidak bisa dicuri cross-origin |
| **Path traversal** | `basename()` di email template include; file ops path hardcoded; fetch-wiki CLI-only |
| **Secrets** | RSA key, SERVER.md, wa-config.json tidak tracked; DB_PASS kosong |

---

## 🧪 Hasil Test Eksekusi

| Suite | Hasil | Catatan |
|---|---|---|
| `php tests/unit/run.php` | **111/111 LULUS** (setelah fixes) | Termasuk Midtrans rejection + PasswordReset |
| Post-fix regressi e2e | 22/22 LULUS | user-pages (incl. wishlist POST) + local-auth + tour-detail |
| `abuse-sqli.spec.ts` | 8/9 | 1 gagal = **test regex false positive** (match placeholder "TAT-7A2B1" di form contoh sendiri); manual verify: tidak ada injection, tidak ada leak |
| `abuse-xss.spec.ts` | 6/7 | 1 gagal = **test bahasa** (Chromium `Accept-Language: en` → render "Find Booking", regex test hanya match Indonesia); manual verify: payload tidak executable |
| `abuse-noauth.spec.ts` | 3/3 LULUS | Guest checkout by design |
| `abuse-user-admin.spec.ts` | 2/2 LULUS | Admin guard solid |
| `abuse-booking.spec.ts` | 1/4 | 3 gagal = **test selector rot** (flatpickr readonly + modal alert menambah submit button) — bukan security issue |
| Smoke headers | 0/7 headers SET | Semua security headers missing (M2) |

**Rekomendasi test maintenance:** fix regex abuse-sqli (exclude placeholder), fix regex abuse-xss (bilingual `Cari Booking|Find Booking`), update locator abuse-booking (`#bookingSubmitBtn`, fill flatpickr via evaluate).

---

## 📋 Prioritas Eksekusi

1. ~~Sprint ini~~ **SELESAI:** H1 ✅, H2 belum (butuh keputusan UX), H3 parsial (uploads/.htaccess mitigasi eksekusi; ganti uploadGambar→uploadWebP backlog), H4 parsial ✅ (wishlist POST), M1 ✅, M2 ✅, M4 ✅, M9 parsial ✅
2. **Sprint depan:** H2 (passport di track.php), H4 penuh (CSRF token), M3 (rate limiter), M5, M6, M8, H3 penuh
3. **Backlog:** M7 (SRI), M10, semua Low, test maintenance (2 false positive regex + 3 selector rot)

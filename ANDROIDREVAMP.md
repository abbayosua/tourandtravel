# ANDROIDREVAMP — WebView Seamless + Push Notification Admin Panel

## Ringkasan

Revamp Android app experience: hilangkan top header & footer website via CSS injection dari WebView (tanpa edit PHP), dan buat dedicated push notification page di admin panel.

---

## 1. Hilangkan ActionBar Android (Top Bar Native)

**File:** `android-app/app/src/main/res/values/themes.xml`

Ubah parent theme `Theme.MaterialComponents.DayNight.DarkActionBar` → `Theme.MaterialComponents.DayNight.NoActionBar`. Ini menghilangkan ActionBar native Android di atas WebView.

**Dampak:** WebView full-screen dari atas layar. Tidak perlu sentuh kode Java.

---

## 2. Sembunyikan Header & Footer Website via CSS Injection

**Strategy:** Inject CSS dari `MainActivity.java` via `webView.evaluateJavascript()` setelah page load, bukan edit file PHP.

**Approach A — CSS Injection via `WebViewClient.onPageFinished()`:**

```java
webView.setWebViewClient(new WebViewClient() {
    @Override
    public void onPageFinished(WebView view, String url) {
        super.onPageFinished(view, url);
        // Sembunyikan sticky top navbar (2 navbars) + footer
        view.evaluateJavascript(
            "(function() {" +
            "  var style = document.createElement('style');" +
            "  style.id = 'android-hide-elements';" +
            "  style.textContent = '.sticky-top { display: none !important; }" +
            "    footer, .footer, .site-footer { display: none !important; }';" +
            "  document.head.appendChild(style);" +
            "})();", null);
    }
});
```

**Key points:**
- CSS selector `.sticky-top` mencakup kedua navbar (line 1 + line 2)
- Footer selector pakai `footer, .footer, .site-footer` — cover semua kemungkinan class
- Hanya aktif di WebView (tidak di browser biasa)
- Zero change ke PHP codebase

**Approach B (fallback jika ada page transition):** inject via `shouldInterceptRequest` untuk inject `<style>` di HTML response. Tapi Approach A lebih sederhana dan cukup.

---

## 3. Dedicated Push Notification Admin Page

**New file:** `admin/push-notifications.php`

Halaman untuk admin compose & send push notification ke:
- All users (all FCM tokens)
- Users by language (id/en/zh)
- Specific user ID

### 3.1 Database

No new migration — `fcm_tokens` table sudah ada dengan `lang` column.

### 3.2 UI Components

- Form: Title input + Body textarea + Language filter dropdown (All / ID / EN / ZH) + Target (All users / Specific user ID)
- Send button → AJAX ke `admin/ajax/send-push.php`
- History table log pengiriman (opsional)

### 3.3 Backend

**New file:** `admin/ajax/send-push.php`

```php
// Query fcm_tokens based on filter
// Loop tokens, send via fcm-push.php helper
// Return JSON {sent: N, failed: N}
```

### 3.4 Sidebar Nav Item

Tambah di `admin/includes/admin-header.php` di section Marketing, setelah Price Alerts:

```php
$navItem('push-notifications.php', 'bi-bell-fill', t('Push Notifikasi'), ['push-notifications.php']);
```

### 3.5 Multi-language

Pesan push notification support 3 bahasa (id/en/zh). Admin bisa compose 3 versi, system kirim sesuai `fcm_tokens.lang` masing-masing user.

---

## 4. E2E Testing Strategy

### 4.1 AVD Heypico — Android Native Testing

Gunakan AVD yang sudah ada (`heypico`) untuk test visual WebView:

```
# Start AVD
emulator -avd heypico

# Build & install APK debug
cd android-app
./gradlew assembleDebug
adb install -r app/build/outputs/apk/debug/app-debug.apk

# Test scenarios (manual via AVD):
```

**Test scenarios di AVD:**

| # | Test | Verifikasi |
|---|---|---|
| 1 | Buka app | ActionBar native TIDAK muncul |
| 2 | Scroll halaman | `.sticky-top` navbar website TIDAK tampil |
| 3 | Scroll ke bawah | Footer website TIDAK tampil |
| 4 | Ganti halaman (navigasi) | CSS injection tetap jalan di setiap page load |
| 5 | Notifikasi masuk | Notification tray muncul, title/body sesuai bahasa |

**Screenshot-based:** Bisa pakai `adb exec-out screencap -p > screenshot.png` untuk dokumentasi.

### 4.2 Playwright — Admin Push Page

Buat `tests/e2e/admin-push.spec.ts` untuk test admin push notification panel:

```
- Login admin → verify sidebar ada "Push Notifikasi"
- Open page → verify form elements (title, body, language filter)
- Submit empty → verify validation error
- Submit valid → verify success response (mock FCM atau assert AJAX 200)
```

### 4.3 Existing Push Points (already implemented)

### 4.1 Existing Push Points (already implemented)

| Trigger | File | Status |
|---|---|---|
| Booking baru dari user | `tour-detail.php:130` | ✅ Already calls `sendPushNotification` ke admin |
| Ubah status booking di admin | `admin/bookings.php:85` | ✅ Multi-lang (id/en/zh) |
| FCM token register | `api/fcm-token.php` | ✅ Accepts `lang` field |

### 4.2 New Test Plan

1. **WebView native:** Buka app → pastikan ActionBar tidak muncul
2. **Header hidden:** Pastikan `.sticky-top` navbar tidak tampil
3. **Footer hidden:** Pastikan footer website tidak tampil
4. **Admin push page:** Buka `admin/push-notifications.php` → compose → send
5. **FCM integration test:** Kirim notifikasi via admin page → terima di device (butuh FCM_SERVER_KEY terisi)

### 4.3 Prerequisites for Testing

- `config.php`: `FCM_SERVER_KEY` harus terisi dengan Firebase server key
- Android app: `google-services.json` valid + Firebase project setup
- Real device atau emulator dengan Google Play Services

---

## 5. Files Changed

| File | Action |
|---|---|
| `android-app/app/src/main/res/values/themes.xml` | Edit: `DarkActionBar` → `NoActionBar` |
| `android-app/app/src/main/java/.../MainActivity.java` | Edit: inject CSS di `onPageFinished` |
| `admin/push-notifications.php` | **New:** compose & send push |
| `admin/ajax/send-push.php` | **New:** AJAX endpoint |
| `admin/includes/admin-header.php` | Edit: tambah nav item |
| `ANDROIDREVAMP.md` | This file |

---

### 4.4 Prerequisites

Sebelum testing:

1. **FCM_SERVER_KEY** terisi di `includes/config.php`
2. APK diinstall via `adb install -r`
3. AVD heypico running
4. Website running di `http://10.0.2.2/tourandtravel` (dari emulator)

---

## 6. Execution Order

1. Edit `themes.xml` — NoActionBar
2. Edit `MainActivity.java` — CSS injection + send `lang` in FCM token registration
3. Buat `admin/push-notifications.php` — form + send logic
4. Buat `admin/ajax/send-push.php` — AJAX handler
5. Edit `admin-header.php` — sidebar link
6. Test: WebView fullscreen, header/footer hidden
7. Test: Admin push notification page
8. Update `WEBVIEWBRIDGE.md` jika ada perubahan API

---

## 7. Risk & Mitigation

| Risk | Mitigation |
|---|---|
| CSS injection tidak cover semua halaman | Inject via `onPageFinished`, trigger setiap page load |
| `.sticky-top` class berubah di masa depan | Pakai multiple selector, tambah komentar di Android code |
| ActionBar hilang tapi WebView masih ada notch/status bar | Test di real device, adjust `fitsSystemWindows` jika perlu |
| Push notification butuh FCM key | Sudah ada `FCM_SERVER_KEY` di config, tinggal isi |

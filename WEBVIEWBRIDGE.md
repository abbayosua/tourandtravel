# WebView Bridge — Android (Tanpa Capacitor)

Panduan membuat Android app dengan **WebView murni** + **JavaScript Bridge** untuk notifikasi push.

---

## 1. Arsitektur

```
┌─────────────────────────────────────┐
│  Android App (WebView)              │
│  ┌───────────────────────────────┐  │
│  │  WebView.loadUrl(URL)         │  │
│  │  ┌─────────────────────────┐  │  │
│  │  │  PHP Website (mobile)   │  │  │
│  │  └────────────┬────────────┘  │  │
│  │               │ JS calls      │  │
│  │  ┌────────────▼────────────┐  │  │
│  │  │  JavaScriptInterface    │  │  │
│  │  │  (Android ← JS bridge)  │  │  │
│  │  └─────────────────────────┘  │  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │  Firebase Cloud Messaging     │  │
│  │  (Push Notification)          │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
         │
         ▼ HTTP
┌─────────────────────────────────────┐
│  PHP Backend (same website)         │
└─────────────────────────────────────┘
```

---

## 2. Android Project Setup

### 2.1 `build.gradle` (app)

```groovy
plugins {
    id 'com.android.application'
}

android {
    namespace 'com.tourandtravel.app'
    compileSdk 34

    defaultConfig {
        applicationId "com.tourandtravel.app"
        minSdk 24
        targetSdk 34
        versionCode 1
        versionName "1.0"
    }

    buildTypes {
        release {
            minifyEnabled true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }

    buildFeatures {
        viewBinding true
    }
}

dependencies {
    implementation 'androidx.appcompat:appcompat:1.6.1'
    implementation 'com.google.android.material:material:1.11.0'
    implementation 'androidx.webkit:webkit:1.9.0'

    // Firebase
    implementation platform('com.google.firebase:firebase-bom:32.7.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

### 2.2 `AndroidManifest.xml`

```xml
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">

    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    <uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
    <uses-permission android:name="android.permission.VIBRATE" />

    <application
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="Tour & Travel"
        android:theme="@style/Theme.TourAndTravel"
        android:usesCleartextTraffic="true">

        <activity
            android:name=".MainActivity"
            android:exported="true"
            android:configChanges="orientation|screenSize|keyboard|keyboardHidden"
            android:launchMode="singleTop">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>

        <!-- Firebase Messaging Service -->
        <service
            android:name=".FirebaseMessageService"
            android:exported="false">
            <intent-filter>
                <action android:name="com.google.firebase.MESSAGING_EVENT" />
            </intent-filter>
        </service>

    </application>
</manifest>
```

---

## 3. MainActivity.java

```java
package com.tourandtravel.app;

import android.Manifest;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.Bundle;
import android.webkit.JavascriptInterface;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.app.NotificationManagerCompat;

import com.google.firebase.messaging.FirebaseMessaging;

public class MainActivity extends AppCompatActivity {

    private WebView webView;
    private static final String BASE_URL = "https://yourdomain.com";
    private static final String FCM_TOKEN_URL = BASE_URL + "/api/fcm-token.php";
    private static final int NOTIFICATION_PERMISSION_CODE = 1001;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        setupWebView();
        requestNotificationPermission();
        getFcmToken();
    }

    private void setupWebView() {
        webView = findViewById(R.id.webView);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);

        // JavaScript Bridge — register interface
        webView.addJavascriptInterface(new WebAppBridge(), "AndroidBridge");

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();
                // Tetap di WebView untuk URL website sendiri
                if (url.startsWith(BASE_URL)) {
                    return false;
                }
                // Buka external link di browser
                return true;
            }
        });

        webView.setWebChromeClient(new WebChromeClient());

        webView.loadUrl(BASE_URL);
    }

    private void requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ActivityCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS)
                    != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this,
                        new String[]{Manifest.permission.POST_NOTIFICATIONS},
                        NOTIFICATION_PERMISSION_CODE);
            }
        }
    }

    private void getFcmToken() {
        FirebaseMessaging.getInstance().getToken().addOnCompleteListener(task -> {
            if (!task.isSuccessful()) return;
            String token = task.getResult();
            // Kirim token ke PHP backend untuk disimpan
            sendTokenToServer(token);
        });
    }

    private void sendTokenToServer(String token) {
        new Thread(() -> {
            try {
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection)
                        new java.net.URL(FCM_TOKEN_URL).openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                // Kirim token + user_id (jika login)
                String json = "{\"token\":\"" + token + "\"}";
                conn.getOutputStream().write(json.getBytes());
                conn.getResponseCode(); // trigger request
                conn.disconnect();
            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }

    // ─────────────────────────────────────────────
    //  JavaScript Bridge — dipanggil dari JS di web
    // ─────────────────────────────────────────────
    public class WebAppBridge {

        @JavascriptInterface
        public void showToast(String message) {
            runOnUiThread(() -> Toast.makeText(MainActivity.this, message, Toast.LENGTH_SHORT).show());
        }

        @JavascriptInterface
        public void shareContent(String title, String text, String url) {
            Intent intent = new Intent(Intent.ACTION_SEND);
            intent.setType("text/plain");
            intent.putExtra(Intent.EXTRA_SUBJECT, title);
            intent.putExtra(Intent.EXTRA_TEXT, text + "\n" + url);
            startActivity(Intent.createChooser(intent, "Share"));
        }

        @JavascriptInterface
        public void openExternalLink(String url) {
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
            startActivity(intent);
        }

        @JavascriptInterface
        public void vibrate(int milliseconds) {
            Vibrator v = (Vibrator) getSystemService(VIBRATOR_SERVICE);
            if (v != null) v.vibrate(VibrationEffect.createOneShot(milliseconds, VibrationEffect.DEFAULT_AMPLITUDE));
        }

        @JavascriptInterface
        public boolean isNotificationEnabled() {
            return NotificationManagerCompat.from(MainActivity.this).areNotificationsEnabled();
        }

        @JavascriptInterface
        public void requestNotificationPermission() {
            MainActivity.this.requestNotificationPermission();
        }

        @JavascriptInterface
        public void getFcmToken() {
            FirebaseMessaging.getInstance().getToken().addOnCompleteListener(task -> {
                if (task.isSuccessful()) {
                    String token = task.getResult();
                    // Kirim token ke JS di WebView
                    runOnUiThread(() -> webView.evaluateJavascript(
                            "window.onFcmToken && window.onFcmToken('" + token + "')", null));
                }
            });
        }

        @JavascriptInterface
        public void closeApp() {
            finishAffinity();
        }
    }
}
```

---

## 4. Layout: `activity_main.xml`

```xml
<?xml version="1.0" encoding="utf-8"?>
<FrameLayout xmlns:android="http://schemas.android.com/apk/res/android"
    android:layout_width="match_parent"
    android:layout_height="match_parent">

    <WebView
        android:id="@+id/webView"
        android:layout_width="match_parent"
        android:layout_height="match_parent" />

</FrameLayout>
```

---

## 5. Firebase Cloud Messaging Service

```java
package com.tourandtravel.app;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Intent;
import android.os.Build;

import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

public class FirebaseMessageService extends FirebaseMessagingService {

    private static final String CHANNEL_ID = "tour_travel_channel";

    @Override
    public void onNewToken(@NonNull String token) {
        super.onNewToken(token);
        // Token refreshed — kirim ke server
        sendTokenToServer(token);
    }

    @Override
    public void onMessageReceived(@NonNull RemoteMessage message) {
        super.onMessageReceived(message);

        String title = message.getNotification() != null
                ? message.getNotification().getTitle()
                : "Tour & Travel";
        String body = message.getNotification() != null
                ? message.getNotification().getBody()
                : "";
        String type = message.getData().get("type"); // booking, promo, dll
        String deeplink = message.getData().get("deeplink");

        showNotification(title, body, type, deeplink);
    }

    private void showNotification(String title, String body, String type, String deeplink) {
        createNotificationChannel();

        // Intent — buka app, bisa navigate ke halaman tertentu
        Intent intent = new Intent(this, MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);

        if (deeplink != null && !deeplink.isEmpty()) {
            intent.putExtra("deeplink", deeplink);
        }

        PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, intent,
                PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);

        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_notification)
                .setContentTitle(title)
                .setContentText(body)
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setAutoCancel(true)
                .setContentIntent(pendingIntent);

        // Add action buttons berdasarkan type
        if ("booking".equals(type)) {
            Intent bookIntent = new Intent(this, MainActivity.class);
            bookIntent.putExtra("deeplink", "/my-bookings");
            PendingIntent bookPending = PendingIntent.getActivity(this, 1, bookIntent,
                    PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);
            builder.addAction(R.drawable.ic_booking, "Lihat Booking", bookPending);
        }

        NotificationManagerCompat.from(this).notify((int) System.currentTimeMillis(), builder.build());
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(
                    CHANNEL_ID,
                    "Tour & Travel Notifications",
                    NotificationManager.IMPORTANCE_HIGH
            );
            channel.setDescription("Notifikasi booking, promo, dan info penting");
            NotificationManager manager = getSystemService(NotificationManager.class);
            if (manager != null) manager.createNotificationChannel(channel);
        }
    }

    private void sendTokenToServer(String token) {
        // Sama seperti di MainActivity — kirim ke PHP backend
    }
}
```

---

## 6. JavaScript Bridge — Sisi PHP/Web

Tambahkan script ini di website (misalnya di `header.php` atau `footer.php`) agar bisa detect WebView dan panggil bridge:

```html
<script>
const isAndroidApp = typeof window.AndroidBridge !== 'undefined';

const AppBridge = {
  showToast: (msg) => {
    if (isAndroidApp) window.AndroidBridge.showToast(msg);
  },
  share: (title, text, url) => {
    if (isAndroidApp) window.AndroidBridge.shareContent(title, text, url);
  },
  openExternal: (url) => {
    if (isAndroidApp) window.AndroidBridge.openExternalLink(url);
  },
  vibrate: (ms = 200) => {
    if (isAndroidApp) window.AndroidBridge.vibrate(ms);
  },
  isNotificationEnabled: () => {
    if (isAndroidApp) return window.AndroidBridge.isNotificationEnabled();
    return true; // default for browser
  },
  requestNotification: () => {
    if (isAndroidApp) window.AndroidBridge.requestNotificationPermission();
  },
  getFcmToken: () => {
    if (isAndroidApp) window.AndroidBridge.getFcmToken();
  },
  closeApp: () => {
    if (isAndroidApp) window.AndroidBridge.closeApp();
  }
};

// Contoh penggunaan:
// AppBridge.showToast('Booking berhasil!');
// AppBridge.share('Paket Tour Bali', 'Mulai dari Rp 2.5Juta', 'https://site.com/tours/bali');
// AppBridge.vibrate(100);

// Callback dari Android untuk FCM token
window.onFcmToken = function(token) {
  console.log('FCM Token:', token);
  // Simpan token untuk push notification dari website
};
</script>
```

---

## 7. Deep Link dari Notifikasi

Di `MainActivity.java`, handle `deeplink` dari notifikasi:

```java
@Override
protected void onNewIntent(Intent intent) {
    super.onNewIntent(intent);
    handleDeeplink(intent);
}

private void handleDeeplink(Intent intent) {
    if (intent == null || intent.getExtras() == null) return;

    String deeplink = intent.getStringExtra("deeplink");
    if (deeplink != null && !deeplink.isEmpty()) {
        // Navigate WebView ke halaman tertentu
        webView.evaluateJavascript(
            "window.location.href = '" + BASE_URL + deeplink + "'", null);
    }
}
```

PHP backend kirim FCM data payload dengan `deeplink` (pesan mengikuti bahasa
device/user — `lang` di tabel `fcm_tokens`: `id`, `en`, atau `zh`):

```php
// Di PHP, saat kirim notifikasi via FCM (pilih pesan sesuai lang user)
$messages = [
    'id' => ['Booking Dikonfirmasi!', 'Booking ' . $bookingCode . ' telah dikonfirmasi.'],
    'en' => ['Booking Confirmed!', 'Booking ' . $bookingCode . ' has been confirmed.'],
    'zh' => ['订单已确认！', '订单 ' . $bookingCode . ' 已确认。'],
];
[$title, $body] = $messages[$userLang] ?? $messages['id'];
$payload = [
    'to' => $fcmToken,
    'notification' => ['title' => $title, 'body' => $body],
    'data' => [
        'type' => 'booking',
        'deeplink' => '/my-bookings/' . $bookingCode,
        'booking_code' => $bookingCode,
    ]
];
```

---

## 8. PHP Backend: FCM Token Storage

### `api/fcm-token.php`

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = trim($input['token'] ?? '');
$lang = trim($input['lang'] ?? ''); // opsional: 'id' | 'en' | 'zh' (default dari session)

if (!$token) {
    echo json_encode(['ok' => false, 'error' => 'token_required']);
    exit;
}

// Simpan token (user_id nullable untuk guest)
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
}

$stmt = db()->prepare("INSERT INTO fcm_tokens (user_id, token, platform, lang) VALUES (?, ?, 'android', NULLIF(?, '')) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), lang = VALUES(lang), updated_at = NOW()");
$stmt->execute([$userId, $token, $lang ?: null]);

echo json_encode(['ok' => true]);
```

### `database/migrate-fcm.sql`

```sql
CREATE TABLE IF NOT EXISTS fcm_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    token VARCHAR(500) NOT NULL,
    platform ENUM('android', 'ios') DEFAULT 'android',
    lang VARCHAR(5) NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_token (token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## 9. Kirim Notifikasi dari PHP (Helper)

### `includes/fcm-push.php`

```php
<?php
function sendPushNotification(array $userIds, string $title, string $body, array $data = []): int {
    $fcmKey = FCM_SERVER_KEY; // Simpan di config.php atau env
    if (!$fcmKey) return 0;

    // Ambil tokens untuk user IDs
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = db()->prepare("SELECT token FROM fcm_tokens WHERE user_id IN ($placeholders)");
    $stmt->execute($userIds);
    $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tokens)) return 0;

    $sent = 0;
    foreach ($tokens as $token) {
        $payload = json_encode([
            'to' => $token,
            'notification' => ['title' => $title, 'body' => $body],
            'data' => $data,
        ]);

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . $fcmKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($resp, true);
        if (($result['success'] ?? 0) === 1) $sent++;
    }

    return $sent;
}
```

### Contoh pakai:

```php
// Booking baru — kirim ke admin
sendPushNotification([$adminId], 'Booking Baru!', "Booking #{$code} dari {$name}", [
    'type' => 'booking',
    'deeplink' => '/admin/bookings',
]);

// Booking dikonfirmasi — kirim ke user
sendPushNotification([$userId], 'Booking Dikonfirmasi!', "Booking #{$code} sudah dikonfirmasi", [
    'type' => 'booking',
    'deeplink' => "/my-bookings/{$code}",
]);
```

---

## 10. Bridge Functions Lengkap (JS Reference)

| Function | Parameter | Deskripsi |
|---|---|---|
| `AppBridge.showToast(msg)` | string | Tampilkan toast native |
| `AppBridge.share(title, text, url)` | 3 strings | Share via intent |
| `AppBridge.openExternal(url)` | string | Buka di browser external |
| `AppBridge.vibrate(ms)` | number | Getarkan device |
| `AppBridge.isNotificationEnabled()` | - | Cek status notifikasi (return bool) |
| `AppBridge.requestNotification()` | - | Minta permission notifikasi |
| `AppBridge.getFcmToken()` | - | Ambil FCM token |
| `AppBridge.closeApp()` | - | Tutup aplikasi |

---

## 11. Build APK

```bash
# Debug
./gradlew assembleDebug
# Output: app/build/outputs/apk/debug/app-debug.apk

# Release
./gradlew assembleRelease
# Output: app/build/outputs/apk/release/app-release.apk
```

---

## 12. Checklist

- [ ] Setup Android project dengan WebView
- [ ] Tambah `AndroidBridge` JavaScript interface di WebView
- [ ] Tambah script `AppBridge` di PHP website (`header.php`/`footer.php`)
- [ ] Setup Firebase project + `google-services.json`
- [ ] Buat `FirebaseMessageService.java`
- [ ] Buat `fcm_tokens` table + API endpoint
- [ ] Buat `includes/fcm-push.php` helper
- [ ] Kirim notifikasi dari PHP saat booking events
- [ ] Handle deep link dari notifikasi ke halaman spesifik
- [ ] Test notifikasi di real device
- [ ] Build & sign APK

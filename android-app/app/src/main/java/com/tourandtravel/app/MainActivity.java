package com.tourandtravel.app;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.VibrationEffect;
import android.os.Vibrator;
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
    private static final String BASE_URL = "https://tourandtravel.web.id";
    private static final String FCM_TOKEN_URL = BASE_URL + "/api/fcm-token.php";
    private static final int NOTIFICATION_PERMISSION_CODE = 1001;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        setupWebView();
        requestNotificationPermission();
        getFcmToken();
        handleDeeplink(getIntent());
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

        webView.addJavascriptInterface(new WebAppBridge(), "AndroidBridge");

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();
                if (url.startsWith(BASE_URL)) {
                    return false;
                }
                Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                startActivity(intent);
                return true;
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                view.evaluateJavascript(
                    "(function() {" +
                    "  if (document.getElementById('android-hide-elements')) return;" +
                    "  var style = document.createElement('style');" +
                    "  style.id = 'android-hide-elements';" +
                    "  style.textContent = '.sticky-top { display: none !important; }" +
                    "    footer, .footer, .site-footer { display: none !important; }';" +
                    "  document.head.appendChild(style);" +
                    "})();", null);
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
            webView.evaluateJavascript("(localStorage.getItem('lang') || '')", lang -> {
                String cleanLang = lang != null ? lang.replace("\"", "").trim() : "";
                if (!cleanLang.equals("id") && !cleanLang.equals("en") && !cleanLang.equals("zh")) {
                    cleanLang = "";
                }
                sendTokenToServer(token, cleanLang);
            });
        });
    }

    private void sendTokenToServer(String token, String lang) {
        new Thread(() -> {
            try {
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection)
                        new java.net.URL(FCM_TOKEN_URL).openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);

                String json = "{\"token\":\"" + token + "\",\"lang\":\"" + lang + "\"}";
                conn.getOutputStream().write(json.getBytes());
                conn.getResponseCode();
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

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        handleDeeplink(intent);
    }

    private void handleDeeplink(Intent intent) {
        if (intent == null || intent.getExtras() == null) return;
        String deeplink = intent.getStringExtra("deeplink");
        if (deeplink != null && !deeplink.isEmpty()) {
            webView.evaluateJavascript(
                    "window.location.href = '" + BASE_URL + deeplink + "'", null);
        }
    }

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

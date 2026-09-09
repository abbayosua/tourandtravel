package com.tourandtravel.app;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Intent;
import android.os.Build;

import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

public class FirebaseMessageService extends FirebaseMessagingService {

    private static final String CHANNEL_ID = "tour_travel_channel";

    @Override
    public void onNewToken(@NonNull String token) {
        super.onNewToken(token);
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
        String type = message.getData().get("type");
        String deeplink = message.getData().get("deeplink");

        showNotification(title, body, type, deeplink);
    }

    private void showNotification(String title, String body, String type, String deeplink) {
        createNotificationChannel();

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
        new Thread(() -> {
            try {
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection)
                        new java.net.URL("https://tourandtravel.web.id/api/fcm-token.php").openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);
                String json = "{\"token\":\"" + token + "\"}";
                conn.getOutputStream().write(json.getBytes());
                conn.getResponseCode();
                conn.disconnect();
            } catch (Exception e) {
                e.printStackTrace();
            }
        }).start();
    }
}

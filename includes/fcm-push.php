<?php
function sendPushNotification(array $userIds, string $title, string $body, array $data = []): int {
    if (empty($userIds) || !defined('FCM_SERVER_KEY') || FCM_SERVER_KEY === '') return 0;

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
                'Authorization: key=' . FCM_SERVER_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($resp, true);
        if (($result['success'] ?? 0) === 1) $sent++;
    }

    return $sent;
}

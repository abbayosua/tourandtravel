<?php
/**
 * Webhook untuk WUZAPI
 * Menerima notifikasi status pesan & pesan masuk
 * Dipanggil oleh WUZAPI server setiap ada event
 */

// Log webhook events (opsional)
$logFile = __DIR__ . '/../logs/wa-webhook.log';
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

// Pastikan dir logs ada
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log event
$logEntry = date('Y-m-d H:i:s') . ' ' . ($data['event'] ?? 'unknown') . ' ' . json_encode($data) . PHP_EOL;
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Respond 200 OK
http_response_code(200);
echo 'OK';

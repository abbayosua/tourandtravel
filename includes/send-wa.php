<?php
/**
 * Kirim notifikasi WhatsApp via WUZAPI
 * Nomor tujuan bisa diatur dari Admin Panel (admin/wa-settings.php)
 */

$wa_config_file = __DIR__ . '/wa-config.json';
$wa_defaults = [
    'admin_phone' => '6285174488415',
    'token'       => 'abbayosua',
    'server_url'  => 'http://45.158.126.130:48499',
];

if (file_exists($wa_config_file)) {
    $wa_settings = json_decode(file_get_contents($wa_config_file), true);
    if (!is_array($wa_settings)) {
        $wa_settings = $wa_defaults;
    } else {
        $wa_settings = array_merge($wa_defaults, $wa_settings);
    }
} else {
    $wa_settings = $wa_defaults;
}

define('WUZAPI_URL', $wa_settings['server_url']);
define('WUZAPI_TOKEN', $wa_settings['token']);
define('WA_ADMIN', $wa_settings['admin_phone']);

function sendWA($phone, $message) {
    $payload = json_encode([
        'Phone' => $phone,
        'Body'  => $message,
        'Id'    => time() . rand(100, 999),
    ]);

    $ch = curl_init(WUZAPI_URL . '/chat/send/text');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Token: ' . WUZAPI_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("WA send error: $error");
        return false;
    }

    $data = json_decode($result, true);
    if ($httpCode !== 200 || !($data['success'] ?? false)) {
        error_log("WA send failed ($httpCode): " . ($data['error'] ?? $result));
        return false;
    }

    return true;
}

/**
 * Kirim notifikasi booking ke admin
 */
function sendBookingNotification($tour, $bookingCode, $name, $phone, $participants, $totalPrice, $departureDate) {
    $message = "🆕 *PESANAN BARU - TourAndTravel*\n\n"
        . "📋 *Kode Booking:* $bookingCode\n"
        . "🏖️ *Tour:* {$tour['title']}\n"
        . "📅 *Keberangkatan:* $departureDate\n"
        . "👤 *Nama:* $name\n"
        . "📞 *WhatsApp:* $phone\n"
        . "👥 *Peserta:* $participants orang\n"
        . "💰 *Total:* Rp " . number_format($totalPrice, 0, ',', '.') . "\n\n"
        . "🔗 " . (defined('BASE_URL') ? BASE_URL : 'http://tourandtravel.web.id') . "/track.php?code=$bookingCode";

    return sendWA(WA_ADMIN, $message);
}

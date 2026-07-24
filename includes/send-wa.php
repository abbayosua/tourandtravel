<?php
/**
 * Kirim notifikasi WhatsApp via WUZAPI
 * Server: 45.158.126.130:48499
 * Token: abbayosua (auto-digenerate)
 */

define('WUZAPI_URL', 'http://45.158.126.130:48499');
define('WUZAPI_TOKEN', 'abbayosua');
define('WA_ADMIN', '6285174488415'); // Nomor admin/supplier

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

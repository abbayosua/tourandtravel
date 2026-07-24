<?php
/**
 * AJAX handler untuk koneksi WhatsApp
 */
require_once '../includes/config.php';
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$wa_config_file = __DIR__ . '/../includes/wa-config.json';
$wa_defaults = [
    'admin_phone' => '6285174488415',
    'token'       => 'abbayosua',
    'server_url'  => 'http://45.158.126.130:48499',
];
$settings = $wa_defaults;
if (file_exists($wa_config_file)) {
    $loaded = json_decode(file_get_contents($wa_config_file), true);
    if (is_array($loaded)) $settings = array_merge($wa_defaults, $loaded);
}

$base = rtrim($settings['server_url'], '/');
$token = $settings['token'];
$result = ['success' => false, 'error' => 'Unknown action'];

switch ($action) {
    case 'status':
        $ch = curl_init("$base/session/status");
        curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ["Token: $token"], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $res = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http === 200) {
            $result = json_decode($res, true);
        } else {
            $result = ['success' => false, 'error' => 'Cannot reach WUZAPI'];
        }
        break;

    case 'connect':
        // Logout dulu jika ada session lama
        $ch = curl_init("$base/session/logout");
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_HTTPHEADER => ["Token: $token", "Content-Type: application/json"], CURLOPT_POSTFIELDS => '{}', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        curl_exec($ch);
        curl_close($ch);
        sleep(1);

        // Connect
        $ch = curl_init("$base/session/connect");
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_HTTPHEADER => ["Token: $token", "Content-Type: application/json"], CURLOPT_POSTFIELDS => '{"Immediate":false}', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $res = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http === 200) {
            sleep(2);
            // Ambil QR
            $ch = curl_init("$base/session/qr");
            curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ["Token: $token"], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            $qrRes = curl_exec($ch);
            $qrHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($qrHttp === 200) {
                $qrData = json_decode($qrRes, true);
                $result = ['success' => true, 'qrcode' => $qrData['data']['QRCode'] ?? ''];
            } else {
                // QR belum siap, coba lagi
                $result = ['success' => true, 'qrcode' => '', 'message' => 'Menghubungkan...'];
            }
        } else {
            $data = json_decode($res, true);
            $result = ['success' => false, 'error' => $data['error'] ?? 'Connect failed'];
        }
        break;

    case 'disconnect':
        $ch = curl_init("$base/session/logout");
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_HTTPHEADER => ["Token: $token", "Content-Type: application/json"], CURLOPT_POSTFIELDS => '{}', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $res = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = $http === 200 ? ['success' => true] : ['success' => false, 'error' => 'Disconnect failed'];
        break;

    case 'get_qr':
        $ch = curl_init("$base/session/qr");
        curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ["Token: $token"], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $res = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http === 200) {
            $data = json_decode($res, true);
            $result = ['success' => true, 'qrcode' => $data['data']['QRCode'] ?? ''];
        } else {
            $result = ['success' => false, 'error' => 'QR not available'];
        }
        break;

    default:
        $result = ['success' => false, 'error' => "Unknown action: $action"];
}

header('Content-Type: application/json');
echo json_encode($result);

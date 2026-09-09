<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_NAME', 'tourandtravel');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL website (sesuaikan dengan localhost)
define('BASE_URL', 'http://localhost/tourandtravel');
define('SITE_NAME', 'TourAndTravel');

// Firebase Cloud Messaging (isi dengan server key dari Firebase Console)
if (!defined('FCM_SERVER_KEY')) {
    define('FCM_SERVER_KEY', '');
}

// Session
session_start();

// Language switcher (validasi via registry bahasa — getSupportedLanguages)
if (!function_exists('getSupportedLanguages')) {
    require_once __DIR__ . '/functions.php';
}
if (!function_exists('getFlashSalePrice')) {
    require_once __DIR__ . '/flash-sales.php';
}
if (!function_exists('awardPointsForPaidBooking')) {
    require_once __DIR__ . '/points.php';
}
if (isset($_GET['lang']) && preg_match('/^[a-z]{2,5}$/', $_GET['lang'])
    && in_array($_GET['lang'], array_keys(getSupportedLanguages()))) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (86400 * 365), '/');
    $params = $_GET;
    unset($params['lang']);
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    if (!empty($params)) $redirect .= '?' . http_build_query($params);
    header('Location: ' . $redirect);
    exit;
}
if (!isset($_SESSION['lang']) && isset($_COOKIE['lang'])) {
    $_SESSION['lang'] = $_COOKIE['lang'];
}

// Price alert checker: run max 1x per hour (throttled via filemtime on tmp marker)
if (php_sapi_name() !== 'cli') {
    $alertMarker = sys_get_temp_dir() . '/tat_price_alert_check';
    $lastRun = @filemtime($alertMarker) ?: 0;
    if (time() - $lastRun > 3600) {
        @touch($alertMarker);
        require_once __DIR__ . '/price-alert-checker.php';
        try { checkPriceAlerts(); } catch (Throwable $e) { error_log('price-alert-check: ' . $e->getMessage()); }
    }
}
?>

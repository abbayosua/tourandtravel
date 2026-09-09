<?php
// Konfigurasi database — override via environment variables (lihat DEPLOY.md)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'tourandtravel');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// URL website — auto-detect localhost
if (getenv('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL'));
} elseif (($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || str_starts_with($_SERVER['HTTP_HOST'] ?? '', '127.')) {
    // Detect subdirectory from SCRIPT_NAME (e.g. /tourandtravel/index.php → /tourandtravel)
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    define('BASE_URL', 'http://localhost' . $scriptDir);
} else {
    define('BASE_URL', 'https://tourandtravel.web.id');
}
define('SITE_NAME', 'TourAndTravel');

// Firebase Cloud Messaging (isi dengan server key dari Firebase Console)
if (!defined('FCM_SERVER_KEY')) {
    define('FCM_SERVER_KEY', getenv('FCM_SERVER_KEY') ?: '');
}

// Duffel API (flight live) — override via env agar token live tidak ter-commit
if (!defined('DUFFEL_TOKEN') && getenv('DUFFEL_TOKEN')) {
    define('DUFFEL_TOKEN', getenv('DUFFEL_TOKEN'));
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

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
if (isset($_GET['lang']) && isValidLang($_GET['lang'])) {
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

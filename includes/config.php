<?php
// Muat .env lokal bila ada (tanpa dependensi composer). Env server tetap menang bila sudah diset.
(function () {
    $f = __DIR__ . '/../.env';
    if (!is_readable($f)) return;
    foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $k = trim(substr($line, 0, $pos));
        $v = trim(substr($line, $pos + 1));
        if ($k === '' || getenv($k) !== false) continue;
        putenv($k . '=' . $v);
        $_ENV[$k] = $v;
    }
})();

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
    // Potong segmen '/admin' beserta apapun di bawahnya agar BASE tetap root aplikasi
    // walau dipanggil dari admin/* (mis. admin/ajax/tour-translate-ai.php)
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (preg_match('#^(.*)/admin(/|$)#', $scriptDir, $m)) {
        $scriptDir = $m[1];
    }
    define('BASE_URL', 'http://localhost' . $scriptDir);
} else {
    define('BASE_URL', 'https://tourandtravel.web.id');
}
define('SITE_NAME', 'TourAndTravel');

// Samakan timezone PHP dengan MySQL (WIB) agar perbandingan tanggal konsisten
date_default_timezone_set('Asia/Jakarta');

// Firebase Cloud Messaging (isi dengan server key dari Firebase Console)
if (!defined('FCM_SERVER_KEY')) {
    define('FCM_SERVER_KEY', getenv('FCM_SERVER_KEY') ?: '');
}

// Duffel API (flight live) — override via env agar token live tidak ter-commit
if (!defined('DUFFEL_TOKEN') && getenv('DUFFEL_TOKEN')) {
    define('DUFFEL_TOKEN', getenv('DUFFEL_TOKEN'));
}

// AI AUTO translate paket tour (Meta LLM, OpenAI-compatible) — isi via env / .env.
if (!defined('AI_API_KEY')) define('AI_API_KEY', getenv('AI_API_KEY') ?: getenv('ATRIA_API_KEY') ?: '');
if (!defined('AI_BASE_URL')) define('AI_BASE_URL', getenv('AI_BASE_URL') ?: getenv('ATRIA_BASE_URL') ?: 'https://api.meta.ai/v1');
if (!defined('AI_MODEL')) define('AI_MODEL', getenv('AI_MODEL') ?: getenv('ATRIA_MODEL') ?: 'muse-spark-1.3-contributor');

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

// Google OAuth (Fase 1 social login) — kosongkan untuk menyembunyikan tombol Google
if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', '');

// PayPal (Backlog #7) — kosongkan untuk disable
if (!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', '');
if (!defined('PAYPAL_SECRET')) define('PAYPAL_SECRET', '');
?>

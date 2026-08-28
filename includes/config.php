<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_NAME', 'tourandtravel');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL website (sesuaikan dengan localhost)
define('BASE_URL', 'http://localhost/tourandtravel');
define('SITE_NAME', 'TourAndTravel');

// Session
session_start();

// Language switcher
if (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (86400 * 365), '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
if (!isset($_SESSION['lang']) && isset($_COOKIE['lang'])) {
    $_SESSION['lang'] = $_COOKIE['lang'];
}
?>

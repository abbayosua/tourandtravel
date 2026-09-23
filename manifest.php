<?php
// Manifest PWA dinamis — nama mengikuti setting Brand & Logo.
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$name = function_exists('siteName') ? siteName() : (defined('SITE_NAME') ? SITE_NAME : 'TourAndTravel');
$short = mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'TAT', 0, 12);
header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'name' => $name,
    'short_name' => $short,
    'start_url' => '/tourandtravel/index.php',
    'display' => 'standalone',
    'background_color' => '#0d6efd',
    'theme_color' => '#0d6efd',
    'icons' => [
        ['src' => '/tourandtravel/assets/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ['src' => '/tourandtravel/assets/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

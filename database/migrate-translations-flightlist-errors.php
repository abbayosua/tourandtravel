<?php
/**
 * migrate-translations-flightlist-errors.php
 *
 * Pesan error FlightList (includes/flightlist.php + flights.php) sebelumnya
 * hardcoded Indonesia sehingga bocor di en/zh. Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-flightlist-errors.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'FlightList tidak terjangkau' => 'FlightList unreachable',
        'FlightList dibatasi (HTML)' => 'FlightList blocked (HTML)',
        'FlightList juga tidak terjangkau' => 'FlightList also unreachable',
    ],
    'zh' => [
        'FlightList tidak terjangkau' => 'FlightList 无法访问',
        'FlightList dibatasi (HTML)' => 'FlightList 被拦截（HTML）',
        'FlightList juga tidak terjangkau' => 'FlightList 也无法访问',
    ],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count flightlist error translation rows.\n";

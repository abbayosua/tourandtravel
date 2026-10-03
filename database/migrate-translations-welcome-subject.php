<?php
/**
 * migrate-translations-welcome-subject.php
 *
 * Subject email welcome (register.php + includes/oauth.php) sebelumnya
 * hardcoded 'Selamat Datang di '. Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-welcome-subject.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Selamat Datang di %s' => 'Welcome to %s'],
    'zh' => ['Selamat Datang di %s' => '欢迎来到 %s'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count welcome subject translation rows.\n";

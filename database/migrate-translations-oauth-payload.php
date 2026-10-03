<?php
/**
 * migrate-translations-oauth-payload.php
 *
 * includes/oauth.php melempar InvalidArgumentException('Payload Google tidak
 * lengkap') hardcoded Indonesia; pesannya ditampilkan api/oauth-google.php ->
 * google-signin alert. Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-oauth-payload.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Payload Google tidak lengkap' => 'Google payload is incomplete'],
    'zh' => ['Payload Google tidak lengkap' => 'Google 数据不完整'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count oauth payload translation rows.\n";

<?php
/**
 * migrate-translations-oauth-messages.php
 *
 * Pesan error api/oauth-google.php sebelumnya hardcoded Indonesia dan
 * ditampilkan oleh google-signin.php via alert(d.message). Kini via t(); seed en/zh.
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-oauth-messages.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Credential kosong atau terlalu panjang' => 'Credential is empty or too long',
        'Token bukan untuk aplikasi ini' => 'Token is not for this application',
    ],
    'zh' => [
        'Credential kosong atau terlalu panjang' => '凭据为空或过长',
        'Token bukan untuk aplikasi ini' => '令牌不属于此应用',
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
echo "Upserted $count oauth message translation rows.\n";

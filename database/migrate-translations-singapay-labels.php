<?php
/**
 * migrate-translations-singapay-labels.php
 *
 * Label pengaturan Singapay di admin/payments.php sebelumnya hardcoded
 * (bukan t()): Environment, Sandbox, Production, Client ID, Client Secret,
 * API Key, Account ID. Kini via t(); seed key yang belum ada.
 * ('Environment'/'Sandbox'/'Production' sudah punya en/zh.)
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-singapay-labels.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'API Key' => 'API Key',
        'Client ID' => 'Client ID',
        'Client Secret' => 'Client Secret',
        'Account ID' => 'Account ID',
    ],
    'zh' => [
        'API Key' => 'API 密钥',
        'Client ID' => '客户端 ID',
        'Client Secret' => '客户端密钥',
        'Account ID' => '账户 ID',
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
echo "Upserted $count Singapay label translation rows.\n";

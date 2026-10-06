<?php
/**
 * migrate-translations-xendit.php
 *
 * Terjemahan halaman pembayaran Xendit Sandbox (admin + booking sukses).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-xendit.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Secret Key' => 'Secret Key',
        'Public Key' => 'Public Key',
        'Callback Token' => 'Callback Token',
        'Webhook Xendit Sandbox:' => 'Xendit Sandbox Webhook:',
        'Bayar via Xendit Sandbox (VA / Kartu)' => 'Pay via Xendit Sandbox (VA / Card)',
        'Pilih Virtual Account bank atau kartu kredit di halaman checkout.' => 'Choose a bank Virtual Account or credit card on the checkout page.',
        'Pengaturan Xendit Sandbox (VA + Kartu)' => 'Xendit Sandbox Settings (VA + Card)',
        'Otomatis dialihkan dalam' => 'Auto-redirecting in',
        'detik…' => 'seconds…',
    ],
    'zh' => [
        'Secret Key' => '密钥',
        'Public Key' => '公钥',
        'Callback Token' => '回调令牌',
        'Webhook Xendit Sandbox:' => 'Xendit沙盒Webhook：',
        'Bayar via Xendit Sandbox (VA / Kartu)' => '通过Xendit沙盒支付（虚拟账户/银行卡）',
        'Pilih Virtual Account bank atau kartu kredit di halaman checkout.' => '在结账页面选择银行虚拟账户或信用卡。',
        'Pengaturan Xendit Sandbox (VA + Kartu)' => 'Xendit沙盒设置（虚拟账户+银行卡）',
        'Otomatis dialihkan dalam' => '自动跳转，还有',
        'detik…' => '秒…',
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
echo "Upserted $count xendit translation rows.\n";

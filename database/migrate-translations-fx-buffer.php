<?php
/**
 * migrate-translations-fx-buffer.php
 *
 * Terjemahan en/zh untuk fitur FX buffer & penyesuaian kurs (tour).
 * Idempotent — upsert by (key, lang).
 * Jalankan: php database/migrate-translations-fx-buffer.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Penyesuaian kurs' => 'Currency adjustment',
        'FX Buffer (Tour)' => 'FX Buffer (Tour)',
        'Buffer kurs (%)' => 'Currency buffer (%)',
        'Bantalan margin terhadap pergerakan kurs. Ditambahkan setelah diskon, sebelum poin/saldo. 0 = nonaktif. Hanya berlaku untuk paket tour.' => 'Margin buffer against exchange-rate movement. Added after discounts, before points/wallet. 0 = disabled. Applies to tour packages only.',
        'FX buffer disimpan: %s%%' => 'FX buffer saved: %s%%',
    ],
    'zh' => [
        'Penyesuaian kurs' => '汇率调整',
        'FX Buffer (Tour)' => '汇率缓冲（旅游）',
        'Buffer kurs (%)' => '汇率缓冲（%）',
        'Bantalan margin terhadap pergerakan kurs. Ditambahkan setelah diskon, sebelum poin/saldo. 0 = nonaktif. Hanya berlaku untuk paket tour.' => '应对汇率波动的利润缓冲。在折扣之后、积分/余额之前加入。0 = 禁用。仅适用于旅游套餐。',
        'FX buffer disimpan: %s%%' => '汇率缓冲已保存：%s%%',
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
echo "Upserted $count fx-buffer translation rows.\n";
